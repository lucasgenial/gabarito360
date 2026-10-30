<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Gabarito360')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800;900&family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}" />
@stack('styles')
</head>
<body>

<header class="app-header">
  <div class="container">
    <a class="brand" href="{{ route('planos.index') }}">
      <span class="logo">G360</span> Gabarito360
    </a>
    <nav class="app-nav">
      <a href="{{ route('planos.index') }}" @if(request()->routeIs('planos.index')) class="active" @endif>Planos</a>
      <a href="{{ route('leads.index') }}" @if(request()->routeIs('leads.*')) class="active" @endif>Fale Conosco</a>
    </nav>
    <div class="header-right">
      <a href="{{ route('login') }}" class="btn btn-outline" style="margin-right:.5rem">Entrar</a>
    </div>
  </div>
</header>

<div class="container">
  @if(session('sucesso'))
    <div class="alert alert-success" style="margin-top:1rem">{{ session('sucesso') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger" style="margin-top:1rem">
      <ul style="margin:0;padding-left:1.25rem">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif
  @yield('content')
</div>

<footer style="text-align:center;padding:2rem;color:var(--color-secondary-06);font-size:.875rem;margin-top:3rem;border-top:1px solid var(--color-secondary-03)">
  © {{ date('Y') }} Gabarito360 · <a href="{{ route('leads.index') }}">Fale Conosco</a> · <a href="#">Privacidade</a>
</footer>

<script src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
