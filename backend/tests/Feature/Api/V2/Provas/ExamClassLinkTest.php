<?php

namespace Tests\Feature\Api\V2\Provas;

use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class ExamClassLinkTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);
    }

    private function criarProvaPublicada(User $gestor, Nucleo $nucleo): string
    {
        $token = $this->bearerToken($gestor);
        $prova = $this->withToken($token)->postJson('/api/v2/provas', [
            'titulo' => 'Prova', 'disciplina' => 'Matemática', 'num_questoes' => 2,
            'padrao' => ['alternativas' => 5, 'nota_maxima' => 10],
        ])->assertCreated()->json('data.id');

        $this->withToken($token)->putJson("/api/v2/provas/{$prova}/gabarito", [
            'respostas' => [['questao' => 1, 'correta' => 'A'], ['questao' => 2, 'correta' => 'B']],
        ])->assertOk();
        $this->withToken($token)->postJson("/api/v2/provas/{$prova}/publicar")->assertOk();

        return $prova;
    }

    public function test_link_list_and_unlink_class(): void
    {
        $nucleo = Nucleo::factory()->create();
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);
        $token = $this->bearerToken($gestor);
        $escola = Escola::factory()->create(['nucleo_id' => $nucleo->id]);
        $turma = Turma::factory()->for($escola)->create();

        $prova = $this->criarProvaPublicada($gestor, $nucleo);

        $this->withToken($token)->postJson("/api/v2/provas/{$prova}/turmas", ['turma_id' => $turma->id])
            ->assertCreated()
            ->assertJsonPath('data.turma_id', $turma->id);

        $this->assertDatabaseHas('prova_turmas', ['prova_id' => $prova, 'turma_id' => $turma->id]);

        $this->withToken($token)->getJson("/api/v2/provas/{$prova}/turmas")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($token)->deleteJson("/api/v2/provas/{$prova}/turmas/{$turma->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('prova_turmas', ['prova_id' => $prova, 'turma_id' => $turma->id]);
    }

    public function test_cannot_link_class_to_draft_exam(): void
    {
        $nucleo = Nucleo::factory()->create();
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);
        $token = $this->bearerToken($gestor);
        $escola = Escola::factory()->create(['nucleo_id' => $nucleo->id]);
        $turma = Turma::factory()->for($escola)->create();

        $prova = $this->withToken($token)->postJson('/api/v2/provas', [
            'titulo' => 'Rascunho', 'disciplina' => 'Matemática', 'num_questoes' => 2,
            'padrao' => ['alternativas' => 5, 'nota_maxima' => 10],
        ])->assertCreated()->json('data.id');

        $this->withToken($token)->postJson("/api/v2/provas/{$prova}/turmas", ['turma_id' => $turma->id])
            ->assertForbidden();
    }
}
