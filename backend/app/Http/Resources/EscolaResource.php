<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EscolaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'nucleo_id' => $this->resource->nucleo_id,
            'codigo' => $this->resource->codigo,
            'nome' => $this->resource->nome,
            'municipio' => $this->resource->municipio,
            'estado' => $this->resource->estado,
            'endereco' => $this->resource->endereco,
            'email' => $this->resource->email,
            'telefone' => $this->resource->telefone,
            'status' => $this->resource->status->value,
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
