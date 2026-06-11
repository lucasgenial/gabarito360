<?php

namespace Tests\Feature\Api\Usuarios;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\DispositivoMobile;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UsuarioManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_creates_normalized_user_with_initial_profile_and_masked_document(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);
        $nucleo = Nucleo::factory()->create();

        $response = $this->postJson('/api/v1/usuarios', [
            'nome' => ' Gestora Regional ',
            'email' => ' GESTORA@EXAMPLE.TEST ',
            'documento' => ' 12345678901 ',
            'telefone' => ' (11) 99999-0000 ',
            'password' => 'SenhaSegura123',
            'password_confirmation' => 'SenhaSegura123',
            'perfil_id' => $this->profile(UserRole::EDUCATION_MANAGER)->id,
            'nucleo_id' => $nucleo->id,
        ]);

        $targetId = $response->json('data.id');
        $linkId = $response->json('data.perfis.0.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Gestora Regional')
            ->assertJsonPath('data.email', 'gestora@example.test')
            ->assertJsonPath('data.documento_mascarado', '*******8901')
            ->assertJsonPath('data.perfis.0.codigo', UserRole::EDUCATION_MANAGER->value)
            ->assertJsonPath('data.perfis.0.nucleo_id', $nucleo->id)
            ->assertJsonMissingPath('data.documento')
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('usuarios', [
            'id' => $targetId,
            'nome' => 'Gestora Regional',
            'email' => 'gestora@example.test',
            'documento' => '12345678901',
            'status' => UserStatus::ACTIVE->value,
        ]);
        $this->assertDatabaseHas('usuario_perfis', [
            'id' => $linkId,
            'usuario_id' => $targetId,
            'nucleo_id' => $nucleo->id,
            'concedido_por' => $admin->id,
            'fim_at' => null,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::USER_CREATED->value,
            'usuario_id' => $admin->id,
            'entidade_id' => $targetId,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::PROFILE_GRANTED->value,
            'usuario_id' => $admin->id,
            'entidade_id' => $linkId,
        ]);

        $this->getJson('/api/v1/perfis')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->profile(UserRole::EDUCATION_MANAGER)->id,
                'codigo' => UserRole::EDUCATION_MANAGER->value,
            ]);
    }

    public function test_education_manager_creates_users_only_inside_own_education_center(): void
    {
        $ownCenter = Nucleo::factory()->create();
        $otherCenter = Nucleo::factory()->create();
        $ownSchool = Escola::factory()->create(['nucleo_id' => $ownCenter->id]);
        $otherSchool = Escola::factory()->create(['nucleo_id' => $otherCenter->id]);
        $this->actingAsRole(UserRole::EDUCATION_MANAGER, nucleo: $ownCenter);

        $this->postJson('/api/v1/usuarios', [
            ...$this->validUserPayload('professora@example.test'),
            'perfil_id' => $this->profile(UserRole::TEACHER)->id,
            'escola_id' => $ownSchool->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.perfis.0.escola_id', $ownSchool->id);

        $this->postJson('/api/v1/usuarios', [
            ...$this->validUserPayload('fora@example.test'),
            'perfil_id' => $this->profile(UserRole::TEACHER)->id,
            'escola_id' => $otherSchool->id,
        ])->assertForbidden();

        $this->postJson('/api/v1/usuarios', [
            ...$this->validUserPayload('admin@example.test'),
            'perfil_id' => $this->profile(UserRole::ADMINISTRATOR)->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('usuarios', 2);
    }

    public function test_school_manager_cannot_grant_privileged_profiles_or_access_another_school(): void
    {
        $ownSchool = Escola::factory()->create();
        $otherSchool = Escola::factory()->create();
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, school: $ownSchool);

        $created = $this->postJson('/api/v1/usuarios', [
            ...$this->validUserPayload('aplicador@example.test'),
            'perfil_id' => $this->profile(UserRole::APPLICATOR)->id,
            'escola_id' => $ownSchool->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.perfis.0.escola_id', $ownSchool->id);

        $this->postJson('/api/v1/usuarios', [
            ...$this->validUserPayload('gestor@example.test'),
            'perfil_id' => $this->profile(UserRole::EDUCATION_MANAGER)->id,
            'nucleo_id' => $ownSchool->nucleo_id,
        ])->assertForbidden();

        $this->postJson('/api/v1/usuarios', [
            ...$this->validUserPayload('outro@example.test'),
            'perfil_id' => $this->profile(UserRole::TEACHER)->id,
            'escola_id' => $otherSchool->id,
        ])->assertForbidden();

        $foreignUser = User::factory()->create();
        UsuarioPerfil::factory()->create([
            'usuario_id' => $foreignUser->id,
            'perfil_id' => $this->profile(UserRole::SCHOOL_MANAGER)->id,
            'escola_id' => $otherSchool->id,
        ]);

        $this->getJson('/api/v1/usuarios/'.$foreignUser->id)->assertForbidden();
        $this->patchJson('/api/v1/usuarios/'.$foreignUser->id, ['nome' => 'Acesso cruzado'])->assertForbidden();
        $this->postJson('/api/v1/usuarios/'.$foreignUser->id.'/inativar')->assertForbidden();

        $this->assertNotNull($created->json('data.id'));
        $this->assertSame(UserStatus::ACTIVE, $foreignUser->fresh()->status);
    }

    public function test_list_and_show_expose_only_profiles_inside_actor_scope(): void
    {
        $ownSchool = Escola::factory()->create();
        $otherSchool = Escola::factory()->create();
        $target = User::factory()->create(['documento' => '98765432100']);
        $ownLink = UsuarioPerfil::factory()->create([
            'usuario_id' => $target->id,
            'perfil_id' => $this->profile(UserRole::TEACHER)->id,
            'escola_id' => $ownSchool->id,
        ]);
        UsuarioPerfil::factory()->create([
            'usuario_id' => $target->id,
            'perfil_id' => $this->profile(UserRole::APPLICATOR)->id,
            'escola_id' => $otherSchool->id,
        ]);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, school: $ownSchool);

        $this->getJson('/api/v1/usuarios')
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonFragment(['id' => $target->id])
            ->assertJsonMissing(['escola_id' => $otherSchool->id]);

        $this->getJson('/api/v1/usuarios/'.$target->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.perfis')
            ->assertJsonPath('data.perfis.0.id', $ownLink->id)
            ->assertJsonPath('data.documento_mascarado', '*******2100')
            ->assertJsonMissingPath('data.documento');

        $this->patchJson('/api/v1/usuarios/'.$target->id, ['nome' => 'Mudanca global'])->assertForbidden();
        $this->postJson('/api/v1/usuarios/'.$target->id.'/inativar')->assertForbidden();
    }

    public function test_manager_assigns_and_revokes_profile_preserving_history(): void
    {
        $school = Escola::factory()->create();
        $manager = $this->actingAsRole(UserRole::SCHOOL_MANAGER, school: $school);
        $target = User::factory()->create();
        UsuarioPerfil::factory()->create([
            'usuario_id' => $target->id,
            'perfil_id' => $this->profile(UserRole::TEACHER)->id,
            'escola_id' => $school->id,
        ]);

        $payload = [
            'perfil_id' => $this->profile(UserRole::APPLICATOR)->id,
            'escola_id' => $school->id,
        ];

        $response = $this->postJson('/api/v1/usuarios/'.$target->id.'/perfis', $payload)
            ->assertCreated()
            ->assertJsonCount(2, 'data.perfis');

        $linkId = collect($response->json('data.perfis'))
            ->firstWhere('codigo', UserRole::APPLICATOR->value)['id'];

        $this->postJson('/api/v1/usuarios/'.$target->id.'/perfis', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('perfil_id', 'error.details');

        $this->deleteJson('/api/v1/usuarios/'.$target->id.'/perfis/'.$linkId)
            ->assertOk()
            ->assertJsonCount(1, 'data.perfis');

        $this->assertDatabaseHas('usuario_perfis', [
            'id' => $linkId,
            'usuario_id' => $target->id,
            'concedido_por' => $manager->id,
        ]);
        $this->assertNotNull(UsuarioPerfil::query()->findOrFail($linkId)->fim_at);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::PROFILE_REVOKED->value,
            'entidade_id' => $linkId,
        ]);
    }

    public function test_authorized_manager_updates_user_but_cannot_change_status_or_password_directly(): void
    {
        $school = Escola::factory()->create();
        $manager = $this->actingAsRole(UserRole::SCHOOL_MANAGER, school: $school);
        $target = User::factory()->create();
        UsuarioPerfil::factory()->create([
            'usuario_id' => $target->id,
            'perfil_id' => $this->profile(UserRole::TEACHER)->id,
            'escola_id' => $school->id,
        ]);

        $this->patchJson('/api/v1/usuarios/'.$target->id, [
            'nome' => ' Nome atualizado ',
            'email' => ' ATUALIZADO@EXAMPLE.TEST ',
            'documento' => ' 1234 ',
        ])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Nome atualizado')
            ->assertJsonPath('data.email', 'atualizado@example.test')
            ->assertJsonPath('data.documento_mascarado', '****');

        $this->patchJson('/api/v1/usuarios/'.$target->id, [
            'status' => UserStatus::INACTIVE->value,
            'password' => 'OutraSenha123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'password'], 'error.details');

        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::USER_UPDATED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $target->id,
        ]);
    }

    public function test_inactivation_revokes_tokens_sessions_and_devices_while_preserving_history(): void
    {
        $school = Escola::factory()->create();
        $manager = $this->actingAsRole(UserRole::SCHOOL_MANAGER, school: $school);
        $target = User::factory()->create();
        $link = UsuarioPerfil::factory()->create([
            'usuario_id' => $target->id,
            'perfil_id' => $this->profile(UserRole::TEACHER)->id,
            'escola_id' => $school->id,
        ]);
        $token = $target->createToken('ativo', ['api'])->plainTextToken;
        $device = DispositivoMobile::query()->create([
            'usuario_id' => $target->id,
            'identificador' => fake()->uuid(),
            'plataforma' => 'android',
            'versao_app' => '1.0.0',
        ]);
        DB::table('sessions')->insert([
            'id' => 'session-target',
            'user_id' => $target->id,
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->postJson('/api/v1/usuarios/'.$target->id.'/inativar')
            ->assertOk()
            ->assertJsonPath('data.status', UserStatus::INACTIVE->value);

        $this->assertSame(UserStatus::INACTIVE, $target->fresh()->status);
        $this->assertNull($link->fresh()->fim_at);
        $this->assertNotNull($device->fresh()->revogado_at);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $target->id]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::USER_INACTIVATED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $target->id,
        ]);

        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_unauthenticated_and_unrelated_profiles_cannot_manage_users(): void
    {
        $target = User::factory()->create();

        $this->getJson('/api/v1/usuarios')->assertUnauthorized();
        $this->getJson('/api/v1/perfis')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(), ['api']);

        $this->getJson('/api/v1/usuarios')->assertForbidden();
        $this->getJson('/api/v1/usuarios/'.$target->id)->assertForbidden();
        $this->getJson('/api/v1/perfis')->assertForbidden();
    }

    /** @return array<string, string> */
    private function validUserPayload(string $email): array
    {
        return [
            'nome' => 'Usuario de teste',
            'email' => $email,
            'password' => 'SenhaSegura123',
            'password_confirmation' => 'SenhaSegura123',
        ];
    }

    private function actingAsRole(
        UserRole $role,
        ?Nucleo $nucleo = null,
        ?Escola $school = null,
    ): User {
        $actor = User::factory()->create();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $actor->id,
            'perfil_id' => $this->profile($role)->id,
            'nucleo_id' => $role === UserRole::EDUCATION_MANAGER ? $nucleo?->id : null,
            'escola_id' => $role === UserRole::SCHOOL_MANAGER ? $school?->id : null,
            'inicio_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($actor, ['api']);

        return $actor;
    }

    private function profile(UserRole $role): Perfil
    {
        return Perfil::query()->where('codigo', $role->value)->firstOrFail();
    }
}
