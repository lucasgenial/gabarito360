<?php

namespace Tests\Feature\Api\V2\Me;

use App\Models\SessaoUsuario;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class MePasswordTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_password_change_revokes_tokens_and_sessions(): void
    {
        $user = $this->userWithRole(attributes: ['password' => 'SenhaAtual@1']);
        SessaoUsuario::factory()->create(['usuario_id' => $user->id]);

        $this->withToken($this->bearerToken($user))
            ->putJson('/api/v2/me/senha', [
                'senha_atual' => 'SenhaAtual@1',
                'senha' => 'SenhaNova@12345',
            ])
            ->assertNoContent();

        $this->assertTrue(Hash::check('SenhaNova@12345', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseMissing('sessoes_usuarios', [
            'usuario_id' => $user->id,
            'encerrado_at' => null,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $user->id,
            'acao' => 'usuario.password_changed',
        ]);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->userWithRole(attributes: ['password' => 'SenhaAtual@1']);

        $this->withToken($this->bearerToken($user))
            ->putJson('/api/v2/me/senha', [
                'senha_atual' => 'errada',
                'senha' => 'SenhaNova@12345',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['senha_atual']);
    }

    public function test_new_password_must_meet_minimum_length(): void
    {
        $user = $this->userWithRole(attributes: ['password' => 'SenhaAtual@1']);

        $this->withToken($this->bearerToken($user))
            ->putJson('/api/v2/me/senha', [
                'senha_atual' => 'SenhaAtual@1',
                'senha' => 'curta',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['senha']);
    }
}
