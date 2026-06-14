<?php

namespace Tests\Feature\Api\V2\Me;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Resources\Api\V2\UsuarioResource;
use App\Models\Perfil;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class MeShowTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_me_returns_contract_shape(): void
    {
        $user = $this->userWithRole(UserRole::ADMINISTRATOR, attributes: [
            'documento' => '12345678909',
        ]);
        $perfil = Perfil::query()->where('codigo', UserRole::ADMINISTRATOR->value)->firstOrFail();

        $response = $this->withToken($this->bearerToken($user))->getJson('/api/v2/me');

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.nome', $user->nome)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.cpf', '12345678909')
            ->assertJsonPath('data.perfil', $perfil->nome)
            ->assertJsonPath('data.escopo', 'global')
            ->assertJsonPath('data.foto_url', null)
            ->assertJsonPath('data.status', 'ativo');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v2/me')->assertUnauthorized();
    }

    public function test_blocked_status_maps_to_suspenso(): void
    {
        $user = User::factory()->create(['status' => UserStatus::BLOCKED]);

        $array = (new UsuarioResource($user))->toArray(request());

        $this->assertSame('suspenso', $array['status']);
    }
}
