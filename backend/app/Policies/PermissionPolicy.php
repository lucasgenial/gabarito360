<?php

namespace App\Policies;

use App\Enums\PermissionCode;
use App\Models\User;
use App\Services\Authorization\AuthorizationContext;

class PermissionPolicy extends BasePolicy
{
    public function allows(
        User $user,
        PermissionCode $permission,
        ?AuthorizationContext $context = null,
    ): bool {
        return $this->authorize($user, $permission, $context);
    }
}
