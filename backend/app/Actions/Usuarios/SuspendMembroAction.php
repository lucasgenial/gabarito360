<?php

namespace App\Actions\Usuarios;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class SuspendMembroAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(User $membro, User $actor): User
    {
        return DB::transaction(function () use ($membro, $actor): User {
            $before = $membro->status;

            if ($membro->status !== UserStatus::BLOCKED) {
                $membro->update(['status' => UserStatus::BLOCKED]);
                $membro->tokens()->delete();
                $membro->sessoes()->ativas()->update(['encerrado_at' => now()]);
            }

            $this->audit->record(
                action: AuditAction::USER_STATUS_CHANGED,
                entityType: 'usuario',
                entityId: $membro->id,
                actorUserId: $actor->id,
                before: ['status' => $before],
                after: ['status' => UserStatus::BLOCKED],
                metadata: ['operacao' => 'suspender'],
            );

            return $membro->refresh();
        });
    }
}
