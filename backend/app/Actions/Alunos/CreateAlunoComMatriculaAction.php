<?php

namespace App\Actions\Alunos;

use App\Actions\Turmas\CreateMatriculaTurmaAction;
use App\Enums\MatriculaTurmaStatus;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAlunoComMatriculaAction
{
    public function __construct(
        private CreateAlunoAction $createAluno,
        private CreateMatriculaTurmaAction $createMatricula,
        private SyncResponsavelAction $syncResponsavel,
    ) {}

    /**
     * @param  array<string, mixed>  $alunoAttributes
     */
    public function execute(
        Turma $turma,
        array $alunoAttributes,
        ?string $responsavel,
        ?string $numeroChamada,
        User $actor,
    ): Aluno {
        return DB::transaction(function () use ($turma, $alunoAttributes, $responsavel, $numeroChamada, $actor): Aluno {
            $student = $this->createAluno->execute(
                [...$alunoAttributes, 'escola_id' => $turma->escola_id],
                $actor,
            );

            $this->createMatricula->execute($turma, [
                'aluno_id' => $student->id,
                'numero_chamada' => $numeroChamada,
                'status' => MatriculaTurmaStatus::ACTIVE->value,
                'inicio_em' => today(),
            ], $actor);

            if ($responsavel !== null && trim($responsavel) !== '') {
                $this->syncResponsavel->execute($student, $responsavel);
            }

            return $student->refresh();
        });
    }
}
