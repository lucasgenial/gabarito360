<?php

namespace App\Actions\StudentImports;

use App\Enums\StudentImportStatus;
use App\Models\Escola;
use App\Models\ImportacaoAluno;
use App\Models\Turma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Import\StudentCsvImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class CreateStudentImportAction
{
    public function __construct(
        private StudentCsvImporter $importer,
        private AuditService $audit,
    ) {}

    public function execute(UploadedFile $file, Escola $school, Turma $class, User $actor): ImportacaoAluno
    {
        if ($class->escola_id !== $school->id) {
            throw new InvalidArgumentException('The class must belong to the selected school.');
        }

        $realPath = $file->getRealPath();
        $checksum = is_string($realPath) ? hash_file('sha256', $realPath) : false;

        if (! is_string($checksum)) {
            throw new RuntimeException('Nao foi possivel calcular a integridade do arquivo de importacao.');
        }

        $disk = (string) config('filesystems.private');
        $path = Storage::disk($disk)->putFileAs(
            'student-imports',
            $file,
            Str::uuid().'.csv',
        );

        if (! is_string($path)) {
            throw new RuntimeException('Nao foi possivel armazenar o arquivo de importacao.');
        }

        try {
            $result = $this->importer->inspect($disk, $path, $school, $class);

            return DB::transaction(function () use (
                $actor,
                $checksum,
                $class,
                $disk,
                $file,
                $path,
                $result,
                $school,
            ): ImportacaoAluno {
                $import = ImportacaoAluno::query()->create([
                    'escola_id' => $school->id,
                    'turma_id' => $class->id,
                    'solicitado_por' => $actor->id,
                    'status' => $result->isValid()
                        ? StudentImportStatus::VALIDATED
                        : StudentImportStatus::HAS_ERRORS,
                    'arquivo_disco' => $disk,
                    'arquivo_caminho' => $path,
                    'arquivo_nome' => Str::limit(basename($file->getClientOriginalName()), 255, ''),
                    'arquivo_checksum_sha256' => $checksum,
                    'resumo' => $result->summary,
                    'erros' => $result->errors,
                ]);

                $school->loadMissing('nucleo');
                $this->audit->record(
                    action: AuditAction::STUDENT_IMPORT_CREATED,
                    entityType: 'importacao_aluno',
                    entityId: $import->id,
                    actorUserId: $actor->id,
                    after: ['status' => $import->status, 'resumo' => $result->summary],
                    metadata: ['arquivo_checksum_sha256' => $import->arquivo_checksum_sha256],
                    nucleoId: $school->nucleo_id,
                    escolaId: $school->id,
                );

                return $import;
            }, 3);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }
}
