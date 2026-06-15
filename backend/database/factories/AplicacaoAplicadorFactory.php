<?php

namespace Database\Factories;

use App\Models\Aplicacao;
use App\Models\AplicacaoAplicador;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AplicacaoAplicador>
 */
class AplicacaoAplicadorFactory extends Factory
{
    protected $model = AplicacaoAplicador::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'aplicacao_id' => Aplicacao::factory(),
            'usuario_id' => User::factory(),
            'papel' => 'aplicador',
            'inicio_at' => now(),
            'fim_at' => null,
        ];
    }
}
