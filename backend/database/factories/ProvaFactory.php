<?php

namespace Database\Factories;

use App\Enums\ModeloCartaoStatus;
use App\Enums\ProvaStatus;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\Prova;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prova>
 */
class ProvaFactory extends Factory
{
    protected $model = Prova::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $alternatives = ['A', 'B', 'C', 'D', 'E'];

        return [
            'nucleo_id' => Nucleo::factory(),
            'escola_id' => null,
            'modelo_cartao_id' => ModeloCartao::factory()->state([
                'nucleo_id' => null,
                'status' => ModeloCartaoStatus::APPROVED,
                'homologado_por' => User::factory(),
                'homologado_at' => now(),
            ]),
            'codigo' => fake()->unique()->bothify('PROVA-####'),
            'titulo' => fake()->sentence(4),
            'descricao' => fake()->optional()->sentence(),
            'tipo' => fake()->randomElement(['simulado', 'diagnostico', 'avaliacao']),
            'nivel' => fake()->optional()->randomElement(['5 ano', '6 ano', '7 ano']),
            'ano_referencia' => (int) now()->format('Y'),
            'quantidade_questoes' => 20,
            'quantidade_alternativas' => count($alternatives),
            'alternativas' => $alternatives,
            'status' => ProvaStatus::DRAFT,
            'criado_por' => User::factory(),
            'publicada_at' => null,
            'finalizada_at' => null,
        ];
    }
}
