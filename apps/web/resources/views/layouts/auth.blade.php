<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="@ativo('js/navegacao.js')" defer></script>
<title>@yield('title', 'Gabarito360') — Acesso</title>
<meta name="description" content="Acesse sua conta no Gabarito360 ou recupere sua senha com segurança.">
<meta name="robots" content="noindex, nofollow">
@if(\App\Support\ContextoWeb::revisao())
<meta name="g360-revisao" content="{{ \App\Support\ContextoWeb::revisao() }}">
<script src="@ativo('js/contexto-aba.js')"></script>
@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="@ativo('css/app.css')" />
@stack('styles')
@include('layouts._marca-head')
</head>
<body class="area-publica pagina-acesso">
@unless(request()->routeIs('login') || ($semMenuPublico ?? false))<x-menu-publico />@endunless
<div class="acesso-conteudo">@yield('content')</div>
@unless(request()->routeIs('login') || ($semMenuPublico ?? false))<x-rodape-publico />@endunless
<script src="@ativo('js/app.js')"></script>
@if($errors->any())
<script>document.querySelector('form [aria-invalid="true"]')?.focus();</script>
@endif
@stack('scripts')
</body>
</html>
