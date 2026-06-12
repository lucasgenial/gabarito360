@extends('layouts.admin')

@section('title', 'Escolas')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Organizacao</p>
        <h1>Escolas</h1>
        <p>Unidades visiveis no seu contexto atual, com totais derivados do banco.</p>
    </header>

    <div class="card-grid">
        @forelse ($schools as $school)
            <x-ui.card labelledby="school-{{ $school->id }}">
                <div class="section-heading">
                    <div>
                        <h2 id="school-{{ $school->id }}">{{ $school->nome }}</h2>
                        <p>{{ $school->codigo }} / {{ $school->nucleo->nome }}</p>
                    </div>
                    <x-admin.status-badge :status="$school->status->value" />
                </div>
                <div class="badge-list">
                    <x-ui.badge variant="info">{{ $school->turmas_count }} turmas</x-ui.badge>
                    <x-ui.badge variant="success">{{ $school->alunos_count }} alunos</x-ui.badge>
                    <x-ui.badge variant="neutral">{{ $school->aplicacoes_count }} aplicacoes</x-ui.badge>
                </div>
                <div class="form-actions">
                    <x-ui.button :href="route('portal.schools.show', $school)" wire:navigate>Ver escola</x-ui.button>
                    @can('viewAny', App\Models\User::class)
                        <x-ui.button :href="route('portal.schools.team', $school)" variant="neutral" wire:navigate>Ver equipe</x-ui.button>
                    @endcan
                </div>
            </x-ui.card>
        @empty
            <x-ui.card>
                <x-ui.empty-state title="Nenhuma escola visivel">Seu contexto atual nao possui escolas autorizadas.</x-ui.empty-state>
            </x-ui.card>
        @endforelse
    </div>
    <x-ui.pagination :paginator="$schools" />
@endsection
