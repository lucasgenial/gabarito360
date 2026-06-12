<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AplicacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prova_id' => $this->prova_id,
            'turma_id' => $this->turma_id,
            'escola_id' => $this->escola_id,
            'gabarito_oficial_id' => $this->gabarito_oficial_id,
            'titulo' => $this->titulo,
            'status' => $this->status,
            'inicio_previsto_at' => $this->inicio_previsto_at?->toAtomString(),
            'fim_previsto_at' => $this->fim_previsto_at?->toAtomString(),
            'iniciada_at' => $this->iniciada_at?->toAtomString(),
            'finalizada_at' => $this->finalizada_at?->toAtomString(),
            'alunos_count' => $this->whenCounted('alunos'),
            'leituras_count' => $this->whenCounted('leituras'),
            'resultados_count' => $this->whenCounted('resultados'),
        ];
    }
}
