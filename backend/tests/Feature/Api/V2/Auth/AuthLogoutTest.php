<?php

namespace Tests\Feature\Api\V2\Auth;

use App\Models\SessaoUsuario;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class AuthLogoutTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_logout_revokes_token_and_closes_session(): void
    {
        $user = $this->userWithRole();
        $token = $user->createToken('api', ['api']);
        $tokenId = $token->accessToken->id;

        SessaoUsuario::factory()->create([
            'usuario_id' => $user->id,
            'personal_access_token_id' => $tokenId,
        ]);

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v2/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
        $this->assertDatabaseMissing('sessoes_usuarios', [
            'usuario_id' => $user->id,
            'encerrado_at' => null,
        ]);
        $this->assertDatabaseHas('historicos_acesso', [
            'usuario_id' => $user->id,
            'evento' => 'logout',
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $user->id,
            'acao' => 'auth.logout',
        ]);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v2/auth/logout')->assertUnauthorized();
    }
}
