@php
    $portalScope = app(App\Services\Authorization\PortalScope::class);
    $actor = auth()->user();
@endphp

<nav class="nav-list" aria-label="Navegacao principal">
    <a class="nav-link {{ request()->routeIs('portal.dashboard', 'admin.index') ? 'is-active' : '' }}" href="{{ route('portal.dashboard') }}" wire:navigate>
        Painel
    </a>
    @if ($actor instanceof App\Models\User && $portalScope->applySchools(App\Models\Escola::query(), $actor)->exists())
        <a class="nav-link {{ request()->routeIs('portal.schools.*') ? 'is-active' : '' }}" href="{{ route('portal.schools.index') }}" wire:navigate>
            Escolas
        </a>
    @endif
    @can('viewAny', App\Models\Turma::class)
        <a class="nav-link {{ request()->routeIs('portal.classes.*', 'portal.students.*') ? 'is-active' : '' }}" href="{{ route('portal.classes.index') }}" wire:navigate>
            Turmas e alunos
        </a>
    @endcan
    @if ($actor instanceof App\Models\User && $portalScope->applyExams(App\Models\Prova::query(), $actor)->exists())
        <a class="nav-link {{ request()->routeIs('portal.exams.*') ? 'is-active' : '' }}" href="{{ route('portal.exams.index') }}" wire:navigate>
            Provas
        </a>
    @endif
    @if ($actor instanceof App\Models\User && $portalScope->canViewApplications($actor))
        <a class="nav-link {{ request()->routeIs('portal.operations.*') ? 'is-active' : '' }}" href="{{ route('portal.operations.index') }}" wire:navigate>
            Correcoes
        </a>
    @endif

    <span class="nav-section-label">Administracao</span>
    @can('viewAny', App\Models\Nucleo::class)
        <a class="nav-link {{ request()->routeIs('admin.nucleos.*') ? 'is-active' : '' }}" href="{{ route('admin.nucleos.index') }}" wire:navigate>
            Nucleos
        </a>
    @endcan
    @can('viewAny', App\Models\User::class)
        <a class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'is-active' : '' }}" href="{{ route('admin.usuarios.index') }}" wire:navigate>
            Equipe e perfis
        </a>
    @endcan
    @can('viewClassLinksAny', App\Models\Prova::class)
        <a class="nav-link {{ request()->routeIs('admin.provas.*') ? 'is-active' : '' }}" href="{{ route('admin.provas.index') }}" wire:navigate>
            Vinculos de provas
        </a>
    @endcan
</nav>
