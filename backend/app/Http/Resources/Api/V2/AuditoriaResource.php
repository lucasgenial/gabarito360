<?php

namespace App\Http\Resources\Api\V2;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Auditoria
 */
class AuditoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_id' => $this->request_id,
            'acao' => $this->acao,
            'entidade_tipo' => $this->entidade_tipo,
            'entidade_id' => $this->entidade_id,
            'usuario_id' => $this->usuario_id,
            'nucleo_id' => $this->nucleo_id,
            'escola_id' => $this->escola_id,
            'dados_anteriores' => $this->dados_anteriores,
            'dados_novos' => $this->dados_novos,
            'metadados' => $this->metadados,
            'ip' => $this->ip,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
