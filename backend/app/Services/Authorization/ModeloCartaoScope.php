<?php

namespace App\Services\Authorization;

use App\Enums\PermissionCode;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ModeloCartaoScope
{
    public function __construct(
        private ScopeResolver $scopeResolver,
    ) {}

    public function canAccessAny(User $user): bool
    {
        return $this->hasGlobalAccess($user) || $this->educationCenterIds($user)->isNotEmpty();
    }

    public function canView(User $user, ModeloCartao $model): bool
    {
        if ($model->nucleo_id === null) {
            return $this->canAccessAny($user);
        }

        return $this->hasGlobalAccess($user) || $this->allowsEducationCenter($user, $model->nucleo_id);
    }

    public function canManage(User $user, ModeloCartao $model): bool
    {
        if ($model->nucleo_id === null) {
            return $this->hasGlobalAccess($user);
        }

        return $this->hasGlobalAccess($user) || $this->allowsEducationCenter($user, $model->nucleo_id);
    }

    public function canCreate(User $user, ?Nucleo $educationCenter): bool
    {
        if ($educationCenter === null) {
            return $this->hasGlobalAccess($user);
        }

        return $this->hasGlobalAccess($user) || $this->allowsEducationCenter($user, $educationCenter->id);
    }

    /** @param Builder<ModeloCartao> $query */
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
                ->whereNull('nucleo_id')
                ->orWhereIn('nucleo_id', $educationCenterIds);
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
