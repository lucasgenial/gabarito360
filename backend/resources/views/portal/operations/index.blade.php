@extends('layouts.admin')

@section('title', 'Acompanhar correcoes')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Operacao</p>
        <h1>Acompanhar correcoes</h1>
        <p>Aplicacoes autorizadas com progresso persistido e atualizacao operacional em tempo real.</p>
    </header>

    <x-ui.card>
        <x-ui.table caption="Aplicacoes para acompanhamento">
            <thead><tr><th scope="col">Aplicacao</th><th scope="col">Turma</th><th scope="col">Leituras</th><th scope="col">Resultados</th><th scope="col">Status</th></tr></thead>
            <tbody>
                @forelse ($applications as $application)
                    <tr>
                        <td><a class="text-link" href="{{ route('portal.operations.show', $application) }}" wire:navigate>{{ $application->titulo }}</a><span class="cell-detail">{{ $application->prova->titulo }}</span></td>
                        <td>{{ $application->turma->nome }}<span class="cell-detail">{{ $application->escola->nome }}</span></td>
                        <td>{{ $application->leituras_count }} / {{ $application->alunos_count }}</td>
                        <td>{{ $application->resultados_count }}</td>
                        <td><x-admin.status-badge :status="$application->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-ui.empty-state title="Nenhuma aplicacao autorizada" compact>As aplicacoes criadas no fluxo operacional aparecerao aqui.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
        <x-ui.pagination :paginator="$applications" />
    </x-ui.card>
@endsection
