<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Escola;
use App\Models\Turma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Turma>
 */
class TurmaFactory extends Factory
{
    protected $model = Turma::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'escola_id' => Escola::factory(),
            'codigo' => fake()->unique()->bothify('TUR-####'),
            'nome' => fake()->randomElement(['Turma A', 'Turma B', 'Turma C']),
            'serie_ano' => fake()->randomElement(['5 ano', '6 ano', '7 ano']),
            'turno' => fake()->randomElement(['matutino', 'vespertino', 'noturno', 'integral']),
            'ano_letivo' => (int) now()->format('Y'),
            'status' => StatusEnum::ACTIVE,
        ];
    }
}
