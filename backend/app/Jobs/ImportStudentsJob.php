<?php

namespace App\Jobs;

use App\Enums\StudentImportStatus;
use App\Models\ImportacaoAluno;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Import\StudentCsvImporter;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class ImportStudentsJob implements ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    public function __construct(public readonly string $importId)
    {
        if (! Str::isUuid($importId)) {
            throw new InvalidArgumentException('The student import identifier must be a UUID.');
        }
    }

    public function handle(StudentCsvImporter $importer): void
    {
        $importer->process($this->importId);
    }

    public function failed(Throwable $exception): void
    {
        $import = ImportacaoAluno::query()->with('escola')->find($this->importId);

        if ($import === null || $import->status === StudentImportStatus::COMPLETED) {
            return;
        }

        $import->update([
            'status' => StudentImportStatus::FAILED,
            'erros' => [[
                'linha' => 0,
                'campo' => 'processamento',
                'mensagem' => 'Nao foi possivel concluir a importacao.',
            ]],
            'processado_at' => now(),
        ]);

        app(AuditService::class)->record(
            action: AuditAction::STUDENT_IMPORT_FAILED,
            entityType: 'importacao_aluno',
            entityId: $import->id,
            actorUserId: $import->confirmado_por,
            after: ['status' => StudentImportStatus::FAILED],
            metadata: ['falha_interna_omitida' => true],
            nucleoId: $import->escola->nucleo_id,
            escolaId: $import->escola_id,
        );
    }
}
