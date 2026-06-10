<?php

namespace App\Services\Authorization;

use App\Enums\AccessScope;
use App\Enums\PermissionCode;
use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Illuminate\Database\Eloquent\Collection;

class ScopeResolver
{
    public function allows(
        User $user,
        PermissionCode $permission,
        ?AuthorizationContext $context = null,
    ): bool {
        if ($context === null || $user->trashed() || $user->status !== UserStatus::ACTIVE) {
            return false;
        }

        return $this->activeLinks($user)
            ->contains(fn (UsuarioPerfil $link): bool => $this->linkAllows($link, $permission, $context));
    }

    /**
     * The caller must create the context from persisted resources and trusted
     * relationships. Client-provided filters never establish authorization.
     *
     * @return Collection<int, UsuarioPerfil>
     */
    protected function activeLinks(User $user): Collection
    {
        return $user->perfilVinculos()
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at')
            ->whereHas('perfil', fn ($query) => $query->where('status', StatusEnum::ACTIVE->value))
            ->with('perfil.permissoes')
            ->get();
    }

    private function linkAllows(
        UsuarioPerfil $link,
        PermissionCode $permission,
        AuthorizationContext $context,
    ): bool {
        $profile = $link->perfil;
        $role = UserRole::tryFrom($profile->codigo);

        if (
            $role === null
            || $profile->escopo_permitido !== $role->allowedScope()
            || ! $profile->permissoes->contains('codigo', $permission->value)
        ) {
            return false;
        }

        if ($role === UserRole::VIEWER && $permission->isMutation()) {
            return false;
        }

        if ($role === UserRole::TECHNICAL_SUPPORT && ! $context->isAuditedDiagnostic()) {
            return false;
        }

        if ($role === UserRole::TECHNICAL_SUPPORT && $permission->isMutation()) {
            return false;
        }

        if (
            $permission->requiresOperationalRole()
            && ! in_array($role, [UserRole::TEACHER, UserRole::APPLICATOR], strict: true)
        ) {
            return false;
        }

        if ($permission->requiresAuditedDiagnosticContext() && ! $context->isAuditedDiagnostic()) {
            return false;
        }

        return match ($role->allowedScope()) {
            AccessScope::GLOBAL => $link->nucleo_id === null && $link->escola_id === null,
            AccessScope::EDUCATION_CENTER => $link->nucleo_id !== null
                && $link->escola_id === null
                && $link->nucleo_id === $context->nucleoId,
            AccessScope::SCHOOL => $link->nucleo_id === null
                && $link->escola_id !== null
                && $link->escola_id === $context->escolaId,
            AccessScope::OPERATIONAL => $link->nucleo_id === null
                && $link->escola_id === null
                && $context->operationallyLinked,
        };
    }
}
