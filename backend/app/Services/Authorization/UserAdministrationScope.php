<?php

namespace App\Services\Authorization;

use App\Enums\AccessScope;
use App\Enums\PermissionCode;
use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserAdministrationScope
{
    public function __construct(
        private ScopeResolver $scopeResolver,
    ) {}

    public function canAccessAny(User $actor): bool
    {
        return $this->hasGlobalAccess($actor)
            || $this->educationCenterIds($actor)->isNotEmpty()
            || $this->schoolIds($actor)->isNotEmpty();
    }

    /** @param Builder<User> $query */
    public function apply(Builder $query, User $actor): Builder
    {
        if ($this->hasGlobalAccess($actor)) {
            return $query;
        }

        return $query->whereHas(
            'perfilVinculos',
            fn (Builder $links): Builder => $this->applyLinks($links, $actor),
        );
    }

    /** @param Builder<UsuarioPerfil> $query */
    public function applyLinks(Builder $query, User $actor): Builder
    {
        $query
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereHas('perfil', fn (Builder $profiles) => $profiles->where('status', StatusEnum::ACTIVE->value));

        if ($this->hasGlobalAccess($actor)) {
            return $query;
        }

        $educationCenterIds = $this->educationCenterIds($actor);
        $schoolIds = $this->schoolIds($actor);

        return $query->where(function (Builder $scope) use ($educationCenterIds, $schoolIds): void {
            $scope
                ->whereIn('nucleo_id', $educationCenterIds)
                ->orWhereIn('escola_id', $schoolIds);
        });
    }

    public function canView(User $actor, User $target): bool
    {
        return $this->hasGlobalAccess($actor)
            || $this->applyLinks($target->perfilVinculos()->getQuery(), $actor)->exists();
    }

    public function canManage(User $actor, User $target): bool
    {
        if ($this->hasGlobalAccess($actor)) {
            return true;
        }

        $activeLinks = $target->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->get();

        return $activeLinks->isNotEmpty()
            && $activeLinks->every(fn (UsuarioPerfil $link): bool => $this->canManageLink($actor, $link));
    }

    public function canManageLink(User $actor, UsuarioPerfil $link): bool
    {
        if ($this->hasGlobalAccess($actor)) {
            return true;
        }

        $link->loadMissing('perfil');
        $role = UserRole::tryFrom($link->perfil->codigo);

        if ($role === null || $link->perfil->escopo_permitido !== $role->allowedScope()) {
            return false;
        }

        return match ($role->allowedScope()) {
            AccessScope::GLOBAL => false,
            AccessScope::EDUCATION_CENTER => $link->nucleo_id !== null
                && $link->escola_id === null
                && $this->canAccessEducationCenter($actor, $link->nucleo_id),
            AccessScope::SCHOOL, AccessScope::OPERATIONAL => $link->nucleo_id === null
                && $link->escola_id !== null
                && $this->canAccessSchoolId($actor, $link->escola_id),
        };
    }

    public function canAssign(
        User $actor,
        Perfil $profile,
        ?Nucleo $educationCenter,
        ?Escola $school,
    ): bool {
        $role = UserRole::tryFrom($profile->codigo);

        if (
            $role === null
            || $profile->status !== StatusEnum::ACTIVE
            || $profile->escopo_permitido !== $role->allowedScope()
        ) {
            return false;
        }

        return match ($role->allowedScope()) {
            AccessScope::GLOBAL => $educationCenter === null
                && $school === null
                && $this->hasGlobalAccess($actor),
            AccessScope::EDUCATION_CENTER => $educationCenter?->status === StatusEnum::ACTIVE
                && $school === null
                && $this->canAccessEducationCenter($actor, $educationCenter->id),
            AccessScope::SCHOOL, AccessScope::OPERATIONAL => $educationCenter === null
                && $school?->status === StatusEnum::ACTIVE
                && $school->nucleo?->status === StatusEnum::ACTIVE
                && $this->canAccessSchool($actor, $school),
        };
    }

    public function hasGlobalAccess(User $actor): bool
    {
        return $this->scopeResolver->allows(
            $actor,
            PermissionCode::MANAGE_USERS_PROFILES_LINKS,
            AuthorizationContext::global(),
        );
    }

    public function canAccessEducationCenter(User $actor, string $educationCenterId): bool
    {
        return $this->scopeResolver->allows(
            $actor,
            PermissionCode::MANAGE_USERS_PROFILES_LINKS,
            AuthorizationContext::educationCenter($educationCenterId),
        );
    }

    public function canAccessSchool(User $actor, Escola $school): bool
    {
        return $this->scopeResolver->allows(
            $actor,
            PermissionCode::MANAGE_USERS_PROFILES_LINKS,
            AuthorizationContext::school($school->nucleo_id, $school->id),
        );
    }

    private function canAccessSchoolId(User $actor, string $schoolId): bool
    {
        $school = Escola::query()->find($schoolId);

        return $school instanceof Escola && $this->canAccessSchool($actor, $school);
    }

    /** @return Collection<int, string> */
    private function educationCenterIds(User $actor): Collection
    {
        return $actor->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereNotNull('nucleo_id')
            ->distinct()
            ->pluck('nucleo_id')
            ->filter(fn (string $id): bool => $this->canAccessEducationCenter($actor, $id))
            ->values();
    }

    /** @return Collection<int, string> */
    private function schoolIds(User $actor): Collection
    {
        $directIds = $actor->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereNotNull('escola_id')
            ->distinct()
            ->pluck('escola_id');

        $educationCenterSchoolIds = Escola::query()
            ->whereIn('nucleo_id', $this->educationCenterIds($actor))
            ->pluck('id');

        return $directIds
            ->merge($educationCenterSchoolIds)
            ->unique()
            ->filter(fn (string $id): bool => $this->canAccessSchoolId($actor, $id))
            ->values();
    }
}
