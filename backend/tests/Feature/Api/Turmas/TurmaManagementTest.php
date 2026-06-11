<?php

namespace Tests\Feature\Api\Turmas;

use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TurmaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_can_create_normalized_class_in_active_school(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);
        $school = Escola::factory()->create();

        $response = $this->postJson('/api/v1/turmas', [
            'escola_id' => $school->id,
            'codigo' => ' turma-01 ',
            'nome' => ' Turma Primeiro A ',
            'serie_ano' => ' 1 ano ',
            'turno' => ' MATUTINO ',
            'ano_letivo' => 2026,
        ]);

        $turmaId = $response->json('data.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.escola_id', $school->id)
            ->assertJsonPath('data.codigo', 'TURMA-01')
            ->assertJsonPath('data.nome', 'Turma Primeiro A')
            ->assertJsonPath('data.serie_ano', '1 ano')
            ->assertJsonPath('data.turno', 'matutino')
            ->assertJsonPath('data.ano_letivo', 2026)
            ->assertJsonPath('data.status', StatusEnum::ACTIVE->value);

        $this->assertDatabaseHas('turmas', [
            'id' => $turmaId,
            'escola_id' => $school->id,
            'codigo' => 'TURMA-01',
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::CLASS_CREATED->value,
            'usuario_id' => $admin->id,
            'nucleo_id' => $school->nucleo_id,
            'escola_id' => $school->id,
            'entidade_id' => $turmaId,
        ]);
    }

    public function test_code_is_unique_only_in_same_school_and_school_year(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $school = Escola::factory()->create();
        $otherSchool = Escola::factory()->create();

        Turma::factory()->create([
            'escola_id' => $school->id,
            'codigo' => 'TURMA-UNICA',
            'ano_letivo' => 2026,
        ]);

        $payload = [
            'codigo' => 'turma-unica',
            'nome' => 'Turma repetida',
            'serie_ano' => '5 ano',
            'turno' => 'matutino',
        ];

        $this->postJson('/api/v1/turmas', [
            ...$payload,
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('codigo', 'error.details');

        $this->postJson('/api/v1/turmas', [
            ...$payload,
            'escola_id' => $school->id,
            'ano_letivo' => 2027,
        ])->assertCreated();

        $this->postJson('/api/v1/turmas', [
            ...$payload,
            'escola_id' => $otherSchool->id,
            'ano_letivo' => 2026,
        ])->assertCreated();

        $this->assertDatabaseCount('turmas', 3);
    }

    public function test_managers_only_list_and_manage_classes_inside_their_scope(): void
    {
        $ownCenter = Nucleo::factory()->create();
        $otherCenter = Nucleo::factory()->create();
        $ownSchool = Escola::factory()->create(['nucleo_id' => $ownCenter->id]);
        $otherSchool = Escola::factory()->create(['nucleo_id' => $otherCenter->id]);
        $ownClass = Turma::factory()->create(['escola_id' => $ownSchool->id]);
        $otherClass = Turma::factory()->create(['escola_id' => $otherSchool->id]);

        $this->actingAsRole(UserRole::EDUCATION_MANAGER, nucleo: $ownCenter);

        $this->getJson('/api/v1/turmas')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $ownClass->id);
        $this->getJson('/api/v1/turmas?escola_id='.$otherSchool->id)
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
        $this->patchJson('/api/v1/turmas/'.$ownClass->id, [
            'nome' => 'Turma atualizada',
        ])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Turma atualizada');
        $this->getJson('/api/v1/turmas/'.$otherClass->id)->assertForbidden();
        $this->patchJson('/api/v1/turmas/'.$otherClass->id, [
            'nome' => 'Fora do escopo',
        ])->assertForbidden();
    }

    public function test_update_keeps_school_year_and_status_stable_and_inactivation_preserves_record(): void
    {
        $manager = $this->actingAsRole(
            UserRole::SCHOOL_MANAGER,
            escola: $school = Escola::factory()->create(),
        );
        $turma = Turma::factory()->create([
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ]);

        $this->patchJson('/api/v1/turmas/'.$turma->id, [
            'escola_id' => Escola::factory()->create()->id,
            'ano_letivo' => 2027,
            'status' => StatusEnum::INACTIVE->value,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['escola_id', 'ano_letivo', 'status'], 'error.details');

        $this->patchJson('/api/v1/turmas/'.$turma->id, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payload', 'error.details');

        $this->deleteJson('/api/v1/turmas/'.$turma->id)
            ->assertOk()
            ->assertJsonPath('data.status', StatusEnum::INACTIVE->value);

        $this->getJson('/api/v1/turmas/'.$turma->id)
            ->assertOk()
            ->assertJsonPath('data.id', $turma->id)
            ->assertJsonPath('data.status', StatusEnum::INACTIVE->value);

        $this->assertDatabaseHas('turmas', [
            'id' => $turma->id,
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
            'status' => StatusEnum::INACTIVE->value,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::CLASS_INACTIVATED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $turma->id,
        ]);
    }

    public function test_class_cannot_be_created_in_inactive_school_or_education_center(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $inactiveCenter = Nucleo::factory()->create(['status' => StatusEnum::INACTIVE]);
        $schoolInInactiveCenter = Escola::factory()->create(['nucleo_id' => $inactiveCenter->id]);
        $inactiveSchool = Escola::factory()->create(['status' => StatusEnum::INACTIVE]);

        foreach ([$schoolInInactiveCenter, $inactiveSchool] as $school) {
            $this->postJson('/api/v1/turmas', [
                'escola_id' => $school->id,
                'codigo' => 'TURMA-INATIVA-'.$school->codigo,
                'nome' => 'Turma inativa',
                'serie_ano' => '5 ano',
                'ano_letivo' => 2026,
            ])->assertForbidden();
        }

        $this->assertDatabaseCount('turmas', 0);
    }

    public function test_unauthenticated_and_operational_profiles_without_links_cannot_access_classes(): void
    {
        $turma = Turma::factory()->create();

        $this->getJson('/api/v1/turmas')->assertUnauthorized();

        $this->actingAsRole(UserRole::TEACHER);

        $this->getJson('/api/v1/turmas')->assertForbidden();
        $this->getJson('/api/v1/turmas/'.$turma->id)->assertForbidden();
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
