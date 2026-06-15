<?php

namespace App\Events;

use App\Models\LeituraCartao;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReadingReviewRequired implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public LeituraCartao $reading) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('applications.'.$this->reading->aplicacao_id)];
    }

    public function broadcastAs(): string
    {
        return 'reading.review.required';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'reading_id' => $this->reading->id,
            'application_id' => $this->reading->aplicacao_id,
            'application_student_id' => $this->reading->aplicacao_aluno_id,
            'alerts' => $this->reading->alertas,
            'created_at' => now()->toAtomString(),
        ];
    }
}
