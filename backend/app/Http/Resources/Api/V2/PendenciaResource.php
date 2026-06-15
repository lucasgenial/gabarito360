<?php

namespace App\Http\Resources\Api\V2;

use App\Models\LeituraCartao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeituraCartao
 *
 * Pendência de revisão: a leitura mais as questões que exigem decisão manual
 * (ambígua, dupla marcação ou em branco). Espera `aplicacaoAluno.aluno`,
 * `aplicacao.turma` e `respostasDetectadas.questao` carregados.
 */
class PendenciaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'leitura_id' => $this->id,
            'aplicacao_id' => $this->aplicacao_id,
            'status' => $this->status,
            'confianca_geral' => $this->confianca_geral !== null ? (float) $this->confianca_geral : null,
            'alertas' => $this->alertas ?? [],
            'aluno' => [
                'id' => $this->aplicacaoAluno?->aluno_id,
                'nome' => $this->aplicacaoAluno?->aluno?->nome,
            ],
            'turma' => [
                'id' => $this->aplicacao?->turma_id,
                'nome' => $this->aplicacao?->turma?->nome,
            ],
            'questoes_pendentes' => $this->respostasDetectadas
                ->filter(fn ($resposta): bool => in_array($resposta->tipo_deteccao, ['ambigua', 'dupla', 'branco'], true))
                ->sortBy(fn ($resposta) => $resposta->questao?->numero)
                ->map(fn ($resposta): array => [
                    'questao' => $resposta->questao?->numero,
                    'tipo' => $resposta->tipo_deteccao,
                    'detectada' => $resposta->alternativa_detectada,
                    'confianca' => $resposta->confianca !== null ? (float) $resposta->confianca : null,
                ])
                ->values()
                ->all(),
        ];
    }
}
