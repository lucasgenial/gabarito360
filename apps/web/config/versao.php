<?php

// Versão do produto; fase e SHA da publicação permanecem em release.php.
return [
    'numero' => '0.1.0',
    'ambiente' => match (env('APP_ENV', 'production')) {
        'staging' => 'Homologação',
        'production' => 'Produção',
        default => 'Desenvolvimento',
    },
];
