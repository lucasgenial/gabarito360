<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;

class UserAccessObserver
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function updated(User $user): void
    {
        if (! $user->wasChanged('status')) {
            return;
        }

        $this->audit->record(
            action: AuditAction::USER_STATUS_CHANGED,
            entityType: 'usuario',
            entityId: $user->id,
            before: ['status' => $user->getOriginal('status')],
            after: ['status' => $user->status],
        );
    }
}
