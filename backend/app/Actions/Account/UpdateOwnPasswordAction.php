<?php

namespace App\Actions\Account;

use App\Models\User;

class UpdateOwnPasswordAction
{
    public function execute(User $user, string $password): void
    {
        $user->forceFill(['password' => $password])->save();
        $user->tokens()->delete();
    }
}
