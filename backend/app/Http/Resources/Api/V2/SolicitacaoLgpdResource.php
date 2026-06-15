<?php

namespace App\Http\Resources\Api\V2;

use App\Models\SolicitacaoLgpd;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SolicitacaoLgpd
 */
class SolicitacaoLgpdResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'titular_tipo' => $this->titular_tipo,
            'titular_id' => $this->titular_id,
            'status' => $this->status,
            'descricao' => $this->descricao,
            'decisao' => $this->decisao,
            'solicitante_id' => $this->solicitante_id,
            'prazo_at' => $this->prazo_at?->toDateString(),
            'concluida_at' => $this->concluida_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'execucoes' => $this->whenLoaded('execucoes', fn (): array => $this->execucoes
                ->map(fn ($e): array => [
                    'id' => $e->id,
                    'acao' => $e->acao,
                    'entidade_tipo' => $e->entidade_tipo,
                    'afetados' => $e->afetados,
                    'executado_at' => $e->executado_at?->toIso8601String(),
                ])
                ->values()
                ->all()),
        ];
    }
}
