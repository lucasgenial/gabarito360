<?php
return ['ativo' => env('PEDIDOS_TITULARES_ATIVOS', false),
    'autorizacoes_exportacao' => env('PEDIDOS_EXPORTACAO_AUTORIZACOES_ATIVAS', false),
    'geracao_exportacao' => env('PEDIDOS_EXPORTACAO_GERACAO_ATIVA', false),
    'entrega_exportacao' => env('PEDIDOS_EXPORTACAO_ENTREGA_ATIVA', false)];
