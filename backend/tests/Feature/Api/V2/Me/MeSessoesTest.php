<?php

namespace Tests\Feature\Api\V2\Me;

use App\Models\SessaoUsuario;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class MeSessoesTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_lists_only_active_sessions_with_masked_token(): void
    {
        $user = $this->userWithRole();
        SessaoUsuario::factory()->count(2)->create(['usuario_id' => $user->id]);
        SessaoUsuario::factory()->encerrada()->create(['usuario_id' => $user->id]);

        $response = $this->withToken($this->bearerToken($user))->getJson('/api/v2/me/sessoes');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.token', '');
    }

    public function test_user_can_revoke_own_session(): void
    {
        $user = $this->userWithRole();
        $token = $user->createToken('api', ['api']);
        $sessao = SessaoUsuario::factory()->create([
            'usuario_id' => $user->id,
            'personal_access_token_id' => $token->accessToken->id,
        ]);

        $this->withToken($this->bearerToken($user))
            ->deleteJson("/api/v2/me/sessoes/{$sessao->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertNotNull($sessao->fresh()->encerrado_at);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $user->id,
            'acao' => 'auth.session.revoked',
        ]);
    }

    public function test_user_cannot_revoke_another_users_session(): void
    {
        $user = $this->userWithRole();
        $outro = $this->userWithRole();
        $sessao = SessaoUsuario::factory()->create(['usuario_id' => $outro->id]);

        $this->withToken($this->bearerToken($user))
            ->deleteJson("/api/v2/me/sessoes/{$sessao->id}")
            ->assertNotFound();

        $this->assertNull($sessao->fresh()->encerrado_at);
    }

    public function test_sessions_require_authentication(): void
    {
        $this->getJson('/api/v2/me/sessoes')->assertUnauthorized();
    }
}
