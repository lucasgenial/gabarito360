<?php

namespace App\Http\Resources\Api\V2;

use App\Models\AtividadeRecente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AtividadeRecente
 */
class AtividadeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'descricao' => $this->descricao,
            'nucleo_id' => $this->nucleo_id,
            'escola_id' => $this->escola_id,
            'ator_id' => $this->ator_id,
            'sujeito_tipo' => $this->sujeito_tipo,
            'sujeito_id' => $this->sujeito_id,
            'dados' => $this->dados,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
