<?php

namespace App\Http\Resources\Api\V2;

use App\Models\Exportacao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Exportacao
 */
class ExportacaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'formato' => $this->formato,
            'status' => $this->status,
            'prova_id' => $this->prova_id,
            'turma_id' => $this->turma_id,
            'filtros' => $this->filtros,
            'escopo' => $this->escopo,
            'linhas' => $this->linhas,
            'solicitado_at' => $this->solicitado_at?->toIso8601String(),
            'concluido_at' => $this->concluido_at?->toIso8601String(),
            'expira_at' => $this->expira_at?->toIso8601String(),
            'erro_codigo' => $this->erro_codigo,
            'arquivo' => $this->arquivo_id ? [
                'id' => $this->arquivo_id,
                'nome' => $this->arquivo?->nome_original,
                'tamanho_bytes' => $this->arquivo?->tamanho_bytes,
            ] : null,
        ];
    }
}
