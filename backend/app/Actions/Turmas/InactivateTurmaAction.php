<?php

namespace App\Actions\Turmas;

use App\Enums\StatusEnum;
use App\Models\Turma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class InactivateTurmaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(Turma $turma, User $actor): Turma
    {
        return DB::transaction(function () use ($turma, $actor): Turma {
            if ($turma->status === StatusEnum::INACTIVE) {
                return $turma;
            }

            $turma->update(['status' => StatusEnum::INACTIVE]);
            $turma->loadMissing('escola');

            $this->audit->record(
                action: AuditAction::CLASS_INACTIVATED,
                entityType: 'turma',
                entityId: $turma->id,
                actorUserId: $actor->id,
                before: ['status' => StatusEnum::ACTIVE],
                after: ['status' => StatusEnum::INACTIVE],
                nucleoId: $turma->escola->nucleo_id,
                escolaId: $turma->escola_id,
            );

            return $turma->refresh();
        });
    }
}
