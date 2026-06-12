<?php

namespace Tests\Feature\Api\Gabaritos;

use App\Actions\Gabaritos\UpsertGabaritoRespostaAction;
use App\Enums\GabaritoOficialStatus;
use App\Enums\ModeloCartaoStatus;
use App\Enums\ProvaStatus;
use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\GabaritoOficial;
use App\Models\GabaritoResposta;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Prova;
use App\Models\Questao;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GabaritoManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_creates_sequential_draft_versions_with_audit(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);
        $exam = Prova::factory()->create();

        $first = $this->postJson('/api/v1/provas/'.$exam->id.'/gabaritos')
            ->assertCreated()
            ->assertJsonPath('data.versao', 1)
            ->assertJsonPath('data.status', GabaritoOficialStatus::DRAFT->value);
        $second = $this->postJson('/api/v1/provas/'.$exam->id.'/gabaritos')
            ->assertCreated()
            ->assertJsonPath('data.versao', 2);

        $this->getJson('/api/v1/provas/'.$exam->id.'/gabaritos')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $second->json('data.id'))
            ->assertJsonPath('data.items.1.id', $first->json('data.id'));
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::ANSWER_KEY_CREATED->value,
            'usuario_id' => $admin->id,
            'entidade_id' => $first->json('data.id'),
        ]);
    }

    public function test_manager_access_is_limited_to_own_nucleus_and_draft_exam(): void
    {
        $ownNucleus = Nucleo::factory()->create();
        $otherNucleus = Nucleo::factory()->create();
        $ownExam = Prova::factory()->create(['nucleo_id' => $ownNucleus->id]);
        $foreignExam = Prova::factory()->create(['nucleo_id' => $otherNucleus->id]);
        $publishedExam = Prova::factory()->create([
            'nucleo_id' => $ownNucleus->id,
            'status' => ProvaStatus::PUBLISHED,
            'publicada_at' => now(),
        ]);
        $this->actingAsRole(UserRole::EDUCATION_MANAGER, $ownNucleus);

        $this->postJson('/api/v1/provas/'.$ownExam->id.'/gabaritos')->assertCreated();
        $this->postJson('/api/v1/provas/'.$foreignExam->id.'/gabaritos')->assertNotFound();
        $this->postJson('/api/v1/provas/'.$publishedExam->id.'/gabaritos')->assertForbidden();
    }

    public function test_school_manager_cannot_discover_or_manage_answer_keys(): void
    {
        $school = Escola::factory()->create();
        $exam = Prova::factory()->create(['nucleo_id' => $school->nucleo_id]);
        $answerKey = GabaritoOficial::factory()->create(['prova_id' => $exam->id]);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, escola: $school);

        $this->getJson('/api/v1/provas/'.$exam->id.'/gabaritos')->assertNotFound();
        $this->getJson('/api/v1/provas/'.$exam->id.'/gabaritos/'.$answerKey->id)->assertNotFound();
        $this->postJson('/api/v1/provas/'.$exam->id.'/gabaritos')->assertNotFound();
    }

    public function test_response_is_created_and_updated_idempotently_with_annulment_policy(): void
    {
        $nucleus = Nucleo::factory()->create();
        $manager = $this->actingAsRole(UserRole::EDUCATION_MANAGER, $nucleus);
        $exam = Prova::factory()->create(['nucleo_id' => $nucleus->id]);
        $question = Questao::factory()->create([
            'prova_id' => $exam->id,
            'numero' => 1,
            'peso_padrao' => 2.5,
        ]);
        $answerKey = GabaritoOficial::factory()->create(['prova_id' => $exam->id]);
        $url = '/api/v1/provas/'.$exam->id.'/gabaritos/'.$answerKey->id.'/respostas/'.$question->id;

        $responseId = $this->putJson($url, [
            'alternativa_correta' => ' b ',
            'anulada' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.alternativa_correta', 'B')
            ->assertJsonPath('data.anulada', false)
            ->assertJsonPath('data.peso', '2.5000')
            ->json('data.id');

        $this->putJson($url, [
            'alternativa_correta' => null,
            'anulada' => true,
            'peso' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $responseId)
            ->assertJsonPath('data.alternativa_correta', null)
            ->assertJsonPath('data.anulada', true)
            ->assertJsonPath('data.peso', '3.0000');

        $this->assertDatabaseCount('gabarito_respostas', 1);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::ANSWER_KEY_RESPONSE_CREATED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $responseId,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::ANSWER_KEY_RESPONSE_UPDATED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $responseId,
        ]);
    }

    public function test_invalid_alternatives_and_annulment_combinations_are_rejected(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $exam = Prova::factory()->create();
        $question = Questao::factory()->create(['prova_id' => $exam->id, 'numero' => 1]);
        $answerKey = GabaritoOficial::factory()->create(['prova_id' => $exam->id]);
        $url = '/api/v1/provas/'.$exam->id.'/gabaritos/'.$answerKey->id.'/respostas/'.$question->id;

        $this->putJson($url, ['alternativa_correta' => 'Z', 'anulada' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('alternativa_correta', 'error.details');
        $this->putJson($url, ['alternativa_correta' => 'A', 'anulada' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('alternativa_correta', 'error.details');
        $this->putJson($url, ['alternativa_correta' => null, 'anulada' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('alternativa_correta', 'error.details');
    }

    public function test_question_from_another_exam_is_not_discoverable_in_answer_key(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $exam = Prova::factory()->create();
        $otherExam = Prova::factory()->create();
        $question = Questao::factory()->create(['prova_id' => $otherExam->id, 'numero' => 1]);
        $answerKey = GabaritoOficial::factory()->create(['prova_id' => $exam->id]);

        $this->putJson(
            '/api/v1/provas/'.$exam->id.'/gabaritos/'.$answerKey->id.'/respostas/'.$question->id,
            ['alternativa_correta' => 'A', 'anulada' => false],
        )->assertNotFound();
    }

    public function test_exam_alternatives_cannot_invalidate_existing_official_responses(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $exam = Prova::factory()->create();
        $question = Questao::factory()->create(['prova_id' => $exam->id, 'numero' => 1]);
        $answerKey = GabaritoOficial::factory()->create(['prova_id' => $exam->id]);
        $this->putJson(
            '/api/v1/provas/'.$exam->id.'/gabaritos/'.$answerKey->id.'/respostas/'.$question->id,
            ['alternativa_correta' => 'E', 'anulada' => false],
        )->assertOk();
        $newAlternatives = ['A', 'B', 'C', 'D', 'F'];
        $newModel = ModeloCartao::factory()->create([
            'nucleo_id' => null,
            'alternativas' => $newAlternatives,
            'status' => ModeloCartaoStatus::APPROVED,
            'homologado_por' => User::factory(),
            'homologado_at' => now(),
        ]);

        $this->patchJson('/api/v1/provas/'.$exam->id, [
            'modelo_cartao_id' => $newModel->id,
            'alternativas' => $newAlternatives,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('alternativas', 'error.details');
    }

    public function test_completeness_validation_reports_missing_answers_until_draft_is_complete(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $exam = $this->examWithQuestionCount(2);
        $first = Questao::factory()->create(['prova_id' => $exam->id, 'numero' => 1]);
        $second = Questao::factory()->create(['prova_id' => $exam->id, 'numero' => 2]);
        $answerKey = GabaritoOficial::factory()->create(['prova_id' => $exam->id]);
        $baseUrl = '/api/v1/provas/'.$exam->id.'/gabaritos/'.$answerKey->id;

        $this->getJson($baseUrl.'/validacao')
            ->assertOk()
            ->assertJsonPath('data.completo', false)
            ->assertJsonPath('data.questoes_esperadas', 2)
            ->assertJsonPath('data.questoes_ativas', 2)
            ->assertJsonPath('data.respostas_registradas', 0)
            ->assertJsonPath('data.questoes_sem_resposta.0', 1)
            ->assertJsonPath('data.questoes_sem_resposta.1', 2);

        foreach ([$first, $second] as $question) {
            $this->putJson($baseUrl.'/respostas/'.$question->id, [
                'alternativa_correta' => 'A',
                'anulada' => false,
            ])->assertOk();
        }

        $this->getJson($baseUrl.'/validacao')
            ->assertOk()
            ->assertJsonPath('data.completo', true)
            ->assertJsonPath('data.respostas_registradas', 2)
            ->assertJsonPath('data.questoes_sem_resposta', [])
            ->assertJsonPath('data.problemas', []);
    }

    public function test_database_rejects_answer_for_question_from_another_exam(): void
    {
        $exam = Prova::factory()->create();
        $otherExam = Prova::factory()->create();
        $question = Questao::factory()->create(['prova_id' => $otherExam->id, 'numero' => 1]);
        $answerKey = GabaritoOficial::factory()->create(['prova_id' => $exam->id]);

        $this->expectException(QueryException::class);

        GabaritoResposta::query()->create([
            'prova_id' => $exam->id,
            'gabarito_oficial_id' => $answerKey->id,
            'questao_id' => $question->id,
            'alternativa_correta' => 'A',
            'anulada' => false,
            'peso' => 1,
        ]);
    }

    public function test_answer_key_action_rejects_alternative_outside_exam_configuration(): void
    {
        $exam = Prova::factory()->create();
        $question = Questao::factory()->create(['prova_id' => $exam->id, 'numero' => 1]);
        $answerKey = GabaritoOficial::factory()->create(['prova_id' => $exam->id]);

        $this->expectException(ValidationException::class);

        app(UpsertGabaritoRespostaAction::class)->execute($exam, $answerKey, $question, [
            'alternativa_correta' => 'Z',
            'anulada' => false,
            'peso' => 1,
        ], User::factory()->create());
    }

    public function test_answer_key_action_rejects_changes_to_current_answer_key(): void
    {
        $exam = Prova::factory()->create();
        $question = Questao::factory()->create(['prova_id' => $exam->id, 'numero' => 1]);
        $answerKey = GabaritoOficial::factory()->create(['prova_id' => $exam->id]);
        $response = GabaritoResposta::query()->create([
            'prova_id' => $exam->id,
            'gabarito_oficial_id' => $answerKey->id,
            'questao_id' => $question->id,
            'alternativa_correta' => 'A',
            'anulada' => false,
            'peso' => 1,
        ]);
        $publisher = User::factory()->create();
        $answerKey->update([
            'status' => GabaritoOficialStatus::CURRENT,
            'publicado_por' => $publisher->id,
            'publicado_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(UpsertGabaritoRespostaAction::class)->execute($exam, $answerKey->refresh(), $question, [
            'alternativa_correta' => 'B',
            'anulada' => false,
            'peso' => 1,
        ], $publisher);
    }

    private function examWithQuestionCount(int $count): Prova
    {
        $model = ModeloCartao::factory()->create([
            'nucleo_id' => null,
            'quantidade_questoes' => $count,
            'status' => ModeloCartaoStatus::APPROVED,
            'homologado_por' => User::factory(),
            'homologado_at' => now(),
        ]);

        return Prova::factory()->create([
            'modelo_cartao_id' => $model->id,
            'quantidade_questoes' => $count,
        ]);
    }

    private function actingAsRole(
        UserRole $role,
        ?Nucleo $nucleo = null,
        ?Escola $escola = null,
    ): User {
        $user = User::factory()->create();
        $profile = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'nucleo_id' => $role === UserRole::EDUCATION_MANAGER ? $nucleo?->id : null,
            'escola_id' => $role === UserRole::SCHOOL_MANAGER ? $escola?->id : null,
            'inicio_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($user, ['api']);

        return $user;
    }
}
