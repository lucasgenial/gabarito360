<?php

namespace App\Http\Resources;

use App\Models\UsuarioPerfil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Collection<int, UsuarioPerfil> $links */
        $links = $this->resource->relationLoaded('perfilVinculos')
            ? $this->resource->perfilVinculos
            : collect();

        $profiles = $links->map(function (UsuarioPerfil $link): array {
            $profile = $link->perfil;

            return [
                'codigo' => $profile->codigo,
                'nome' => $profile->nome,
                'escopo' => $profile->escopo_permitido->value,
                'nucleo_id' => $link->nucleo_id,
                'escola_id' => $link->escola_id,
                'permissoes' => $profile->permissoes
                    ->pluck('codigo')
                    ->sort()
                    ->values()
                    ->all(),
            ];
        })->values();

        return [
            'id' => $this->resource->id,
            'nome' => $this->resource->nome,
            'email' => $this->resource->email,
            'status' => $this->resource->status->value,
            'contexto_autorizado' => [
                'perfis' => $profiles->all(),
                'permissoes' => $profiles
                    ->pluck('permissoes')
                    ->flatten()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
            ],
        ];
    }
}
