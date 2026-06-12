<?php

namespace App\Policies;

use App\Enums\PermissionCode;
use App\Models\Relatorio;
use App\Models\User;
use App\Services\Authorization\AuthorizationContext;

class RelatorioPolicy extends BasePolicy
{
    public function view(User $user, Relatorio $relatorio): bool
    {
        $nucleoId = $relatorio->escopo['nucleo_id'] ?? null;
        $escolaId = $relatorio->escopo['escola_id'] ?? null;

        return $this->authorize($user, PermissionCode::VIEW_REPORTS, match (true) {
            is_string($escolaId) && is_string($nucleoId) => AuthorizationContext::school($nucleoId, $escolaId),
            is_string($nucleoId) => AuthorizationContext::educationCenter($nucleoId),
            default => AuthorizationContext::global(),
        });
    }
}
