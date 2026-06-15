<?php

namespace App\Http\Controllers\Api\V2\Relatorios;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\Prova;
use App\Services\Relatorios\ProvaReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelatorioProvaController extends BaseApiController
{
    public function __construct(private ProvaReportService $reports) {}

    /**
     * Relatório consolidado de uma prova (KPIs, acertos por tema, aproveitamento
     * e resultado por aluno). Aceita recorte por turma via `?turma_id=`.
     *
     * GET /api/v2/relatorios/prova/{prova}
     */
    public function show(Request $request, Prova $prova): JsonResponse
    {
        $turmaId = $request->query('turma_id');
        $turmaId = is_string($turmaId) && $turmaId !== '' ? $turmaId : null;

        $applicationIds = $this->reports->visibleApplicationIds($prova, $this->actor($request), $turmaId);

        // 404 quando a prova não tem aplicações visíveis ao ator (mesmo padrão
        // de correção/dashboard de prova).
        abort_if($applicationIds->isEmpty(), 404);

        return $this->successResponse($this->reports->build($prova, $applicationIds, $turmaId));
    }
}
