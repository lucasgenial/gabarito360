<?php

return [
    'ativa' => env('AUDITORIA_ATIVA', false),
    // Conexão assíncrona independente do driver usado em outras tarefas.
    'connection' => env('AUDITORIA_QUEUE_CONNECTION', 'redis'),
    // Política de 180 dias aprovada pelo responsável; não executa exclusão.
    'retencao_dias' => env('AUDITORIA_RETENCAO_DIAS', 180),
    'conexao_gravacao' => env('AUDITORIA_DB_CONNECTION', 'auditoria_gravacao'),
    'meses_preservados' => array_values(array_filter(explode(',', env('AUDITORIA_MESES_PRESERVADOS', '')), fn ($v) => $v !== '')),
];
