@extends('layouts.admin')

@section('title', $prova->titulo)

@section('content')
    <header class="page-heading">
        <p class="eyebrow">{{ $prova->codigo }}</p>
        <h1>{{ $prova->titulo }}</h1>
        <p>{{ $prova->descricao ?? 'Sem descricao cadastrada.' }}</p>
    </header>

    <div class="dashboard-grid">
        <x-ui.kpi label="Questoes" :value="$prova->questoes->count()" context="Configuradas" variant="info" />
        <x-ui.kpi label="Alternativas" :value="$prova->quantidade_alternativas" context="{{ implode(', ', $prova->alternativas) }}" variant="success" />
        <x-ui.kpi label="Turmas vinculadas" :value="$prova->provaTurmas->count()" context="Autorizadas" variant="warning" />
        <x-ui.kpi label="Aplicacoes" :value="$prova->aplicacoes_count" context="Registradas" variant="info" />
    </div>

    <div class="form-actions">
        @if ($canViewAnswerKey)
            <x-ui.button :href="route('portal.exams.answer-key', $prova)" wire:navigate>Consultar gabarito</x-ui.button>
        @endif
        @if ($canViewReports)
            <x-ui.button :href="route('portal.reports.exam', $prova)" variant="neutral" wire:navigate>Relatorio da prova</x-ui.button>
        @endif
    </div>

    <div class="content-grid">
        <x-ui.card labelledby="questoes-prova">
            <h2 id="questoes-prova">Questoes</h2>
            <x-ui.table caption="Questoes da prova">
                <thead><tr><th scope="col">Numero</th><th scope="col">Codigo</th><th scope="col">Peso</th><th scope="col">Tema principal</th></tr></thead>
                <tbody>
                    @foreach ($prova->questoes as $question)
                        <tr>
                            <td>{{ $question->numero }}</td>
                            <td>{{ $question->codigo }}</td>
                            <td>{{ number_format((float) $question->peso_padrao, 2, ',', '.') }}</td>
                            <td>{{ $question->temasHabilidades->first()?->nome ?? 'Nao informado' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <x-ui.card labelledby="turmas-prova">
            <h2 id="turmas-prova">Turmas vinculadas</h2>
            <x-ui.table caption="Turmas vinculadas a prova">
                <thead><tr><th scope="col">Turma</th><th scope="col">Escola</th><th scope="col">Data prevista</th></tr></thead>
                <tbody>
                    @forelse ($prova->provaTurmas as $link)
                        <tr>
                            <td><a class="text-link" href="{{ route('portal.classes.show', $link->turma) }}" wire:navigate>{{ $link->turma->nome }}</a></td>
                            <td>{{ $link->turma->escola->nome }}</td>
                            <td>{{ $link->data_prevista?->format('d/m/Y') ?? 'Nao informada' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-ui.empty-state title="Sem turmas vinculadas" compact>Vincule turmas antes de criar aplicacoes.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>
@endsection
