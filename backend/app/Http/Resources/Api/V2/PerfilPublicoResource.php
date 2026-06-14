<?php

namespace App\Http\Resources\Api\V2;

use App\Enums\PermissionCode;
use App\Models\Perfil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Perfil
 */
class PerfilPublicoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $atribuidas = $this->permissoes->pluck('codigo');

        return [
            'chave' => $this->codigo,
            'nome' => $this->nome,
            'fixo' => (bool) $this->sistema,
            'membros' => (int) ($this->membros_count ?? 0),
            'permissoes' => array_map(
                fn (PermissionCode $code): array => [
                    'chave' => $code->value,
                    'permitido' => $atribuidas->contains($code->value),
                    'fixo' => true,
                ],
                PermissionCode::cases(),
            ),
        ];
    }
}
