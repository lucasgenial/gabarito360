<?php

namespace App\Actions\Usuarios;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class InactivateUsuarioAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(User $target, User $actor): User
    {
        return DB::transaction(function () use ($target, $actor): User {
            if ($target->status === UserStatus::INACTIVE) {
                return $target;
            }

            $before = $target->status;
            $target->update(['status' => UserStatus::INACTIVE]);

            $this->audit->record(
                action: AuditAction::USER_INACTIVATED,
                entityType: 'usuario',
                entityId: $target->id,
                actorUserId: $actor->id,
                before: ['status' => $before],
                after: ['status' => UserStatus::INACTIVE],
            );

            return $target->refresh();
        });
    }
}
