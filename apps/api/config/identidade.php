<?php

return [
    // Backfill é uma operação explícita; não acompanha login ou cadastro legado.
    'provisorios_ativos' => (bool) env('IDENTIDADE_PROVISORIOS_ATIVOS', false),
];
