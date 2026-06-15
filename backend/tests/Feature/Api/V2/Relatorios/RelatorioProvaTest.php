<?php

namespace Tests\Feature\Api\V2\Relatorios;

use App\Enums\UserRole;
use App\Models\Disciplina;
use App\Models\Nucleo;
use App\Models\Resultado;
use App\Models\TemaHabilidade;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class RelatorioProvaTest extends TestCase
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
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'pk-'.$reading->id])
            ->assertOk();

        return Resultado::query()
            ->where('leitura_cartao_id', $reading->id)
            ->where('status', 'vigente')
            ->firstOrFail();
    }

    public function test_relatorio_prova_retorna_kpis_e_alunos(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $response = $this->actingAsToken($ctx['gestor'])
            ->getJson("/api/v2/relatorios/prova/{$ctx['prova']->id}");

        $response->assertOk()
            ->assertJsonPath('data.prova_id', $ctx['prova']->id)
            ->assertJsonPath('data.kpis.total_alunos', 2)
            ->assertJsonPath('data.kpis.cartoes_corrigidos', 1)
            ->assertJsonPath('data.kpis.aprovados', 1)
            ->assertJsonPath('data.kpis.aprovacao_percentual', 100)
            ->assertJsonCount(1, 'data.resultado_por_aluno')
            ->assertJsonStructure([
                'data' => [
                    'meta_aprovacao',
                    'kpis' => ['total_alunos', 'cartoes_corrigidos', 'pendencias_leitura', 'media_nota', 'aprovacao_percentual'],
                    'acertos_por_tema',
                    'resultado_por_aluno' => [['aluno_id', 'aluno_nome', 'nota_percentual', 'status']],
                ],
            ]);
    }

    public function test_relatorio_prova_agrega_acertos_por_tema(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);

        // Tema principal vinculado à primeira questão da prova.
        $disciplina = Disciplina::query()->firstOrFail();
        $tema = TemaHabilidade::query()->create([
            'disciplina_id' => $disciplina->id,
            'codigo' => 'T1',
            'nome' => 'Operações fundamentais',
            'tipo' => 'tema',
            'ativo' => true,
        ]);
        $questao = $ctx['questoes']->firstWhere('numero', 1);
        $questao->temasHabilidades()->attach($tema->id, ['principal' => true]);

        $aluno = $application->alunos()->firstOrFail();
        $this->confirmarLeitura($ctx, $application, $aluno);

        $response = $this->actingAsToken($ctx['gestor'])
            ->getJson("/api/v2/relatorios/prova/{$ctx['prova']->id}")
            ->assertOk()
            ->assertJsonPath('data.acertos_por_tema.0.tema_nome', 'Operações fundamentais')
            ->assertJsonPath('data.acertos_por_tema.0.total', 1)
            ->assertJsonPath('data.acertos_por_tema.0.acertos', 1)
            ->assertJsonPath('data.acertos_por_tema.0.percentual', 100);
    }

    public function test_relatorio_prova_sem_aplicacoes_visiveis_retorna_404(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2]);
        $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);

        $this->actingAsToken($outro)
            ->getJson("/api/v2/relatorios/prova/{$ctx['prova']->id}")
            ->assertNotFound();
    }
}
