<?php

namespace App\Actions\Turmas;

use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CloseMatriculaTurmaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(
        Turma $turma,
        MatriculaTurma $matricula,
        array $attributes,
        User $actor,
    ): MatriculaTurma {
        return DB::transaction(function () use ($turma, $matricula, $attributes, $actor): MatriculaTurma {
            $before = $matricula->only(['status', 'fim_em']);
            $matricula->update($attributes);
            $turma->loadMissing('escola');

            $this->audit->record(
                action: AuditAction::ENROLLMENT_CLOSED,
                entityType: 'matricula_turma',
                entityId: $matricula->id,
                actorUserId: $actor->id,
                before: $before,
                after: $matricula->only(['status', 'fim_em']),
                metadata: ['turma_id' => $turma->id, 'aluno_id' => $matricula->aluno_id],
                nucleoId: $turma->escola->nucleo_id,
                escolaId: $turma->escola_id,
            );

            return $matricula->refresh();
        });
    }
}
