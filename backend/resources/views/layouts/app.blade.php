@php($user = auth()->user())
@php($initials = \Illuminate\Support\Str::of($user?->nome ?? '')->explode(' ')->filter()->take(2)->map(fn ($p) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($p, 0, 1)))->implode('') ?: 'G3')
@php($portalUi = $portalUi ?? [])
@php($navigation = $portalUi['navigation'] ?? [])
@php($roleLabel = $portalUi['roleLabel'] ?? 'Perfil não definido')
@php($scopeLabel = $portalUi['scopeLabel'] ?? ($scopeLabel ?? null))
<!doctype html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel') - Gabarito360</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800;900&family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#conteudo">Ir para o conteúdo</a>

    <div class="govbar">
        <div class="container">
            <span>Governo Federal</span>
            <a href="#conteudo">Acessibilidade</a>
            <a href="#" id="contrast-link">Alto contraste</a>
        </div>
    </div>

    <header class="app-header">
        <div class="container">
            <a class="brand" href="{{ route('portal.dashboard') }}"><span class="logo">G360</span> Gabarito360</a>
            <nav class="app-nav" aria-label="Navegação principal">
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}" @class(['active' => request()->routeIs(...(array) $item['active'])])>{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <div class="header-right">
                @isset($scopeLabel)
                    <span class="badge badge-info badge-dot">{{ $scopeLabel }}</span>
                @endisset
                <button class="theme-btn" id="theme-toggle" type="button" aria-label="Alternar tema" title="Alternar tema"></button>
                <div class="user-menu-container">
                    <div class="avatar" id="user-menu-trigger" role="button" tabindex="0" aria-haspopup="true" aria-label="Menu da conta">{{ $initials }}</div>
                    <div class="user-menu" id="user-menu" role="menu">
                        <div class="user-menu-header">
                            <div class="user-name">{{ $user?->nome }}</div>
                            <div class="user-role">{{ $roleLabel }} @if($scopeLabel) &middot; {{ $scopeLabel }} @endif</div>
                            <div class="user-email">{{ $user?->email }}</div>
                        </div>
                        <div class="user-menu-links">
                            <a href="{{ route('portal.profile') }}" role="menuitem">Meu Perfil</a>
                            <a href="{{ route('portal.settings') }}" role="menuitem">Configurações</a>
                            <hr>
                            <form method="POST" action="{{ route('portal.logout') }}">
                                @csrf
                                <button type="submit" class="logout" role="menuitem">Sair</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container" id="conteudo">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
