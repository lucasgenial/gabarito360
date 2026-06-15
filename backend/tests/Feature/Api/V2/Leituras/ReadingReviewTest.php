<?php

namespace Tests\Feature\Api\V2\Leituras;

use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class ReadingReviewTest extends TestCase
{
    use InteractsWithIdentity, InteractsWithOperations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);
    }

    public function test_pending_review_is_listed_for_manager(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 5]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador'], ['ambigua_numero' => 2]);

        $this->withToken($this->bearerToken($ctx['gestor']))
            ->getJson("/api/v2/correcao/{$ctx['prova']->id}/pendencias")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.leitura_id', $reading->id);
    }

    public function test_review_resolves_pending_by_number(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 5]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador'], ['ambigua_numero' => 2]);

        $this->withToken($this->bearerToken($ctx['aplicador']))
            ->postJson("/api/v2/leituras/{$reading->id}/revisao", [
                'questao' => 2,
                'decisao' => 'C',
                'motivo' => 'Marcacao corrigida na revisao.',
            ])
            ->assertOk()
            ->assertJsonPath('data.requer_revisao', false);

        $this->assertDatabaseHas('respostas_detectadas', [
            'leitura_cartao_id' => $reading->id,
            'alternativa_final' => 'C',
            'alterada_manualmente' => true,
        ]);
    }

    public function test_review_with_unknown_question_is_rejected(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador'], ['ambigua_numero' => 1]);

        $this->withToken($this->bearerToken($ctx['aplicador']))
            ->postJson("/api/v2/leituras/{$reading->id}/revisao", [
                'questao' => 99,
                'decisao' => 'A',
                'motivo' => 'Tentativa invalida de revisao.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['questao']);
    }

    public function test_confirm_generates_current_result(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador']);

        $response = $this->withToken($this->bearerToken($ctx['aplicador']))
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'confirm-ok']);

        $response->assertOk()->assertJsonPath('data.status', 'confirmada');
        $this->assertNotNull($response->json('meta.resultado_id'));
        $this->assertDatabaseHas('resultados', [
            'leitura_cartao_id' => $reading->id,
            'status' => 'vigente',
        ]);
    }

    public function test_confirm_requires_review_to_be_resolved(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador'], ['ambigua_numero' => 1]);

        $this->withToken($this->bearerToken($ctx['aplicador']))
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'confirm-pend'])
            ->assertStatus(409);
    }

    public function test_review_after_confirmation_conflicts(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador']);

        $this->withToken($this->bearerToken($ctx['aplicador']))
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'confirm-then-review'])
            ->assertOk();

        $this->withToken($this->bearerToken($ctx['aplicador']))
            ->postJson("/api/v2/leituras/{$reading->id}/revisao", [
                'questao' => 1,
                'decisao' => 'A',
                'motivo' => 'Tentativa apos confirmacao.',
            ])
            ->assertStatus(409);
    }

    public function test_progress_snapshot_reflects_counters(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 4]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $alunos = $application->alunos()->orderBy('id')->get();
        $this->operations()->captureReading($application, $alunos[0], $ctx['aplicador']);
        $this->operations()->captureReading($application, $alunos[1], $ctx['aplicador'], ['ambigua_numero' => 2]);

        $this->withToken($this->bearerToken($ctx['gestor']))
            ->getJson("/api/v2/correcao/{$ctx['prova']->id}")
            ->assertOk()
            ->assertJsonPath('data.total', 4)
            ->assertJsonPath('data.lidos', 2)
            ->assertJsonPath('data.pendentes', 1)
            ->assertJsonPath('data.ambiguos', 1);
    }
}
