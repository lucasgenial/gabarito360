<?php

namespace App\Http\Controllers\Api\V2\Configuracoes;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\Escola;
use App\Models\PlanoUso;
use App\Models\Prova;
use App\Models\Turma;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanoUsoController extends BaseApiController
{
    /** @var array<string, int> */
    private const LIMITES_PADRAO = ['escolas' => 50, 'alunos' => 20000, 'provas' => 1000];

    public function __construct(private PortalScope $scope) {}

    /**
     * Plano contratado, limites e uso real do núcleo do ator.
     * Admin com visão de rede informa `?nucleo_id=`.
     *
     * GET /api/v2/plano-uso
     */
    public function show(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $nucleoId = $request->query('nucleo_id');

        if (is_string($nucleoId) && $nucleoId !== '') {
            abort_unless($this->scope->canViewNucleo($actor, $nucleoId), 403);
        } else {
            $nucleoId = $this->scope->accessibleNucleoIds($actor)->first();
            abort_if($nucleoId === null, 404);
        }

        $plano = PlanoUso::query()->firstOrCreate(
            ['nucleo_id' => $nucleoId],
            ['plano' => 'institucional', 'limites' => self::LIMITES_PADRAO, 'uso' => []],
        );

        return $this->successResponse([
            'nucleo_id' => $nucleoId,
            'plano' => $plano->plano,
            'limites' => $plano->limites,
            'uso' => $this->usoReal((string) $nucleoId),
            'ciclo_inicio' => $plano->ciclo_inicio?->toDateString(),
            'ciclo_fim' => $plano->ciclo_fim?->toDateString(),
        ]);
    }

    /**
     * Uso real do escopo (estado atual, não snapshot).
     *
     * @return array<string, int>
     */
    private function usoReal(string $nucleoId): array
    {
        $escolaIds = Escola::query()->where('nucleo_id', $nucleoId)->pluck('id');

        $alunos = Turma::query()
            ->whereIn('escola_id', $escolaIds)
            ->withCount('matriculas')
            ->get()
            ->sum('matriculas_count');

        return [
            'escolas' => $escolaIds->count(),
            'alunos' => (int) $alunos,
            'provas' => Prova::query()->where('nucleo_id', $nucleoId)->count(),
        ];
    }
}
