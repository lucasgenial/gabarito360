<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'prova_id' => $this->resource->prova_id,
            'numero' => $this->resource->numero,
            'codigo' => $this->resource->codigo,
            'enunciado' => $this->resource->enunciado,
            'peso_padrao' => $this->resource->peso_padrao,
            'metadados' => $this->resource->metadados,
            'status' => $this->resource->status->value,
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
