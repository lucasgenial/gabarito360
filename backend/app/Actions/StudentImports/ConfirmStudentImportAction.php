<?php

namespace App\Actions\StudentImports;

use App\Enums\StudentImportStatus;
use App\Jobs\ImportStudentsJob;
use App\Models\ImportacaoAluno;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ConfirmStudentImportAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(ImportacaoAluno $import, User $actor): ImportacaoAluno
    {
        DB::transaction(function () use ($import, $actor): void {
            $lockedImport = ImportacaoAluno::query()->lockForUpdate()->findOrFail($import->id);

            if (in_array($lockedImport->status, [
                StudentImportStatus::PROCESSING,
                StudentImportStatus::COMPLETED,
            ], strict: true)) {
                return;
            }

            if ($lockedImport->status !== StudentImportStatus::VALIDATED) {
                throw new ConflictHttpException('A importacao precisa estar validada antes da confirmacao.');
            }

            $lockedImport->update([
                'status' => StudentImportStatus::PROCESSING,
                'confirmado_por' => $actor->id,
                'confirmado_at' => now(),
            ]);
            $lockedImport->loadMissing('escola');

            $this->audit->record(
                action: AuditAction::STUDENT_IMPORT_CONFIRMED,
                entityType: 'importacao_aluno',
                entityId: $lockedImport->id,
                actorUserId: $actor->id,
                before: ['status' => StudentImportStatus::VALIDATED],
                after: ['status' => StudentImportStatus::PROCESSING],
                metadata: ['arquivo_checksum_sha256' => $lockedImport->arquivo_checksum_sha256],
                nucleoId: $lockedImport->escola->nucleo_id,
                escolaId: $lockedImport->escola_id,
            );

            ImportStudentsJob::dispatch($lockedImport->id);
        }, 3);

        return $import->refresh();
    }
}
