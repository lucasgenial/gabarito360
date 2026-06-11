<?php

namespace App\Actions\Turmas;

use App\Models\AplicadorTurma;
use App\Models\Turma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class AssignAplicadorTurmaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Turma $class, array $attributes, User $actor): AplicadorTurma
    {
        return DB::transaction(function () use ($class, $attributes, $actor): AplicadorTurma {
            $link = $class->aplicadores()->create([
                ...$attributes,
                'vinculado_por' => $actor->id,
            ])->refresh();
            $class->loadMissing('escola');

            $this->audit->record(
                action: AuditAction::CLASS_STAFF_ASSIGNED,
                entityType: 'aplicador_turma',
                entityId: $link->id,
                actorUserId: $actor->id,
                after: $link->only(['turma_id', 'usuario_id', 'papel', 'inicio_em', 'fim_em']),
                nucleoId: $class->escola->nucleo_id,
                escolaId: $class->escola_id,
            );

            return $link;
        });
    }
}
