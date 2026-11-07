<?php

declare(strict_types=1);

namespace App\Support;

/** Resumo cadastral da coleção completa no recorte, sem dados de avaliação. */
final class ResumoCadastralTurmas
{
    /**
     * Completa refere-se ao resultado autorizado inteiro do filtro, não à página visível.
     * Sem confirmação de completude, não extrapola os registros recebidos para um total.
     * Cadastros ativos não são pessoas únicas globais, matrículas ou participações.
     *
     * @return array{estado:string,base:string,turmas:?int,em_uso:?int,arquivadas:?int,cadastros_ativos:?int}
     */
    public static function montar(mixed $turmas, bool $consultaConcluida, ?bool $colecaoCompleta): array
    {
        $resumo = ['estado'=>'erro', 'base'=>'colecao_completa_no_recorte',
            'turmas'=>null, 'em_uso'=>null, 'arquivadas'=>null, 'cadastros_ativos'=>null];
        if (!$consultaConcluida || !is_array($turmas) || !array_is_list($turmas)) return $resumo;
        if ($colecaoCompleta !== true) {
            $resumo['estado'] = 'parcial';
            return $resumo;
        }

        $ids = [];
        $emUso = 0;
        $cadastros = 0;
        $estadosValidos = true;
        $contagensValidas = true;
        foreach ($turmas as $turma) {
            if (!is_array($turma) || !isset($turma['id']) || !is_int($turma['id'])
                || $turma['id'] < 1 || isset($ids[$turma['id']])) return $resumo;
            $ids[$turma['id']] = true;

            $ativo = $turma['ativo'] ?? null;
            if (is_bool($ativo) || in_array($ativo, [0, 1], true)) {
                if ($ativo === true || $ativo === 1) $emUso++;
            } else {
                $estadosValidos = false;
            }

            $total = $turma['total_alunos'] ?? null;
            if (!is_int($total) || $total < 0 || $total > PHP_INT_MAX - $cadastros) {
                $contagensValidas = false;
            } else {
                $cadastros += $total;
            }
        }

        $quantidade = count($turmas);
        $resumo['turmas'] = $quantidade;
        if ($estadosValidos) {
            $resumo['em_uso'] = $emUso;
            $resumo['arquivadas'] = $quantidade - $emUso;
        }
        if ($contagensValidas) $resumo['cadastros_ativos'] = $cadastros;
        $resumo['estado'] = !$estadosValidos || !$contagensValidas ? 'parcial' : ($quantidade === 0 ? 'vazio' : 'funcional');
        return $resumo;
    }
}
