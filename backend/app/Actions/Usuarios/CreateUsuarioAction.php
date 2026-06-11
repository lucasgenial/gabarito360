<?php

namespace App\Actions\Usuarios;

use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreateUsuarioAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $userAttributes
     * @param  array<string, mixed>  $assignmentAttributes
     */
    public function execute(array $userAttributes, array $assignmentAttributes, User $actor): User
    {
        return DB::transaction(function () use ($userAttributes, $assignmentAttributes, $actor): User {
            $user = User::query()->create($userAttributes)->refresh();

            $link = $user->perfilVinculos()->create([
                ...$assignmentAttributes,
                'concedido_por' => $actor->id,
                'inicio_at' => now(),
            ]);

            $this->audit->record(
                action: AuditAction::USER_CREATED,
                entityType: 'usuario',
                entityId: $user->id,
                actorUserId: $actor->id,
                after: $user->only(['nome', 'email', 'documento', 'telefone', 'status']),
                metadata: ['vinculo_inicial_id' => $link->id],
                nucleoId: $link->nucleo_id,
                escolaId: $link->escola_id,
            );

            return $user;
        });
    }
}
