<?php

namespace App\Events;

use App\Models\Resultado;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResultCalculated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Resultado $resultado) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('applications.'.$this->resultado->aplicacao_id)];
    }

    public function broadcastAs(): string
    {
        return 'result.calculated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'result_id' => $this->resultado->id,
            'application_id' => $this->resultado->aplicacao_id,
            'student_id' => $this->resultado->aluno_id,
            'nota_percentual' => (float) $this->resultado->nota_percentual,
            'calculated_at' => $this->resultado->calculado_at?->toAtomString(),
        ];
    }
}
