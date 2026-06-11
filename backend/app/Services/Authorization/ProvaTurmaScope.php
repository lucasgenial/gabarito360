<?php

namespace App\Services\Authorization;

use App\Enums\PermissionCode;
use App\Enums\ProvaStatus;
use App\Enums\StatusEnum;
use App\Models\Escola;
use App\Models\Prova;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProvaTurmaScope
{
    public function __construct(
        private ScopeResolver $scopeResolver,
    ) {}

    public function canAccessAny(User $user): bool
    {
        return $this->hasGlobalAccess($user)
            || $this->educationCenterIds($user)->isNotEmpty()
            || $this->schoolIds($user)->isNotEmpty();
    }

    public function canViewLinks(User $user, Prova $exam): bool
    {
        return $exam->status === ProvaStatus::PUBLISHED
            && $this->applyProvas(Prova::query()->whereKey($exam->id), $user)->exists();
    }

    public function canLink(User $user, Prova $exam, Turma $class): bool
    {
        $class->loadMissing('escola.nucleo');

        return $exam->status === ProvaStatus::PUBLISHED
            && $class->status === StatusEnum::ACTIVE
            && $class->escola->status === StatusEnum::ACTIVE
            && $class->escola->nucleo->status === StatusEnum::ACTIVE
            && $this->isCompatible($exam, $class)
            && $this->allowsSchool($user, $class->escola);
    }

    public function canUnlink(User $user, Prova $exam, Turma $class): bool
    {
        $class->loadMissing('escola');

        return $this->isCompatible($exam, $class)
            && $this->allowsSchool($user, $class->escola);
    }

    public function isCompatible(Prova $exam, Turma $class): bool
    {
        $class->loadMissing('escola');

        return $exam->nucleo_id !== null
            ? $exam->nucleo_id === $class->escola->nucleo_id
            : $exam->escola_id === $class->escola_id;
    }

    /** @param Builder<Prova> $query */
    public function applyProvas(Builder $query, User $user): Builder
    {
        $query->where('status', ProvaStatus::PUBLISHED->value);

        if ($this->hasGlobalAccess($user)) {
            return $query;
        }

        $educationCenterIds = $this->educationCenterIds($user);
        $schoolIds = $this->schoolIds($user);
        $availableEducationCenterIds = $educationCenterIds
            ->merge(Escola::query()->whereIn('id', $schoolIds)->pluck('nucleo_id'))
            ->unique()
            ->values();

        return $query->where(function (Builder $scope) use ($availableEducationCenterIds, $educationCenterIds, $schoolIds): void {
            $scope
                ->whereIn('nucleo_id', $availableEducationCenterIds)
                ->orWhereIn('escola_id', $schoolIds)
                ->orWhereHas('escola', fn (Builder $schools) => $schools->whereIn('nucleo_id', $educationCenterIds));
        });
    }

    /** @param Builder<Turma> $query */
    public function applyTurmas(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalAccess($user)) {
            return $query;
        }

        $educationCenterIds = $this->educationCenterIds($user);
        $schoolIds = $this->schoolIds($user);

        return $query->where(function (Builder $scope) use ($educationCenterIds, $schoolIds): void {
            $scope
                ->whereIn('escola_id', $schoolIds)
                ->orWhereHas('escola', fn (Builder $schools) => $schools->whereIn('nucleo_id', $educationCenterIds));
        });
    }

    private function hasGlobalAccess(User $user): bool
    {
        return $this->scopeResolver->allows(
            $user,
            PermissionCode::CREATE_APPLICATIONS,
            AuthorizationContext::global(),
        );
    }

    private function allowsSchool(User $user, Escola $school): bool
    {
        return $this->scopeResolver->allows(
            $user,
            PermissionCode::CREATE_APPLICATIONS,
            AuthorizationContext::school($school->nucleo_id, $school->id),
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
            ->filter(fn (string $id): bool => $this->scopeResolver->allows(
                $user,
                PermissionCode::CREATE_APPLICATIONS,
                AuthorizationContext::educationCenter($id),
            ))
            ->values();
    }

    /** @return Collection<int, string> */
    private function schoolIds(User $user): Collection
    {
        return $user->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereNotNull('escola_id')
            ->distinct()
            ->pluck('escola_id')
            ->filter(function (string $id) use ($user): bool {
                $school = Escola::query()->find($id);

                return $school instanceof Escola && $this->allowsSchool($user, $school);
            })
            ->values();
    }
}
