@extends('layouts.admin')

@section('title', $escola->nome)

@section('content')
    <header class="page-heading">
        <p class="eyebrow">{{ $escola->nucleo->nome }}</p>
        <h1>{{ $escola->nome }}</h1>
        <p>{{ $escola->codigo }} / {{ $escola->municipio }} - {{ $escola->estado }}</p>
    </header>

    <div class="dashboard-grid">
        <x-ui.kpi label="Turmas" :value="$escola->turmas->count()" context="Cadastradas" variant="info" />
        <x-ui.kpi label="Alunos" :value="$escola->alunos_count" context="Cadastrados" variant="success" />
        <x-ui.kpi label="Provas proprias" :value="$escola->provas_count" context="Da unidade" variant="warning" />
        <x-ui.kpi label="Lotacoes" :value="$escola->lotacoes_count" context="Historicas" variant="info" />
    </div>

    <div class="content-grid">
        <x-ui.card labelledby="turmas-escola">
            <div class="section-heading">
                <div>
                    <h2 id="turmas-escola">Turmas</h2>
                    <p>Estrutura academica cadastrada.</p>
                </div>
                <x-ui.button :href="route('portal.classes.index')" variant="neutral" size="sm" wire:navigate>Ver todas</x-ui.button>
            </div>
            <x-ui.table caption="Turmas da escola">
                <thead><tr><th scope="col">Turma</th><th scope="col">Ano</th><th scope="col">Matriculas</th></tr></thead>
                <tbody>
                    @forelse ($escola->turmas as $class)
                        <tr>
                            <td><a class="text-link" href="{{ route('portal.classes.show', $class) }}" wire:navigate>{{ $class->nome }}</a><span class="cell-detail">{{ $class->serie_ano }}</span></td>
                            <td>{{ $class->ano_letivo }}</td>
                            <td>{{ $class->matriculas_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-ui.empty-state title="Sem turmas" compact>Nenhuma turma cadastrada.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <x-ui.card labelledby="aplicacoes-escola">
            <h2 id="aplicacoes-escola">Aplicacoes recentes</h2>
            <x-ui.table caption="Aplicacoes recentes da escola">
                <thead><tr><th scope="col">Aplicacao</th><th scope="col">Turma</th><th scope="col">Status</th></tr></thead>
                <tbody>
                    @forelse ($escola->aplicacoes as $application)
                        <tr>
                            <td><a class="text-link" href="{{ route('portal.operations.show', $application) }}" wire:navigate>{{ $application->titulo }}</a></td>
                            <td>{{ $application->turma->nome }}</td>
                            <td><x-admin.status-badge :status="$application->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-ui.empty-state title="Sem aplicacoes" compact>Nenhuma aplicacao registrada.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>
@endsection
