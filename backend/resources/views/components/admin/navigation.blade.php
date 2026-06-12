<nav class="nav-list" aria-label="Navegacao principal">
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
