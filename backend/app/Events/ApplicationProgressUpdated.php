<?php

namespace App\Events;

use App\Models\Aplicacao;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationProgressUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /** @param array<string, int|string> $metrics */
    public function __construct(
        public Aplicacao $aplicacao,
        public array $metrics,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('applications.'.$this->aplicacao->id)];
    }

    public function broadcastAs(): string
    {
        return 'application.progress.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'application_id' => $this->aplicacao->id,
            'metrics' => $this->metrics,
            'updated_at' => now()->toAtomString(),
        ];
    }
}
