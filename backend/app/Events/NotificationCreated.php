<?php

namespace App\Events;

use App\Models\Notificacao;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Notificacao $notificacao) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.'.$this->notificacao->usuario_id)];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'notification_id' => $this->notificacao->id,
            'tipo' => $this->notificacao->tipo,
            'titulo' => $this->notificacao->titulo,
            'mensagem' => $this->notificacao->mensagem,
            'link' => $this->notificacao->link,
            'created_at' => $this->notificacao->created_at?->toAtomString(),
        ];
    }
}
