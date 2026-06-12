@extends('layouts.admin')

@section('title', 'Provas')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Avaliacoes</p>
        <h1>Provas</h1>
        <p>Provas gerenciaveis, publicadas ou vinculadas as suas turmas.</p>
    </header>

    @can('viewAny', App\Models\Prova::class)
        <div class="form-actions">
            <x-ui.button :href="route('portal.exams.create')" wire:navigate>Nova prova</x-ui.button>
        </div>
    @endcan

    <x-ui.card>
        <x-ui.table caption="Provas disponiveis">
            <thead>
                <tr><th scope="col">Prova</th><th scope="col">Contexto</th><th scope="col">Questoes</th><th scope="col">Aplicacoes</th><th scope="col">Status</th></tr>
            </thead>
            <tbody>
                @forelse ($exams as $exam)
                    <tr>
                        <td><a class="text-link" href="{{ route('portal.exams.show', $exam) }}" wire:navigate>{{ $exam->titulo }}</a><span class="cell-detail">{{ $exam->codigo }}</span></td>
                        <td>{{ $exam->disciplina?->nome ?? $exam->tipo }}<span class="cell-detail">{{ $exam->serieAno?->nome ?? $exam->nivel ?? 'Sem serie definida' }}</span></td>
                        <td>{{ $exam->questoes_count }}</td>
                        <td>{{ $exam->aplicacoes_count }}</td>
                        <td><x-admin.status-badge :status="$exam->status->value" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-ui.empty-state title="Nenhuma prova disponivel" compact>Crie e publique provas pelo contrato de autoria existente.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
        <x-ui.pagination :paginator="$exams" />
    </x-ui.card>

    @can('viewClassLinksAny', App\Models\Prova::class)
        <x-ui.alert title="Gestao de vinculos">
            O fluxo administrativo de vinculo entre provas publicadas e turmas continua disponivel em
            <a class="text-link" href="{{ route('admin.provas.index') }}" wire:navigate>vinculos de provas</a>.
        </x-ui.alert>
    @endcan
@endsection
