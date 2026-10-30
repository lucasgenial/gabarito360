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

    {{-- Menu único por perfil (App\Support\Acesso, doc 03 "Navegação por Perfil") --}}
    <nav class="app-nav" aria-label="Menu principal">
      @foreach(\App\Support\Acesso::menu() as $item)
        <a href="{{ $item['url'] }}" @class(['active' => $item['ativo']])>{{ $item['rotulo'] }}</a>
      @endforeach
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

<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
