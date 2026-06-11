<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlunoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'escola_id' => $this->resource->escola_id,
            'matricula' => $this->resource->matricula,
            'codigo_interno' => $this->resource->codigo_interno,
            'nome' => $this->resource->nome,
            'status' => $this->resource->status->value,
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
