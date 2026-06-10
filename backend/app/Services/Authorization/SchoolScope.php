<?php

namespace App\Services\Authorization;

use App\Enums\PermissionCode;
use App\Models\Escola;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SchoolScope
{
    public function __construct(
        private ScopeResolver $scopeResolver,
    ) {}

    public function canAccessAny(User $user): bool
    {
        return $this->hasGlobalAccess($user) || $this->educationCenterIds($user)->isNotEmpty();
    }

    public function canAccessEducationCenter(User $user, string $nucleoId): bool
    {
        return $this->scopeResolver->allows(
            $user,
            PermissionCode::MANAGE_SCHOOLS,
            AuthorizationContext::educationCenter($nucleoId),
        );
    }

    /** @param Builder<Escola> $query */
    public function apply(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalAccess($user)) {
            return $query;
        }

        return $query->whereIn('nucleo_id', $this->educationCenterIds($user));
    }

    private function hasGlobalAccess(User $user): bool
    {
        return $this->scopeResolver->allows(
            $user,
            PermissionCode::MANAGE_SCHOOLS,
            AuthorizationContext::global(),
        );
    }

    /** @return Collection<int, string> */
    private function educationCenterIds(User $user): Collection
    {
        return $user->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereNotNull('nucleo_id')
            ->distinct()
            ->pluck('nucleo_id')
            ->filter(fn (string $nucleoId): bool => $this->canAccessEducationCenter($user, $nucleoId))
            ->values();
    }
}
