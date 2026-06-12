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
            'nome_social' => $this->resource->nome_social,
            'foto_arquivo_id' => $this->resource->foto_arquivo_id,
            'status' => $this->resource->status->value,
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
