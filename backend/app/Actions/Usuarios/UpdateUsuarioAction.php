<?php

namespace App\Actions\Usuarios;

use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateUsuarioAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(User $target, array $attributes, User $actor): User
    {
        return DB::transaction(function () use ($target, $attributes, $actor): User {
            $fields = ['nome', 'email', 'documento', 'telefone', 'status'];
            $before = $target->only($fields);

            $target->update($attributes);

            if ($target->wasChanged()) {
                $this->audit->record(
                    action: AuditAction::USER_UPDATED,
                    entityType: 'usuario',
                    entityId: $target->id,
                    actorUserId: $actor->id,
                    before: $before,
                    after: $target->only($fields),
                );
            }

            return $target->refresh();
        });
    }
}
