<?php

namespace Tests\Feature\Api\V2\Dashboards;

use App\Enums\UserRole;
use App\Models\Nucleo;
use App\Models\Resultado;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class SnapshotTest extends TestCase
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
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'sk-'.$reading->id])
            ->assertOk();

        return Resultado::query()
            ->where('leitura_cartao_id', $reading->id)
            ->where('status', 'vigente')
            ->firstOrFail();
    }

    public function test_snapshot_da_prova_persiste_indicadores(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $response = $this->actingAsToken($ctx['gestor'])
            ->getJson("/api/v2/dashboards/prova/{$ctx['prova']->id}/snapshot");

        $response->assertCreated()
            ->assertJsonPath('data.escopo_tipo', 'prova')
            ->assertJsonPath('data.prova_id', $ctx['prova']->id)
            ->assertJsonPath('data.total_resultados', 1)
            ->assertJsonStructure(['data' => ['id', 'indicadores' => ['kpis', 'acertos_por_tema'], 'gerado_at']]);

        $this->assertDatabaseHas('snapshots_indicadores', [
            'escopo_tipo' => 'prova',
            'prova_id' => $ctx['prova']->id,
            'total_resultados' => 1,
        ]);
    }

    public function test_snapshot_sem_aplicacoes_visiveis_retorna_404(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2]);
        $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);

        $this->actingAsToken($outro)
            ->getJson("/api/v2/dashboards/prova/{$ctx['prova']->id}/snapshot")
            ->assertNotFound();
    }
}
