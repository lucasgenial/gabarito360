@extends('layouts.admin')

@section('title', 'Turmas')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Estrutura academica</p>
        <h1>Turmas</h1>
        <p>Turmas autorizadas para consulta e gestao no contexto atual.</p>
    </header>

    @if ($schools->isNotEmpty())
        <x-ui.card labelledby="nova-turma">
            <h2 id="nova-turma">Nova turma</h2>
            <form class="form-grid" method="POST" action="{{ route('portal.classes.store') }}">
                @csrf
                <x-ui.select name="escola_id" label="Escola" required wide>
                    <option value="">Selecione uma escola</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}" @selected(old('escola_id') === $school->id)>{{ $school->nome }} / {{ $school->nucleo->nome }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="codigo" label="Codigo" :value="old('codigo')" required maxlength="50" />
                <x-ui.input name="nome" label="Nome" :value="old('nome')" required maxlength="120" />
                <x-ui.input name="serie_ano" label="Serie ou ano" :value="old('serie_ano')" required maxlength="60" />
                <x-ui.select name="turno" label="Turno">
                    <option value="">Nao informado</option>
                    @foreach (['matutino' => 'Matutino', 'vespertino' => 'Vespertino', 'noturno' => 'Noturno', 'integral' => 'Integral'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('turno') === $value)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="ano_letivo" label="Ano letivo" type="number" :value="old('ano_letivo', now()->year)" required min="2000" max="2100" />
                <div class="form-actions field-wide"><x-ui.button type="submit">Criar turma</x-ui.button></div>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card labelledby="lista-turmas">
        <h2 id="lista-turmas">Turmas visiveis</h2>
        <x-ui.table caption="Turmas visiveis">
            <thead>
                <tr><th scope="col">Turma</th><th scope="col">Escola</th><th scope="col">Ano</th><th scope="col">Alunos</th><th scope="col">Aplicacoes</th></tr>
            </thead>
            <tbody>
                @forelse ($classes as $class)
                    <tr>
                        <td><a class="text-link" href="{{ route('portal.classes.show', $class) }}" wire:navigate>{{ $class->nome }}</a><span class="cell-detail">{{ $class->codigo }} / {{ $class->serie_ano }}</span></td>
                        <td>{{ $class->escola->nome }}</td>
                        <td>{{ $class->ano_letivo }}</td>
                        <td>{{ $class->matriculas_count }}</td>
                        <td>{{ $class->aplicacoes_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-ui.empty-state title="Nenhuma turma encontrada" compact>Seu escopo ainda nao possui turmas.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
        <x-ui.pagination :paginator="$classes" />
    </x-ui.card>
@endsection
