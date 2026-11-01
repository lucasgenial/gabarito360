<?php

namespace App\Support;

/** Plano fechado e versionado; identificadores nunca vêm da CLI ou de dados livres. */
final class PlanoIntegridadeEspacos
{
    public const VERSAO = 't2-e-v1';

    /** [filha, colunas, pai, colunas referidas, ação de exclusão]. */
    public static function relacoes(): array
    {
        $r = [];
        foreach (EstruturaEspacos::TABELAS as $t) {
            $r[$t.'_dono'] = [$t, ['espaco_id'], 'espacos', ['id'], 'RESTRICT'];
            $r[$t.'_autor'] = [$t, ['criado_por'], 'usuarios', ['id'], 'RESTRICT'];
        }
        foreach (['turmas', 'provas', 'visitas'] as $t) {
            $r[$t.'_escola'] = [$t, ['espaco_id', 'escola_id'], 'espacos', ['id', 'escola_id'], 'RESTRICT'];
        }
        $pais = [
            'alunos' => ['turma_id' => 'turmas'],
            'prova_turmas' => ['prova_id' => 'provas', 'turma_id' => 'turmas'],
            'gabaritos' => ['prova_id' => 'provas'],
            'gabarito_questoes' => ['gabarito_id' => 'gabaritos'],
            'cartoes' => ['prova_id' => 'provas', 'aluno_id' => 'alunos'],
            'cartao_respostas' => ['cartao_id' => 'cartoes'],
            'notas' => ['prova_id' => 'provas', 'turma_id' => 'turmas', 'aluno_id' => 'alunos', 'cartao_id' => 'cartoes'],
            'ambiguidade_logs' => ['cartao_id' => 'cartoes', 'cartao_resposta_id' => 'cartao_respostas'],
            'cartao_envios' => ['prova_id' => 'provas', 'cartao_id' => 'cartoes'],
        ];
        foreach ($pais as $t => $campos) {
            foreach ($campos as $campo => $pai) {
                $acao = ($t === 'cartoes' && $campo === 'aluno_id') || ($t === 'cartao_envios' && $campo === 'cartao_id') ? 'RESTRICT' : 'CASCADE';
                $r[$t.'_'.$campo] = [$t, ['espaco_id', $campo], $pai, ['espaco_id', 'id'], $acao];
            }
        }
        $r['logs_resposta_cartao'] = ['ambiguidade_logs', ['espaco_id', 'cartao_resposta_id', 'cartao_id'], 'cartao_respostas', ['espaco_id', 'id', 'cartao_id'], 'CASCADE'];
        $r['visitas_nucleo'] = ['visitas', ['escola_id', 'nucleo_id'], 'escolas', ['id', 'nucleo_id'], 'RESTRICT'];
        return $r;
    }

    public static function indices(): array
    {
        $r = [];
        foreach (self::relacoes() as [$t, $cols, $pai, $referidas]) {
            if (count($referidas) > 1) $r[$pai.'_'.implode('_', $referidas)] = [$pai, $referidas, true];
        }
        foreach (['matricula', 'cpf_hash'] as $campo) $r['alunos_'.$campo] = ['alunos', ['espaco_id', $campo], true];
        $r['envios_usuario'] = ['cartao_envios', ['usuario_id'], false];
        $r['envios_chave'] = ['cartao_envios', ['espaco_id', 'usuario_id', 'chave'], true];
        foreach ([
            'turmas' => ['espaco_id', 'ano_letivo', 'id'],
            'alunos' => ['espaco_id', 'turma_id', 'ativo', 'id'],
            'provas' => ['espaco_id', 'status', 'data_aplicacao', 'id'],
            'cartoes' => ['espaco_id', 'prova_id', 'status', 'id'],
            'notas' => ['espaco_id', 'prova_id', 'turma_id', 'id'],
            'cartao_envios' => ['espaco_id', 'prova_id', 'status', 'id'],
        ] as $t => $cols) $r[$t.'_consulta'] = [$t, $cols, false];
        return $r;
    }

    public static function checks(): array
    {
        $tipos = ['rede' => 'rede_id', 'nucleo' => 'nucleo_id', 'escola' => 'escola_id', 'pessoal' => 'usuario_proprietario_id'];
        $opcoes = [];
        foreach ($tipos as $tipo => $campo) {
            $termos = ["tipo = '$tipo'"];
            foreach ($tipos as $outro) $termos[] = "$outro IS ".($campo === $outro ? 'NOT ' : '').'NULL';
            $opcoes[] = '('.implode(' AND ', $termos).')';
        }
        $r = ['espacos_referencia' => ['espacos', implode(' OR ', $opcoes)]];
        foreach (EstruturaEspacos::TABELAS as $t) {
            $r[$t.'_autoria'] = [$t, "(origem_autoria = 'registrada' AND criado_por IS NOT NULL) OR (origem_autoria = 'legado_desconhecido' AND criado_por IS NULL) OR origem_autoria = 'sistema'"];
        }
        return $r;
    }
}
