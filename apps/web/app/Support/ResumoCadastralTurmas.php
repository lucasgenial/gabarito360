<?php

declare(strict_types=1);

namespace App\Support;

/** Resumo cadastral da coleção completa no recorte, sem dados de avaliação. */
final class ResumoCadastralTurmas
{
    /** API nova: agregado do mesmo filtro. API anterior: contrato de coleção completa preservado. */
    public static function daApi(mixed $turmas, bool $consultaConcluida, mixed $agregado, bool $presente): array
    {
        $local = self::montar($turmas, $consultaConcluida, true);
        if (!$presente || $local['estado'] === 'erro') return $local;
        $erro = self::montar([], false, null);
        if (!is_array($agregado) || ($agregado['versao']??null) !== 1
            || ($agregado['base']??null) !== 'colecao_completa_no_recorte'
            || ($agregado['colecao_completa']??null) !== true) return $erro;
        foreach (['turmas','em_uso','arquivadas','cadastros_ativos'] as $campo) {
            if (!is_int($agregado[$campo]??null) || $agregado[$campo] < 0) return $erro;
            if ($local[$campo] !== null && $agregado[$campo] !== $local[$campo]) return $erro;
        }
        if ($agregado['em_uso'] > $agregado['turmas']
            || $agregado['arquivadas'] !== $agregado['turmas']-$agregado['em_uso']) return $erro;
        foreach (['turmas','em_uso','arquivadas','cadastros_ativos'] as $campo) $local[$campo] = $agregado[$campo];
        $local['estado'] = $local['turmas'] === 0 ? 'vazio' : 'funcional';
        return $local;
    }

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
