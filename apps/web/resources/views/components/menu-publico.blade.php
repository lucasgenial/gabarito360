<header class="lp-nav">
  <nav class="lp-nav-inner" aria-label="Navegação principal">
    <a href="{{ route('home') }}" class="brand" aria-label="Gabarito360 — página inicial"><span class="logo">G360</span> Gabarito360</a>
    <div class="lp-links">
      <a href="{{ request()->routeIs('home') ? '#como-funciona' : route('home') . '#como-funciona' }}">Como funciona</a>
      <a href="{{ request()->routeIs('home') ? '#recursos' : route('home') . '#recursos' }}">Recursos</a>
      <a href="{{ request()->routeIs('home') ? '#planos' : route('home') . '#planos' }}">Planos</a>
    </div>
    <div class="lp-acoes">
      <a href="{{ route('login') }}" class="btn btn-ghost">Entrar</a>
      <a href="{{ route('leads.index') }}" class="btn btn-primary">Falar com a gente</a>
    </div>
  </nav>
</header>
