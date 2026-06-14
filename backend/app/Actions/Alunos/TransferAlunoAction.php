<?php

namespace App\Actions\Alunos;

use App\Actions\Turmas\CloseMatriculaTurmaAction;
use App\Actions\Turmas\CreateMatriculaTurmaAction;
use App\Enums\MatriculaTurmaStatus;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferAlunoAction
{
    public function __construct(
        private CloseMatriculaTurmaAction $closeMatricula,
        private CreateMatriculaTurmaAction $createMatricula,
    ) {}

    public function execute(Aluno $student, Turma $turmaDestino, ?string $numeroChamada, User $actor): void
    {
        DB::transaction(function () use ($student, $turmaDestino, $numeroChamada, $actor): void {
            $ativa = $student->matriculasTurmas()
                ->where('status', MatriculaTurmaStatus::ACTIVE->value)
                ->first();

            if ($ativa !== null && $ativa->turma_id === $turmaDestino->id) {
                return;
            }

            if ($student->escola_id !== $turmaDestino->escola_id) {
                throw ValidationException::withMessages([
                    'turma_id' => ['A transferencia so e permitida dentro da mesma escola.'],
                ]);
            }

            // Encerra a matrícula vigente ANTES de criar a nova (chave_vigente).
            if ($ativa !== null) {
                $this->closeMatricula->execute(
                    $ativa->turma,
                    $ativa,
                    ['status' => MatriculaTurmaStatus::TRANSFERRED->value, 'fim_em' => today()],
                    $actor,
                );
            }

            $this->createMatricula->execute($turmaDestino, [
                'aluno_id' => $student->id,
                'numero_chamada' => $numeroChamada,
                'status' => MatriculaTurmaStatus::ACTIVE->value,
                'inicio_em' => today(),
            ], $actor);
        });
    }
}
