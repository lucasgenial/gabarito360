<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GabaritoOficialResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'prova_id' => $this->resource->prova_id,
            'versao' => $this->resource->versao,
            'status' => $this->resource->status->value,
            'justificativa' => $this->resource->justificativa,
            'criado_por' => $this->resource->criado_por,
            'publicado_por' => $this->resource->publicado_por,
            'publicado_at' => $this->resource->publicado_at?->toAtomString(),
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
