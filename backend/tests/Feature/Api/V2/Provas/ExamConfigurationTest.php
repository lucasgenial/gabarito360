<?php

namespace Tests\Feature\Api\V2\Provas;

use App\Enums\UserRole;
use App\Models\Nucleo;
use App\Models\Prova;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class ExamConfigurationTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);
    }

    public function test_manager_creates_exam_with_internal_questions(): void
    {
        $nucleo = Nucleo::factory()->create();
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);

        $response = $this->withToken($this->bearerToken($gestor))
            ->postJson('/api/v2/provas', [
                'titulo' => 'Prova Diagnóstica',
                'disciplina' => 'Matemática Aplicada',
                'num_questoes' => 20,
                'padrao' => ['alternativas' => 5, 'nota_maxima' => 10],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.titulo', 'Prova Diagnóstica')
            ->assertJsonPath('data.disciplina', 'Matemática Aplicada')
            ->assertJsonPath('data.num_questoes', 20)
            ->assertJsonPath('data.status', 'rascunho')
            ->assertJsonPath('data.versao_gabarito', 0)
            ->assertJsonPath('data.turmas', []);

        $prova = Prova::query()->where('titulo', 'Prova Diagnóstica')->firstOrFail();
        $this->assertSame($nucleo->id, $prova->nucleo_id);
        $this->assertNull($prova->modelo_cartao_id);
        $this->assertSame(20, $prova->questoes()->where('status', 'ativa')->count());
    }

    public function test_admin_without_owner_scope_is_rejected(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->postJson('/api/v2/provas', [
                'titulo' => 'Sem dono', 'disciplina' => 'História', 'num_questoes' => 10,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['proprietario']);
    }

    public function test_index_is_scoped_and_paginated(): void
    {
        $nucleo = Nucleo::factory()->create();
        $outro = Nucleo::factory()->create();
        Prova::factory()->count(2)->create(['nucleo_id' => $nucleo->id, 'escola_id' => null, 'modelo_cartao_id' => null]);
        Prova::factory()->create(['nucleo_id' => $outro->id, 'escola_id' => null, 'modelo_cartao_id' => null]);

        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);

        $this->withToken($this->bearerToken($gestor))
            ->getJson('/api/v2/provas')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data', 'meta' => ['request_id', 'page', 'per_page', 'total']]);
    }

    public function test_draft_exam_can_be_updated(): void
    {
        $nucleo = Nucleo::factory()->create();
        $prova = Prova::factory()->create(['nucleo_id' => $nucleo->id, 'escola_id' => null, 'modelo_cartao_id' => null]);
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);

        $this->withToken($this->bearerToken($gestor))
            ->putJson("/api/v2/provas/{$prova->id}", ['titulo' => 'Titulo Novo'])
            ->assertOk()
            ->assertJsonPath('data.titulo', 'Titulo Novo');

        $this->assertDatabaseHas('provas', ['id' => $prova->id, 'titulo' => 'Titulo Novo']);
    }

    public function test_viewer_cannot_create_exam(): void
    {
        $nucleo = Nucleo::factory()->create();
        $viewer = $this->userWithRole(UserRole::VIEWER, nucleoId: $nucleo->id);

        $this->withToken($this->bearerToken($viewer))
            ->postJson('/api/v2/provas', [
                'titulo' => 'X', 'disciplina' => 'Ciências', 'num_questoes' => 5,
            ])
            ->assertForbidden();
    }
}
