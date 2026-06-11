<?php

namespace Tests\Feature\Api\ModelosCartao;

use App\Enums\ModeloCartaoOrigemCodigo;
use App\Enums\ModeloCartaoStatus;
use App\Enums\ModeloCartaoTipoCodigo;
use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use Database\Factories\ModeloCartaoFactory;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModeloCartaoManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_creates_global_draft_with_explicit_versioned_configuration(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);

        $response = $this->postJson('/api/v1/modelos-cartao', $this->payload());
        $modelId = $response->json('data.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.nucleo_id', null)
            ->assertJsonPath('data.status', ModeloCartaoStatus::DRAFT->value)
            ->assertJsonPath('data.tipo_codigo', ModeloCartaoTipoCodigo::WITHOUT_CODE->value)
            ->assertJsonPath('data.origem_codigo', ModeloCartaoOrigemCodigo::NONE->value)
            ->assertJsonPath('data.configuracao_omr.markers.items.TL.id', 0)
            ->assertJsonPath('data.configuracao_omr.thresholds.preenchimento_minimo', 0.65);

        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::CARD_MODEL_CREATED->value,
            'usuario_id' => $admin->id,
            'entidade_id' => $modelId,
        ]);
    }

    public function test_education_manager_reads_global_and_owned_models_but_only_manages_owned_scope(): void
    {
        $ownNucleus = Nucleo::factory()->create();
        $otherNucleus = Nucleo::factory()->create();
        $global = ModeloCartao::factory()->create(['nucleo_id' => null]);
        $own = ModeloCartao::factory()->create(['nucleo_id' => $ownNucleus->id]);
        $other = ModeloCartao::factory()->create(['nucleo_id' => $otherNucleus->id]);

        $this->actingAsRole(UserRole::EDUCATION_MANAGER, $ownNucleus);

        $this->getJson('/api/v1/modelos-cartao')
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonFragment(['id' => $global->id])
            ->assertJsonFragment(['id' => $own->id])
            ->assertJsonMissing(['id' => $other->id]);

        $this->patchJson('/api/v1/modelos-cartao/'.$own->id, [
            'artefato_checksum_sha256' => str_repeat('b', 64),
        ])->assertOk();
        $this->patchJson('/api/v1/modelos-cartao/'.$global->id, [
            'artefato_checksum_sha256' => str_repeat('c', 64),
        ])->assertForbidden();
        $this->getJson('/api/v1/modelos-cartao/'.$other->id)->assertNotFound();
        $this->patchJson('/api/v1/modelos-cartao/'.$other->id, [
            'artefato_checksum_sha256' => str_repeat('c', 64),
        ])->assertNotFound();
        $this->deleteJson('/api/v1/modelos-cartao/'.$other->id)->assertNotFound();
    }

    public function test_school_manager_cannot_access_card_model_management(): void
    {
        $model = ModeloCartao::factory()->create();
        $global = ModeloCartao::factory()->create(['nucleo_id' => null]);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER);

        $this->getJson('/api/v1/modelos-cartao')->assertForbidden();
        $this->getJson('/api/v1/modelos-cartao/'.$model->id)->assertNotFound();
        $this->getJson('/api/v1/modelos-cartao/'.$global->id)->assertNotFound();
        $this->postJson('/api/v1/modelos-cartao', $this->payload())->assertForbidden();
    }

    public function test_name_and_version_are_unique_case_insensitively_inside_the_same_scope(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);

        $this->postJson('/api/v1/modelos-cartao', $this->payload(['nome' => 'Cartao Oficial']))
            ->assertCreated();

        $this->postJson('/api/v1/modelos-cartao', $this->payload(['nome' => 'cartao oficial']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('versao', 'error.details');

        $this->postJson('/api/v1/modelos-cartao', $this->payload([
            'nome' => 'Cartao Oficial',
            'versao' => 2,
        ]))->assertCreated();
    }

    public function test_external_printed_code_and_system_affixed_code_have_distinct_explicit_semantics(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);

        $external = $this->codedPayload(
            ModeloCartaoTipoCodigo::QR_CODE,
            ModeloCartaoOrigemCodigo::EXTERNAL,
            'Cartao impresso externo',
        );
        $affixed = $this->codedPayload(
            ModeloCartaoTipoCodigo::QR_CODE,
            ModeloCartaoOrigemCodigo::AFFIXED_SYSTEM,
            'Cartao com etiqueta G360',
        );

        $this->postJson('/api/v1/modelos-cartao', $external)
            ->assertCreated()
            ->assertJsonPath('data.configuracao_omr.printed_code.semantic_source', 'externo');
        $this->postJson('/api/v1/modelos-cartao', $affixed)
            ->assertCreated()
            ->assertJsonPath('data.configuracao_omr.printed_code.semantic_source', 'sistema_afixado');

        $invalid = $external;
        $invalid['nome'] = 'Semantica invalida';
        $invalid['origem_codigo'] = ModeloCartaoOrigemCodigo::AFFIXED_SYSTEM->value;

        $this->postJson('/api/v1/modelos-cartao', $invalid)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('configuracao_omr.printed_code.semantic_source', 'error.details');
    }

    public function test_approval_requires_final_thresholds_checksum_and_no_placeholders(): void
    {
        $nucleus = Nucleo::factory()->create();
        $this->actingAsRole(UserRole::EDUCATION_MANAGER, $nucleus);
        $configuration = ModeloCartaoFactory::configuration();
        $configuration['thresholds']['preenchimento_minimo'] = null;
        $configuration['spec_version'] = 'PREENCHER_VERSAO';
        $model = ModeloCartao::factory()->create([
            'nucleo_id' => $nucleus->id,
            'configuracao_omr' => $configuration,
            'artefato_checksum_sha256' => null,
        ]);

        $this->postJson('/api/v1/modelos-cartao/'.$model->id.'/homologar')
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'configuracao_omr.thresholds.preenchimento_minimo',
                'configuracao_omr',
                'artefato_checksum_sha256',
            ], 'error.details');
    }

    public function test_draft_rejects_regions_outside_canvas_and_incomplete_answer_centers(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $configuration = ModeloCartaoFactory::configuration();
        $configuration['markers']['items']['TR']['region'] = [2400, 120, 140, 140];
        unset($configuration['answers']['centers_x']['E']);

        $this->postJson('/api/v1/modelos-cartao', $this->payload([
            'configuracao_omr' => $configuration,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'configuracao_omr.markers.items.TR.region',
                'configuracao_omr.answers.centers_x',
            ], 'error.details');
    }

    public function test_authorized_manager_approves_then_inactivates_an_immutable_model(): void
    {
        $nucleus = Nucleo::factory()->create();
        $manager = $this->actingAsRole(UserRole::EDUCATION_MANAGER, $nucleus);
        $model = ModeloCartao::factory()->create(['nucleo_id' => $nucleus->id]);

        $this->postJson('/api/v1/modelos-cartao/'.$model->id.'/homologar')
            ->assertOk()
            ->assertJsonPath('data.status', ModeloCartaoStatus::APPROVED->value)
            ->assertJsonPath('data.homologado_por', $manager->id);

        $this->patchJson('/api/v1/modelos-cartao/'.$model->id, [
            'artefato_checksum_sha256' => str_repeat('d', 64),
        ])->assertForbidden();

        $this->deleteJson('/api/v1/modelos-cartao/'.$model->id)
            ->assertOk()
            ->assertJsonPath('data.status', ModeloCartaoStatus::INACTIVE->value);

        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::CARD_MODEL_APPROVED->value,
            'entidade_id' => $model->id,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::CARD_MODEL_INACTIVATED->value,
            'entidade_id' => $model->id,
        ]);
    }

    public function test_model_from_inactive_nucleus_cannot_be_approved(): void
    {
        $nucleus = Nucleo::factory()->create(['status' => StatusEnum::INACTIVE]);
        $this->actingAsRole(UserRole::EDUCATION_MANAGER, $nucleus);
        $model = ModeloCartao::factory()->create(['nucleo_id' => $nucleus->id]);

        $this->postJson('/api/v1/modelos-cartao/'.$model->id.'/homologar')->assertForbidden();
    }

    public function test_database_trigger_blocks_direct_mutation_of_approved_configuration(): void
    {
        $model = ModeloCartao::factory()->create();
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $this->postJson('/api/v1/modelos-cartao/'.$model->id.'/homologar')->assertOk();

        $this->expectException(QueryException::class);

        DB::table('modelos_cartao')
            ->where('id', $model->id)
            ->update(['configuracao_omr' => json_encode(['alterado' => true], JSON_THROW_ON_ERROR)]);
    }

    public function test_draft_can_be_updated_and_inactivated_without_homologation_metadata(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $model = ModeloCartao::factory()->create(['nucleo_id' => null]);

        $this->patchJson('/api/v1/modelos-cartao/'.$model->id, [
            'artefato_checksum_sha256' => str_repeat('e', 64),
        ])
            ->assertOk()
            ->assertJsonPath('data.artefato_checksum_sha256', str_repeat('e', 64));

        $this->deleteJson('/api/v1/modelos-cartao/'.$model->id)
            ->assertOk()
            ->assertJsonPath('data.status', ModeloCartaoStatus::INACTIVE->value)
            ->assertJsonPath('data.homologado_at', null);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return [
            'nucleo_id' => null,
            'nome' => 'Modelo OMR MVP',
            'versao' => 1,
            'quantidade_questoes' => 20,
            'quantidade_alternativas' => 5,
            'alternativas' => ['A', 'B', 'C', 'D', 'E'],
            'tipo_codigo' => ModeloCartaoTipoCodigo::WITHOUT_CODE->value,
            'origem_codigo' => ModeloCartaoOrigemCodigo::NONE->value,
            'configuracao_omr' => ModeloCartaoFactory::configuration(),
            'artefato_checksum_sha256' => str_repeat('a', 64),
            ...$overrides,
        ];
    }

    private function codedPayload(
        ModeloCartaoTipoCodigo $type,
        ModeloCartaoOrigemCodigo $origin,
        string $name,
    ): array {
        $configuration = ModeloCartaoFactory::configuration();
        $configuration['printed_code']['type'] = $type->value;
        $configuration['printed_code']['semantic_source'] = $origin->value;
        $configuration['printed_code']['normalization'] = [
            'strategy' => 'trim_uppercase',
            'check_digit' => 'modulo_11',
        ];

        return $this->payload([
            'nome' => $name,
            'tipo_codigo' => $type->value,
            'origem_codigo' => $origin->value,
            'configuracao_omr' => $configuration,
        ]);
    }

    private function actingAsRole(UserRole $role, ?Nucleo $nucleus = null): User
    {
        $user = User::factory()->create();
        $profile = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'nucleo_id' => $role === UserRole::EDUCATION_MANAGER ? $nucleus?->id : null,
            'escola_id' => null,
            'inicio_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($user, ['api']);

        return $user;
    }
}
