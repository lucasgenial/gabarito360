<?php

namespace Database\Factories;

use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsuarioPerfil>
 */
class UsuarioPerfilFactory extends Factory
{
    protected $model = UsuarioPerfil::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'perfil_id' => Perfil::factory(),
            'nucleo_id' => null,
            'escola_id' => null,
            'concedido_por' => null,
            'inicio_at' => now(),
            'fim_at' => null,
        ];
    }
}
