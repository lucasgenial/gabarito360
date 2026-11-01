<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>@yield('title', 'Gabarito360') — Acesso</title>
<meta name="description" content="Acesse sua conta no Gabarito360 ou recupere sua senha com segurança.">
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}" />
@stack('styles')
</head>
<body class="area-publica pagina-acesso">
@unless(request()->routeIs('login') || ($semMenuPublico ?? false))<x-menu-publico />@endunless
<div class="acesso-conteudo">@yield('content')</div>
@unless(request()->routeIs('login') || ($semMenuPublico ?? false))<x-rodape-publico />@endunless
<script src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
