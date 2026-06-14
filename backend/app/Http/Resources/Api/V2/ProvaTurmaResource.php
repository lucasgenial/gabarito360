<?php

namespace App\Http\Resources\Api\V2;

use App\Models\ProvaTurma;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProvaTurma
 */
class ProvaTurmaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'turma_id' => $this->turma_id,
            'turma' => $this->turma?->nome,
            'data_prevista' => $this->data_prevista?->toDateString(),
            'status' => $this->status,
        ];
    }
}
