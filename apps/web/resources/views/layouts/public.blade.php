<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Gabarito360 — Gestão de avaliações')</title>
<meta name="description" content="@yield('description', 'Conheça o Gabarito360: correção de cartões-resposta pelo celular e relatórios por aluno, turma e escola.')">
@hasSection('robots')<meta name="robots" content="@yield('robots')">@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
@stack('styles')
</head>
<body class="area-publica pagina-publica">
<a class="pular-conteudo" href="#conteudo">Pular para o conteúdo</a>
<x-menu-publico />
<main id="conteudo" class="publico-conteudo" tabindex="-1">
  @if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
  @if($errors->any())
    <x-aviso tipo="erro"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-aviso>
  @endif
  @yield('content')
</main>
<x-rodape-publico />
<script src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
