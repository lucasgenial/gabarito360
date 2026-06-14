<?php

namespace App\Http\Resources\Api\V2;

use App\Models\SessaoUsuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SessaoUsuario
 */
class SessaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ultimoAcesso = $this->relationLoaded('token') && $this->token?->last_used_at !== null
            ? $this->token->last_used_at
            : $this->ultimo_acesso_at;

        return [
            'id' => $this->id,
            // O token em texto puro só é exposto na resposta de login; nas
            // listagens permanece mascarado por segurança.
            'token' => '',
            'dispositivo' => $this->dispositivo,
            'criado_em' => $this->criado_em?->toAtomString(),
            'ultimo_acesso' => $ultimoAcesso?->toAtomString(),
        ];
    }
}
