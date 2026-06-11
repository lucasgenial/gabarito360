<?php

namespace Database\Factories;

use App\Enums\AplicadorTurmaPapel;
use App\Models\AplicadorTurma;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AplicadorTurma>
 */
class AplicadorTurmaFactory extends Factory
{
    protected $model = AplicadorTurma::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'turma_id' => Turma::factory(),
            'usuario_id' => User::factory(),
            'papel' => AplicadorTurmaPapel::TEACHER,
            'inicio_em' => now()->toDateString(),
            'fim_em' => null,
            'vinculado_por' => null,
        ];
    }
}
