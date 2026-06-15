<?php

namespace Tests\Feature\Api\V2\Aplicacoes;

use App\Enums\GabaritoOficialStatus;
use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\GabaritoOficial;
use App\Models\Nucleo;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class ApplicationLifecycleTest extends TestCase
{
    use InteractsWithIdentity, InteractsWithOperations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);
    }

    public function test_manager_creates_application_with_student_snapshot(): void
    {
        $ctx = $this->bootstrapOperations(['alunos' => 6]);

        $response = $this->withToken($this->bearerToken($ctx['gestor']))
            ->postJson('/api/v2/aplicacoes', [
                'prova_id' => $ctx['prova']->id,
                'turma_id' => $ctx['turma']->id,
                'titulo' => 'Aplicacao Diagnostica',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'agendada')
            ->assertJsonPath('data.titulo', 'Aplicacao Diagnostica')
            ->assertJsonPath('data.metricas.expected_students', 6);

        $this->assertDatabaseHas('aplicacoes', [
            'prova_id' => $ctx['prova']->id,
            'turma_id' => $ctx['turma']->id,
            'status' => 'agendada',
        ]);
    }

    public function test_create_without_current_answer_key_is_rejected(): void
    {
        $ctx = $this->bootstrapOperations();
        GabaritoOficial::query()->whereKey($ctx['gabarito']->id)
            ->update(['status' => GabaritoOficialStatus::DRAFT->value]);

        $this->withToken($this->bearerToken($ctx['gestor']))
            ->postJson('/api/v2/aplicacoes', [
                'prova_id' => $ctx['prova']->id,
                'turma_id' => $ctx['turma']->id,
                'titulo' => 'Sem gabarito',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gabarito']);
    }

    public function test_start_and_finish_application(): void
    {
        $ctx = $this->bootstrapOperations();
        $application = $this->operations()->createApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $token = $this->bearerToken($ctx['aplicador']);

        $this->withToken($token)->postJson("/api/v2/aplicacoes/{$application->id}/iniciar")
            ->assertOk()
            ->assertJsonPath('data.status', 'em_andamento');

        // Já em andamento → 409
        $this->withToken($token)->postJson("/api/v2/aplicacoes/{$application->id}/iniciar")
            ->assertStatus(409);

        $this->withToken($token)->postJson("/api/v2/aplicacoes/{$application->id}/finalizar")
            ->assertOk()
            ->assertJsonPath('data.status', 'finalizada');
    }

    public function test_finish_blocked_when_pending_review_exists(): void
    {
        $ctx = $this->bootstrapOperations();
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $this->operations()->captureReading($application, $aluno, $ctx['aplicador'], ['ambigua_numero' => 2]);

        $this->withToken($this->bearerToken($ctx['aplicador']))
            ->postJson("/api/v2/aplicacoes/{$application->id}/finalizar")
            ->assertStatus(409);
    }

    public function test_index_is_scoped_and_paginated(): void
    {
        $ctx = $this->bootstrapOperations();
        $this->operations()->createApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);

        // Aplicação em outro núcleo, fora do escopo do gestor.
        $outroNucleo = Nucleo::factory()->create();
        $outraEscola = Escola::factory()->create(['nucleo_id' => $outroNucleo->id]);
        $outroGestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $outroNucleo->id);
        $outroCenario = $this->operations()->publishedExamWithClass($outraEscola, $outroGestor);
        $this->operations()->createApplication($outroCenario, $outroGestor);

        $this->withToken($this->bearerToken($ctx['gestor']))
            ->getJson('/api/v2/aplicacoes')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data', 'meta' => ['request_id', 'page', 'per_page', 'total']]);
    }

    public function test_viewer_cannot_create_application(): void
    {
        $ctx = $this->bootstrapOperations();
        $viewer = $this->userWithRole(UserRole::VIEWER, nucleoId: $ctx['nucleo']->id);

        $this->withToken($this->bearerToken($viewer))
            ->postJson('/api/v2/aplicacoes', [
                'prova_id' => $ctx['prova']->id,
                'turma_id' => $ctx['turma']->id,
                'titulo' => 'X',
            ])
            ->assertForbidden();
    }
}
