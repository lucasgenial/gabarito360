<?php

namespace App\Support;

use App\Models\Prova;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Arquivo: apps/api/app/Support/Correcao.php
 * Função: Regras da correção aberta/fechada. Aberta: professor/corretor enviam, excluem,
 *         vinculam e revisam. Fechada: só quem tem `correcao.reabrir` (padrão: coordenador,
 *         diretor escolar, admin) altera, exclui, inclui ou reabre.
 */
class Correcao
{
    public static function fechada(Prova $prova): bool
    {
        return $prova->correcao_fechada_em !== null;
    }

    /** null = pode alterar; senão, a mensagem explicando o bloqueio. */
    public static function bloqueio(Usuario $u, Prova $prova): ?string
    {
        // Quem pode reabrir (padrão: coordenador, diretor escolar, admin; ou extra) continua alterando
        if (!self::fechada($prova) || Permissao::pode($u, 'correcao.reabrir')) {
            return null;
        }

        $quem = $prova->correcao_fechada_por
            ? DB::table('usuarios')->where('id', $prova->correcao_fechada_por)->value('nome') : null;

        return 'A correção desta prova foi fechada'
            . ($quem ? " por {$quem}" : '')
            . ' em ' . $prova->correcao_fechada_em->timezone('America/Bahia')->format('d/m/Y \à\s H:i')
            . '. Para enviar, excluir ou alterar cartões, peça à coordenação para reabrir a correção.';
    }

    /** Situação para as telas: {fechada, fechada_em, fechada_por}. */
    public static function situacao(Prova $prova): array
    {
        return [
            'fechada'     => self::fechada($prova),
            'fechada_em'  => $prova->correcao_fechada_em?->toIso8601String(),
            'fechada_por' => $prova->correcao_fechada_por
                ? DB::table('usuarios')->where('id', $prova->correcao_fechada_por)->value('nome') : null,
        ];
    }
}
