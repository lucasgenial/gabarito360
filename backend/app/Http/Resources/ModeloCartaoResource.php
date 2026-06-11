<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModeloCartaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'nucleo_id' => $this->resource->nucleo_id,
            'nome' => $this->resource->nome,
            'versao' => $this->resource->versao,
            'quantidade_questoes' => $this->resource->quantidade_questoes,
            'quantidade_alternativas' => $this->resource->quantidade_alternativas,
            'alternativas' => $this->resource->alternativas,
            'tipo_codigo' => $this->resource->tipo_codigo->value,
            'origem_codigo' => $this->resource->origem_codigo->value,
            'configuracao_omr' => $this->resource->configuracao_omr,
            'artefato_checksum_sha256' => $this->resource->artefato_checksum_sha256,
            'status' => $this->resource->status->value,
            'criado_por' => $this->resource->criado_por,
            'homologado_por' => $this->resource->homologado_por,
            'homologado_at' => $this->resource->homologado_at?->toAtomString(),
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
