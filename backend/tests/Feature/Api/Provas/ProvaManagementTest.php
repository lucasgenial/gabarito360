<?php

namespace Tests\Feature\Api\Provas;

use App\Actions\Provas\CreateQuestaoAction;
use App\Actions\Provas\UpdateProvaAction;
use App\Enums\ModeloCartaoStatus;
use App\Enums\ProvaStatus;
use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Prova;
use App\Models\Questao;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProvaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_creates_normalized_nucleus_owned_draft_with_approved_model(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);
        $nucleus = Nucleo::factory()->create();
        $model = $this->approvedModel();

        $response = $this->postJson('/api/v1/provas', $this->payload($model, [
            'nucleo_id' => $nucleus->id,
            'codigo' => ' prova-01 ',
            'titulo' => ' Prova diagnostica ',
            'tipo' => ' DIAGNOSTICO ',
            'alternativas' => [' a ', ' b ', ' c ', ' d ', ' e '],
        ]));
        $examId = $response->json('data.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.nucleo_id', $nucleus->id)
            ->assertJsonPath('data.escola_id', null)
            ->assertJsonPath('data.codigo', 'PROVA-01')
            ->assertJsonPath('data.titulo', 'Prova diagnostica')
            ->assertJsonPath('data.tipo', 'diagnostico')
            ->assertJsonPath('data.status', ProvaStatus::DRAFT->value)
            ->assertJsonPath('data.alternativas.0', 'A');

        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::EXAM_CREATED->value,
            'usuario_id' => $admin->id,
            'entidade_id' => $examId,
            'nucleo_id' => $nucleus->id,
        ]);
    }

    public function test_education_manager_manages_nucleus_and_school_owned_drafts_only_inside_own_nucleus(): void
    {
        $ownNucleus = Nucleo::factory()->create();
        $otherNucleus = Nucleo::factory()->create();
        $ownSchool = Escola::factory()->create(['nucleo_id' => $ownNucleus->id]);
        $otherSchool = Escola::factory()->create(['nucleo_id' => $otherNucleus->id]);
        $model = $this->approvedModel();
        $this->actingAsRole(UserRole::EDUCATION_MANAGER, $ownNucleus);

        $nucleusExamId = $this->postJson('/api/v1/provas', $this->payload($model, [
            'nucleo_id' => $ownNucleus->id,
            'codigo' => 'NUCLEO-01',
        ]))->assertCreated()->json('data.id');
        $schoolExamId = $this->postJson('/api/v1/provas', $this->payload($model, [
            'escola_id' => $ownSchool->id,
            'codigo' => 'ESCOLA-01',
        ]))->assertCreated()->json('data.id');

        $this->postJson('/api/v1/provas', $this->payload($model, [
            'escola_id' => $otherSchool->id,
            'codigo' => 'FORA-01',
        ]))->assertForbidden();

        $foreign = Prova::factory()->create([
            'nucleo_id' => $otherNucleus->id,
            'codigo' => 'FORA-02',
        ]);

        $this->getJson('/api/v1/provas')
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonFragment(['id' => $nucleusExamId])
            ->assertJsonFragment(['id' => $schoolExamId])
            ->assertJsonMissing(['id' => $foreign->id]);
        $this->getJson('/api/v1/provas/'.$foreign->id)->assertNotFound();
        $this->patchJson('/api/v1/provas/'.$foreign->id, ['titulo' => 'Fora'])->assertNotFound();
    }

    public function test_school_manager_cannot_manage_exam_drafts(): void
    {
        $school = Escola::factory()->create();
        $exam = Prova::factory()->create(['nucleo_id' => $school->nucleo_id]);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, escola: $school);

        $this->getJson('/api/v1/provas')->assertForbidden();
        $this->getJson('/api/v1/provas/'.$exam->id)->assertNotFound();
        $this->postJson('/api/v1/provas', $this->payload($this->approvedModel(), [
            'escola_id' => $school->id,
        ]))->assertForbidden();
    }

    public function test_owner_model_scope_and_model_compatibility_are_validated(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $nucleus = Nucleo::factory()->create();
        $otherNucleus = Nucleo::factory()->create();
        $school = Escola::factory()->create(['nucleo_id' => $nucleus->id]);
        $draftModel = ModeloCartao::factory()->create(['nucleo_id' => $nucleus->id]);
        $foreignModel = $this->approvedModel($otherNucleus);
        $compatibleModel = $this->approvedModel($nucleus);

        $this->postJson('/api/v1/provas', $this->payload($compatibleModel, [
            'nucleo_id' => $nucleus->id,
            'escola_id' => $school->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('proprietario', 'error.details');

        $this->postJson('/api/v1/provas', $this->payload($draftModel, [
            'nucleo_id' => $nucleus->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('modelo_cartao_id', 'error.details');

        $this->postJson('/api/v1/provas', $this->payload($foreignModel, [
            'nucleo_id' => $nucleus->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('modelo_cartao_id', 'error.details');

        $this->postJson('/api/v1/provas', $this->payload($compatibleModel, [
            'nucleo_id' => $nucleus->id,
            'quantidade_questoes' => 19,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('modelo_cartao_id', 'error.details');
    }

    public function test_exam_code_is_unique_case_insensitively_only_per_owner(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $firstNucleus = Nucleo::factory()->create();
        $secondNucleus = Nucleo::factory()->create();
        $model = $this->approvedModel();

        $this->postJson('/api/v1/provas', $this->payload($model, [
            'nucleo_id' => $firstNucleus->id,
            'codigo' => 'PROVA-UNICA',
        ]))->assertCreated();
        $this->postJson('/api/v1/provas', $this->payload($model, [
            'nucleo_id' => $firstNucleus->id,
            'codigo' => 'prova-unica',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('codigo', 'error.details');
        $this->postJson('/api/v1/provas', $this->payload($model, [
            'nucleo_id' => $secondNucleus->id,
            'codigo' => 'PROVA-UNICA',
        ]))->assertCreated();
    }

    public function test_authorized_manager_edits_only_draft_without_changing_owner_or_status(): void
    {
        $nucleus = Nucleo::factory()->create();
        $manager = $this->actingAsRole(UserRole::EDUCATION_MANAGER, $nucleus);
        $exam = Prova::factory()->create(['nucleo_id' => $nucleus->id]);

        $this->patchJson('/api/v1/provas/'.$exam->id, [
            'nucleo_id' => Nucleo::factory()->create()->id,
            'status' => ProvaStatus::PUBLISHED->value,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nucleo_id', 'status'], 'error.details');

        $this->patchJson('/api/v1/provas/'.$exam->id, ['titulo' => 'Titulo atualizado'])
            ->assertOk()
            ->assertJsonPath('data.titulo', 'Titulo atualizado')
            ->assertJsonPath('data.status', ProvaStatus::DRAFT->value);

        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::EXAM_UPDATED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $exam->id,
        ]);

        $published = Prova::factory()->create([
            'nucleo_id' => $nucleus->id,
            'status' => ProvaStatus::PUBLISHED,
            'publicada_at' => now(),
        ]);
        $this->patchJson('/api/v1/provas/'.$published->id, ['titulo' => 'Nao permitido'])->assertForbidden();
    }

    public function test_questions_are_created_and_edited_inside_draft_without_duplicate_number(): void
    {
        $nucleus = Nucleo::factory()->create();
        $manager = $this->actingAsRole(UserRole::EDUCATION_MANAGER, $nucleus);
        $exam = Prova::factory()->create(['nucleo_id' => $nucleus->id]);

        $response = $this->postJson('/api/v1/provas/'.$exam->id.'/questoes', [
            'numero' => 1,
            'codigo' => ' q-01 ',
            'peso_padrao' => 2.5,
        ]);
        $questionId = $response->json('data.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.prova_id', $exam->id)
            ->assertJsonPath('data.numero', 1)
            ->assertJsonPath('data.codigo', 'Q-01');

        $this->postJson('/api/v1/provas/'.$exam->id.'/questoes', ['numero' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('numero', 'error.details');
        $this->postJson('/api/v1/provas/'.$exam->id.'/questoes', ['numero' => 21])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('numero', 'error.details');

        $this->patchJson('/api/v1/provas/'.$exam->id.'/questoes/'.$questionId, [
            'numero' => 2,
            'peso_padrao' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('data.numero', 2)
            ->assertJsonPath('data.peso_padrao', '3.0000');

        $this->getJson('/api/v1/provas/'.$exam->id.'/questoes')
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::QUESTION_CREATED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $questionId,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::QUESTION_UPDATED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $questionId,
        ]);
    }

    public function test_nested_question_from_another_exam_is_not_discoverable(): void
    {
        $nucleus = Nucleo::factory()->create();
        $this->actingAsRole(UserRole::EDUCATION_MANAGER, $nucleus);
        $firstExam = Prova::factory()->create(['nucleo_id' => $nucleus->id]);
        $secondExam = Prova::factory()->create(['nucleo_id' => $nucleus->id]);
        $question = Questao::factory()->create(['prova_id' => $secondExam->id, 'numero' => 1]);

        $this->getJson('/api/v1/provas/'.$firstExam->id.'/questoes/'.$question->id)->assertNotFound();
        $this->patchJson('/api/v1/provas/'.$firstExam->id.'/questoes/'.$question->id, [
            'peso_padrao' => 2,
        ])->assertNotFound();
    }

    public function test_update_action_rejects_incompatible_exam(): void
    {
        $nucleus = Nucleo::factory()->create();
        $model = $this->approvedModel();
        $exam = Prova::factory()->create([
            'nucleo_id' => $nucleus->id,
            'modelo_cartao_id' => $model->id,
        ]);

        $this->expectException(ValidationException::class);

        app(UpdateProvaAction::class)->execute($exam, ['quantidade_questoes' => 19], User::factory()->create());
    }

    public function test_create_question_action_rejects_number_above_limit(): void
    {
        $exam = Prova::factory()->create();

        $this->expectException(ValidationException::class);

        app(CreateQuestaoAction::class)->execute($exam, [
            'numero' => 21,
            'peso_padrao' => 1,
        ], User::factory()->create());
    }

    public function test_published_exam_rejects_new_questions(): void
    {
        $nucleus = Nucleo::factory()->create();
        $this->actingAsRole(UserRole::EDUCATION_MANAGER, $nucleus);
        $exam = Prova::factory()->create([
            'nucleo_id' => $nucleus->id,
            'status' => ProvaStatus::PUBLISHED,
            'publicada_at' => now(),
        ]);

        $this->postJson('/api/v1/provas/'.$exam->id.'/questoes', ['numero' => 1])->assertForbidden();
    }

    /** @param array<string, mixed> $overrides */
    private function payload(ModeloCartao $model, array $overrides = []): array
    {
        return [
            'nucleo_id' => null,
            'escola_id' => null,
            'modelo_cartao_id' => $model->id,
            'codigo' => 'PROVA-MVP',
            'titulo' => 'Prova MVP',
            'descricao' => null,
            'tipo' => 'simulado',
            'nivel' => '6 ano',
            'ano_referencia' => 2026,
            'quantidade_questoes' => 20,
            'quantidade_alternativas' => 5,
            'alternativas' => ['A', 'B', 'C', 'D', 'E'],
            ...$overrides,
        ];
    }

    private function approvedModel(?Nucleo $nucleus = null): ModeloCartao
    {
        return ModeloCartao::factory()->create([
            'nucleo_id' => $nucleus?->id,
            'status' => ModeloCartaoStatus::APPROVED,
            'homologado_por' => User::factory(),
            'homologado_at' => now(),
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
