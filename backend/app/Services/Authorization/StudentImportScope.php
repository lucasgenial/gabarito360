<?php

namespace App\Services\Authorization;

use App\Enums\PermissionCode;
use App\Models\Escola;
use App\Models\ImportacaoAluno;
use App\Models\User;

class StudentImportScope
{
    public function __construct(
        private ScopeResolver $scopeResolver,
    ) {}

    public function canImport(User $user, Escola $school): bool
    {
        return $this->scopeResolver->allows(
            $user,
            PermissionCode::IMPORT_STUDENTS,
            AuthorizationContext::school($school->nucleo_id, $school->id),
        );
    }

    public function canAccess(User $user, ImportacaoAluno $import): bool
    {
        $import->loadMissing('escola');

        return $this->canImport($user, $import->escola);
    }
}
