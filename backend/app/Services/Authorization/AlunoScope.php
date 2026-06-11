<?php

namespace App\Services\Authorization;

use App\Enums\PermissionCode;
use App\Models\Aluno;
use App\Models\AplicadorTurma;
use App\Models\Escola;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AlunoScope
{
    public function __construct(
        private ScopeResolver $scopeResolver,
    ) {}

    public function canAccessAny(User $user): bool
    {
        return $this->hasGlobalAccess($user, PermissionCode::VIEW_CLASSES_STUDENTS)
            || $this->schoolIds($user, PermissionCode::VIEW_CLASSES_STUDENTS)->isNotEmpty()
            || $this->operationalClassIds($user)->isNotEmpty();
    }

    public function canManageAny(User $user): bool
    {
        return $this->hasGlobalAccess($user, PermissionCode::MANAGE_CLASSES_STUDENTS)
            || $this->schoolIds($user, PermissionCode::MANAGE_CLASSES_STUDENTS)->isNotEmpty();
    }

    public function canCreateInSchool(User $user, Escola $school): bool
    {
        return $this->allowsSchool($user, $school, PermissionCode::MANAGE_CLASSES_STUDENTS);
    }

    public function canManage(User $user, Aluno $student): bool
    {
        if ($this->hasGlobalAccess($user, PermissionCode::MANAGE_CLASSES_STUDENTS)) {
            return true;
        }

        if (! $student->escola instanceof Escola) {
            return false;
        }

        return $this->allowsSchool($user, $student->escola, PermissionCode::MANAGE_CLASSES_STUDENTS);
    }

    /** @param Builder<Aluno> $query */
    public function apply(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalAccess($user, PermissionCode::VIEW_CLASSES_STUDENTS)) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($user): void {
            $scope
                ->whereIn('escola_id', $this->schoolIds($user, PermissionCode::VIEW_CLASSES_STUDENTS))
                ->orWhereHas('matriculasTurmas', fn (Builder $enrollments) => $enrollments
                    ->whereIn('turma_id', $this->operationalClassIds($user)));
        });
    }

    /** @param Builder<Aluno> $query */
    public function applyManageable(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalAccess($user, PermissionCode::MANAGE_CLASSES_STUDENTS)) {
            return $query;
        }

        return $query->whereIn('escola_id', $this->schoolIds($user, PermissionCode::MANAGE_CLASSES_STUDENTS));
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

    /** @return Collection<int, string> */
    private function operationalClassIds(User $user): Collection
    {
        return Turma::query()
            ->whereHas('aplicadores', fn (Builder $links) => $links
                ->where('usuario_id', $user->id)
                ->whereDate('inicio_em', '<=', today())
                ->whereNull('fim_em'))
            ->with('escola')
            ->get()
            ->filter(function (Turma $class) use ($user): bool {
                return AplicadorTurma::query()
                    ->where('turma_id', $class->id)
                    ->where('usuario_id', $user->id)
                    ->whereDate('inicio_em', '<=', today())
                    ->whereNull('fim_em')
                    ->exists()
                    && $this->scopeResolver->allows(
                        $user,
                        PermissionCode::VIEW_CLASSES_STUDENTS,
                        AuthorizationContext::operational(
                            explicitlyLinked: true,
                            nucleoId: $class->escola->nucleo_id,
                            escolaId: $class->escola_id,
                        ),
                    );
            })
            ->pluck('id')
            ->values();
    }
}
