<?php

namespace Tests\Feature\Api\V2\Auth;

use App\Enums\UserStatus;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_valid_credentials_return_a_session_with_token(): void
    {
        $user = User::factory()->create([
            'email' => 'professor@escola.test',
            'password' => 'Segredo@12345',
            'documento' => '12345678909',
        ]);

        $response = $this->postJson('/api/v2/auth/login', [
            'email' => 'professor@escola.test',
            'senha' => 'Segredo@12345',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'token', 'dispositivo', 'criado_em', 'ultimo_acesso'],
                'meta' => ['request_id'],
            ]);

        $this->assertTrue(Str::isUuid($response->json('data.id')));
        $this->assertNotEmpty($response->json('data.token'));

        $this->assertDatabaseHas('sessoes_usuarios', [
            'usuario_id' => $user->id,
            'encerrado_at' => null,
        ]);
        $this->assertDatabaseHas('historicos_acesso', [
            'usuario_id' => $user->id,
            'evento' => 'login',
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $user->id,
            'acao' => 'auth.login.succeeded',
        ]);

        $token = PersonalAccessToken::query()->where('tokenable_id', $user->id)->firstOrFail();
        $this->assertSame(['api'], $token->abilities);
    }

    public function test_invalid_password_is_rejected_and_recorded(): void
    {
        $user = User::factory()->create([
            'email' => 'professor@escola.test',
            'password' => 'Segredo@12345',
        ]);

        $this->postJson('/api/v2/auth/login', [
            'email' => 'professor@escola.test',
            'senha' => 'senha-errada',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('meta.code', 'INVALID_CREDENTIALS');

        $this->assertDatabaseHas('historicos_acesso', [
            'usuario_id' => $user->id,
            'evento' => 'login_falho',
        ]);
        $this->assertDatabaseHas('auditorias', ['acao' => 'auth.login.failed']);
        $this->assertDatabaseMissing('sessoes_usuarios', ['usuario_id' => $user->id]);
    }

    public function test_unknown_email_returns_the_same_neutral_error(): void
    {
        $this->postJson('/api/v2/auth/login', [
            'email' => 'ninguem@escola.test',
            'senha' => 'qualquer-coisa',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('meta.code', 'INVALID_CREDENTIALS')
            ->assertJsonPath('message', 'Credenciais invalidas.');
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'inativo@escola.test',
            'password' => 'Segredo@12345',
            'status' => UserStatus::INACTIVE,
        ]);

        $this->postJson('/api/v2/auth/login', [
            'email' => 'inativo@escola.test',
            'senha' => 'Segredo@12345',
        ])->assertUnauthorized();

        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $user->id,
            'acao' => 'auth.login.blocked_user',
        ]);
    }

    public function test_remember_me_extends_token_expiration(): void
    {
        User::factory()->create([
            'email' => 'lembrar@escola.test',
            'password' => 'Segredo@12345',
        ]);

        $this->postJson('/api/v2/auth/login', [
            'email' => 'lembrar@escola.test',
            'senha' => 'Segredo@12345',
            'manter_conectado' => true,
        ])->assertOk();

        $token = PersonalAccessToken::query()->latest('id')->firstOrFail();
        $this->assertTrue($token->expires_at->greaterThan(now()->addDays(20)));
    }

    public function test_short_session_when_not_remembered(): void
    {
        User::factory()->create([
            'email' => 'curto@escola.test',
            'password' => 'Segredo@12345',
        ]);

        $this->postJson('/api/v2/auth/login', [
            'email' => 'curto@escola.test',
            'senha' => 'Segredo@12345',
            'manter_conectado' => false,
        ])->assertOk();

        $token = PersonalAccessToken::query()->latest('id')->firstOrFail();
        $this->assertTrue($token->expires_at->lessThan(now()->addDays(1)));
    }

    public function test_missing_fields_fail_validation(): void
    {
        $this->postJson('/api/v2/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'senha']);
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'limite@escola.test',
            'password' => 'Segredo@12345',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v2/auth/login', [
                'email' => 'limite@escola.test',
                'senha' => 'senha-errada',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v2/auth/login', [
            'email' => 'limite@escola.test',
            'senha' => 'senha-errada',
        ])->assertStatus(429);
    }
}
