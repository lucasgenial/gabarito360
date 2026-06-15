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

class DashboardTest extends TestCase
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
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'dk-'.$reading->id])
            ->assertOk();

        return Resultado::query()
            ->where('leitura_cartao_id', $reading->id)
            ->where('status', 'vigente')
            ->firstOrFail();
    }

    public function test_dashboard_aplicacao_retorna_progresso(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $response = $this->actingAsToken($ctx['gestor'])
            ->getJson("/api/v2/dashboards/aplicacao/{$application->id}");

        $response->assertOk()
            ->assertJsonPath('data.aplicacao_id', $application->id)
            ->assertJsonPath('data.progresso.total_alunos', 2)
            ->assertJsonPath('data.progresso.total_confirmados', 1)
            ->assertJsonStructure([
                'data' => [
                    'progresso' => ['total_alunos', 'total_lidos', 'total_confirmados', 'percentual_lido'],
                    'resultados' => ['total', 'media_nota', 'menor_nota', 'maior_nota'],
                    'distribuicao_notas',
                ],
            ]);
    }

    public function test_dashboard_aplicacao_proibido_para_usuario_sem_acesso(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);

        $this->actingAsToken($outro)
            ->getJson("/api/v2/dashboards/aplicacao/{$application->id}")
            ->assertForbidden();
    }

    public function test_dashboard_prova_retorna_indicadores_por_turma(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $response = $this->actingAsToken($ctx['gestor'])
            ->getJson("/api/v2/dashboards/prova/{$ctx['prova']->id}");

        $response->assertOk()
            ->assertJsonPath('data.prova_id', $ctx['prova']->id)
            ->assertJsonStructure([
                'data' => [
                    'geral' => ['total', 'media_nota'],
                    'por_turma' => [['turma_id', 'turma_nome', 'total', 'media_nota']],
                ],
            ]);
    }

    public function test_dashboard_prova_sem_aplicacoes_visiveis_retorna_404(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2]);

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);

        $this->actingAsToken($outro)
            ->getJson("/api/v2/dashboards/prova/{$ctx['prova']->id}")
            ->assertNotFound();
    }
}
