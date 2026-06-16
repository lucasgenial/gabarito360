<?php

namespace App\Support\Web;

use App\Enums\UserRole;
use App\Models\Nucleo;
use App\Models\Prova;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\PortalScope;
use App\Services\Authorization\SchoolScope;
use App\Services\Authorization\TurmaScope;
use App\Support\Authorization\PrimaryRoleResolver;
use Illuminate\Support\Facades\Route;

class PortalUiContext
{
    public function __construct(
        private PortalScope $portalScope,
        private SchoolScope $schoolScope,
        private TurmaScope $turmaScope,
    ) {}

    /**
     * @return array{
     *     role: ?UserRole,
     *     roleLabel: string,
     *     scopeLabel: string,
     *     dashboardVariant: string,
     *     navigation: list<array{label: string, route: string, active: list<string>}>
     * }
     */
    public function forUser(User $user): array
    {
        $link = PrimaryRoleResolver::resolve($user);
        $role = $link?->perfil ? UserRole::tryFrom($link->perfil->codigo) : null;

        return [
            'role' => $role,
            'roleLabel' => $role?->label() ?? 'Perfil não definido',
            'scopeLabel' => $this->scopeLabel($user, $role),
            'dashboardVariant' => $this->dashboardVariant($role),
            'navigation' => $this->navigation($user, $role),
        ];
    }

    private function dashboardVariant(?UserRole $role): string
    {
        return match ($role) {
            UserRole::ADMINISTRATOR => 'admin',
            UserRole::EDUCATION_MANAGER => 'nucleo',
            UserRole::SCHOOL_MANAGER => 'school',
            UserRole::TEACHER => 'teacher',
            UserRole::APPLICATOR => 'applicator',
            UserRole::VIEWER => 'viewer',
            UserRole::TECHNICAL_SUPPORT => 'support',
            default => 'default',
        };
    }

    private function scopeLabel(User $user, ?UserRole $role): string
    {
        if ($role === UserRole::ADMINISTRATOR) {
            return 'Rede';
        }

        $nucleoId = $this->portalScope->accessibleNucleoIds($user)->first();

        if (is_string($nucleoId)) {
            return (string) (Nucleo::query()->whereKey($nucleoId)->value('nome') ?? 'Meu núcleo');
        }

        if ($role === UserRole::APPLICATOR) {
            $classes = $this->turmaScope->apply(Turma::query(), $user)->count();

            return $classes > 0 ? "{$classes} turma(s) vinculada(s)" : 'Operação';
        }

        return 'Meu escopo';
    }

    /**
     * @return list<array{label: string, route: string, active: list<string>}>
     */
    private function navigation(User $user, ?UserRole $role): array
    {
        $items = [
            ['label' => 'Painel', 'route' => 'portal.dashboard', 'active' => ['portal.dashboard']],
        ];

        if ($this->canShowExams($user, $role)) {
            $items[] = ['label' => 'Provas', 'route' => 'portal.exams.index', 'active' => ['portal.exams.*']];
        }

        if ($this->turmaScope->canAccessAny($user)) {
            $items[] = ['label' => 'Turmas', 'route' => 'portal.classes.index', 'active' => ['portal.classes.*', 'portal.students.*']];
        }

        if ($this->schoolScope->canAccessAny($user)) {
            $items[] = ['label' => 'Escolas', 'route' => 'portal.schools.index', 'active' => ['portal.schools.*']];
        }

        if ($this->portalScope->canViewApplications($user)) {
            $items[] = ['label' => 'Correções', 'route' => 'portal.operations.index', 'active' => ['portal.operations.*']];
        }

        return array_values(array_filter(
            $items,
            fn (array $item): bool => Route::has($item['route']),
        ));
    }

    private function canShowExams(User $user, ?UserRole $role): bool
    {
        if ($role === UserRole::APPLICATOR) {
            return false;
        }

        return $user->can('viewAny', Prova::class)
            || $this->portalScope
            ->applyExams(Prova::query(), $user)
            ->exists();
    }
}
