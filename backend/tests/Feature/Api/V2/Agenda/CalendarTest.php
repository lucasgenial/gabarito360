<?php

namespace Tests\Feature\Api\V2\Agenda;

use App\Enums\UserRole;
use App\Events\CalendarEventChanged;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    private Nucleo $nucleo;

    private Escola $escola;

    private User $gestor;

    private User $participante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->nucleo = Nucleo::factory()->create();
        $this->escola = Escola::factory()->create(['nucleo_id' => $this->nucleo->id]);
        $this->gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $this->nucleo->id);
        $this->participante = $this->userWithRole(UserRole::APPLICATOR, nucleoId: $this->nucleo->id);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'tipo' => 'reuniao',
            'titulo' => 'Reunião de acompanhamento',
            'escola_id' => $this->escola->id,
            'inicio_at' => '2026-06-20T14:00:00+00:00',
            'fim_at' => '2026-06-20T15:00:00+00:00',
            'participantes' => [$this->participante->id],
        ], $overrides);
    }

    public function test_criar_evento_dispara_calendar_event_changed(): void
    {
        Event::fake([CalendarEventChanged::class]);

        $response = $this->actingAsToken($this->gestor)
            ->postJson('/api/v2/agenda', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.titulo', 'Reunião de acompanhamento')
            ->assertJsonPath('data.escola_id', $this->escola->id)
            ->assertJsonPath('data.nucleo_id', $this->nucleo->id)
            ->assertJsonCount(2, 'data.participantes');

        Event::assertDispatched(
            CalendarEventChanged::class,
            fn (CalendarEventChanged $e): bool => $e->acao === 'created' && $e->evento->escola_id === $this->escola->id,
        );
    }

    public function test_lista_agenda_respeita_escopo(): void
    {
        $this->actingAsToken($this->gestor)->postJson('/api/v2/agenda', $this->payload())->assertCreated();

        $this->actingAsToken($this->gestor)
            ->getJson('/api/v2/agenda')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);
        $this->actingAsToken($outro)
            ->getJson('/api/v2/agenda')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_criar_evento_fora_do_escopo_retorna_403(): void
    {
        $outro = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);

        $this->actingAsToken($outro)
            ->postJson('/api/v2/agenda', $this->payload())
            ->assertForbidden();
    }

    public function test_participante_confirma_presenca(): void
    {
        $eventoId = $this->actingAsToken($this->gestor)
            ->postJson('/api/v2/agenda', $this->payload())
            ->json('data.id');

        $this->actingAsToken($this->participante)
            ->postJson("/api/v2/agenda/{$eventoId}/confirmar")
            ->assertOk();

        $this->assertDatabaseHas('participantes_eventos', [
            'evento_id' => $eventoId,
            'usuario_id' => $this->participante->id,
            'status' => 'confirmado',
        ]);
    }

    public function test_confirmar_sem_ser_participante_retorna_403(): void
    {
        $eventoId = $this->actingAsToken($this->gestor)
            ->postJson('/api/v2/agenda', $this->payload(['participantes' => []]))
            ->json('data.id');

        $estranho = $this->userWithRole(UserRole::APPLICATOR, nucleoId: $this->nucleo->id);

        $this->actingAsToken($estranho)
            ->postJson("/api/v2/agenda/{$eventoId}/confirmar")
            ->assertForbidden();
    }

    public function test_atualizar_evento_dispara_calendar_event_changed(): void
    {
        $eventoId = $this->actingAsToken($this->gestor)
            ->postJson('/api/v2/agenda', $this->payload())
            ->json('data.id');

        Event::fake([CalendarEventChanged::class]);

        $this->actingAsToken($this->gestor)
            ->putJson("/api/v2/agenda/{$eventoId}", ['status' => 'cancelado'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelado');

        Event::assertDispatched(
            CalendarEventChanged::class,
            fn (CalendarEventChanged $e): bool => $e->acao === 'cancelled',
        );
    }
}
