<?php

return [
    'ativa' => env('AUDITORIA_ATIVA', false),
    // Conexão assíncrona independente do driver usado em outras tarefas.
    'connection' => env('AUDITORIA_QUEUE_CONNECTION', 'redis'),
];
