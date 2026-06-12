<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aplicacao_id' => $this->aplicacao_id,
            'aplicacao_aluno_id' => $this->aplicacao_aluno_id,
            'aluno_id' => $this->aluno_id,
            'prova_id' => $this->prova_id,
            'versao' => $this->versao,
            'status' => $this->status,
            'acertos' => $this->acertos,
            'erros' => $this->erros,
            'brancos' => $this->brancos,
            'duplas' => $this->duplas,
            'anuladas' => $this->anuladas,
            'pontuacao' => $this->pontuacao,
            'nota_percentual' => $this->nota_percentual,
            'calculado_at' => $this->calculado_at?->toAtomString(),
        ];
    }
}
