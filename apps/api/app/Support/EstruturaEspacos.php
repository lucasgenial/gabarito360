<?php

namespace App\Support;

class EstruturaEspacos
{
    public const TABELAS = [
        'turmas', 'alunos', 'provas', 'prova_turmas', 'gabaritos', 'gabarito_questoes',
        'cartoes', 'cartao_respostas', 'notas', 'ambiguidade_logs', 'cartao_envios',
        'visitas', 'sincronizacoes_seges',
    ];

    /** Hierarquia institucional aprovada; sem dados de pessoas ou contratos. */
    public const MANIFESTO = [
        1 => [1 => [1, 2], 2 => [3, 4], 3 => [5, 6]],
        2 => [4 => [8]],
    ];

    public const VERSAO = 'legado-20261031-v1';
}
