<?php

namespace App\Policies;

use App\Enums\PermissionCode;
use App\Models\Nucleo;
use App\Models\User;
use App\Services\Authorization\AuthorizationContext;

class NucleoPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, Nucleo $nucleo): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Nucleo $nucleo): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Nucleo $nucleo): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $this->authorize(
            $user,
            PermissionCode::MANAGE_EDUCATION_CENTERS,
            AuthorizationContext::global(),
        );
    }
}
