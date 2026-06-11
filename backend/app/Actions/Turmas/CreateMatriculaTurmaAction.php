<?php

namespace App\Actions\Turmas;

use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreateMatriculaTurmaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Turma $turma, array $attributes, User $actor): MatriculaTurma
    {
        return DB::transaction(function () use ($turma, $attributes, $actor): MatriculaTurma {
            $matricula = $turma->matriculas()->create([
                ...$attributes,
                'ano_letivo' => $turma->ano_letivo,
            ])->refresh();
            $turma->loadMissing('escola');

            $this->audit->record(
                action: AuditAction::ENROLLMENT_CREATED,
                entityType: 'matricula_turma',
                entityId: $matricula->id,
                actorUserId: $actor->id,
                after: $matricula->only(['aluno_id', 'turma_id', 'ano_letivo', 'numero_chamada', 'status', 'inicio_em', 'fim_em']),
                nucleoId: $turma->escola->nucleo_id,
                escolaId: $turma->escola_id,
            );

            return $matricula;
        });
    }
}
