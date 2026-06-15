<?php

namespace App\Http\Controllers\Api\V2\Relatorios;

use App\Actions\Relatorios\GenerateExportacaoAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\ExportacaoResource;
use App\Models\Exportacao;
use App\Models\Prova;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Relatorios\ProvaReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportacaoController extends BaseApiController
{
    public function __construct(private ProvaReportService $reports) {}

    /**
     * Lista exportações solicitadas pelo ator autenticado.
     *
     * GET /api/v2/exportacoes
     */
    public function index(Request $request): JsonResponse
    {
        $exportacoes = Exportacao::query()
            ->where('solicitante_id', $this->actor($request)->id)
            ->with('arquivo:id,nome_original,tamanho_bytes')
            ->orderByDesc('solicitado_at')
            ->paginate(20);

        return $this->paginatedResponse($exportacoes, ExportacaoResource::class);
    }

    /**
     * Status de uma exportação (somente do próprio solicitante).
     *
     * GET /api/v2/exportacoes/{exportacao}
     */
    public function show(Request $request, Exportacao $exportacao): JsonResponse
    {
        abort_unless($exportacao->solicitante_id === $this->actor($request)->id, 403);

        $exportacao->load('arquivo:id,nome_original,tamanho_bytes');

        return $this->successResponse(new ExportacaoResource($exportacao));
    }

    /**
     * Solicita a exportação do relatório de uma prova em csv/pdf/xlsx.
     * Idempotente via `Idempotency-Key`. Autorizado pelo escopo do ator.
     *
     * POST /api/v2/relatorios/prova/{prova}/exportar
     * Body: { "formato": "csv|pdf|xlsx", "turma_id": "uuid?" }
     */
    public function store(Request $request, Prova $prova, GenerateExportacaoAction $action): JsonResponse
    {
        $validated = $request->validate([
            'formato' => ['required', 'string', 'in:csv,pdf,xlsx'],
            'turma_id' => ['nullable', 'uuid', 'exists:turmas,id'],
        ]);

        $turmaId = $validated['turma_id'] ?? null;
        $actor = $this->actor($request);

        $applicationIds = $this->reports->visibleApplicationIds($prova, $actor, $turmaId);
        abort_if($applicationIds->isEmpty(), 403);

        $report = $this->reports->build($prova, $applicationIds, $turmaId);
        $exportacao = $action->execute($prova, $validated['formato'], $report, $actor, $turmaId);
        $exportacao->load('arquivo:id,nome_original,tamanho_bytes');

        return $this->successResponse(new ExportacaoResource($exportacao), 201);
    }

    /**
     * Download do arquivo exportado. Auditado.
     *
     * GET /api/v2/exportacoes/{exportacao}/download
     */
    public function download(Request $request, Exportacao $exportacao, AuditService $audit): StreamedResponse
    {
        $actor = $this->actor($request);
        abort_unless($exportacao->solicitante_id === $actor->id, 403);
        abort_unless($exportacao->status === 'concluido' && $exportacao->arquivo_id !== null, 422);

        $exportacao->load('arquivo');
        $arquivo = $exportacao->arquivo;
        $disk = config('filesystems.private');

        abort_unless(Storage::disk((string) $disk)->exists((string) $arquivo->caminho), 404);

        $audit->record(
            AuditAction::EXPORT_DOWNLOADED,
            'exportacao',
            $exportacao->id,
            $actor->id,
            metadata: ['arquivo_id' => $arquivo->id, 'formato' => $exportacao->formato],
            nucleoId: is_array($exportacao->escopo) ? ($exportacao->escopo['nucleo_id'] ?? null) : null,
        );

        return Storage::disk((string) $disk)->download(
            (string) $arquivo->caminho,
            (string) $arquivo->nome_original,
            ['Content-Type' => (string) $arquivo->mime],
        );
    }
}
