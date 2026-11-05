@php($ancora = fn($id) => request()->routeIs('home') ? '#'.$id : route('home').'#'.$id)
<header class="lp-nav">
  <nav class="lp-nav-inner" aria-label="Navegação principal">
    <a href="{{ route('home') }}" class="brand" aria-label="Gabarito360 — página inicial"><x-marca /></a>
    <div class="lp-links">
      <a href="{{ $ancora('como-funciona') }}">Como funciona</a>
      <a href="{{ $ancora('recursos') }}">Recursos</a>
      <a href="{{ $ancora('demonstracoes') }}">Demonstrações</a>
      <a href="{{ $ancora('planos') }}">Modalidades</a>
    </div>
    <details class="lp-menu-movel" data-menu-publico>
      <summary>Menu</summary>
      <div class="lp-menu-links" role="group" aria-label="Navegação móvel">
        <a href="{{ $ancora('como-funciona') }}">Como funciona</a>
        <a href="{{ $ancora('recursos') }}">Recursos</a>
        <a href="{{ $ancora('demonstracoes') }}">Demonstrações</a>
        <a href="{{ $ancora('planos') }}">Modalidades</a>
        <a href="{{ $ancora('duvidas') }}">Dúvidas frequentes</a>
        <a href="{{ route('leads.index') }}">Falar com a gente</a>
      </div>
    </details>
    <div class="lp-acoes">
      <a href="{{ route('login') }}" class="btn btn-ghost" aria-label="Entrar no sistema com uma conta existente">Entrar</a>
      <a href="{{ route('leads.index') }}" class="btn btn-primary">Falar com a gente</a>
    </div>
  </nav>
</header>
