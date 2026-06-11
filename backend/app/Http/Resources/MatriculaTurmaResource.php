<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatriculaTurmaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'aluno_id' => $this->resource->aluno_id,
            'turma_id' => $this->resource->turma_id,
            'ano_letivo' => $this->resource->ano_letivo,
            'numero_chamada' => $this->resource->numero_chamada,
            'status' => $this->resource->status->value,
            'inicio_em' => $this->resource->inicio_em?->toDateString(),
            'fim_em' => $this->resource->fim_em?->toDateString(),
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
