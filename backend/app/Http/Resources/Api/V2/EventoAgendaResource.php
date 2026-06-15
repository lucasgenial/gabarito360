<?php

namespace App\Http\Resources\Api\V2;

use App\Models\EventoAgenda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EventoAgenda
 */
class EventoAgendaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'nucleo_id' => $this->nucleo_id,
            'escola_id' => $this->escola_id,
            'turma_id' => $this->turma_id,
            'prova_id' => $this->prova_id,
            'aplicacao_id' => $this->aplicacao_id,
            'local' => $this->local,
            'inicio_at' => $this->inicio_at?->toIso8601String(),
            'fim_at' => $this->fim_at?->toIso8601String(),
            'status' => $this->status,
            'criado_por_id' => $this->criado_por_id,
            'participantes' => $this->whenLoaded('participantes', fn (): array => $this->participantes
                ->map(fn ($p): array => [
                    'usuario_id' => $p->usuario_id,
                    'papel' => $p->papel,
                    'status' => $p->status,
                    'respondido_at' => $p->respondido_at?->toIso8601String(),
                ])
                ->values()
                ->all()),
        ];
    }
}
