<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TurmaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'escola_id' => $this->resource->escola_id,
            'periodo_letivo_id' => $this->resource->periodo_letivo_id,
            'serie_ano_id' => $this->resource->serie_ano_id,
            'codigo' => $this->resource->codigo,
            'nome' => $this->resource->nome,
            'serie_ano' => $this->resource->serie_ano,
            'turno' => $this->resource->turno,
            'capacidade' => $this->resource->capacidade,
            'ano_letivo' => $this->resource->ano_letivo,
            'status' => $this->resource->status->value,
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
