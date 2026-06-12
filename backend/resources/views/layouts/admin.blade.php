<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
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
        <aside class="sidebar desktop-sidebar" aria-label="Menu lateral">
            <a class="brand" href="{{ route('admin.index') }}" wire:navigate>
                <span class="brand-mark" aria-hidden="true">G360</span>
                <span class="brand-copy">
                    <strong>Gabarito360</strong>
                    <small>Gestao educacional</small>
                </span>
            </a>

            <x-admin.navigation />
        </aside>

        <div class="admin-main">
            <header class="topbar">
                <div class="topbar-context">
                    <x-ui.button variant="neutral" size="sm" class="mobile-menu-trigger" data-drawer-open="navigation-drawer" aria-label="Abrir menu principal">
                        Menu
                    </x-ui.button>
                    <div>
                        <p class="eyebrow">Painel administrativo</p>
                        <p class="topbar-user">{{ auth()->user()->nome }}</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <x-ui.theme-toggle />
                    <x-ui.account-menu :name="auth()->user()->nome" context="Conta institucional">
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <x-ui.button type="submit" variant="text" size="sm">Sair da conta</x-ui.button>
                        </form>
                    </x-ui.account-menu>
                </div>
            </header>

            <main id="conteudo" class="content" tabindex="-1">
                @php($pageTitle = trim($__env->yieldContent('title', 'Painel')))
                <x-ui.breadcrumb :items="[
                    ['label' => 'Inicio', 'href' => route('admin.index'), 'navigate' => true],
                    ['label' => $pageTitle],
                ]" />
                <x-admin.flash />
                @yield('content')
            </main>
        </div>
    </div>

    <x-ui.drawer id="navigation-drawer" title="Menu principal" description="Acesse as areas autorizadas do Gabarito360.">
        <a class="brand drawer-brand" href="{{ route('admin.index') }}" wire:navigate>
            <span class="brand-mark" aria-hidden="true">G360</span>
            <span class="brand-copy">
                <strong>Gabarito360</strong>
                <small>Gestao educacional</small>
            </span>
        </a>
        <x-admin.navigation />
    </x-ui.drawer>

    @livewireScripts
</body>
</html>
