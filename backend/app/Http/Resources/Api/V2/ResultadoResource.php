<?php

namespace App\Http\Resources\Api\V2;

use App\Models\Resultado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Resultado
 *
 * Espera `aluno` e opcionalmente `questoes.questao` carregados.
 */
class ResultadoResource extends JsonResource
{
    private bool $includeQuestoes = false;

    public function withQuestoes(): self
    {
        $this->includeQuestoes = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'aplicacao_id' => $this->aplicacao_id,
            'aluno' => [
                'id' => $this->aluno_id,
                'nome' => $this->aluno?->nome_social ?: $this->aluno?->nome,
                'matricula' => $this->aluno?->matricula,
            ],
            'prova_id' => $this->prova_id,
            'versao' => $this->versao,
            'status' => $this->status,
            'acertos' => $this->acertos,
            'erros' => $this->erros,
            'brancos' => $this->brancos,
            'duplas' => $this->duplas,
            'anuladas' => $this->anuladas,
            'pontuacao' => (float) $this->pontuacao,
            'nota_percentual' => (float) $this->nota_percentual,
            'calculado_at' => $this->calculado_at?->toIso8601String(),
        ];

        if ($this->includeQuestoes) {
            $data['questoes'] = $this->whenLoaded('questoes', function () {
                return $this->questoes
                    ->sortBy(fn ($q) => $q->questao?->numero)
                    ->map(fn ($q): array => [
                        'numero' => $q->questao?->numero,
                        'questao_id' => $q->questao_id,
                        'resposta_final' => $q->resposta_final,
                        'situacao' => $q->situacao,
                        'pontuacao' => (float) $q->pontuacao,
                    ])
                    ->values()
                    ->all();
            });
        }

        return $data;
    }
}
