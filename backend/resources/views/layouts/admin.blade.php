<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'Painel organizacional') | Gabarito360</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <a class="skip-link" href="#conteudo">Ir para o conteudo principal</a>

    <div class="admin-shell">
        <aside class="sidebar" aria-label="Navegacao principal">
            <a class="brand" href="{{ route('admin.index') }}" wire:navigate>
                <span class="brand-mark" aria-hidden="true">G360</span>
                <span>Gabarito360</span>
            </a>

            <nav class="nav-list">
                <a class="nav-link {{ request()->routeIs('admin.index') ? 'is-active' : '' }}" href="{{ route('admin.index') }}" wire:navigate>
                    Inicio
                </a>
                @can('viewAny', App\Models\Nucleo::class)
                    <a class="nav-link {{ request()->routeIs('admin.nucleos.*') ? 'is-active' : '' }}" href="{{ route('admin.nucleos.index') }}" wire:navigate>
                        Nucleos
                    </a>
                @endcan
                @can('viewAny', App\Models\Escola::class)
                    <a class="nav-link {{ request()->routeIs('admin.escolas.*') ? 'is-active' : '' }}" href="{{ route('admin.escolas.index') }}" wire:navigate>
                        Escolas
                    </a>
                @endcan
                @can('viewAny', App\Models\User::class)
                    <a class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'is-active' : '' }}" href="{{ route('admin.usuarios.index') }}" wire:navigate>
                        Usuarios
                    </a>
                @endcan
                @can('viewClassLinksAny', App\Models\Prova::class)
                    <a class="nav-link {{ request()->routeIs('admin.provas.*') ? 'is-active' : '' }}" href="{{ route('admin.provas.index') }}" wire:navigate>
                        Provas
                    </a>
                @endcan
            </nav>
        </aside>

        <div class="admin-main">
            <header class="topbar">
                <div>
                    <p class="eyebrow">Painel administrativo</p>
                    <p class="topbar-user">{{ auth()->user()->nome }}</p>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="button button-neutral button-sm" type="submit">Sair</button>
                </form>
            </header>

            <main id="conteudo" class="content" tabindex="-1">
                <x-admin.flash />
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
