<?php

namespace App\Policies;

use App\Enums\PermissionCode;
use App\Models\Integracao;
use App\Models\User;
use App\Services\Authorization\AuthorizationContext;

class IntegracaoPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Integracao $integracao): bool
    {
        return $this->canManage($user);
    }

    public function test(User $user, Integracao $integracao): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $this->authorize(
            $user,
            PermissionCode::MANAGE_SETTINGS,
            AuthorizationContext::global(),
        );
    }
}
