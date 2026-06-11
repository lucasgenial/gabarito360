<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'nucleo_id' => $this->resource->nucleo_id,
            'escola_id' => $this->resource->escola_id,
            'modelo_cartao_id' => $this->resource->modelo_cartao_id,
            'codigo' => $this->resource->codigo,
            'titulo' => $this->resource->titulo,
            'descricao' => $this->resource->descricao,
            'tipo' => $this->resource->tipo,
            'nivel' => $this->resource->nivel,
            'ano_referencia' => $this->resource->ano_referencia,
            'quantidade_questoes' => $this->resource->quantidade_questoes,
            'quantidade_alternativas' => $this->resource->quantidade_alternativas,
            'alternativas' => $this->resource->alternativas,
            'status' => $this->resource->status->value,
            'criado_por' => $this->resource->criado_por,
            'publicada_at' => $this->resource->publicada_at?->toAtomString(),
            'finalizada_at' => $this->resource->finalizada_at?->toAtomString(),
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
