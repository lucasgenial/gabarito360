<?php

namespace App\Support;

use App\Models\Usuario;

final class AutoriaProva
{
    /** Perfil selecionado, nunca a função atual consultada posteriormente na conta do autor. */
    public static function capturar(Usuario $u): array
    {
        $v = ContextoVinculo::presente($u) ? ContextoVinculo::resolver($u) : null;
        return ['perfil_criacao' => $v?->perfil ?? $u->perfil, 'vinculo_criacao_id' => $v?->id, 'autoria_origem' => 'registrada'];
    }
}
