<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioPerfilResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'perfil_id' => $this->resource->perfil_id,
            'codigo' => $this->resource->perfil->codigo,
            'nome' => $this->resource->perfil->nome,
            'escopo' => $this->resource->perfil->escopo_permitido->value,
            'nucleo_id' => $this->resource->nucleo_id,
            'escola_id' => $this->resource->escola_id,
            'inicio_at' => $this->resource->inicio_at?->toAtomString(),
            'fim_at' => $this->resource->fim_at?->toAtomString(),
        ];
    }
}
