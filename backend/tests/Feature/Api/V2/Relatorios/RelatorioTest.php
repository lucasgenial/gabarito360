<?php

namespace Tests\Feature\Api\V2\Relatorios;

use App\Enums\UserRole;
use App\Models\Nucleo;
use App\Models\Resultado;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class RelatorioTest extends TestCase
{
    use InteractsWithIdentity, InteractsWithOperations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);
        Storage::fake((string) config('filesystems.private'));
    }

    private function confirmarLeitura(array $ctx, object $application, object $aluno): Resultado
    {
        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador']);

        $this->actingAsToken($ctx['aplicador'])
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'rk-'.$reading->id])
            ->assertOk();

        return Resultado::query()
            ->where('leitura_cartao_id', $reading->id)
            ->where('status', 'vigente')
            ->firstOrFail();
    }

    public function test_solicitar_relatorio_csv_cria_relatorio_concluido(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $response = $this->actingAsToken($ctx['gestor'])
            ->postJson('/api/v2/relatorios', [
                'tipo' => 'resultados_aplicacao',
                'aplicacao_id' => $application->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'concluido')
            ->assertJsonPath('data.formato', 'csv')
            ->assertJsonStructure(['data' => ['id', 'arquivo' => ['id', 'nome', 'tamanho_bytes']]]);

        $this->assertDatabaseHas('relatorios', [
            'tipo' => 'resultados_aplicacao',
            'status' => 'concluido',
            'solicitante_id' => $ctx['gestor']->id,
        ]);
    }

    public function test_lista_relatorios_retorna_apenas_os_do_usuario(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        // Gestor solicita relatório.
        $this->actingAsToken($ctx['gestor'])
            ->postJson('/api/v2/relatorios', [
                'tipo' => 'resultados_aplicacao',
                'aplicacao_id' => $application->id,
            ])
            ->assertCreated();

        // Outro usuário não vê.
        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $ctx['nucleo']->id);

        $this->actingAsToken($outro)
            ->getJson('/api/v2/relatorios')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        // Gestor vê o próprio.
        $this->actingAsToken($ctx['gestor'])
            ->getJson('/api/v2/relatorios')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_download_relatorio_retorna_csv(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $relatorioId = $this->actingAsToken($ctx['gestor'])
            ->postJson('/api/v2/relatorios', [
                'tipo' => 'resultados_aplicacao',
                'aplicacao_id' => $application->id,
            ])
            ->json('data.id');

        $response = $this->actingAsToken($ctx['gestor'])
            ->get("/api/v2/relatorios/{$relatorioId}/download")
            ->assertOk();

        // O Symfony anexa "; charset=utf-8" para tipos text/*; basta o tipo base.
        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('Content-Type'));
    }

    public function test_download_proibido_para_outro_usuario(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $relatorioId = $this->actingAsToken($ctx['gestor'])
            ->postJson('/api/v2/relatorios', [
                'tipo' => 'resultados_aplicacao',
                'aplicacao_id' => $application->id,
            ])
            ->json('data.id');

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $ctx['nucleo']->id);

        $this->actingAsToken($outro)
            ->get("/api/v2/relatorios/{$relatorioId}/download")
            ->assertForbidden();
    }

    public function test_solicitar_relatorio_sem_acesso_a_aplicacao_retorna_403(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);

        $this->actingAsToken($outro)
            ->postJson('/api/v2/relatorios', [
                'tipo' => 'resultados_aplicacao',
                'aplicacao_id' => $application->id,
            ])
            ->assertForbidden();
    }
}
