<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Nucleo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Nucleo>
 */
class NucleoFactory extends Factory
{
    protected $model = Nucleo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('NUC-####'),
            'nome' => fake()->company(),
            'municipio' => fake()->city(),
            'estado' => fake()->stateAbbr(),
            'email' => fake()->unique()->companyEmail(),
            'telefone' => fake()->numerify('(##) ####-####'),
            'status' => StatusEnum::ACTIVE,
        ];
    }
}
