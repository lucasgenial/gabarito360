<?php

namespace App\Support\Authorization;

use App\Enums\AccessScope;
use App\Enums\StatusEnum;
use App\Models\User;
use App\Models\UsuarioPerfil;

/**
 * Resolve o vínculo de perfil "principal" de um usuário: o de maior escopo
 * (global > nucleo > escola > operacional); em empate, o de início mais
 * recente. Usado para achatar `perfil`/`escopo` no schema `Usuario` do
 * contrato V2.
 */
class PrimaryRoleResolver
{
    public static function resolve(User $user): ?UsuarioPerfil
    {
        $links = $user->relationLoaded('perfilVinculos')
            ? $user->perfilVinculos
            : $user->perfilVinculos()
                ->where('inicio_at', '<=', now())
                ->whereNull('fim_at')
                ->whereHas('perfil', fn ($query) => $query->where('status', StatusEnum::ACTIVE->value))
                ->with('perfil')
                ->get();

        return $links
            ->filter(fn (UsuarioPerfil $link): bool => $link->perfil !== null)
            ->sort(function (UsuarioPerfil $a, UsuarioPerfil $b): int {
                $priority = self::priority($a->perfil->escopo_permitido) <=> self::priority($b->perfil->escopo_permitido);

                if ($priority !== 0) {
                    return $priority;
                }

                return $b->inicio_at <=> $a->inicio_at;
            })
            ->first();
    }

    private static function priority(AccessScope $scope): int
    {
        return match ($scope) {
            AccessScope::GLOBAL => 0,
            AccessScope::EDUCATION_CENTER => 1,
            AccessScope::SCHOOL => 2,
            AccessScope::OPERATIONAL => 3,
        };
    }
}
