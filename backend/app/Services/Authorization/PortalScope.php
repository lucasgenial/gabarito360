<?php

namespace App\Services\Authorization;

use App\Enums\PermissionCode;
use App\Models\Aplicacao;
use App\Models\Escola;
use App\Models\Prova;
use App\Models\Relatorio;
use App\Models\Resultado;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PortalScope
{
    public function __construct(
        private ScopeResolver $resolver,
        private TurmaScope $turmaScope,
        private ProvaScope $provaScope,
        private ProvaTurmaScope $provaTurmaScope,
    ) {}

    /** @param Builder<Escola> $query */
    public function applySchools(Builder $query, User $user): Builder
    {
        if ($this->hasGlobal($user, PermissionCode::VIEW_APPLICATION_DASHBOARD)
            || $this->hasGlobal($user, PermissionCode::MANAGE_SCHOOLS)) {
            return $query;
        }

        return $query->whereIn('id', $this->schoolIds($user));
    }

    public function canViewSchool(User $user, Escola $school): bool
    {
        return $this->applySchools(Escola::query()->whereKey($school), $user)->exists();
    }

    /** @param Builder<Aplicacao> $query */
    public function applyApplications(Builder $query, User $user): Builder
    {
        if ($this->hasGlobal($user, PermissionCode::VIEW_APPLICATION_DASHBOARD)) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($user): void {
            $scope
                ->whereIn('escola_id', $this->schoolIdsFor($user, PermissionCode::VIEW_APPLICATION_DASHBOARD))
                ->orWhereHas('aplicadores', fn (Builder $links) => $links
                    ->where('usuario_id', $user->id)
                    ->whereNull('fim_at'))
                ->orWhereHas('turma.aplicadores', fn (Builder $links) => $links
                    ->where('usuario_id', $user->id)
                    ->whereNull('fim_em'));
        });
    }

    public function canViewApplications(User $user): bool
    {
        return $this->applyApplications(Aplicacao::query(), $user)->exists()
            || $this->hasAnyPermission($user, PermissionCode::VIEW_APPLICATION_DASHBOARD);
    }

    /** @param Builder<Resultado> $query */
    public function applyResults(Builder $query, User $user): Builder
    {
        if ($this->hasGlobal($user, PermissionCode::VIEW_RESULTS)) {
            return $query;
        }

        $schoolIds = $this->schoolIdsFor($user, PermissionCode::VIEW_RESULTS);

        return $query->whereHas('aplicacao', function (Builder $applications) use ($schoolIds, $user): void {
            $applications->where(function (Builder $scope) use ($schoolIds, $user): void {
                $scope
                    ->whereIn('escola_id', $schoolIds)
                    ->orWhereHas('aplicadores', fn (Builder $links) => $links
                        ->where('usuario_id', $user->id)
                        ->whereNull('fim_at'))
                    ->orWhereHas('turma.aplicadores', fn (Builder $links) => $links
                        ->where('usuario_id', $user->id)
                        ->whereNull('fim_em'));
            });
        });
    }

    public function canViewResults(User $user): bool
    {
        return $this->applyResults(Resultado::query(), $user)->exists()
            || $this->hasAnyPermission($user, PermissionCode::VIEW_RESULTS);
    }

    /** @param Builder<Relatorio> $query */
    public function applyReports(Builder $query, User $user): Builder
    {
        return $query->where('solicitante_id', $user->id);
    }

    /** @param Builder<Prova> $query */
    public function applyExams(Builder $query, User $user): Builder
    {
        $ids = $this->provaScope->apply(Prova::query(), $user)->pluck('id')
            ->merge($this->provaTurmaScope->applyProvas(Prova::query(), $user)->pluck('id'))
            ->merge(
                Prova::query()
                    ->whereHas('provaTurmas', fn (Builder $links) => $links
                        ->whereIn('turma_id', $this->visibleClassIds($user)))
                    ->pluck('id'),
            )
            ->unique()
            ->values();

        return $query->whereIn('id', $ids);
    }

    /** @return Collection<int, string> */
    public function visibleClassIds(User $user): Collection
    {
        return $this->turmaScope->apply(Turma::query(), $user)->pluck('id');
    }

    public function hasAnyPermission(User $user, PermissionCode $permission): bool
    {
        return $user->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereHas('perfil.permissoes', fn (Builder $permissions) => $permissions
                ->where('codigo', $permission->value))
            ->exists();
    }

    private function hasGlobal(User $user, PermissionCode $permission): bool
    {
        return $this->resolver->allows($user, $permission, AuthorizationContext::global());
    }

    /** @return Collection<int, string> */
    private function schoolIds(User $user): Collection
    {
        return $this->schoolIdsFor($user, PermissionCode::VIEW_APPLICATION_DASHBOARD)
            ->merge($this->schoolIdsFor($user, PermissionCode::MANAGE_SCHOOLS))
            ->unique()
            ->values();
    }

    /** @return Collection<int, string> */
    private function schoolIdsFor(User $user, PermissionCode $permission): Collection
    {
        $directIds = $user->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereNotNull('escola_id')
            ->pluck('escola_id');
        $nucleusIds = $user->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereNotNull('nucleo_id')
            ->pluck('nucleo_id');

        return Escola::query()
            ->where(fn (Builder $query) => $query
                ->whereIn('id', $directIds)
                ->orWhereIn('nucleo_id', $nucleusIds))
            ->get()
            ->filter(fn (Escola $school): bool => $this->resolver->allows(
                $user,
                $permission,
                AuthorizationContext::school($school->nucleo_id, $school->id),
            ))
            ->pluck('id')
            ->values();
    }
}
