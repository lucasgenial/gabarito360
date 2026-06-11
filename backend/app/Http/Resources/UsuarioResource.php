<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class UsuarioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'nome' => $this->resource->nome,
            'email' => $this->resource->email,
            'documento_mascarado' => $this->maskedDocument(),
            'telefone' => $this->resource->telefone,
            'status' => $this->resource->status->value,
            'ultimo_acesso_at' => $this->resource->ultimo_acesso_at?->toAtomString(),
            'perfis' => UsuarioPerfilResource::collection($this->whenLoaded('perfilVinculos'))->resolve($request),
            'created_at' => $this->resource->created_at?->toAtomString(),
            'updated_at' => $this->resource->updated_at?->toAtomString(),
        ];
    }

    private function maskedDocument(): ?string
    {
        $document = $this->resource->documento;

        if (! is_string($document) || $document === '') {
            return null;
        }

        if (mb_strlen($document) <= 4) {
            return Str::repeat('*', mb_strlen($document));
        }

        return Str::repeat('*', mb_strlen($document) - 4).mb_substr($document, -4);
    }
}
