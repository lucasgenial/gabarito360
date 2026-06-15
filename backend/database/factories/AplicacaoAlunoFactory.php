<?php

namespace Database\Factories;

use App\Models\Aluno;
use App\Models\Aplicacao;
use App\Models\AplicacaoAluno;
use App\Models\MatriculaTurma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AplicacaoAluno>
 */
class AplicacaoAlunoFactory extends Factory
{
    protected $model = AplicacaoAluno::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'aplicacao_id' => Aplicacao::factory(),
            'aluno_id' => Aluno::factory(),
            'matricula_turma_id' => MatriculaTurma::factory(),
            'status' => 'previsto',
        ];
    }
}
