<?php

namespace App\Services\Authorization;

use App\Enums\PermissionCode;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Prova;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProvaScope
{
    public function __construct(
        private ScopeResolver $scopeResolver,
    ) {}

    public function canAccessAny(User $user): bool
    {
        return $this->hasGlobalAccess($user) || $this->educationCenterIds($user)->isNotEmpty();
    }

    public function canView(User $user, Prova $exam): bool
    {
        return $this->canManage($user, $exam);
    }

    public function canManage(User $user, Prova $exam): bool
    {
        return $this->hasGlobalAccess($user)
            || $this->allowsEducationCenter($user, $exam->ownerNucleoId());
    }

    public function canCreateForNucleo(User $user, Nucleo $educationCenter): bool
    {
        return $this->hasGlobalAccess($user)
            || $this->allowsEducationCenter($user, $educationCenter->id);
    }

    public function canCreateForSchool(User $user, Escola $school): bool
    {
        return $this->hasGlobalAccess($user)
            || $this->allowsEducationCenter($user, $school->nucleo_id);
    }

    /** @param Builder<Prova> $query */
    public function apply(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalAccess($user)) {
            return $query;
        }

        $educationCenterIds = $this->educationCenterIds($user);

        if ($educationCenterIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scope) use ($educationCenterIds): void {
            $scope
                ->whereIn('nucleo_id', $educationCenterIds)
                ->orWhereHas('escola', fn (Builder $schools) => $schools->whereIn('nucleo_id', $educationCenterIds));
        });
    }

    private function hasGlobalAccess(User $user): bool
    {
        return $this->scopeResolver->allows(
            $user,
            PermissionCode::MANAGE_EXAMS_ANSWER_KEYS,
            AuthorizationContext::global(),
        );
    }

    private function allowsEducationCenter(User $user, string $educationCenterId): bool
    {
        return $this->scopeResolver->allows(
            $user,
            PermissionCode::MANAGE_EXAMS_ANSWER_KEYS,
            AuthorizationContext::educationCenter($educationCenterId),
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
            ->filter(fn (string $id): bool => $this->allowsEducationCenter($user, $id))
            ->values();
    }
}
