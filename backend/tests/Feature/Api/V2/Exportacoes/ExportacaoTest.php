<?php

namespace Tests\Feature\Api\V2\Exportacoes;

use App\Enums\UserRole;
use App\Models\Aplicacao;
use App\Models\Nucleo;
use App\Models\Resultado;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class ExportacaoTest extends TestCase
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
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'ek-'.$reading->id])
            ->assertOk();

        return Resultado::query()
            ->where('leitura_cartao_id', $reading->id)
            ->where('status', 'vigente')
            ->firstOrFail();
    }

    /** @return array{0: array<string, mixed>, 1: Aplicacao} */
    private function cenarioComResultado(): array
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        return [$ctx, $application];
    }

    public function test_exportar_relatorio_prova_csv_cria_exportacao(): void
    {
        [$ctx] = $this->cenarioComResultado();

        $response = $this->actingAsToken($ctx['gestor'])
            ->postJson("/api/v2/relatorios/prova/{$ctx['prova']->id}/exportar", ['formato' => 'csv']);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'concluido')
            ->assertJsonPath('data.formato', 'csv')
            ->assertJsonPath('data.tipo', 'relatorio_prova')
            ->assertJsonStructure(['data' => ['id', 'linhas', 'arquivo' => ['id', 'nome', 'tamanho_bytes']]]);

        $this->assertDatabaseHas('exportacoes', [
            'prova_id' => $ctx['prova']->id,
            'formato' => 'csv',
            'status' => 'concluido',
            'solicitante_id' => $ctx['gestor']->id,
        ]);
    }

    public function test_exportar_relatorio_prova_pdf_gera_arquivo_pdf(): void
    {
        [$ctx] = $this->cenarioComResultado();

        $id = $this->actingAsToken($ctx['gestor'])
            ->postJson("/api/v2/relatorios/prova/{$ctx['prova']->id}/exportar", ['formato' => 'pdf'])
            ->assertCreated()
            ->json('data.id');

        $content = $this->actingAsToken($ctx['gestor'])
            ->get("/api/v2/exportacoes/{$id}/download")
            ->assertOk()
            ->streamedContent();

        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_exportar_relatorio_prova_xlsx_gera_arquivo_zip(): void
    {
        [$ctx] = $this->cenarioComResultado();

        $id = $this->actingAsToken($ctx['gestor'])
            ->postJson("/api/v2/relatorios/prova/{$ctx['prova']->id}/exportar", ['formato' => 'xlsx'])
            ->assertCreated()
            ->json('data.id');

        $content = $this->actingAsToken($ctx['gestor'])
            ->get("/api/v2/exportacoes/{$id}/download")
            ->assertOk()
            ->streamedContent();

        // XLSX é um contêiner ZIP — deve começar com a assinatura "PK\x03\x04".
        $this->assertStringStartsWith("PK\x03\x04", $content);
    }

    public function test_exportar_formato_invalido_retorna_422(): void
    {
        [$ctx] = $this->cenarioComResultado();

        $this->actingAsToken($ctx['gestor'])
            ->postJson("/api/v2/relatorios/prova/{$ctx['prova']->id}/exportar", ['formato' => 'docx'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['formato']);
    }

    public function test_exportar_sem_acesso_a_prova_retorna_403(): void
    {
        [$ctx] = $this->cenarioComResultado();

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);

        $this->actingAsToken($outro)
            ->postJson("/api/v2/relatorios/prova/{$ctx['prova']->id}/exportar", ['formato' => 'csv'])
            ->assertForbidden();
    }

    public function test_download_exportacao_proibido_para_outro_usuario(): void
    {
        [$ctx] = $this->cenarioComResultado();

        $id = $this->actingAsToken($ctx['gestor'])
            ->postJson("/api/v2/relatorios/prova/{$ctx['prova']->id}/exportar", ['formato' => 'csv'])
            ->json('data.id');

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $ctx['nucleo']->id);

        $this->actingAsToken($outro)
            ->get("/api/v2/exportacoes/{$id}/download")
            ->assertForbidden();
    }

    public function test_exportacao_e_idempotente_por_chave(): void
    {
        [$ctx] = $this->cenarioComResultado();

        $primeira = $this->actingAsToken($ctx['gestor'])
            ->postJson("/api/v2/relatorios/prova/{$ctx['prova']->id}/exportar", ['formato' => 'csv'], ['Idempotency-Key' => 'exp-1'])
            ->assertCreated()
            ->json('data.id');

        $segunda = $this->actingAsToken($ctx['gestor'])
            ->postJson("/api/v2/relatorios/prova/{$ctx['prova']->id}/exportar", ['formato' => 'csv'], ['Idempotency-Key' => 'exp-1'])
            ->assertCreated()
            ->json('data.id');

        $this->assertSame($primeira, $segunda);
        $this->assertDatabaseCount('exportacoes', 1);
    }

    public function test_lista_exportacoes_apenas_do_usuario(): void
    {
        [$ctx] = $this->cenarioComResultado();

        $this->actingAsToken($ctx['gestor'])
            ->postJson("/api/v2/relatorios/prova/{$ctx['prova']->id}/exportar", ['formato' => 'csv'])
            ->assertCreated();

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $ctx['nucleo']->id);

        $this->actingAsToken($outro)
            ->getJson('/api/v2/exportacoes')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->actingAsToken($ctx['gestor'])
            ->getJson('/api/v2/exportacoes')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }
}
