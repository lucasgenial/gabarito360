<?php

namespace App\Services\Authorization;

use App\Enums\PermissionCode;
use App\Models\Escola;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TurmaScope
{
    public function __construct(
        private ScopeResolver $scopeResolver,
    ) {}

    public function canAccessAny(User $user): bool
    {
        return $this->hasGlobalAccess($user, PermissionCode::VIEW_CLASSES_STUDENTS)
            || $this->schoolIds($user, PermissionCode::VIEW_CLASSES_STUDENTS)->isNotEmpty();
    }

    public function canView(User $user, Turma $turma): bool
    {
        return $this->allowsSchool($user, $turma->escola, PermissionCode::VIEW_CLASSES_STUDENTS);
    }

    public function canManage(User $user, Turma $turma): bool
    {
        return $this->allowsSchool($user, $turma->escola, PermissionCode::MANAGE_CLASSES_STUDENTS);
    }

    public function canCreateInSchool(User $user, Escola $school): bool
    {
        return $this->allowsSchool($user, $school, PermissionCode::MANAGE_CLASSES_STUDENTS);
    }

    /** @param Builder<Turma> $query */
    public function apply(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalAccess($user, PermissionCode::VIEW_CLASSES_STUDENTS)) {
            return $query;
        }

        return $query->whereIn('escola_id', $this->schoolIds($user, PermissionCode::VIEW_CLASSES_STUDENTS));
    }

    private function allowsSchool(User $user, Escola $school, PermissionCode $permission): bool
    {
        return $this->hasGlobalAccess($user, $permission)
            || $this->scopeResolver->allows(
                $user,
                $permission,
                AuthorizationContext::school($school->nucleo_id, $school->id),
            );
    }

    private function hasGlobalAccess(User $user, PermissionCode $permission): bool
    {
        return $this->scopeResolver->allows($user, $permission, AuthorizationContext::global());
    }

    /** @return Collection<int, string> */
    private function schoolIds(User $user, PermissionCode $permission): Collection
    {
        $directIds = $user->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereNotNull('escola_id')
            ->distinct()
            ->pluck('escola_id');

        $educationCenterIds = $user->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereNotNull('nucleo_id')
            ->distinct()
            ->pluck('nucleo_id');

        return Escola::query()
            ->where(function (Builder $query) use ($directIds, $educationCenterIds): void {
                $query
                    ->whereIn('id', $directIds)
                    ->orWhereIn('nucleo_id', $educationCenterIds);
            })
            ->get()
            ->filter(fn (Escola $school): bool => $this->allowsSchool($user, $school, $permission))
            ->pluck('id')
            ->values();
    }
}
