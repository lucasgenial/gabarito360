<?php

namespace App\Events;

use App\Models\EventoAgenda;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CalendarEventChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /**
     * @param  'created'|'updated'|'cancelled'  $acao
     */
    public function __construct(
        public EventoAgenda $evento,
        public string $acao,
    ) {}

    /**
     * Escopado: transmite no canal da escola e/ou do núcleo do evento.
     *
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->evento->escola_id !== null) {
            $channels[] = new PrivateChannel('escolas.'.$this->evento->escola_id);
        }
        if ($this->evento->nucleo_id !== null) {
            $channels[] = new PrivateChannel('nucleos.'.$this->evento->nucleo_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'calendar.event.changed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->evento->id,
            'acao' => $this->acao,
            'tipo' => $this->evento->tipo,
            'titulo' => $this->evento->titulo,
            'inicio_at' => $this->evento->inicio_at?->toAtomString(),
            'changed_at' => now()->toAtomString(),
        ];
    }
}
