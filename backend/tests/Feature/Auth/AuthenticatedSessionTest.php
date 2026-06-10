<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthenticatedSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_requires_a_valid_token_and_returns_only_safe_user_data(): void
    {
        $user = User::factory()->create([
            'documento' => 'dado-pessoal-nao-retornado',
            'telefone' => 'dado-pessoal-nao-retornado',
        ]);
        $token = $user->createToken('teste', ['api'])->plainTextToken;

        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.contexto_autorizado.perfis', [])
            ->assertJsonPath('data.contexto_autorizado.permissoes', [])
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.documento')
            ->assertJsonMissingPath('data.telefone');
    }

    public function test_logout_revokes_only_the_current_token_and_revoked_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('atual', ['api'])->plainTextToken;
        $otherToken = $user->createToken('outro', ['api'])->plainTextToken;

        $this->withToken($currentToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertSame(1, $user->tokens()->count());
        $this->assertNull(PersonalAccessToken::findToken($currentToken));

        Auth::forgetGuards();
        $this->withToken($currentToken)
            ->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');

        Auth::forgetGuards();
        $this->withToken($otherToken)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_existing_token_is_rejected_and_revoked_when_user_becomes_inactive(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('teste', ['api'])->plainTextToken;
        $user->update(['status' => UserStatus::INACTIVE]);

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
