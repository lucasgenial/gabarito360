<?php

namespace App\Actions\Turmas;

use App\Models\Turma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateTurmaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Turma $turma, array $attributes, User $actor): Turma
    {
        return DB::transaction(function () use ($turma, $attributes, $actor): Turma {
            $fields = ['escola_id', 'codigo', 'nome', 'serie_ano', 'turno', 'ano_letivo', 'status'];
            $before = $turma->only($fields);
            $turma->update($attributes);

            if ($turma->wasChanged()) {
                $turma->loadMissing('escola');
                $this->audit->record(
                    action: AuditAction::CLASS_UPDATED,
                    entityType: 'turma',
                    entityId: $turma->id,
                    actorUserId: $actor->id,
                    before: $before,
                    after: $turma->only($fields),
                    nucleoId: $turma->escola->nucleo_id,
                    escolaId: $turma->escola_id,
                );
            }

            return $turma->refresh();
        });
    }
}
