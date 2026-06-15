<?php

namespace App\Http\Controllers\Api\V2\Relatorios;

use App\Actions\Relatorios\GenerateApplicationCsvReportAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\RelatorioResource;
use App\Models\Aplicacao;
use App\Models\Relatorio;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController extends BaseApiController
{
    /**
     * Lista relatórios solicitados pelo ator autenticado.
     *
     * GET /api/v2/relatorios
     */
    public function index(Request $request, PortalScope $scope): JsonResponse
    {
        $query = $scope->applyReports(Relatorio::query(), $this->actor($request));

        $relatorios = $query
            ->with('arquivo:id,nome_original,tamanho_bytes')
            ->orderByDesc('solicitado_at')
            ->paginate(20);

        return $this->paginatedResponse($relatorios, RelatorioResource::class);
    }

    /**
     * Status de um relatório (somente do próprio solicitante).
     *
     * GET /api/v2/relatorios/{relatorio}
     */
    public function show(Request $request, Relatorio $relatorio): JsonResponse
    {
        abort_unless($relatorio->solicitante_id === $this->actor($request)->id, 403);

        $relatorio->load('arquivo:id,nome_original,tamanho_bytes');

        return $this->successResponse(new RelatorioResource($relatorio));
    }

    /**
     * Solicita geração de relatório CSV de resultados de uma aplicação.
     *
     * POST /api/v2/relatorios
     * Body: { "tipo": "resultados_aplicacao", "aplicacao_id": "uuid" }
     */
    public function store(
        Request $request,
        PortalScope $scope,
        GenerateApplicationCsvReportAction $action,
    ): JsonResponse {
        $validated = $request->validate([
            'tipo' => ['required', 'string', 'in:resultados_aplicacao'],
            'aplicacao_id' => ['required', 'uuid', 'exists:aplicacoes,id'],
        ]);

        $actor = $this->actor($request);
        $aplicacao = Aplicacao::query()
            ->with('escola:id,nucleo_id,nome')
            ->findOrFail($validated['aplicacao_id']);

        abort_unless(
            $scope->applyApplications(Aplicacao::query()->whereKey($aplicacao), $actor)->exists(),
            403,
        );

        $relatorio = $action->execute($aplicacao, $actor);
        $relatorio->load('arquivo:id,nome_original,tamanho_bytes');

        return $this->successResponse(new RelatorioResource($relatorio), 201);
    }

    /**
     * Download do arquivo gerado. Auditado.
     *
     * GET /api/v2/relatorios/{relatorio}/download
     */
    public function download(Request $request, Relatorio $relatorio, AuditService $audit): StreamedResponse
    {
        $actor = $this->actor($request);
        abort_unless($relatorio->solicitante_id === $actor->id, 403);
        abort_unless($relatorio->status === 'concluido' && $relatorio->arquivo_id !== null, 422);

        $relatorio->load('arquivo');
        $arquivo = $relatorio->arquivo;
        $disk = config('filesystems.private');

        abort_unless(Storage::disk((string) $disk)->exists((string) $arquivo->caminho), 404);

        $audit->record(
            AuditAction::REPORT_DOWNLOADED,
            'relatorio',
            $relatorio->id,
            $actor->id,
            metadata: ['arquivo_id' => $arquivo->id],
            nucleoId: is_array($relatorio->escopo) ? ($relatorio->escopo['nucleo_id'] ?? null) : null,
            escolaId: is_array($relatorio->escopo) ? ($relatorio->escopo['escola_id'] ?? null) : null,
        );

        return Storage::disk((string) $disk)->download(
            (string) $arquivo->caminho,
            (string) $arquivo->nome_original,
            ['Content-Type' => (string) $arquivo->mime],
        );
    }
}
