<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\StatusEnum;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;

abstract class AuthController extends BaseApiController
{
    protected function loadAuthorizedContext(User $user): User
    {
        return $user->load([
            'perfilVinculos' => fn ($query) => $query
                ->where('inicio_at', '<=', now())
                ->whereNull('fim_at')
                ->whereHas('perfil', fn ($profileQuery) => $profileQuery
                    ->where('status', StatusEnum::ACTIVE->value))
                ->with('perfil.permissoes')
                ->orderBy('inicio_at'),
        ]);
    }
}
