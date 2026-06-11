<?php

namespace App\Services\Import;

use App\DTOs\StudentCsvValidationResult;
use App\Enums\MatriculaTurmaStatus;
use App\Enums\StatusEnum;
use App\Enums\StudentImportStatus;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\ImportacaoAluno;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class StudentCsvImporter
{
    private const HEADER = ['matricula', 'codigo_interno', 'nome', 'numero_chamada'];

    public function __construct(
        private AuditService $audit,
    ) {}

    public function inspect(string $disk, string $path, Escola $school, Turma $class): StudentCsvValidationResult
    {
        [$rows, $errors, $totalRows] = $this->readRows($disk, $path);
        $summary = $this->emptySummary($totalRows);
        $invalidLines = array_fill_keys(array_column($errors, 'linha'), true);

        foreach ($rows as $row) {
            if (isset($invalidLines[$row['linha']])) {
                continue;
            }

            $student = Aluno::query()
                ->where('escola_id', $school->id)
                ->whereRaw('lower(matricula) = ?', [mb_strtolower($row['matricula'])])
                ->first();

            if ($student?->status === StatusEnum::INACTIVE) {
                $errors[] = $this->error($row['linha'], 'matricula', 'A matricula pertence a um aluno inativo.');

                continue;
            }

            if ($student === null) {
                $summary['inclusoes']++;
                $summary['matriculas_novas']++;

                continue;
            }

            $studentChanged = $student->nome !== $row['nome']
                || $student->codigo_interno !== $row['codigo_interno'];
            $summary[$studentChanged ? 'atualizacoes' : 'sem_alteracao']++;

            $enrollment = MatriculaTurma::query()
                ->where('aluno_id', $student->id)
                ->where('ano_letivo', $class->ano_letivo)
                ->where('status', MatriculaTurmaStatus::ACTIVE->value)
                ->first();

            if ($enrollment !== null && $enrollment->turma_id !== $class->id) {
                $errors[] = $this->error(
                    $row['linha'],
                    'matricula',
                    'O aluno ja possui matricula ativa em outra turma neste ano letivo.',
                );

                continue;
            }

            $summary[$enrollment === null ? 'matriculas_novas' : 'matriculas_existentes']++;
        }

        $summary['erros'] = count($errors);

        return new StudentCsvValidationResult($summary, $errors, $rows);
    }

    public function process(string $importId): void
    {
        DB::transaction(function () use ($importId): void {
            $import = ImportacaoAluno::query()->lockForUpdate()->findOrFail($importId);

            if ($import->status === StudentImportStatus::COMPLETED) {
                return;
            }

            if ($import->status !== StudentImportStatus::PROCESSING) {
                throw new RuntimeException('A importacao nao esta pronta para processamento.');
            }

            $import->loadMissing('escola.nucleo', 'turma');

            if (
                $import->turma->escola_id !== $import->escola_id
                ||
                $import->escola->status !== StatusEnum::ACTIVE
                || $import->escola->nucleo->status !== StatusEnum::ACTIVE
                || $import->turma->status !== StatusEnum::ACTIVE
            ) {
                throw new RuntimeException('A escola ou turma foi inativada antes do processamento.');
            }

            $this->assertFileIntegrity($import);
            $result = $this->inspect(
                $import->arquivo_disco,
                $import->arquivo_caminho,
                $import->escola,
                $import->turma,
            );

            if (! $result->isValid()) {
                throw new RuntimeException('O arquivo deixou de ser valido antes do processamento.');
            }

            foreach ($result->rows as $row) {
                $this->persistRow($import, $row);
            }

            $import->update([
                'status' => StudentImportStatus::COMPLETED,
                'resumo' => $result->summary,
                'erros' => [],
                'processado_at' => now(),
            ]);

            $this->audit->record(
                action: AuditAction::STUDENT_IMPORT_COMPLETED,
                entityType: 'importacao_aluno',
                entityId: $import->id,
                actorUserId: $import->confirmado_por,
                after: ['status' => StudentImportStatus::COMPLETED, 'resumo' => $result->summary],
                metadata: ['arquivo_checksum_sha256' => $import->arquivo_checksum_sha256],
                nucleoId: $import->escola->nucleo_id,
                escolaId: $import->escola_id,
            );
        }, 3);
    }

    /**
     * @param  array{linha: int, matricula: string, codigo_interno: ?string, nome: string, numero_chamada: ?string}  $row
     */
    private function persistRow(ImportacaoAluno $import, array $row): void
    {
        $student = Aluno::query()
            ->where('escola_id', $import->escola_id)
            ->whereRaw('lower(matricula) = ?', [mb_strtolower($row['matricula'])])
            ->lockForUpdate()
            ->first();

        if ($student === null) {
            $student = Aluno::query()->create([
                'escola_id' => $import->escola_id,
                'matricula' => $row['matricula'],
                'codigo_interno' => $row['codigo_interno'],
                'nome' => $row['nome'],
                'status' => StatusEnum::ACTIVE,
            ]);
        } else {
            if ($student->status !== StatusEnum::ACTIVE) {
                throw new RuntimeException('A importacao encontrou um aluno inativo.');
            }

            $student->update([
                'codigo_interno' => $row['codigo_interno'],
                'nome' => $row['nome'],
            ]);
        }

        $enrollment = MatriculaTurma::query()
            ->where('aluno_id', $student->id)
            ->where('ano_letivo', $import->turma->ano_letivo)
            ->where('status', MatriculaTurmaStatus::ACTIVE->value)
            ->lockForUpdate()
            ->first();

        if ($enrollment !== null && $enrollment->turma_id !== $import->turma_id) {
            throw new RuntimeException('A importacao encontrou conflito de matricula ativa.');
        }

        if ($enrollment === null) {
            MatriculaTurma::query()->create([
                'aluno_id' => $student->id,
                'turma_id' => $import->turma_id,
                'ano_letivo' => $import->turma->ano_letivo,
                'numero_chamada' => $row['numero_chamada'],
                'status' => MatriculaTurmaStatus::ACTIVE,
                'inicio_em' => today(),
            ]);

            return;
        }

        if ($enrollment->numero_chamada !== $row['numero_chamada']) {
            $enrollment->update(['numero_chamada' => $row['numero_chamada']]);
        }
    }

    /**
     * @return array{
     *     0: list<array{linha: int, matricula: string, codigo_interno: ?string, nome: string, numero_chamada: ?string}>,
     *     1: list<array{linha: int, campo: string, mensagem: string}>,
     *     2: int
     * }
     */
    private function readRows(string $disk, string $path): array
    {
        $stream = Storage::disk($disk)->readStream($path);

        if (! is_resource($stream)) {
            throw new RuntimeException('Nao foi possivel ler o arquivo de importacao.');
        }

        try {
            $header = fgetcsv($stream, 0, ',', '"', '');

            if (! is_array($header)) {
                return [[], [$this->error(1, 'arquivo', 'O arquivo CSV esta vazio.')], 0];
            }

            $header[0] = $this->removeBom((string) ($header[0] ?? ''));
            $header = array_map(fn (mixed $value): string => mb_strtolower(trim((string) $value)), $header);

            if ($header !== self::HEADER) {
                return [[], [$this->error(
                    1,
                    'cabecalho',
                    'Use exatamente as colunas: matricula,codigo_interno,nome,numero_chamada.',
                )], 0];
            }

            $rows = [];
            $errors = [];
            $seenEnrollments = [];
            $line = 1;
            $totalRows = 0;
            $maxRows = (int) config('gabarito360.imports.students.max_rows');

            while (($columns = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                $line++;

                if ($columns === [null] || collect($columns)->every(fn (mixed $value): bool => trim((string) $value) === '')) {
                    continue;
                }

                $totalRows++;

                if ($totalRows > $maxRows) {
                    $errors[] = $this->error($line, 'arquivo', "O arquivo excede o limite de {$maxRows} linhas.");

                    break;
                }

                if (count($columns) !== count(self::HEADER)) {
                    $errors[] = $this->error($line, 'linha', 'A linha deve possuir exatamente quatro colunas.');

                    continue;
                }

                if (! mb_check_encoding($columns, 'UTF-8')) {
                    $errors[] = $this->error($line, 'linha', 'A linha deve estar codificada em UTF-8.');

                    continue;
                }

                $row = [
                    'linha' => $line,
                    'matricula' => mb_strtoupper(trim((string) $columns[0])),
                    'codigo_interno' => $this->nullableTrimmed($columns[1]),
                    'nome' => trim((string) $columns[2]),
                    'numero_chamada' => $this->nullableTrimmed($columns[3]),
                ];
                $validator = Validator::make(
                    $row,
                    [
                        'matricula' => ['required', 'string', 'max:80'],
                        'codigo_interno' => ['nullable', 'string', 'max:80'],
                        'nome' => ['required', 'string', 'max:180'],
                        'numero_chamada' => ['nullable', 'string', 'max:20'],
                    ],
                    [
                        'required' => 'O campo :attribute e obrigatorio.',
                        'max' => 'O campo :attribute excede o tamanho maximo permitido.',
                    ],
                );

                foreach ($validator->errors()->messages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors[] = $this->error($line, $field, $message);
                    }
                }

                $enrollmentKey = mb_strtolower($row['matricula']);

                if (isset($seenEnrollments[$enrollmentKey])) {
                    $errors[] = $this->error(
                        $line,
                        'matricula',
                        'A matricula esta duplicada no arquivo.',
                    );
                }

                $seenEnrollments[$enrollmentKey] = true;
                $rows[] = $row;
            }

            if ($rows === [] && $errors === []) {
                $errors[] = $this->error(2, 'arquivo', 'O arquivo nao possui linhas de alunos.');
            }

            return [$rows, $errors, $totalRows];
        } finally {
            fclose($stream);
        }
    }

    private function assertFileIntegrity(ImportacaoAluno $import): void
    {
        $stream = Storage::disk($import->arquivo_disco)->readStream($import->arquivo_caminho);

        if (! is_resource($stream)) {
            throw new RuntimeException('O arquivo de importacao nao esta disponivel.');
        }

        try {
            $checksum = hash_init('sha256');
            hash_update_stream($checksum, $stream);

            if (! hash_equals($import->arquivo_checksum_sha256, hash_final($checksum))) {
                throw new RuntimeException('O arquivo de importacao foi alterado apos a validacao.');
            }
        } finally {
            fclose($stream);
        }
    }

    /** @return array<string, int> */
    private function emptySummary(int $rows): array
    {
        return [
            'linhas' => $rows,
            'inclusoes' => 0,
            'atualizacoes' => 0,
            'sem_alteracao' => 0,
            'matriculas_novas' => 0,
            'matriculas_existentes' => 0,
            'erros' => 0,
        ];
    }

    /** @return array{linha: int, campo: string, mensagem: string} */
    private function error(int $line, string $field, string $message): array
    {
        return [
            'linha' => $line,
            'campo' => $field,
            'mensagem' => $message,
        ];
    }

    private function nullableTrimmed(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function removeBom(string $value): string
    {
        return str_starts_with($value, "\xEF\xBB\xBF") ? substr($value, 3) : $value;
    }
}
