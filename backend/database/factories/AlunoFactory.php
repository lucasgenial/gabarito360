<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Aluno;
use App\Models\Escola;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aluno>
 */
class AlunoFactory extends Factory
{
    protected $model = Aluno::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'escola_id' => Escola::factory(),
            'matricula' => fake()->unique()->bothify('MAT-########'),
            'codigo_interno' => null,
            'nome' => fake()->name(),
            'data_nascimento' => null,
            'documento' => null,
            'status' => StatusEnum::ACTIVE,
            'observacoes' => null,
        ];
    }
}
