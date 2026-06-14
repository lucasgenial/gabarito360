<?php

namespace App\Http\Resources\Api\V2;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 *
 * Espera `perfilVinculos` carregado já restrito ao escopo da escola (vigente)
 * com `perfil`, para expor o perfil/escopo do membro NAQUELA escola.
 */
class MembroResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $link = $this->relationLoaded('perfilVinculos')
            ? $this->perfilVinculos->first()
            : null;

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'cpf' => $this->documento,
            'perfil' => $link?->perfil?->nome,
            'escopo' => $link?->perfil?->escopo_permitido?->value,
            'foto_url' => null,
            'status' => $this->mapStatus($this->status),
        ];
    }

    private function mapStatus(UserStatus $status): string
    {
        return match ($status) {
            UserStatus::ACTIVE => 'ativo',
            UserStatus::INACTIVE => 'inativo',
            UserStatus::BLOCKED => 'suspenso',
        };
    }
}
