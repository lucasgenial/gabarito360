<?php

namespace Database\Seeders;

use App\Actions\Leituras\ConfirmReadingAction;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\User;
use Database\Seeders\Support\OperationsScenarioBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Dados de demonstração do ciclo operacional (B5): uma aplicação em andamento
 * com leituras em diferentes estados, para popular as telas de correção em
 * `migrate:fresh --seed`. Não roda nos testes (que usam factories).
 */
class OperationsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $builder = new OperationsScenarioBuilder;

        $nucleo = Nucleo::factory()->create(['nome' => 'Nucleo Demonstracao']);
        $escola = Escola::factory()->create(['nucleo_id' => $nucleo->id, 'nome' => 'Escola Demonstracao']);
        $author = User::factory()->create(['nome' => 'Gestor Demonstracao']);
        $aplicador = User::factory()->create(['nome' => 'Aplicador Demonstracao']);

        $scenario = $builder->publishedExamWithClass($escola, $author, ['questoes' => 5, 'alunos' => 6]);
        $application = $builder->startedApplication($scenario, $author, [$aplicador]);

        $alunos = $application->alunos()->orderBy('id')->get();

        // Leitura limpa confirmada — gera resultado vigente.
        $confirmavel = $builder->captureReading($application, $alunos[0], $aplicador);
        app(ConfirmReadingAction::class)->execute($confirmavel, (string) Str::uuid(), $aplicador);

        // Leitura com pendência de revisão (questão 2 ambígua).
        $builder->captureReading($application, $alunos[1], $aplicador, ['ambigua_numero' => 2]);

        // Leitura limpa aguardando confirmação.
        $builder->captureReading($application, $alunos[2], $aplicador);
    }
}
