<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Arquivo: apps/api/app/Support/Cadastro.php
 * Função: Quem cadastra quem (docs/21, etapa 0). Cada gestor só cria, edita, troca a
 *         senha ou desativa perfis ABAIXO do seu e só dentro do seu escopo — ninguém
 *         dá a outro mais poder do que tem.
 */
class Cadastro
{
    /** Perfis que cada gestor pode cadastrar/gerir. */
    public const PODE_GERIR = [
        'admin_rede'  => ['admin_rede', 'dir_nucleo', 'dir_escolar', 'secretario_escolar', 'coordenador', 'professor', 'aplicador', 'aluno'],
        'dir_nucleo'  => ['dir_escolar', 'secretario_escolar', 'coordenador'],
        'dir_escolar' => ['secretario_escolar', 'coordenador', 'professor', 'aplicador', 'aluno'],
        'coordenador' => ['professor', 'aplicador', 'aluno'],
    ];

    /** Perfis ligados a uma escola (exigem escola no cadastro). */
    public const DE_ESCOLA = PerfisEspaco::POR_TIPO['escola'];

    public static function perfisGeridos(Usuario $gestor): array
    {
        return self::PODE_GERIR[$gestor->perfil] ?? [];
    }

    public static function podeGerirPerfil(Usuario $gestor, string $perfil): bool
    {
        return in_array($perfil, self::perfisGeridos($gestor), true);
    }

    /** Escola dentro do escopo do gestor? (admin: qualquer) */
    public static function escolaNoEscopo(Usuario $gestor, int $escolaId): bool
    {
        $ids = Escopo::escolaIds($gestor);

        return $ids === null || in_array($escolaId, $ids, true);
    }

    /** Núcleo que o gestor pode atribuir (só admin cria diretor de núcleo). */
    public static function nucleoNoEscopo(Usuario $gestor, int $nucleoId): bool
    {
        return $gestor->perfil === 'admin_rede' && Instituicoes::nucleos($gestor)->where('id', $nucleoId)->exists();
    }
}
