<?php

namespace App\Actions\StudentImports;

use App\Enums\StudentImportStatus;
use App\Models\ImportacaoAluno;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Import\StudentCsvImporter;
use Illuminate\Support\Facades\DB;

/**
 * Processa a importação de forma SÍNCRONA (encapsula o fluxo two-phase:
 * confirmação + processamento), para o endpoint único POST /turmas/importar.
 */
class ProcessStudentImportSyncAction
{
    public function __construct(
        private StudentCsvImporter $importer,
        private AuditService $audit,
    ) {}

    public function execute(ImportacaoAluno $import, User $actor): ImportacaoAluno
    {
        if ($import->status !== StudentImportStatus::VALIDATED) {
            return $import;
        }

        DB::transaction(function () use ($import, $actor): void {
            $locked = ImportacaoAluno::query()->lockForUpdate()->findOrFail($import->id);

            if ($locked->status !== StudentImportStatus::VALIDATED) {
                return;
            }

            $locked->update([
                'status' => StudentImportStatus::PROCESSING,
                'confirmado_por' => $actor->id,
                'confirmado_at' => now(),
            ]);
            $locked->loadMissing('escola');

            $this->audit->record(
                action: AuditAction::STUDENT_IMPORT_CONFIRMED,
                entityType: 'importacao_aluno',
                entityId: $locked->id,
                actorUserId: $actor->id,
                before: ['status' => StudentImportStatus::VALIDATED],
                after: ['status' => StudentImportStatus::PROCESSING],
                nucleoId: $locked->escola->nucleo_id,
                escolaId: $locked->escola_id,
            );
        }, 3);

        // Persiste as linhas inline (re-checa checksum, marca COMPLETED, audita).
        $this->importer->process($import->id);

        return $import->refresh();
    }
}
