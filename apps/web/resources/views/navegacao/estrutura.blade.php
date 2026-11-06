<!doctype html>
<html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow"><meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="g360-revisao" content="{{ \App\Support\ContextoWeb::revisao() }}">
<title>Abrindo tela — Gabarito360</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
<script src="{{ asset('js/navegacao.js') }}?v={{ @filemtime(public_path('js/navegacao.js')) }}" defer></script>
<link rel="stylesheet" href="{{ asset('css/marca.css') }}?v={{ filemtime(public_path('css/marca.css')) }}">
</head><body class="area-publica pagina-acesso">
<main class="vinculo-pagina" data-navegacao-estrutura data-consulta="/{{ $meta['consulta'] }}" data-pagina="/{{ $meta['pagina'] }}" data-lista="{{ $meta['lista'] }}" data-exige-selecao="{{ $meta['obrigatorios'] ? '1' : '0' }}">
<a class="brand" href="/app/painel"><x-marca /></a>
<section class="card vinculo-card" aria-busy="true" aria-live="polite" data-navegacao-status>
<h1>Abrindo tela</h1><p>Carregando as informações do seu acesso.</p>
</section>
<noscript><style>[data-navegacao-status]{display:none}</style><section class="card vinculo-card"><h1>JavaScript necessário</h1><p>Ative o JavaScript para abrir e atualizar os registros. Nenhuma ação foi reenviada.</p><div class="contexto-aviso-acoes"><a class="btn btn-primary" href="{{ $meta['lista'] }}">Ir para a lista</a><a class="btn btn-secondary" href="/app/painel">Voltar ao painel</a></div></section></noscript>
</main></body></html>
