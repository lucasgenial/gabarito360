<?php

namespace App\Http\Resources\Api\V2;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\Authorization\PrimaryRoleResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UsuarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $link = PrimaryRoleResolver::resolve($this->resource);

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'cpf' => $this->documento,
            'perfil' => $link?->perfil?->nome,
            'escopo' => $link?->perfil?->escopo_permitido?->value,
            'foto_url' => $this->foto_arquivo_id !== null
                ? route('api.v2.me.foto.show')
                : null,
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
