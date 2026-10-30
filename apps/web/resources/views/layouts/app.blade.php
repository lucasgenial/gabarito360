<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Painel') — Gabarito360</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800;900&family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/gov.css') }}" />
@stack('styles')
</head>
<body>

{{-- Faixa governamental --}}
<div class="govbar">
  <div class="container">
    <span>🇧🇷 Governo Federal</span>
    <a href="#">Acessibilidade</a>
    <a href="#">Alto contraste</a>
  </div>
</div>

{{-- Header --}}
<header class="app-header">
  <div class="container">
    <a class="brand" href="{{ route('painel') }}">
      <span class="logo">G360</span> Gabarito360
    </a>

    {{-- Menu montado pelas permissões efetivas (App\Support\Acesso, docs/21 etapa 4) --}}
    @php $menuTopo = \App\Support\Acesso::menu(); @endphp
    <nav class="app-nav" aria-label="Menu principal">
      @foreach($menuTopo['topo'] as $item)
        <a href="{{ $item['url'] }}" @class(['active' => $item['ativo']])>{{ $item['rotulo'] }}</a>
      @endforeach
      @if($menuTopo['mais'])
        <details class="nav-mais">
          <summary @class(['active' => collect($menuTopo['mais'])->contains('ativo', true)])>Mais ▾</summary>
          <div class="nav-mais-lista">
            @foreach($menuTopo['mais'] as $item)
              <a href="{{ $item['url'] }}" @class(['active' => $item['ativo']])>{{ $item['rotulo'] }}</a>
            @endforeach
          </div>
        </details>
      @endif
    </nav>

    <div class="header-right">
      @hasSection('context-badge')
        @yield('context-badge')
      @else
        <span class="badge badge-info badge-dot">{{ \App\Support\Acesso::rotulo() }}</span>
      @endif
      <button class="theme-btn" id="theme-toggle" aria-label="Alternar tema" title="Alternar tema"></button>
      <div class="user-menu-container">
        <div class="avatar" id="user-menu-trigger">
          {{ strtoupper(substr(session('auth_nome', 'U'), 0, 2)) }}
        </div>
        <div class="user-menu" id="user-menu">
          <div class="user-menu-header">
            <div class="user-name">{{ session('auth_nome') }}</div>
            <div class="user-role">{{ \App\Support\Acesso::rotulo() }} · {{ session('auth_email') }}</div>
          </div>
          <div class="user-menu-links">
            <a href="{{ route('perfil') }}">Meu Perfil</a>
            @pode('rede.configurar')
              <a href="{{ route('configuracoes.index') }}">Configurações</a>
            @endpode
            @if(session('auth_titular'))
              <a href="{{ route('assinatura.index') }}">Assinatura e Cobrança</a>
            @endif
            <hr>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" style="width:100%;background:none;border:none;text-align:left;padding:0;cursor:pointer;">
                <a class="logout" href="#" onclick="this.closest('form').submit(); return false;">Sair</a>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

{{-- Conteúdo principal --}}
<div class="container">
  {{-- Breadcrumb --}}
  @hasSection('breadcrumb')
  <div class="breadcrumb">
    @yield('breadcrumb')
  </div>
  @endif

  {{-- Conteúdo --}}
  @yield('content')
</div>

{{-- Celular: barra inferior fixa com os 4 itens principais + "Mais" (docs/21 etapa 4) --}}
@php $menuCel = \App\Support\Acesso::menuCelular(); @endphp
@if(count($menuCel['barra']) > 1 || $menuCel['mais'])
<nav class="nav-inferior" aria-label="Menu principal (celular)">
  @foreach($menuCel['barra'] as $item)
    <a href="{{ $item['url'] }}" @class(['active' => $item['ativo']]) @if($item['ativo']) aria-current="page" @endif>
      @include('layouts._icone', ['nome' => $item['icone']])<span>{{ $item['curto'] }}</span>
    </a>
  @endforeach
  @if($menuCel['mais'])
    <button type="button" class="nav-inferior-mais" aria-expanded="false" aria-controls="nav-folha">
      @include('layouts._icone', ['nome' => 'mais'])<span>Mais</span>
    </button>
  @endif
</nav>
@if($menuCel['mais'])
<div class="nav-folha" id="nav-folha" hidden>
  <div class="nav-folha-fundo" data-fechar></div>
  <div class="nav-folha-painel" role="dialog" aria-label="Todas as opções">
    <div class="nav-folha-cab"><b>Menu</b><button type="button" class="btn btn-ghost btn-sm" data-fechar>Fechar</button></div>
    @foreach($menuCel['mais'] as $item)
      <a href="{{ $item['url'] }}" @class(['active' => $item['ativo']])>@include('layouts._icone', ['nome' => $item['icone']]) {{ $item['curto'] }}</a>
    @endforeach
    <a href="{{ route('perfil') }}">@include('layouts._icone', ['nome' => 'aluno']) Meu perfil</a>
  </div>
</div>
@endif
<script>
(function () {
  var f = document.getElementById('nav-folha'), b = document.querySelector('.nav-inferior-mais');
  if (!f || !b) return;
  function abrir(sim) { f.hidden = !sim; b.setAttribute('aria-expanded', sim ? 'true' : 'false'); document.body.style.overflow = sim ? 'hidden' : ''; }
  b.addEventListener('click', function () { abrir(f.hidden); });
  f.querySelectorAll('[data-fechar]').forEach(function (x) { x.addEventListener('click', function () { abrir(false); }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') abrir(false); });
})();
</script>
@endif

<script src="{{ asset('js/app.js') }}"></script>
<script>window.addEventListener('beforeprint', function () { document.querySelectorAll('details').forEach(function (d) { d.open = true; }); });</script>
@stack('scripts')
</body>
</html>
