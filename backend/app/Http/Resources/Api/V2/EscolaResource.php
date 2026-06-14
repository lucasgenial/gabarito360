<?php

namespace App\Http\Resources\Api\V2;

use App\Enums\StatusEnum;
use App\Models\Escola;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Escola
 */
class EscolaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'inep' => $this->inep,
            'rede' => $this->rede,
            'endereco' => $this->endereco_texto,
            'cidade' => $this->municipio,
            'uf' => $this->estado,
            'telefone' => $this->telefone,
            'email' => $this->email,
            'diretor' => $this->diretor,
            'status' => $this->status === StatusEnum::ACTIVE ? 'ativa' : 'inativa',
        ];
    }
}
