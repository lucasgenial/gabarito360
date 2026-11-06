<!doctype html>
<html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>@yield('titulo', 'Gabarito360')</title>
<link rel="icon" href="{{ asset('favicon.ico') }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
<link rel="stylesheet" href="{{ asset('css/marca.css') }}?v={{ @filemtime(public_path('css/marca.css')) }}">
<link rel="stylesheet" href="{{ asset('css/estado-tela.css') }}?v={{ @filemtime(public_path('css/estado-tela.css')) }}">
<script src="{{ asset('js/estado-tela.js') }}?v={{ @filemtime(public_path('js/estado-tela.js')) }}"></script>
@yield('cabeca')
</head><body class="area-publica pagina-estado">@yield('conteudo')</body></html>
