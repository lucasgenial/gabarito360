<?php

namespace Tests\Feature\Api\V2\Me;

use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class MePreferenciasTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_dark_theme_is_persisted_and_mapped(): void
    {
        $user = $this->userWithRole();

        $this->withToken($this->bearerToken($user))
            ->patchJson('/api/v2/me/preferencias', ['tema' => 'dark'])
            ->assertOk()
            ->assertJsonPath('data.tema', 'dark');

        $this->assertDatabaseHas('preferencias_usuario', [
            'usuario_id' => $user->id,
            'tema' => 'escuro',
            'tema_sistema' => false,
        ]);
    }

    public function test_contrast_theme_sets_high_contrast(): void
    {
        $user = $this->userWithRole();

        $this->withToken($this->bearerToken($user))
            ->patchJson('/api/v2/me/preferencias', ['tema' => 'contrast'])
            ->assertOk()
            ->assertJsonPath('data.tema', 'contrast');

        $this->assertDatabaseHas('preferencias_usuario', [
            'usuario_id' => $user->id,
            'contraste_alto' => true,
        ]);
    }

    public function test_system_theme_is_flagged(): void
    {
        $user = $this->userWithRole();

        $this->withToken($this->bearerToken($user))
            ->patchJson('/api/v2/me/preferencias', ['tema' => 'system'])
            ->assertOk()
            ->assertJsonPath('data.tema', 'system');

        $this->assertDatabaseHas('preferencias_usuario', [
            'usuario_id' => $user->id,
            'tema_sistema' => true,
        ]);
    }

    public function test_accessibility_and_notifications_are_stored(): void
    {
        $user = $this->userWithRole();

        $this->withToken($this->bearerToken($user))
            ->patchJson('/api/v2/me/preferencias', [
                'idioma' => 'pt-BR',
                'regiao' => 'America/Sao_Paulo',
                'acessibilidade' => ['reduzir_movimento' => true],
                'notificacoes' => ['resultado_prova' => true],
            ])
            ->assertOk()
            ->assertJsonPath('data.acessibilidade.reduzir_movimento', true)
            ->assertJsonPath('data.notificacoes.resultado_prova', true)
            ->assertJsonPath('data.regiao', 'America/Sao_Paulo');
    }

    public function test_invalid_theme_is_rejected(): void
    {
        $user = $this->userWithRole();

        $this->withToken($this->bearerToken($user))
            ->patchJson('/api/v2/me/preferencias', ['tema' => 'roxo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tema']);
    }
}
