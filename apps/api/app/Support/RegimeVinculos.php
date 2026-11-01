<?php

namespace App\Support;

use App\Models\Usuario;

class RegimeVinculos
{
    public static function obrigatorio(): bool
    {
        return (bool) config('vinculos.obrigatorio', false);
    }

    /** Não resolve contexto pelo payload e não recupera o perfil legado após o corte. */
    public static function exigir(Usuario $ator): void
    {
        // Consumidores pedagógicos continuam exigindo contexto escolar/pessoal.
        abort_if(ContextoInterno::presente($ator), 403, 'Selecione um vínculo pedagógico válido.');
        if (self::obrigatorio()) {
            abort_unless(Espacos::ativo() && ContextoVinculo::presente($ator), 403, 'Selecione um vínculo válido.');
        }
        if (ContextoVinculo::presente($ator)) ContextoVinculo::resolver($ator);
    }
}
