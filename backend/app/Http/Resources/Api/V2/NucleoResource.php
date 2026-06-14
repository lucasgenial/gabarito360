<?php

namespace App\Http\Resources\Api\V2;

use App\Models\Nucleo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Nucleo
 */
class NucleoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nome' => $this->nome,
            'municipio' => $this->municipio,
            'estado' => $this->estado,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'status' => $this->status->value,
        ];
    }
}
