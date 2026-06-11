<?php

namespace Database\Factories;

use App\Enums\StudentImportStatus;
use App\Models\Escola;
use App\Models\ImportacaoAluno;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportacaoAluno>
 */
class ImportacaoAlunoFactory extends Factory
{
    protected $model = ImportacaoAluno::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'escola_id' => Escola::factory(),
            'turma_id' => function (array $attributes): string {
                return Turma::factory()->create(['escola_id' => $attributes['escola_id']])->id;
            },
            'solicitado_por' => User::factory(),
            'confirmado_por' => null,
            'status' => StudentImportStatus::VALIDATED,
            'arquivo_disco' => 'private',
            'arquivo_caminho' => 'student-imports/'.fake()->uuid().'.csv',
            'arquivo_nome' => 'importacao-alunos.csv',
            'arquivo_checksum_sha256' => fake()->sha256(),
            'resumo' => [
                'linhas' => 1,
                'inclusoes' => 1,
                'atualizacoes' => 0,
                'sem_alteracao' => 0,
                'matriculas_novas' => 1,
                'matriculas_existentes' => 0,
                'erros' => 0,
            ],
            'erros' => [],
            'confirmado_at' => null,
            'processado_at' => null,
        ];
    }
}
