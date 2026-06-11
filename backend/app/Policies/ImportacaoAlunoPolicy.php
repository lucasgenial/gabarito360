<?php

namespace App\Policies;

use App\Enums\StatusEnum;
use App\Models\Escola;
use App\Models\ImportacaoAluno;
use App\Models\User;
use App\Services\Authorization\StudentImportScope;

class ImportacaoAlunoPolicy
{
    public function __construct(
        private StudentImportScope $scope,
    ) {}

    public function create(User $user, Escola $school): bool
    {
        $school->loadMissing('nucleo');

        return $school->status === StatusEnum::ACTIVE
            && $school->nucleo->status === StatusEnum::ACTIVE
            && $this->scope->canImport($user, $school);
    }

    public function view(User $user, ImportacaoAluno $import): bool
    {
        return $this->scope->canAccess($user, $import);
    }

    public function confirm(User $user, ImportacaoAluno $import): bool
    {
        $import->loadMissing('escola.nucleo', 'turma');

        return $import->escola->status === StatusEnum::ACTIVE
            && $import->escola->nucleo->status === StatusEnum::ACTIVE
            && $import->turma->status === StatusEnum::ACTIVE
            && $this->scope->canAccess($user, $import);
    }
}
