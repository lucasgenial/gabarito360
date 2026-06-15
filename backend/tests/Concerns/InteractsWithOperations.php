<?php

namespace Tests\Concerns;

use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\Nucleo;
use Database\Seeders\Support\OperationsScenarioBuilder;

/**
 * Atalhos para montar o grafo operacional (B5) nos testes de feature.
 * Requer que a classe de teste também use {@see InteractsWithIdentity}.
 */
trait InteractsWithOperations
{
    protected function operations(): OperationsScenarioBuilder
    {
        return new OperationsScenarioBuilder;
    }

    /**
     * Núcleo + escola + gestor (cria aplicações) + aplicador (operacional,
     * vinculável) + prova publicada com turma/gabarito/matrículas.
     *
     * @param  array{questoes?: int, alunos?: int}  $options
     * @return array<string, mixed>
     */
    protected function bootstrapOperations(array $options = []): array
    {
        $nucleo = Nucleo::factory()->create();
        $escola = Escola::factory()->create(['nucleo_id' => $nucleo->id]);
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);
        $aplicador = $this->userWithRole(UserRole::APPLICATOR, nucleoId: $nucleo->id);

        $scenario = $this->operations()->publishedExamWithClass($escola, $gestor, $options);

        return [
            'nucleo' => $nucleo,
            'escola' => $escola,
            'gestor' => $gestor,
            'aplicador' => $aplicador,
        ] + $scenario;
    }
}
