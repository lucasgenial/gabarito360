<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeituraCartaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aplicacao_id' => $this->aplicacao_id,
            'aplicacao_aluno_id' => $this->aplicacao_aluno_id,
            'cartao_resposta_id' => $this->cartao_resposta_id,
            'operacao_id' => $this->operacao_id,
            'status' => $this->status,
            'codigo_impresso_detectado' => $this->codigo_impresso_detectado,
            'codigo_sistema_proposto' => $this->codigo_sistema_proposto,
            'omr_versao' => $this->omr_versao,
            'confianca_geral' => $this->confianca_geral,
            'requer_revisao' => $this->requer_revisao,
            'alertas' => $this->alertas,
            'respostas' => RespostaDetectadaResource::collection($this->whenLoaded('respostasDetectadas')),
            'revisada_at' => $this->revisada_at?->toAtomString(),
            'confirmada_at' => $this->confirmada_at?->toAtomString(),
        ];
    }
}
