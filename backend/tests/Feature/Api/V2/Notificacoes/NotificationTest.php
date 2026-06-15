<?php

namespace Tests\Feature\Api\V2\Notificacoes;

use App\Enums\UserRole;
use App\Events\NotificationCreated;
use App\Models\PreferenciaNotificacao;
use App\Models\User;
use App\Services\Notificacoes\NotificationService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    private function notificar(User $user, string $tipo = 'report.ready', string $titulo = 'Relatório pronto'): void
    {
        app(NotificationService::class)->notify($user, $tipo, $titulo, 'Conteúdo da notificação.');
    }

    public function test_criar_notificacao_dispara_evento_e_aparece_na_lista(): void
    {
        Event::fake([NotificationCreated::class]);
        $user = $this->userWithRole(UserRole::EDUCATION_MANAGER);

        $this->notificar($user);

        Event::assertDispatched(NotificationCreated::class);

        $this->actingAsToken($user)
            ->getJson('/api/v2/notificacoes')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.nao_lidas', 1)
            ->assertJsonPath('data.0.lida', false)
            ->assertJsonStructure(['data' => [['id', 'tipo', 'titulo', 'mensagem', 'lida']]]);
    }

    public function test_marcar_uma_notificacao_como_lida(): void
    {
        $user = $this->userWithRole(UserRole::EDUCATION_MANAGER);
        $this->notificar($user);

        $id = $this->actingAsToken($user)->getJson('/api/v2/notificacoes')->json('data.0.id');

        $this->actingAsToken($user)
            ->postJson("/api/v2/notificacoes/{$id}/ler")
            ->assertOk()
            ->assertJsonPath('data.lida', true);

        $this->actingAsToken($user)
            ->getJson('/api/v2/notificacoes')
            ->assertOk()
            ->assertJsonPath('meta.nao_lidas', 0);
    }

    public function test_marcar_todas_como_lidas(): void
    {
        $user = $this->userWithRole(UserRole::EDUCATION_MANAGER);
        $this->notificar($user);
        $this->notificar($user, 'result.calculated', 'Resultado calculado');

        $this->actingAsToken($user)
            ->postJson('/api/v2/notificacoes/ler-todas')
            ->assertOk()
            ->assertJsonPath('data.marcadas', 2);

        $this->actingAsToken($user)
            ->getJson('/api/v2/notificacoes?nao_lidas=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_notificacoes_sao_escopadas_ao_usuario(): void
    {
        $dono = $this->userWithRole(UserRole::EDUCATION_MANAGER);
        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER);
        $this->notificar($dono);

        $this->actingAsToken($outro)
            ->getJson('/api/v2/notificacoes')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_marcar_lida_de_outro_usuario_e_proibido(): void
    {
        $dono = $this->userWithRole(UserRole::EDUCATION_MANAGER);
        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER);
        $this->notificar($dono);
        $id = $this->actingAsToken($dono)->getJson('/api/v2/notificacoes')->json('data.0.id');

        $this->actingAsToken($outro)
            ->postJson("/api/v2/notificacoes/{$id}/ler")
            ->assertForbidden();
    }

    public function test_preferencia_desabilitada_suprime_notificacao(): void
    {
        $user = $this->userWithRole(UserRole::EDUCATION_MANAGER);
        PreferenciaNotificacao::query()->create([
            'usuario_id' => $user->id,
            'evento' => 'report.ready',
            'canal' => 'sistema',
            'habilitada' => false,
        ]);

        $this->notificar($user);

        $this->actingAsToken($user)
            ->getJson('/api/v2/notificacoes')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_atualizar_preferencias_de_notificacao(): void
    {
        $user = $this->userWithRole(UserRole::EDUCATION_MANAGER);

        $this->actingAsToken($user)
            ->putJson('/api/v2/notificacoes/preferencias', [
                'preferencias' => [
                    ['evento' => 'report.ready', 'canal' => 'email', 'habilitada' => false],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.evento', 'report.ready')
            ->assertJsonPath('data.0.canal', 'email')
            ->assertJsonPath('data.0.habilitada', false);

        $this->assertDatabaseHas('preferencias_notificacao', [
            'usuario_id' => $user->id,
            'evento' => 'report.ready',
            'canal' => 'email',
            'habilitada' => false,
        ]);
    }
}
