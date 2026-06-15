<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportReady implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /**
     * @param  'relatorio'|'exportacao'  $recurso
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public string $usuarioId,
        public string $recurso,
        public string $recursoId,
        public array $extra = [],
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.'.$this->usuarioId)];
    }

    public function broadcastAs(): string
    {
        return 'report.ready';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'recurso' => $this->recurso,
            'recurso_id' => $this->recursoId,
            ...$this->extra,
            'ready_at' => now()->toAtomString(),
        ];
    }
}
