<?php

namespace Database\Factories;

use App\Enums\MatriculaTurmaStatus;
use App\Models\Aluno;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatriculaTurma>
 */
class MatriculaTurmaFactory extends Factory
{
    protected $model = MatriculaTurma::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $turma = Turma::factory()->create();

        return [
            'aluno_id' => Aluno::factory()->create(['escola_id' => $turma->escola_id])->id,
            'turma_id' => $turma->id,
            'ano_letivo' => $turma->ano_letivo,
            'numero_chamada' => null,
            'status' => MatriculaTurmaStatus::ACTIVE,
            'inicio_em' => now()->startOfYear()->toDateString(),
            'fim_em' => null,
        ];
    }
}
