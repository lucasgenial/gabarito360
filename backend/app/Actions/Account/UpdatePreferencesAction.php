<?php

namespace App\Actions\Account;

use App\Models\PreferenciaUsuario;
use App\Models\User;

class UpdatePreferencesAction
{
    /** @param array<string, mixed> $attributes */
    public function execute(User $user, array $attributes): PreferenciaUsuario
    {
        return PreferenciaUsuario::query()->updateOrCreate(
            ['usuario_id' => $user->id],
            $attributes,
        );
    }
}
