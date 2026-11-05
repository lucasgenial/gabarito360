<?php

return [
    // URL pública da WEB, nunca a URL interna da API nem o Host da requisição.
    'validacao_url' => env('DOCUMENT_VALIDATION_URL', 'https://gabarito360.com.br/validar-documento'),
];
