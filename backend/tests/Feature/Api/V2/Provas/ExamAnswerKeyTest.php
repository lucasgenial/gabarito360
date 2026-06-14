<?php

namespace Tests\Feature\Api\V2\Provas;

use App\Enums\UserRole;
use App\Models\Nucleo;
use App\Models\User;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class ExamAnswerKeyTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);
    }

    private function criarProva(User $gestor, int $questoes = 3, int $alternativas = 5): string
    {
        return $this->withToken($this->bearerToken($gestor))
            ->postJson('/api/v2/provas', [
                'titulo' => 'Prova', 'disciplina' => 'Matemática', 'num_questoes' => $questoes,
                'padrao' => ['alternativas' => $alternativas, 'nota_maxima' => 10],
            ])
            ->assertCreated()
            ->json('data.id');
    }

    public function test_answer_key_is_empty_then_upserted_by_number(): void
    {
        $nucleo = Nucleo::factory()->create();
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);
        $token = $this->bearerToken($gestor);
        $prova = $this->criarProva($gestor, 3);

        $this->withToken($token)->getJson("/api/v2/provas/{$prova}/gabarito")
            ->assertOk()
            ->assertJsonPath('data.respostas', []);

        $this->withToken($token)->putJson("/api/v2/provas/{$prova}/gabarito", [
            'respostas' => [
                ['questao' => 1, 'correta' => 'A'],
                ['questao' => 2, 'correta' => 'B'],
                ['questao' => 3, 'correta' => null],
            ],
        ])->assertOk();

        $response = $this->withToken($token)->getJson("/api/v2/provas/{$prova}/gabarito");
        $response->assertOk()
            ->assertJsonPath('data.respostas.0.questao', 1)
            ->assertJsonPath('data.respostas.0.correta', 'A')
            ->assertJsonPath('data.respostas.2.correta', null);
    }

    public function test_answer_outside_alternatives_is_rejected(): void
    {
        $nucleo = Nucleo::factory()->create();
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);
        $prova = $this->criarProva($gestor, 3, 3); // alternativas A,B,C

        $this->withToken($this->bearerToken($gestor))
            ->putJson("/api/v2/provas/{$prova}/gabarito", [
                'respostas' => [['questao' => 1, 'correta' => 'D']],
            ])
            ->assertUnprocessable();
    }

    public function test_publish_requires_complete_answer_key(): void
    {
        $nucleo = Nucleo::factory()->create();
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);
        $token = $this->bearerToken($gestor);
        $prova = $this->criarProva($gestor, 3);

        // Incompleto → 409
        $this->withToken($token)->putJson("/api/v2/provas/{$prova}/gabarito", [
            'respostas' => [['questao' => 1, 'correta' => 'A']],
        ])->assertOk();
        $this->withToken($token)->postJson("/api/v2/provas/{$prova}/publicar")->assertStatus(409);

        // Completo → 200
        $this->withToken($token)->putJson("/api/v2/provas/{$prova}/gabarito", [
            'respostas' => [
                ['questao' => 1, 'correta' => 'A'],
                ['questao' => 2, 'correta' => 'B'],
                ['questao' => 3, 'correta' => 'C'],
            ],
        ])->assertOk();

        $this->withToken($token)->postJson("/api/v2/provas/{$prova}/publicar")
            ->assertOk()
            ->assertJsonPath('data.status', 'publicada')
            ->assertJsonPath('data.versao_gabarito', 1);

        // Após publicada, o gabarito não é mais editável (prova não está em rascunho).
        $this->withToken($token)->putJson("/api/v2/provas/{$prova}/gabarito", [
            'respostas' => [['questao' => 1, 'correta' => 'B']],
        ])->assertForbidden();
    }

    public function test_publish_without_answer_key_conflicts(): void
    {
        $nucleo = Nucleo::factory()->create();
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);
        $prova = $this->criarProva($gestor, 3);

        $this->withToken($this->bearerToken($gestor))
            ->postJson("/api/v2/provas/{$prova}/publicar")
            ->assertStatus(409);
    }

    public function test_answer_key_pdf_is_generated(): void
    {
        $nucleo = Nucleo::factory()->create();
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);
        $token = $this->bearerToken($gestor);
        $prova = $this->criarProva($gestor, 2);

        $this->withToken($token)->putJson("/api/v2/provas/{$prova}/gabarito", [
            'respostas' => [['questao' => 1, 'correta' => 'A'], ['questao' => 2, 'correta' => 'B']],
        ])->assertOk();

        $response = $this->withToken($token)->get("/api/v2/provas/{$prova}/gabarito.pdf");
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
