<?php

namespace Tests\Feature\Api\V2\Onboarding;

use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingSolicitacaoTest extends TestCase
{
    use RefreshDatabase;

    private const CPF_VALIDO = '111.444.777-35';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nome' => 'Maria da Silva',
            'cpf' => self::CPF_VALIDO,
            'perfil' => 'professor',
            'email' => 'maria@escola.test',
            'consentimento_lgpd' => true,
        ], $overrides);
    }

    public function test_registers_a_pending_request_without_creating_access(): void
    {
        $this->withHeader('Idempotency-Key', 'onboarding-001')
            ->postJson('/api/v2/onboarding', $this->payload())
            ->assertStatus(202);

        $this->assertDatabaseHas('solicitacoes_cadastro', [
            'email' => 'maria@escola.test',
            'perfil_codigo' => 'professor',
            'status' => 'pendente',
        ]);
        $this->assertDatabaseCount('consentimentos', 1);
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertDatabaseHas('auditorias', ['acao' => 'onboarding.requested']);
    }

    public function test_requires_idempotency_key(): void
    {
        $this->postJson('/api/v2/onboarding', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['idempotency_key']);
    }

    public function test_repeated_idempotency_key_does_not_duplicate(): void
    {
        $this->withHeader('Idempotency-Key', 'onboarding-dup')
            ->postJson('/api/v2/onboarding', $this->payload())
            ->assertStatus(202);

        $this->withHeader('Idempotency-Key', 'onboarding-dup')
            ->postJson('/api/v2/onboarding', $this->payload())
            ->assertStatus(202);

        $this->assertDatabaseCount('solicitacoes_cadastro', 1);
    }

    public function test_requires_lgpd_consent(): void
    {
        $this->withHeader('Idempotency-Key', 'onboarding-002')
            ->postJson('/api/v2/onboarding', $this->payload(['consentimento_lgpd' => false]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['consentimento_lgpd']);
    }

    public function test_rejects_invalid_cpf(): void
    {
        $this->withHeader('Idempotency-Key', 'onboarding-003')
            ->postJson('/api/v2/onboarding', $this->payload(['cpf' => '111.111.111-11']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);
    }

    public function test_rejects_unknown_profile(): void
    {
        $this->withHeader('Idempotency-Key', 'onboarding-004')
            ->postJson('/api/v2/onboarding', $this->payload(['perfil' => 'inexistente']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['perfil']);
    }
}
