<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AplicadorTurmaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'turma_id' => $this->resource->turma_id,
            'usuario_id' => $this->resource->usuario_id,
            'papel' => $this->resource->papel->value,
            'inicio_em' => $this->resource->inicio_em?->toDateString(),
            'fim_em' => $this->resource->fim_em?->toDateString(),
            'vinculado_por' => $this->resource->vinculado_por,
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
