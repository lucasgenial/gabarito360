<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NucleoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'codigo' => $this->resource->codigo,
            'nome' => $this->resource->nome,
            'municipio' => $this->resource->municipio,
            'estado' => $this->resource->estado,
            'email' => $this->resource->email,
            'telefone' => $this->resource->telefone,
            'status' => $this->resource->status->value,
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
