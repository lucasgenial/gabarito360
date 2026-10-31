<?php

return [
    // Manter estável, com backup separado. Não rotacionar sem reindexar os dados.
    'hash_key' => env('DADOS_HASH_KEY') ?: (env('APP_ENV') === 'testing' ? env('APP_KEY') : null),
];
