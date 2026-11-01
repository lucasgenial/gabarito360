<?php

namespace App\Support;

use App\Models\Usuario;

/** Ator e destino explícitos para escritas operacionais; nunca infere administrador. */
class ContextoImportacao
{
    public static function resolver(int $escola, int $ator, int $espaco, array $permissoes): array
    {
        if (!Espacos::ativo()) return [];
        $u = Usuario::find($ator);
        abort_unless($u && $u->ativo && $espaco > 0, 422, 'Informe ator ativo e espaço de destino.');
        Permissao::limparCache();
        foreach ($permissoes as $p) abort_unless(Permissao::pode($u, $p), 403, 'Ator sem permissão para importar.');
        abort_unless(Espacos::daEscola($u, $escola) === $espaco, 404, 'Destino não autorizado.');
        return Espacos::autoria($espaco, $u);
    }
}
