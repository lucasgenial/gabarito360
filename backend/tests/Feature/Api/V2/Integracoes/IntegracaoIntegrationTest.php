<?php

namespace Tests\Feature\Api\V2\Integracoes;

use App\Enums\UserRole;
use App\Models\Integracao;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class IntegracaoIntegrationTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_list_never_exposes_secrets(): void
    {
        $integracao = Integracao::factory()->conectada()->create(['chave' => 'google_classroom']);
        $integracao->credenciais()->create(['chave' => 'client_secret', 'valor_criptografado' => 'super-secret-value']);

        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $response = $this->withToken($this->bearerToken($admin))->getJson('/api/v2/integracoes');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.chave', 'google_classroom')
            ->assertJsonMissingPath('data.0.credenciais')
            ->assertDontSee('super-secret-value');
    }

    public function test_connect_stores_encrypted_credentials(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $response = $this->withToken($this->bearerToken($admin))
            ->withHeader('Idempotency-Key', 'integ-001')
            ->postJson('/api/v2/integracoes', [
                'chave' => 'google_classroom',
                'credenciais' => ['client_id' => 'abc', 'client_secret' => 'super-secret-value'],
            ]);

        $response->assertCreated()->assertDontSee('super-secret-value');

        $this->assertDatabaseHas('integracoes', ['chave' => 'google_classroom', 'status' => 'conectada']);
        $this->assertDatabaseCount('credenciais_integracoes', 2);
        $this->assertDatabaseMissing('credenciais_integracoes', ['valor_criptografado' => 'super-secret-value']);
    }

    public function test_repeated_idempotency_key_does_not_duplicate(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $token = $this->bearerToken($admin);
        $payload = ['chave' => 'sso_saml', 'credenciais' => ['metadata' => 'x']];

        $this->withToken($token)->withHeader('Idempotency-Key', 'dup')->postJson('/api/v2/integracoes', $payload)->assertCreated();
        $this->withToken($token)->withHeader('Idempotency-Key', 'dup')->postJson('/api/v2/integracoes', $payload)->assertCreated();

        $this->assertDatabaseCount('integracoes', 1);
    }

    public function test_disconnect_removes_credentials_and_soft_deletes(): void
    {
        $integracao = Integracao::factory()->conectada()->create();
        $integracao->credenciais()->create(['chave' => 'token', 'valor_criptografado' => 'abc']);
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->deleteJson("/api/v2/integracoes/{$integracao->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('integracoes', ['id' => $integracao->id]);
        $this->assertDatabaseCount('credenciais_integracoes', 0);
    }

    public function test_testar_returns_ok_when_credentials_exist(): void
    {
        $integracao = Integracao::factory()->create();
        $integracao->credenciais()->create(['chave' => 'token', 'valor_criptografado' => 'abc']);
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->postJson("/api/v2/integracoes/{$integracao->id}/testar")
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');
    }

    public function test_testar_returns_failure_without_credentials(): void
    {
        $integracao = Integracao::factory()->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->postJson("/api/v2/integracoes/{$integracao->id}/testar")
            ->assertOk()
            ->assertJsonPath('data.status', 'falha');
    }

    public function test_non_admin_cannot_access_integrations(): void
    {
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER);

        $this->withToken($this->bearerToken($gestor))
            ->getJson('/api/v2/integracoes')
            ->assertForbidden();
    }
}
