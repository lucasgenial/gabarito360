<?php

namespace Tests\Feature\Api\V2\Onboarding;

use App\Enums\PermissionCode;
use App\Enums\UserRole;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class OnboardingPerfisTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_lists_public_profiles_with_permission_matrix(): void
    {
        $this->userWithRole(UserRole::ADMINISTRATOR);

        $response = $this->getJson('/api/v2/onboarding/perfis');

        $response
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['chave', 'nome', 'fixo', 'membros', 'permissoes' => [['chave', 'permitido', 'fixo']]],
                ],
            ]);

        $this->assertCount(count(PermissionCode::cases()), $response->json('data.0.permissoes'));
        $this->assertTrue($response->json('data.0.fixo'));

        $admin = collect($response->json('data'))
            ->firstWhere('chave', UserRole::ADMINISTRATOR->value);

        $this->assertSame(1, $admin['membros']);
    }
}
