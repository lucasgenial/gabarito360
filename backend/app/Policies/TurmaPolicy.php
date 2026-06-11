<?php

namespace App\Policies;

use App\Enums\StatusEnum;
use App\Models\Escola;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\TurmaScope;

class TurmaPolicy
{
    public function __construct(
        private TurmaScope $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->scope->canAccessAny($user);
    }

    public function view(User $user, Turma $turma): bool
    {
        return $this->scope->canView($user, $turma);
    }

    public function create(User $user, Escola $escola): bool
    {
        $escola->loadMissing('nucleo');

        return $escola->status === StatusEnum::ACTIVE
            && $escola->nucleo->status === StatusEnum::ACTIVE
            && $this->scope->canCreateInSchool($user, $escola);
    }

    public function update(User $user, Turma $turma): bool
    {
        return $this->scope->canManage($user, $turma);
    }

    public function delete(User $user, Turma $turma): bool
    {
        return $this->scope->canManage($user, $turma);
    }

    public function createEnrollment(User $user, Turma $turma): bool
    {
        $turma->loadMissing('escola.nucleo');

        return $turma->status === StatusEnum::ACTIVE
            && $turma->escola->status === StatusEnum::ACTIVE
            && $turma->escola->nucleo->status === StatusEnum::ACTIVE
            && $this->scope->canManage($user, $turma);
    }

    public function closeEnrollment(User $user, Turma $turma): bool
    {
        return $this->scope->canManage($user, $turma);
    }
}
