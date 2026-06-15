<?php

namespace Tests\Feature\Api\V2\Comparativos;

use App\Enums\UserRole;
use App\Models\Nucleo;
use App\Models\Resultado;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class ComparativoTest extends TestCase
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
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'ck-'.$reading->id])
            ->assertOk();

        return Resultado::query()
            ->where('leitura_cartao_id', $reading->id)
            ->where('status', 'vigente')
            ->firstOrFail();
    }

    public function test_comparativo_nucleo_lista_escolas_com_indicadores(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $response = $this->actingAsToken($ctx['gestor'])
            ->getJson("/api/v2/comparativos/nucleo/{$ctx['nucleo']->id}");

        $response->assertOk()
            ->assertJsonPath('data.tipo', 'escolas_nucleo')
            ->assertJsonPath('data.nucleo_id', $ctx['nucleo']->id)
            ->assertJsonPath('data.escolas.0.escola_id', $ctx['escola']->id)
            ->assertJsonPath('data.escolas.0.total', 1)
            ->assertJsonStructure([
                'data' => ['id', 'escolas' => [['escola_id', 'escola_nome', 'total', 'media_nota', 'aprovacao_percentual']]],
            ]);

        $this->assertDatabaseHas('comparativos', [
            'tipo' => 'escolas_nucleo',
            'nucleo_id' => $ctx['nucleo']->id,
        ]);
    }

    public function test_comparativo_nucleo_sem_acesso_retorna_404(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2]);
        $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);

        $this->actingAsToken($outro)
            ->getJson("/api/v2/comparativos/nucleo/{$ctx['nucleo']->id}")
            ->assertNotFound();
    }
}
