<?php

namespace Tests\Concerns;

use App\Enums\UserRole;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;

trait InteractsWithIdentity
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function userWithRole(
        UserRole $role = UserRole::ADMINISTRATOR,
        ?string $nucleoId = null,
        ?string $escolaId = null,
        array $attributes = [],
    ): User {
        $user = User::factory()->create($attributes);
        $perfil = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $perfil->id,
            'nucleo_id' => $nucleoId,
            'escola_id' => $escolaId,
            'inicio_at' => now()->subMinute(),
            'fim_at' => null,
        ]);

        return $user;
    }

    protected function bearerToken(User $user): string
    {
        return $user->createToken('api', ['api'])->plainTextToken;
    }
}
