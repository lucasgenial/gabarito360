<?php

namespace Tests\Feature\Api\Escolas;

use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EscolaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_can_create_normalized_school_in_active_education_center(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);
        $nucleo = Nucleo::factory()->create();

        $response = $this->postJson('/api/v1/escolas', [
            'nucleo_id' => $nucleo->id,
            'codigo' => ' escola-01 ',
            'nome' => ' Escola Municipal Centro ',
            'municipio' => ' Cidade Teste ',
            'estado' => 'sp',
            'endereco' => [
                'logradouro' => ' Rua Principal ',
                'numero' => ' 10 ',
                'complemento' => '',
            ],
            'email' => ' ESCOLA@EXAMPLE.TEST ',
            'telefone' => ' (11) 3333-4444 ',
        ]);

        $escolaId = $response->json('data.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.nucleo_id', $nucleo->id)
            ->assertJsonPath('data.codigo', 'ESCOLA-01')
            ->assertJsonPath('data.nome', 'Escola Municipal Centro')
            ->assertJsonPath('data.estado', 'SP')
            ->assertJsonPath('data.endereco.logradouro', 'Rua Principal')
            ->assertJsonPath('data.email', 'escola@example.test')
            ->assertJsonPath('data.status', StatusEnum::ACTIVE->value);

        $this->assertDatabaseHas('escolas', [
            'id' => $escolaId,
            'nucleo_id' => $nucleo->id,
            'codigo' => 'ESCOLA-01',
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::SCHOOL_CREATED->value,
            'usuario_id' => $admin->id,
            'nucleo_id' => $nucleo->id,
            'escola_id' => $escolaId,
        ]);
    }

    public function test_code_is_unique_only_inside_the_same_education_center(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $firstCenter = Nucleo::factory()->create();
        $secondCenter = Nucleo::factory()->create();
        Escola::factory()->create([
            'nucleo_id' => $firstCenter->id,
            'codigo' => 'ESC-UNICA',
        ]);

        $payload = [
            'codigo' => 'esc-unica',
            'nome' => 'Escola duplicada',
            'municipio' => 'Cidade',
            'estado' => 'SP',
        ];

        $this->postJson('/api/v1/escolas', [
            ...$payload,
            'nucleo_id' => $firstCenter->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('codigo', 'error.details');

        $this->postJson('/api/v1/escolas', [
            ...$payload,
            'nucleo_id' => $secondCenter->id,
        ])->assertCreated();

        $this->assertDatabaseCount('escolas', 2);
    }

    public function test_manager_lists_only_own_education_center_and_filters_do_not_leak_other_centers(): void
    {
        $ownCenter = Nucleo::factory()->create();
        $otherCenter = Nucleo::factory()->create();
        $ownSchool = Escola::factory()->create(['nucleo_id' => $ownCenter->id]);
        Escola::factory()->create(['nucleo_id' => $otherCenter->id]);

        $this->actingAsRole(UserRole::EDUCATION_MANAGER, $ownCenter);

        $this->getJson('/api/v1/escolas')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $ownSchool->id);

        $this->getJson('/api/v1/escolas?nucleo_id='.$otherCenter->id)
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this->getJson('/api/v1/escolas?nucleo_id='.fake()->uuid())
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    public function test_manager_can_manage_own_school_but_cannot_access_another_education_center(): void
    {
        $ownCenter = Nucleo::factory()->create();
        $otherCenter = Nucleo::factory()->create();
        $ownSchool = Escola::factory()->create(['nucleo_id' => $ownCenter->id]);
        $otherSchool = Escola::factory()->create(['nucleo_id' => $otherCenter->id]);

        $manager = $this->actingAsRole(UserRole::EDUCATION_MANAGER, $ownCenter);

        $this->getJson('/api/v1/escolas/'.$ownSchool->id)->assertOk();
        $this->patchJson('/api/v1/escolas/'.$ownSchool->id, [
            'nome' => 'Escola atualizada',
        ])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Escola atualizada');

        $this->getJson('/api/v1/escolas/'.$otherSchool->id)->assertForbidden();
        $this->patchJson('/api/v1/escolas/'.$otherSchool->id, [
            'nome' => 'Acesso cruzado',
        ])->assertForbidden();
        $this->deleteJson('/api/v1/escolas/'.$otherSchool->id)->assertForbidden();
        $this->postJson('/api/v1/escolas', [
            'nucleo_id' => $otherCenter->id,
            'codigo' => 'FORA-ESCOPO',
            'nome' => 'Fora do escopo',
            'municipio' => 'Cidade',
            'estado' => 'SP',
        ])->assertForbidden();

        $this->assertNotSame('Acesso cruzado', $otherSchool->fresh()->nome);
        $this->assertSame(StatusEnum::ACTIVE, $otherSchool->fresh()->status);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::SCHOOL_UPDATED->value,
            'usuario_id' => $manager->id,
            'nucleo_id' => $ownCenter->id,
            'escola_id' => $ownSchool->id,
        ]);
    }

    public function test_update_cannot_move_school_or_change_status_and_preserves_code_uniqueness(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $center = Nucleo::factory()->create();
        $otherCenter = Nucleo::factory()->create();
        $school = Escola::factory()->create([
            'nucleo_id' => $center->id,
            'codigo' => 'ESC-01',
        ]);
        Escola::factory()->create([
            'nucleo_id' => $center->id,
            'codigo' => 'ESC-02',
        ]);

        $this->patchJson('/api/v1/escolas/'.$school->id, [
            'codigo' => 'esc-02',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('codigo', 'error.details');

        $this->patchJson('/api/v1/escolas/'.$school->id, [
            'nucleo_id' => $otherCenter->id,
            'status' => StatusEnum::INACTIVE->value,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nucleo_id', 'status'], 'error.details');

        $this->patchJson('/api/v1/escolas/'.$school->id, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payload', 'error.details');

        $this->assertSame($center->id, $school->fresh()->nucleo_id);
        $this->assertSame(StatusEnum::ACTIVE, $school->fresh()->status);
    }

    public function test_invalid_address_shape_is_rejected_instead_of_being_silently_discarded(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $nucleo = Nucleo::factory()->create();

        $this->postJson('/api/v1/escolas', [
            'nucleo_id' => $nucleo->id,
            'codigo' => 'ESC-ENDERECO',
            'nome' => 'Escola endereco invalido',
            'municipio' => 'Cidade',
            'estado' => 'SP',
            'endereco' => 'Rua sem estrutura',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endereco', 'error.details');

        $this->assertDatabaseCount('escolas', 0);

        $school = Escola::factory()->create(['nucleo_id' => $nucleo->id]);

        $this->patchJson('/api/v1/escolas/'.$school->id, [
            'endereco' => 'Rua sem estrutura',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endereco', 'error.details');

        $this->assertNull($school->fresh()->endereco);
    }

    public function test_school_cannot_be_created_in_inactive_education_center(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $inactiveCenter = Nucleo::factory()->create(['status' => StatusEnum::INACTIVE]);

        $this->postJson('/api/v1/escolas', [
            'nucleo_id' => $inactiveCenter->id,
            'codigo' => 'ESC-INATIVA',
            'nome' => 'Escola sem nucleo ativo',
            'municipio' => 'Cidade',
            'estado' => 'SP',
        ])
            ->assertForbidden();

        $this->assertDatabaseCount('escolas', 0);
    }

    public function test_delete_inactivates_school_and_preserves_historical_consultation(): void
    {
        $manager = $this->actingAsRole(
            UserRole::EDUCATION_MANAGER,
            $nucleo = Nucleo::factory()->create(),
        );
        $school = Escola::factory()->create(['nucleo_id' => $nucleo->id]);

        $this->deleteJson('/api/v1/escolas/'.$school->id)
            ->assertOk()
            ->assertJsonPath('data.status', StatusEnum::INACTIVE->value);

        $this->getJson('/api/v1/escolas/'.$school->id)
            ->assertOk()
            ->assertJsonPath('data.status', StatusEnum::INACTIVE->value);

        $this->assertDatabaseHas('escolas', [
            'id' => $school->id,
            'status' => StatusEnum::INACTIVE->value,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::SCHOOL_INACTIVATED->value,
            'usuario_id' => $manager->id,
            'escola_id' => $school->id,
        ]);
    }

    public function test_unauthenticated_and_unrelated_profiles_cannot_access_schools(): void
    {
        $school = Escola::factory()->create();

        $this->getJson('/api/v1/escolas')->assertUnauthorized();

        $this->actingAsRole(UserRole::SCHOOL_MANAGER, escola: $school);

        $this->getJson('/api/v1/escolas')->assertForbidden();
        $this->getJson('/api/v1/escolas/'.$school->id)->assertForbidden();
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
