<?php

namespace App\Http\Resources\Api\V2;

use App\Models\Relatorio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Relatorio
 */
class RelatorioResource extends JsonResource
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
            'filtros' => $this->filtros,
            'escopo' => $this->escopo,
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
