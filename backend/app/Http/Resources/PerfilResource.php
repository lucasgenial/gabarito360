<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerfilResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'codigo' => $this->resource->codigo,
            'nome' => $this->resource->nome,
            'descricao' => $this->resource->descricao,
            'escopo' => $this->resource->escopo_permitido->value,
        ];
    }
}
