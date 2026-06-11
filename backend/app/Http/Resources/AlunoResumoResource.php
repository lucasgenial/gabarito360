<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlunoResumoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'matricula' => $this->resource->matricula,
            'nome' => $this->resource->nome,
            'status' => $this->resource->status->value,
        ];
    }
}
