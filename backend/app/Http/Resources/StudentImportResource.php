<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentImportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'escola_id' => $this->resource->escola_id,
            'turma_id' => $this->resource->turma_id,
            'status' => $this->resource->status->value,
            'arquivo_nome' => $this->resource->arquivo_nome,
            'resumo' => $this->resource->resumo,
            'erros' => $this->resource->erros,
            'confirmado_at' => $this->resource->confirmado_at?->toAtomString(),
            'processado_at' => $this->resource->processado_at?->toAtomString(),
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
