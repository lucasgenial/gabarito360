<?php

namespace App\Http\Resources\Api\V2;

use App\Models\LeituraCartao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeituraCartao
 *
 * Espera `aplicacaoAluno.aluno` e `respostasDetectadas.questao` carregados.
 */
class LeituraResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aplicacao_id' => $this->aplicacao_id,
            'status' => $this->status,
            'requer_revisao' => (bool) $this->requer_revisao,
            'confianca_geral' => $this->confianca_geral !== null ? (float) $this->confianca_geral : null,
            'alertas' => $this->alertas ?? [],
            'aluno' => [
                'id' => $this->aplicacaoAluno?->aluno_id,
                'nome' => $this->aplicacaoAluno?->aluno?->nome,
            ],
            'respostas' => $this->respostasDetectadas
                ->sortBy(fn ($resposta) => $resposta->questao?->numero)
                ->map(fn ($resposta): array => [
                    'questao' => $resposta->questao?->numero,
                    'detectada' => $resposta->alternativa_detectada,
                    'final' => $resposta->alternativa_final,
                    'tipo' => $resposta->tipo_deteccao,
                    'confianca' => $resposta->confianca !== null ? (float) $resposta->confianca : null,
                ])
                ->values()
                ->all(),
        ];
    }
}
