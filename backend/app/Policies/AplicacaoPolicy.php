<?php

namespace App\Policies;

use App\Enums\PermissionCode;
use App\Models\Aplicacao;
use App\Models\User;
use App\Services\Authorization\AuthorizationContext;

class AplicacaoPolicy extends BasePolicy
{
    public function view(User $user, Aplicacao $aplicacao): bool
    {
        return $this->authorizeInApplicationContext($user, PermissionCode::VIEW_APPLICATION_DASHBOARD, $aplicacao);
    }

    public function run(User $user, Aplicacao $aplicacao): bool
    {
        return $this->authorize(
            $user,
            PermissionCode::RUN_APPLICATIONS,
            AuthorizationContext::operational(
                explicitlyLinked: $this->isOperationallyLinked($user, $aplicacao),
                nucleoId: $aplicacao->escola->nucleo_id,
                escolaId: $aplicacao->escola_id,
            ),
        );
    }

    private function authorizeInApplicationContext(User $user, PermissionCode $permission, Aplicacao $aplicacao): bool
    {
        $aplicacao->loadMissing('escola');

        return $this->authorize(
            $user,
            $permission,
            AuthorizationContext::school($aplicacao->escola->nucleo_id, $aplicacao->escola_id),
        ) || $this->authorize(
            $user,
            $permission,
            AuthorizationContext::operational(
                explicitlyLinked: $this->isOperationallyLinked($user, $aplicacao),
                nucleoId: $aplicacao->escola->nucleo_id,
                escolaId: $aplicacao->escola_id,
            ),
        );
    }

    private function isOperationallyLinked(User $user, Aplicacao $aplicacao): bool
    {
        return $aplicacao->aplicadores()
            ->where('usuario_id', $user->id)
            ->whereNull('fim_at')
            ->exists()
            || $aplicacao->turma->aplicadores()
                ->where('usuario_id', $user->id)
                ->whereNull('fim_em')
                ->exists();
    }
}
