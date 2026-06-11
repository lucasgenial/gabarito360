<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Authorization\UserAdministrationScope;

class UserPolicy
{
    public function __construct(
        private UserAdministrationScope $scope,
    ) {}

    public function viewAny(User $actor): bool
    {
        return $this->scope->canAccessAny($actor);
    }

    public function view(User $actor, User $target): bool
    {
        return $this->scope->canView($actor, $target);
    }

    public function create(User $actor): bool
    {
        return $this->scope->canAccessAny($actor);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->scope->canManage($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->scope->canManage($actor, $target);
    }

    public function assignProfile(User $actor, User $target): bool
    {
        return $this->scope->canView($actor, $target);
    }

    public function revokeProfile(User $actor, User $target, UsuarioPerfil $link): bool
    {
        return $link->usuario_id === $target->id
            && $link->fim_at === null
            && $this->scope->canManageLink($actor, $link);
    }
}
