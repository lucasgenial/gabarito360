<?php

namespace Tests\Feature\Api\V2\Atividades;

use App\Enums\UserRole;
use App\Models\Nucleo;
use App\Models\Resultado;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class AtividadeTest extends TestCase
{
    use InteractsWithIdentity, InteractsWithOperations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);
    }

    private function confirmarLeitura(array $ctx, object $application, object $aluno): Resultado
    {
        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador']);

        $this->actingAsToken($ctx['aplicador'])
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'at-'.$reading->id])
            ->assertOk();

        return Resultado::query()
            ->where('leitura_cartao_id', $reading->id)
            ->where('status', 'vigente')
            ->firstOrFail();
    }

    public function test_resultado_calculado_gera_atividade_no_feed(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $this->actingAsToken($ctx['gestor'])
            ->getJson('/api/v2/atividades-recentes')
            ->assertOk()
            ->assertJsonPath('data.0.tipo', 'resultado.calculado')
            ->assertJsonPath('data.0.escola_id', $ctx['escola']->id)
            ->assertJsonStructure(['data' => [['id', 'tipo', 'descricao', 'created_at']]]);
    }

    public function test_feed_respeita_escopo(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);

        $this->actingAsToken($outro)
            ->getJson('/api/v2/atividades-recentes')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }
}
