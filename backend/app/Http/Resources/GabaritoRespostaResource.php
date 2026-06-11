<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GabaritoRespostaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'prova_id' => $this->resource->prova_id,
            'gabarito_oficial_id' => $this->resource->gabarito_oficial_id,
            'questao_id' => $this->resource->questao_id,
            'numero_questao' => $this->whenLoaded('questao', fn (): int => $this->resource->questao->numero),
            'alternativa_correta' => $this->resource->alternativa_correta,
            'anulada' => $this->resource->anulada,
            'peso' => $this->resource->peso,
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }
}
