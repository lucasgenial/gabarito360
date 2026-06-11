<?php

namespace App\Actions\Usuarios;

use App\Models\UsuarioPerfil;
use Illuminate\Support\Facades\DB;

class RevokeUsuarioPerfilAction
{
    public function execute(UsuarioPerfil $link): UsuarioPerfil
    {
        return DB::transaction(function () use ($link): UsuarioPerfil {
            if ($link->fim_at === null) {
                $link->update(['fim_at' => now()]);
            }

            return $link->refresh();
        });
    }
}
