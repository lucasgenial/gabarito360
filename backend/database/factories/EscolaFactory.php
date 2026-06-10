<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Escola;
use App\Models\Nucleo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Escola>
 */
class EscolaFactory extends Factory
{
    protected $model = Escola::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'nucleo_id' => Nucleo::factory(),
            'codigo' => fake()->unique()->bothify('ESC-####'),
            'nome' => fake()->company(),
            'municipio' => fake()->city(),
            'estado' => fake()->stateAbbr(),
            'endereco' => null,
            'email' => fake()->unique()->companyEmail(),
            'telefone' => fake()->numerify('(##) ####-####'),
            'status' => StatusEnum::ACTIVE,
        ];
    }
}
