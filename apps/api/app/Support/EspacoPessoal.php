<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Http\Request;

final class EspacoPessoal
{
    public const VEDADOS = ['cpf', 'cpf_hash', 'data_nascimento', 'genero', 'nome_responsavel', 'foto', 'foto_path'];

    public static function doAtor(Usuario $u): ?int
    {
        if (!ContextoVinculo::presente($u)) return null;
        $v = ContextoVinculo::resolver($u);
        return $v->espaco->tipo === 'pessoal' ? (int) $v->espaco_id : null;
    }

    public static function validarAluno(Request $r): bool
    {
        if (!self::doAtor($r->user())) return false;
        $r->validate(array_fill_keys(self::VEDADOS, 'prohibited'));
        return true;
    }

    public static function dadosMinimos(array $dados): array
    {
        return array_diff_key($dados, array_flip(self::VEDADOS));
    }
}
