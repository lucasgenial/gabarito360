<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelatorioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'formato' => $this->formato,
            'status' => $this->status,
            'filtros' => $this->filtros,
            'escopo' => $this->escopo,
            'download_url' => $this->arquivo_id ? route('api.v1.relatorios.download', $this->id) : null,
            'solicitado_at' => $this->solicitado_at?->toAtomString(),
            'concluido_at' => $this->concluido_at?->toAtomString(),
            'expira_at' => $this->expira_at?->toAtomString(),
        ];
    }
}
