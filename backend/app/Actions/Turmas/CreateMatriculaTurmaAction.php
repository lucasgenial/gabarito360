<?php

namespace App\Actions\Turmas;

use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateMatriculaTurmaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Turma $turma, array $attributes, User $actor): MatriculaTurma
    {
        return DB::transaction(function () use ($turma, $attributes, $actor): MatriculaTurma {
            $lockedClass = Turma::query()->lockForUpdate()->findOrFail($turma->id);
            $student = $lockedClass->escola->alunos()->findOrFail($attributes['aluno_id']);

            if ($student->escola_id !== $lockedClass->escola_id) {
                throw ValidationException::withMessages([
                    'aluno_id' => ['Aluno e turma devem pertencer a mesma escola.'],
                ]);
            }

            $matricula = $lockedClass->matriculas()->create([
                ...$attributes,
                'ano_letivo' => $lockedClass->ano_letivo,
            ])->refresh();
            $lockedClass->loadMissing('escola');

            $this->audit->record(
                action: AuditAction::ENROLLMENT_CREATED,
                entityType: 'matricula_turma',
                entityId: $matricula->id,
                actorUserId: $actor->id,
                after: $matricula->only(['aluno_id', 'turma_id', 'ano_letivo', 'numero_chamada', 'status', 'inicio_em', 'fim_em']),
                nucleoId: $lockedClass->escola->nucleo_id,
                escolaId: $lockedClass->escola_id,
            );

            return $matricula;
        });
    }
}
