<?php

namespace App\Http\Resources\Api\V2;

use App\Enums\MatriculaTurmaStatus;
use App\Models\Turma;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Turma
 */
class TurmaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $alunos = $this->alunos_ativos
            ?? $this->matriculas()->where('status', MatriculaTurmaStatus::ACTIVE->value)->count();

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'serie' => $this->serie_ano,
            'escola_id' => $this->escola_id,
            'periodo_letivo' => (string) $this->ano_letivo,
            'turno' => $this->turno,
            'alunos' => (int) $alunos,
            'desempenho_medio' => 0,
            // Status de desempenho derivado (pendencia>recuperacao>em-dia);
            // sem resultados ainda (B6) → 'em-dia'.
            'status' => 'em-dia',
        ];
    }
}
