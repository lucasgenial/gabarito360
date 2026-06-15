<?php

namespace App\Http\Controllers\Api\V2\Comparativos;

use App\Actions\Relatorios\BuildComparativoAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComparativoController extends BaseApiController
{
    /**
     * Comparativo das escolas de um núcleo (média, aprovação, volume). Aceita
     * recorte por prova via `?prova_id=`. Restrito às escolas visíveis ao ator.
     *
     * GET /api/v2/comparativos/nucleo/{nucleo}
     */
    public function nucleo(
        Request $request,
        Nucleo $nucleo,
        PortalScope $scope,
        BuildComparativoAction $action,
    ): JsonResponse {
        $request->validate([
            'prova_id' => ['nullable', 'uuid', 'exists:provas,id'],
        ]);

        $actor = $this->actor($request);
        $escolaIds = $scope
            ->applySchools(Escola::query()->where('nucleo_id', $nucleo->id), $actor)
            ->pluck('id');

        // 404 quando o ator não enxerga escola alguma no núcleo.
        abort_if($escolaIds->isEmpty(), 404);

        $provaId = $request->query('prova_id');
        $comparativo = $action->execute($nucleo, $escolaIds, is_string($provaId) ? $provaId : null, $actor);

        return $this->successResponse([
            'id' => $comparativo->id,
            'tipo' => $comparativo->tipo,
            'nucleo_id' => $comparativo->nucleo_id,
            'prova_id' => $comparativo->prova_id,
            'parametros' => $comparativo->parametros,
            'escolas' => $comparativo->resultado,
            'gerado_at' => $comparativo->gerado_at?->toIso8601String(),
        ]);
    }
}
