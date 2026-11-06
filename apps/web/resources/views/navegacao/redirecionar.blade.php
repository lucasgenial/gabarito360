<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow"><meta name="g360-revisao" content="{{ \App\Support\ContextoWeb::revisao() }}">
<title>Abrindo tela — Gabarito360</title><link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
<script src="{{ asset('js/navegacao.js') }}?v={{ @filemtime(public_path('js/navegacao.js')) }}" defer></script>
<link rel="stylesheet" href="{{ asset('css/marca.css') }}?v={{ filemtime(public_path('css/marca.css')) }}">
</head><body class="area-publica pagina-acesso"><main class="vinculo-pagina" data-navegacao-redirecionar data-recurso="{{ json_encode($destino,JSON_THROW_ON_ERROR) }}">
<a class="brand" href="/app/painel"><x-marca /></a><section class="card vinculo-card" aria-live="polite"><h1>Abrindo tela</h1><p>Preparando o próximo passo.</p></section>
<noscript><p>Ative o JavaScript para continuar com a seleção desta tela. Não reenvie a operação anterior.</p><a href="/app/painel">Voltar ao painel</a></noscript>
</main></body></html>
