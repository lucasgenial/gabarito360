<?php

namespace Tests\Feature\AccessControl;

use App\Enums\AccessScope;
use App\Enums\PermissionCode;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccessControlSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_control_seeder_is_idempotent_and_matches_mvp_catalog(): void
    {
        $this->seed(AccessControlSeeder::class);
        $this->seed(AccessControlSeeder::class);

        $this->assertDatabaseCount('perfis', count(UserRole::cases()));
        $this->assertDatabaseCount('permissoes', count(PermissionCode::cases()));
        $this->assertDatabaseCount('perfil_permissoes', 47);

        $profiles = Perfil::query()->with('permissoes')->get()->keyBy('codigo');

        $this->assertSame(
            AccessScope::GLOBAL,
            $profiles[UserRole::ADMINISTRATOR->value]->escopo_permitido,
        );
        $this->assertTrue($profiles[UserRole::ADMINISTRATOR->value]->sistema);
        $this->assertTrue(
            $profiles[UserRole::TEACHER->value]
                ->permissoes
                ->contains('codigo', PermissionCode::CONFIRM_READINGS->value),
        );
        $this->assertFalse(
            $profiles[UserRole::SCHOOL_MANAGER->value]
                ->permissoes
                ->contains('codigo', PermissionCode::MANAGE_EXAMS_ANSWER_KEYS->value),
        );
        $this->assertFalse(
            $profiles[UserRole::TECHNICAL_SUPPORT->value]
                ->permissoes
                ->contains('codigo', PermissionCode::MANAGE_USERS_PROFILES_LINKS->value),
        );
    }

    public function test_user_and_access_models_use_uuids_and_relationships(): void
    {
        $user = User::factory()->create();
        $profile = Perfil::factory()->create();
        $permission = Permissao::factory()->create();

        $profile->permissoes()->attach($permission);
        $link = UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
        ]);

        $this->assertTrue(Str::isUuid($user->id));
        $this->assertSame(UserStatus::ACTIVE, $user->status);
        $this->assertTrue(Str::isUuid($profile->id));
        $this->assertTrue(Str::isUuid($permission->id));
        $this->assertTrue(Str::isUuid($link->id));
        $this->assertTrue($user->perfis->contains($profile));
        $this->assertTrue($profile->permissoes->contains($permission));
    }

    public function test_active_profile_link_cannot_be_duplicated_in_the_same_scope(): void
    {
        $link = UsuarioPerfil::factory()->create();

        $this->expectException(QueryException::class);

        UsuarioPerfil::factory()->create([
            'usuario_id' => $link->usuario_id,
            'perfil_id' => $link->perfil_id,
        ]);
    }

    public function test_revoked_profile_link_preserves_history_and_allows_a_new_active_link(): void
    {
        $revokedLink = UsuarioPerfil::factory()->create([
            'inicio_at' => now()->subMinute(),
            'fim_at' => now(),
        ]);

        $activeLink = UsuarioPerfil::factory()->create([
            'usuario_id' => $revokedLink->usuario_id,
            'perfil_id' => $revokedLink->perfil_id,
        ]);

        $this->assertNotSame($revokedLink->id, $activeLink->id);
        $this->assertNotNull($revokedLink->fim_at);
        $this->assertNull($activeLink->fim_at);
    }

    public function test_profile_link_cannot_reference_nucleo_and_escola_together(): void
    {
        $nucleo = Nucleo::factory()->create();
        $escola = Escola::factory()->create();

        $this->expectException(QueryException::class);

        UsuarioPerfil::factory()->create([
            'nucleo_id' => $nucleo->id,
            'escola_id' => $escola->id,
        ]);
    }

    public function test_active_user_email_is_unique_without_case_sensitivity(): void
    {
        User::factory()->create(['email' => 'GESTOR@EXAMPLE.COM']);

        $this->expectException(QueryException::class);

        User::factory()->create(['email' => 'gestor@example.com']);
    }
}
