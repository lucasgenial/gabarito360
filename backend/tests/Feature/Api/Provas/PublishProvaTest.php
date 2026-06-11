<?php

namespace Tests\Feature\Api\Provas;

use App\Actions\Provas\PublishProva;
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
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class PublishProvaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_authorized_manager_publishes_complete_exam_and_selected_answer_key_atomically(): void
    {
        $nucleus = Nucleo::factory()->create();
        $manager = $this->actingAsRole(UserRole::EDUCATION_MANAGER, $nucleus);
        [$exam, $answerKey] = $this->completeDraft($nucleus);

        $this->postJson('/api/v1/provas/'.$exam->id.'/publicar', [
            'gabarito_oficial_id' => $answerKey->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.prova.status', ProvaStatus::PUBLISHED->value)
            ->assertJsonPath('data.gabarito.status', GabaritoOficialStatus::CURRENT->value)
            ->assertJsonPath('data.gabarito.publicado_por', $manager->id);

        $this->assertDatabaseHas('provas', [
            'id' => $exam->id,
            'status' => ProvaStatus::PUBLISHED->value,
        ]);
        $this->assertDatabaseHas('gabaritos_oficiais', [
            'id' => $answerKey->id,
            'status' => GabaritoOficialStatus::CURRENT->value,
            'publicado_por' => $manager->id,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::EXAM_PUBLISHED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $exam->id,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::ANSWER_KEY_PUBLISHED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $answerKey->id,
        ]);

        $question = $exam->questoes()->firstOrFail();
        $this->patchJson('/api/v1/provas/'.$exam->id, ['titulo' => 'Nao permitido'])->assertForbidden();
        $this->postJson('/api/v1/provas/'.$exam->id.'/questoes', ['numero' => 3])->assertForbidden();
        $this->putJson(
            '/api/v1/provas/'.$exam->id.'/gabaritos/'.$answerKey->id.'/respostas/'.$question->id,
            ['alternativa_correta' => 'B', 'anulada' => false],
        )->assertForbidden();
    }

    public function test_incomplete_answer_key_does_not_publish_any_resource(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        [$exam, $answerKey] = $this->completeDraft(answeredQuestions: 1);

        $this->postJson('/api/v1/provas/'.$exam->id.'/publicar', [
            'gabarito_oficial_id' => $answerKey->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('gabarito_oficial_id', 'error.details');

        $this->assertDatabaseHas('provas', [
            'id' => $exam->id,
            'status' => ProvaStatus::DRAFT->value,
        ]);
        $this->assertDatabaseHas('gabaritos_oficiais', [
            'id' => $answerKey->id,
            'status' => GabaritoOficialStatus::DRAFT->value,
        ]);
    }

    public function test_publication_requires_selected_answer_key_from_exam_and_approved_model(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        [$exam, $answerKey] = $this->completeDraft();
        [, $foreignAnswerKey] = $this->completeDraft();

        $this->postJson('/api/v1/provas/'.$exam->id.'/publicar', [
            'gabarito_oficial_id' => $foreignAnswerKey->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('gabarito_oficial_id', 'error.details');

        $exam->modeloCartao()->update(['status' => ModeloCartaoStatus::INACTIVE]);

        $this->postJson('/api/v1/provas/'.$exam->id.'/publicar', [
            'gabarito_oficial_id' => $answerKey->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('modelo_cartao_id', 'error.details');
    }

    public function test_publication_respects_scope_and_rejects_stale_conflict(): void
    {
        $nucleus = Nucleo::factory()->create();
        $otherNucleus = Nucleo::factory()->create();
        [$exam, $answerKey] = $this->completeDraft($nucleus);
        $manager = $this->actingAsRole(UserRole::EDUCATION_MANAGER, $otherNucleus);

        $this->postJson('/api/v1/provas/'.$exam->id.'/publicar', [
            'gabarito_oficial_id' => $answerKey->id,
        ])->assertNotFound();

        $manager = $this->actingAsRole(UserRole::EDUCATION_MANAGER, $nucleus);
        app(PublishProva::class)->execute($exam, $answerKey, $manager);

        $this->expectException(ConflictHttpException::class);

        app(PublishProva::class)->execute($exam, $answerKey, $manager);
    }

    public function test_database_rejects_two_current_answer_keys_for_same_exam(): void
    {
        [$exam, $first] = $this->completeDraft();
        $publisher = User::factory()->create();
        $second = GabaritoOficial::factory()->create([
            'prova_id' => $exam->id,
            'versao' => 2,
        ]);
        $publication = [
            'status' => GabaritoOficialStatus::CURRENT,
            'publicado_por' => $publisher->id,
            'publicado_at' => now(),
        ];
        $first->update($publication);

        $this->expectException(QueryException::class);

        $second->update($publication);
    }

    /** @return array{Prova, GabaritoOficial} */
    private function completeDraft(?Nucleo $nucleus = null, int $answeredQuestions = 2): array
    {
        $model = ModeloCartao::factory()->create([
            'nucleo_id' => null,
            'quantidade_questoes' => 2,
            'status' => ModeloCartaoStatus::APPROVED,
            'homologado_por' => User::factory(),
            'homologado_at' => now(),
        ]);
        $exam = Prova::factory()->create([
            'nucleo_id' => $nucleus?->id ?? Nucleo::factory(),
            'modelo_cartao_id' => $model->id,
            'quantidade_questoes' => 2,
        ]);
        $answerKey = GabaritoOficial::factory()->create(['prova_id' => $exam->id]);

        foreach ([1, 2] as $number) {
            $question = Questao::factory()->create([
                'prova_id' => $exam->id,
                'numero' => $number,
            ]);
            if ($number <= $answeredQuestions) {
                GabaritoResposta::query()->create([
                    'prova_id' => $exam->id,
                    'gabarito_oficial_id' => $answerKey->id,
                    'questao_id' => $question->id,
                    'alternativa_correta' => 'A',
                    'anulada' => false,
                    'peso' => 1,
                ]);
            }
        }

        return [$exam, $answerKey];
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
