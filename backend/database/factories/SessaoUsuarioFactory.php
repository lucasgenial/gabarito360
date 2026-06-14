<?php

namespace Database\Factories;

use App\Models\SessaoUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessaoUsuario>
 */
class SessaoUsuarioFactory extends Factory
{
    protected $model = SessaoUsuario::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'personal_access_token_id' => null,
            'dispositivo' => fake()->userAgent(),
            'ip' => fake()->ipv4(),
            'manter_conectado' => false,
            'criado_em' => now(),
            'ultimo_acesso_at' => now(),
            'encerrado_at' => null,
        ];
    }

    public function encerrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'encerrado_at' => now(),
        ]);
    }
}
