<?php

namespace Tests\Feature\Api\V2\Me;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class MeUpdateTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_user_can_update_own_profile(): void
    {
        $user = $this->userWithRole();

        $this->withToken($this->bearerToken($user))
            ->putJson('/api/v2/me', ['nome' => 'Nome Atualizado', 'telefone' => '(11) 99999-0000'])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Nome Atualizado');

        $this->assertDatabaseHas('usuarios', [
            'id' => $user->id,
            'nome' => 'Nome Atualizado',
            'telefone' => '(11) 99999-0000',
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $user->id,
            'acao' => 'usuario.updated',
        ]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'ocupado@escola.test']);
        $user = $this->userWithRole();

        $this->withToken($this->bearerToken($user))
            ->putJson('/api/v2/me', ['email' => 'ocupado@escola.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_requires_authentication(): void
    {
        $this->putJson('/api/v2/me', ['nome' => 'X'])->assertUnauthorized();
    }
}
