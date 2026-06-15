<?php

namespace App\Http\Resources\Api\V2;

use App\Models\Notificacao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Notificacao
 */
class NotificacaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'mensagem' => $this->mensagem,
            'dados' => $this->dados,
            'link' => $this->link,
            'nucleo_id' => $this->nucleo_id,
            'escola_id' => $this->escola_id,
            'lida' => $this->lida_at !== null,
            'lida_at' => $this->lida_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
