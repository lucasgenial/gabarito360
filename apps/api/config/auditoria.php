<?php

return [
    'ativa' => env('AUDITORIA_ATIVA', false),
    // Conexão assíncrona independente do driver usado em outras tarefas.
    'connection' => env('AUDITORIA_QUEUE_CONNECTION', 'redis'),
    // Sem prazo implícito: decisão operacional própria, não herdada dos logs.
    'retencao_dias' => env('AUDITORIA_RETENCAO_DIAS'),
    'meses_preservados' => array_values(array_filter(explode(',', env('AUDITORIA_MESES_PRESERVADOS', '')), fn ($v) => $v !== '')),
];
