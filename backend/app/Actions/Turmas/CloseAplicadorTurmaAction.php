<?php

namespace App\Actions\Turmas;

use App\Models\AplicadorTurma;
use App\Models\Turma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CloseAplicadorTurmaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(
        Turma $class,
        AplicadorTurma $link,
        array $attributes,
        User $actor,
    ): AplicadorTurma {
        return DB::transaction(function () use ($class, $link, $attributes, $actor): AplicadorTurma {
            $link->update($attributes);
            $class->loadMissing('escola');

            $this->audit->record(
                action: AuditAction::CLASS_STAFF_CLOSED,
                entityType: 'aplicador_turma',
                entityId: $link->id,
                actorUserId: $actor->id,
                before: ['fim_em' => null],
                after: $link->only(['fim_em']),
                metadata: ['turma_id' => $class->id, 'usuario_id' => $link->usuario_id],
                nucleoId: $class->escola->nucleo_id,
                escolaId: $class->escola_id,
            );

            return $link->refresh();
        });
    }
}
