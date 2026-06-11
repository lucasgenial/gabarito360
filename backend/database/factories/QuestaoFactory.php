<?php

namespace Database\Factories;

use App\Enums\QuestaoStatus;
use App\Models\Prova;
use App\Models\Questao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Questao>
 */
class QuestaoFactory extends Factory
{
    protected $model = Questao::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prova_id' => Prova::factory(),
            'numero' => fake()->numberBetween(1, 20),
            'codigo' => fake()->optional()->bothify('Q-##'),
            'peso_padrao' => 1,
            'status' => QuestaoStatus::ACTIVE,
        ];
    }
}
