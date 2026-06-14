<?php

namespace App\Http\Resources\Api\V2;

use App\Models\GabaritoOficial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GabaritoOficial
 *
 * Espera `respostas.questao` carregado.
 */
class GabaritoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $respostas = $this->relationLoaded('respostas') ? $this->respostas : collect();

        return [
            'prova_id' => $this->prova_id,
            'versao' => $this->versao,
            'respostas' => $respostas
                ->sortBy(fn ($r): int => $r->questao?->numero ?? 0)
                ->map(fn ($r): array => [
                    'questao' => $r->questao?->numero,
                    'correta' => $r->anulada ? null : $r->alternativa_correta,
                ])
                ->values()
                ->all(),
        ];
    }
}
