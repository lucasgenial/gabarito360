<?php

namespace App\Policies;

use App\Enums\PermissionCode;
use App\Models\User;
use App\Services\Authorization\AuthorizationContext;
use App\Services\Authorization\ScopeResolver;

abstract class BasePolicy
{
    public function __construct(
        protected ScopeResolver $scopeResolver,
    ) {}

    protected function authorize(
        User $user,
        PermissionCode $permission,
        ?AuthorizationContext $context,
    ): bool {
        return $this->scopeResolver->allows($user, $permission, $context);
    }
}
