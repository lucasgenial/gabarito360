<?php

namespace App\Policies;

use App\Enums\StatusEnum;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\User;
use App\Services\Authorization\AlunoScope;

class AlunoPolicy
{
    public function __construct(
        private AlunoScope $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->scope->canAccessAny($user);
    }

    public function manageAny(User $user): bool
    {
        return $this->scope->canManageAny($user);
    }

    public function create(User $user, Escola $school): bool
    {
        $school->loadMissing('nucleo');

        return $school->status === StatusEnum::ACTIVE
            && $school->nucleo->status === StatusEnum::ACTIVE
            && $this->scope->canCreateInSchool($user, $school);
    }

    public function update(User $user, Aluno $student): bool
    {
        return $this->scope->canManage($user, $student);
    }

    public function delete(User $user, Aluno $student): bool
    {
        return $this->scope->canManage($user, $student);
    }
}
