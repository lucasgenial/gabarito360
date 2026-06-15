<?php

namespace App\Http\Controllers\Api\V2\Resultados;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\ResultadoResource;
use App\Models\Resultado;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ResultadoController extends BaseApiController
{
    /**
     * Lista resultados vigentes com filtro obrigatório por aplicação, aluno ou prova.
     *
     * GET /api/v2/resultados?aplicacao_id=&aluno_id=&prova_id=
     */
    public function index(Request $request, PortalScope $scope): JsonResponse
    {
        $aplicacaoId = $request->query('aplicacao_id');
        $alunoId = $request->query('aluno_id');
        $provaId = $request->query('prova_id');

        if (! $aplicacaoId && ! $alunoId && ! $provaId) {
            throw ValidationException::withMessages([
                'filtro' => ['Informe ao menos um filtro: aplicacao_id, aluno_id ou prova_id.'],
            ]);
        }

        $query = Resultado::query()->where('STATUS', 'vigente');

        if (is_string($aplicacaoId) && $aplicacaoId !== '') {
            $query->where('aplicacao_id', $aplicacaoId);
        }
        if (is_string($alunoId) && $alunoId !== '') {
            $query->where('aluno_id', $alunoId);
        }
        if (is_string($provaId) && $provaId !== '') {
            $query->where('prova_id', $provaId);
        }

        $scope->applyResults($query, $this->actor($request));

        $resultados = $query
            ->with('aluno:id,nome,nome_social,matricula')
            ->orderByDesc('calculado_at')
            ->paginate(50);

        return $this->paginatedResponse($resultados, ResultadoResource::class);
    }

    /**
     * Detalhe de um resultado incluindo respostas por questão.
     *
     * GET /api/v2/resultados/{resultado}
     */
    public function show(Request $request, Resultado $resultado, PortalScope $scope): JsonResponse
    {
        $actor = $this->actor($request);

        abort_unless(
            $scope->applyResults(Resultado::query()->whereKey($resultado), $actor)->exists(),
            403,
        );

        $resultado->load([
            'aluno:id,nome,nome_social,matricula',
            'questoes.questao:id,numero',
        ]);

        return $this->successResponse(
            (new ResultadoResource($resultado))->withQuestoes(),
        );
    }
}
