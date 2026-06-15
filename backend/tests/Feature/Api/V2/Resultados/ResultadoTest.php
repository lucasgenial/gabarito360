<?php

namespace Tests\Feature\Api\V2\Resultados;

use App\Enums\UserRole;
use App\Models\Nucleo;
use App\Models\Resultado;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class ResultadoTest extends TestCase
{
    use InteractsWithIdentity, InteractsWithOperations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);
    }

    /**
     * Cria uma leitura e a confirma, retornando o resultado vigente.
     */
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

    public function test_lista_resultados_por_aplicacao(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $response = $this->actingAsToken($ctx['gestor'])
            ->getJson("/api/v2/resultados?aplicacao_id={$application->id}");

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data' => [['id', 'nota_percentual', 'acertos', 'aluno']]]);
    }

    public function test_lista_resultados_sem_filtro_retorna_422(): void
    {
        $ctx = $this->bootstrapOperations();

        $this->actingAsToken($ctx['gestor'])
            ->getJson('/api/v2/resultados')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['filtro']);
    }

    public function test_detalhe_resultado_inclui_questoes(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $resultado = $this->confirmarLeitura($ctx, $application, $aluno);

        $response = $this->actingAsToken($ctx['gestor'])
            ->getJson("/api/v2/resultados/{$resultado->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $resultado->id)
            ->assertJsonCount(3, 'data.questoes');
    }

    public function test_detalhe_resultado_proibido_para_outro_usuario(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $resultado = $this->confirmarLeitura($ctx, $application, $aluno);

        // Outro gestor sem acesso ao nucleo.
        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);

        $this->actingAsToken($outro)
            ->getJson("/api/v2/resultados/{$resultado->id}")
            ->assertForbidden();
    }

    public function test_resultado_vigente_unico_por_aluno_prova(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();

        // Confirma primeiro resultado.
        $this->confirmarLeitura($ctx, $application, $aluno);

        // Apenas 1 vigente.
        $this->assertDatabaseCount('resultados', 1);
        $this->assertEquals(
            1,
            Resultado::query()->where('aplicacao_aluno_id', $aluno->id)->where('status', 'vigente')->count(),
        );
    }
}
