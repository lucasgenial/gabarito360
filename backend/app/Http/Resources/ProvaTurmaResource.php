<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvaTurmaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'prova_id' => $this->resource->prova_id,
            'turma_id' => $this->resource->turma_id,
            'data_prevista' => $this->resource->data_prevista?->toDateString(),
            'vinculado_por' => $this->resource->vinculado_por,
            'turma' => $this->whenLoaded(
                'turma',
                fn (): array => TurmaResource::make($this->resource->turma)->resolve($request),
            ),
            'created_at' => $this->resource->created_at?->toAtomString(),
        ];
    }
}
