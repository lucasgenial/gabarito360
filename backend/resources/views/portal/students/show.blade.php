@extends('layouts.admin')

@section('title', $aluno->nome)

@section('content')
    <header class="page-heading">
        <p class="eyebrow">{{ $aluno->escola->nome }}</p>
        <h1>{{ $aluno->nome }}</h1>
        <p>Matricula {{ $aluno->matricula }} / codigo do cartao {{ $aluno->codigo_interno ?? 'nao informado' }}</p>
    </header>

    <div class="form-actions">
        @can('update', $aluno)
            <x-ui.button :href="route('portal.students.edit', $aluno)" wire:navigate>Editar aluno</x-ui.button>
        @endcan
    </div>

    <div class="content-grid">
        <x-ui.card labelledby="matriculas-aluno">
            <h2 id="matriculas-aluno">Matriculas</h2>
            <x-ui.table caption="Matriculas do aluno">
                <thead><tr><th scope="col">Turma</th><th scope="col">Ano</th><th scope="col">Status</th></tr></thead>
                <tbody>
                    @foreach ($aluno->matriculasTurmas as $enrollment)
                        <tr>
                            <td><a class="text-link" href="{{ route('portal.classes.show', $enrollment->turma) }}" wire:navigate>{{ $enrollment->turma->nome }}</a></td>
                            <td>{{ $enrollment->ano_letivo }}</td>
                            <td><x-admin.status-badge :status="$enrollment->status->value" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <x-ui.card labelledby="resultados-aluno">
            <h2 id="resultados-aluno">Resultados</h2>
            <x-ui.table caption="Resultados do aluno">
                <thead><tr><th scope="col">Prova</th><th scope="col">Nota</th><th scope="col">Situacao</th></tr></thead>
                <tbody>
                    @forelse ($aluno->resultados as $result)
                        <tr>
                            <td><a class="text-link" href="{{ route('portal.results.show', $result) }}" wire:navigate>{{ $result->prova->titulo }}</a></td>
                            <td>{{ number_format((float) $result->nota_percentual, 2, ',', '.') }}%</td>
                            <td><x-admin.status-badge :status="$result->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-ui.empty-state title="Sem resultados" compact>Nenhum resultado autorizado foi calculado.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>
@endsection
