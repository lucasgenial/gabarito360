<?php

namespace App\Policies;

use App\Enums\ProvaStatus;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Prova;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\ProvaScope;
use App\Services\Authorization\ProvaTurmaScope;

class ProvaPolicy
{
    public function __construct(
        private ProvaScope $scope,
        private ProvaTurmaScope $classScope,
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

    public function viewClassLinksAny(User $user): bool
    {
        return $this->classScope->canAccessAny($user);
    }

    public function viewClassLinks(User $user, Prova $exam): bool
    {
        return $this->classScope->canViewLinks($user, $exam);
    }

    public function linkClass(User $user, Prova $exam, Turma $class): bool
    {
        return $this->classScope->canLink($user, $exam, $class);
    }

    public function unlinkClass(User $user, Prova $exam, Turma $class): bool
    {
        return $this->classScope->canUnlink($user, $exam, $class);
    }
}
