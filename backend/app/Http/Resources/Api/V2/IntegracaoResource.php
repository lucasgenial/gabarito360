<?php

namespace App\Http\Resources\Api\V2;

use App\Models\Integracao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Integracao
 *
 * Nunca expõe credenciais (segredos permanecem criptografados em repouso).
 */
class IntegracaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chave' => $this->chave,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'status' => $this->status->value,
            'ultima_execucao' => $this->ultima_execucao?->toAtomString(),
            'ultima_sincronizacao' => $this->ultima_sincronizacao?->toAtomString(),
            'erros' => $this->erros ?? [],
            'ativa' => $this->ativa,
        ];
    }
}
