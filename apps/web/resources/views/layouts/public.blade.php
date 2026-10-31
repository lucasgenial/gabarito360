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
<header class="lp-nav">
  <nav class="lp-nav-inner" aria-label="Navegação principal">
    <a class="brand" href="{{ route('home') }}"><span class="logo">G360</span> Gabarito360</a>
    <div class="lp-links">
      <a href="{{ route('home') }}#como-funciona">Como funciona</a>
      <a href="{{ route('planos.index') }}" @if(request()->routeIs('planos.index')) aria-current="page" @endif>Planos</a>
      <a href="{{ route('leads.index') }}" @if(request()->routeIs('leads.*')) aria-current="page" @endif>Fale conosco</a>
    </div>
    <div class="lp-acoes"><a href="{{ route('login') }}" class="btn btn-secondary">Entrar</a></div>
  </nav>
</header>
<main id="conteudo" class="publico-conteudo" tabindex="-1">
  @if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
  @if($errors->any())
    <x-aviso tipo="erro"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-aviso>
  @endif
  @yield('content')
</main>
<footer class="lp-rodape publico-rodape">
  <div class="lp-rodape-grade">
    <a class="brand" href="{{ route('home') }}">Gabarito360</a>
    <nav class="lp-links" aria-label="Links do rodapé">
      <a href="{{ route('planos.index') }}">Planos</a>
      <a href="{{ route('leads.index') }}">Fale conosco</a>
      <a href="{{ route('login') }}">Entrar</a>
    </nav>
    <span>© {{ date('Y') }} Gabarito360</span>
  </div>
</footer>
<script src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
