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
        return [
            'turma_id' => Turma::factory(),
            'aluno_id' => function (array $attributes): string {
                $class = Turma::query()->findOrFail($attributes['turma_id']);

                return Aluno::factory()->create(['escola_id' => $class->escola_id])->id;
            },
            'ano_letivo' => fn (array $attributes): int => Turma::query()
                ->findOrFail($attributes['turma_id'])
                ->ano_letivo,
            'numero_chamada' => null,
            'status' => MatriculaTurmaStatus::ACTIVE,
            'inicio_em' => now()->startOfYear()->toDateString(),
            'fim_em' => null,
        ];
    }
}
