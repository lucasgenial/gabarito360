<?php

namespace Tests\Feature\Auth;

use App\Enums\AccessScope;
use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_authenticates_and_receives_authorized_context(): void
    {
        $schoolId = Escola::factory()->create()->id;
        $user = User::factory()->create([
            'email' => 'gestor@example.test',
            'password' => 'senha-segura',
            'documento' => 'dado-pessoal-nao-retornado',
            'telefone' => 'dado-pessoal-nao-retornado',
        ]);
        $this->attachActiveProfile($user, $schoolId);
        $this->attachUnavailableProfiles($user);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => ' GESTOR@EXAMPLE.TEST ',
            'password' => 'senha-segura',
            'token_name' => 'teste-api',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'gestor@example.test')
            ->assertJsonPath('data.user.status', UserStatus::ACTIVE->value)
            ->assertJsonPath('data.user.contexto_autorizado.perfis.0.codigo', UserRole::SCHOOL_MANAGER->value)
            ->assertJsonPath('data.user.contexto_autorizado.perfis.0.escola_id', $schoolId)
            ->assertJsonPath('data.user.contexto_autorizado.permissoes.0', 'turmas_alunos.visualizar')
            ->assertJsonCount(1, 'data.user.contexto_autorizado.perfis')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['token']])
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.documento')
            ->assertJsonMissingPath('data.user.telefone');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'teste-api',
        ]);
        $this->assertNotNull($user->fresh()->ultimo_acesso_at);
    }

    public function test_invalid_credentials_are_rejected_without_revealing_the_invalid_field(): void
    {
        User::factory()->create([
            'email' => 'usuario@example.test',
            'password' => 'senha-correta',
        ]);

        foreach ([
            ['email' => 'usuario@example.test', 'password' => 'senha-incorreta'],
            ['email' => 'inexistente@example.test', 'password' => 'senha-correta'],
        ] as $credentials) {
            $this->postJson('/api/v1/auth/login', $credentials)
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'INVALID_CREDENTIALS')
                ->assertJsonPath('error.message', 'Credenciais invalidas.');
        }

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        User::factory()->create([
            'email' => 'inativo@example.test',
            'password' => 'senha-correta',
            'status' => UserStatus::INACTIVE,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inativo@example.test',
            'password' => 'senha-correta',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function attachActiveProfile(User $user, string $schoolId): void
    {
        $permission = Permissao::factory()->create([
            'codigo' => 'turmas_alunos.visualizar',
        ]);
        $profile = Perfil::factory()->create([
            'codigo' => UserRole::SCHOOL_MANAGER->value,
            'nome' => 'Gestor de teste',
            'escopo_permitido' => AccessScope::SCHOOL,
            'status' => StatusEnum::ACTIVE,
        ]);
        $profile->permissoes()->attach($permission);

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'escola_id' => $schoolId,
            'inicio_at' => now()->subMinute(),
        ]);
    }

    private function attachUnavailableProfiles(User $user): void
    {
        $inactiveProfile = Perfil::factory()->create([
            'codigo' => UserRole::EDUCATION_MANAGER->value,
            'status' => StatusEnum::INACTIVE,
        ]);
        $expiredProfile = Perfil::factory()->create([
            'codigo' => UserRole::ADMINISTRATOR->value,
        ]);

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $inactiveProfile->id,
            'nucleo_id' => Nucleo::factory()->create()->id,
            'inicio_at' => now()->subMinute(),
        ]);
        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $expiredProfile->id,
            'inicio_at' => now()->subDay(),
            'fim_at' => now()->subMinute(),
        ]);
    }
}
