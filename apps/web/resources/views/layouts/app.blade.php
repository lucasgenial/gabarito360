<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="description" content="Gabarito360: correção de provas e relatórios educacionais.">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="@ativo('js/navegacao.js')" defer></script>
<title>@yield('title', 'Painel') — Gabarito360</title>
<link rel="stylesheet" href="@ativo('css/app-conteudo-legado.css')" />
@if(\App\Support\ContextoWeb::revisao())
<meta name="g360-revisao" content="{{ \App\Support\ContextoWeb::revisao() }}">
<script src="@ativo('js/contexto-aba.js')"></script>
@endif
<link rel="stylesheet" href="@ativo('css/tabelas.css')" />
@stack('styles')
{{-- Aplica o menu recolhido antes de desenhar a página (preferência do navegador) --}}
<script src="@ativo('js/layout-preferencias.js')"></script>
@include('layouts._marca-head')
<link rel="stylesheet" href="@ativo('css/estrutura-app.css')">
</head>
<body class="area-logada tw2-app {{ config('release.sha') ? 'com-release' : '' }} @yield('tela')">

@php $vinculoAtual = \App\Support\ContextoWeb::atual(); @endphp
<a class="estrutura-pular" href="#conteudo-principal">Pular para o conteúdo</a>
<div id="g360-notificacoes" class="no-print" aria-label="Notificações"></div>
<div class="estrutura-app">
<header class="cabecalho-app">
  <div class="cabecalho-app-marca">
    <button type="button" id="menu-recolher" class="controle-estrutura menu-desktop-app" aria-controls="menu-lateral-app" aria-expanded="true" aria-label="Recolher menu principal" title="Recolher menu principal"><x-icone-estrutura nome="menu" /></button>
    <a class="marca-estrutura" href="{{ route('painel') }}" aria-label="Gabarito360 — Painel"><x-marca size="sm" aria-hidden="true" /></a>
  </div>
  <div class="controles-cabecalho-app">
    <button type="button" class="controle-estrutura" id="theme-toggle" aria-label="Alternar tema" title="Alternar tema"></button>
    <div class="conta-container-app">
      <button type="button" id="user-menu-trigger" class="controle-estrutura avatar-conta-app" aria-label="Abrir opções da conta" aria-expanded="false" aria-controls="user-menu" title="Opções da conta"><x-avatar-usuario :nome="session('auth_nome','Usuário')" :avatar-id="session('auth_avatar_id')" :tem-foto="session('auth_tem_foto',false)" :versao="session('auth_imagem_versao',1)" :proprio="true" tamanho="sm" /></button>
      <div id="user-menu" class="conta-app" hidden>
        <div class="conta-app-identidade"><strong>{{ session('auth_nome') }}</strong><span>{{ session('auth_email') }}</span><span>{{ \App\Support\Acesso::rotulo() }}</span></div>
        <nav aria-label="Opções da conta">
          <a href="{{ route('perfil') }}"><x-icone-estrutura nome="aluno" />Meu perfil</a>
          @if($vinculoAtual && $opcoesTroca['disponivel'])
          <button type="button" id="contexto-trocar" hidden aria-haspopup="dialog" aria-controls="troca-contexto-modal" data-alternativa="{{ route('vinculos.index',['trocar'=>1]) }}"><x-icone-estrutura nome="transferir" />{{ \App\Support\Acesso::interno()?'Trocar acesso':'Trocar vínculo' }}</button>
          <noscript><a href="{{ route('vinculos.index',['trocar'=>1]) }}" data-sem-contexto>Trocar vínculo</a></noscript>
          @endif
          @pode('rede.configurar')<a href="{{ route('configuracoes.index') }}"><x-icone-estrutura nome="ajustes" />Configurações</a>@endpode
          @if(session('auth_titular') && !\App\Support\Acesso::interno())<a href="{{ route('assinatura.index') }}">Assinatura e cobrança</a>@endif
        </nav>
        <form method="POST" action="{{ route('logout') }}">@csrf <x-revisao-acesso /><button type="submit" class="conta-app-sair"><x-icone-estrutura nome="sair" />Sair</button></form>
      </div>
    </div>
  </div>
</header>
<aside class="lateral-app" id="menu-lateral-app" aria-label="Menu principal">
  <nav class="lateral-app-itens">@foreach(\App\Support\Acesso::menu() as $item)<a href="{{ $item['url'] }}" title="{{ $item['curto'] }}" aria-label="{{ $item['curto'] }}" @class(['active'=>$item['ativo']]) @if($item['ativo']) aria-current="page" @endif><x-icone-estrutura :nome="$item['icone']" /><span>{{ $item['curto'] }}</span></a>@endforeach</nav>
  @if($vinculoAtual)<x-contexto-ativo :contexto="$vinculoAtual" :fixo="true" />@endif
</aside>
<div class="corpo-app">
{{-- Conteúdo principal --}}
<main class="conteudo-app" id="conteudo-principal">
  @if($vinculoAtual)
    <div class="contexto-conteudo"><x-contexto-ativo :contexto="$vinculoAtual" :erro-troca="$opcoesTroca['erro']" /></div>
  @endif
  {{-- Breadcrumb --}}
  @include('layouts._breadcrumb')

  {{-- Conteúdo --}}
  @yield('content')
  @include('layouts._flash-fallback')
</main>
  @if(config('release.sha'))
  <footer class="rodape-app" aria-label="Versão do software">Gabarito360 - v{{ config('versao.numero') }} - {{ config('versao.ambiente') }}</footer>
  @endif

</div>{{-- .corpo-app --}}
</div>{{-- .estrutura-app --}}

@php $menuCel = \App\Support\Acesso::menuCelular(); @endphp
@if(count($menuCel['barra']) > 1 || $menuCel['mais'])
<nav class="nav-mobile-app" aria-label="Menu principal (celular)">
  @foreach($menuCel['barra'] as $item)<a href="{{ $item['url'] }}" @class(['active'=>$item['ativo']]) @if($item['ativo']) aria-current="page" @endif><x-icone-estrutura :nome="$item['icone']" /><span>{{ $item['curto'] }}</span></a>@endforeach
  @if($menuCel['mais'])<button type="button" class="nav-inferior-mais" aria-expanded="false" aria-controls="nav-folha" aria-haspopup="dialog"><x-icone-estrutura nome="mais" /><span>Mais</span></button>@endif
</nav>
@if($menuCel['mais'])
<dialog class="menu-mobile-app" id="nav-folha" aria-labelledby="menu-mobile-titulo">
  <header><h2 id="menu-mobile-titulo">Menu</h2><button type="button" data-fechar>Fechar</button></header>
  <nav aria-label="Todas as opções">
    @foreach($menuCel['mais'] as $item)<a href="{{ $item['url'] }}" @class(['active'=>$item['ativo']]) @if($item['ativo']) aria-current="page" @endif><x-icone-estrutura :nome="$item['icone']" />{{ $item['curto'] }}</a>@endforeach
    <a href="{{ route('perfil') }}"><x-icone-estrutura nome="aluno" />Meu perfil</a>
  </nav>
</dialog>
@endif
@endif
<script src="@ativo('js/app.js')"></script>
<script src="@ativo('js/carregamento.js')"></script>
<script src="@ativo('js/entrada-vinculo.js')"></script>
@if($vinculoAtual && $opcoesTroca['disponivel']) @include('layouts._troca-contexto') @endif

<script src="@ativo('js/tabelas.js')"></script>
@stack('scripts')
@include('layouts._imagem-usuario')
@include('layouts._notificacoes')
<script src="@ativo('js/layout-interacoes.js')"></script>
</body>
</html>
