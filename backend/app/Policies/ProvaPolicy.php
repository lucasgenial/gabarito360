<?php

namespace App\Policies;

use App\Enums\ProvaStatus;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Prova;
use App\Models\User;
use App\Services\Authorization\ProvaScope;

class ProvaPolicy
{
    public function __construct(
        private ProvaScope $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->scope->canAccessAny($user);
    }

    public function view(User $user, Prova $exam): bool
    {
        return $this->scope->canView($user, $exam);
    }

    public function createForNucleo(User $user, Nucleo $educationCenter): bool
    {
        return $this->scope->canCreateForNucleo($user, $educationCenter);
    }

    public function createForSchool(User $user, Escola $school): bool
    {
        return $this->scope->canCreateForSchool($user, $school);
    }

    public function update(User $user, Prova $exam): bool
    {
        return $exam->status === ProvaStatus::DRAFT
            && $this->scope->canManage($user, $exam);
    }

    public function publish(User $user, Prova $exam): bool
    {
        return $exam->status === ProvaStatus::DRAFT
            && $this->scope->canManage($user, $exam);
    }
}
