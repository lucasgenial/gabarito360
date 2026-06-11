<?php

namespace App\Observers;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

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

        if ($user->status === UserStatus::ACTIVE) {
            return;
        }

        $user->tokens()->delete();
        $user->dispositivosMobile()
            ->whereNull('revogado_at')
            ->update(['revogado_at' => now()]);
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
