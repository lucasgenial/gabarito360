<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateOwnProfileAction
{
    /** @param array<string, mixed> $attributes */
    public function execute(User $user, array $attributes): User
    {
        return DB::transaction(function () use ($user, $attributes): User {
            $user->update($attributes);

            return $user->refresh();
        });
    }
}
