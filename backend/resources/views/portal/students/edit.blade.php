@extends('layouts.admin')

@section('title', 'Editar aluno')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">{{ $aluno->escola->nome }}</p>
        <h1>Editar aluno</h1>
        <p>Somente os campos essenciais desta etapa podem ser alterados.</p>
    </header>

    <x-ui.card labelledby="editar-aluno">
        <h2 id="editar-aluno">{{ $aluno->nome }}</h2>
        <form class="form-grid" method="POST" action="{{ route('portal.students.update', $aluno) }}">
            @csrf
            @method('PATCH')
            <x-ui.input name="nome" label="Nome completo" :value="old('nome', $aluno->nome)" required maxlength="180" wide />
            <x-ui.input name="matricula" label="Matricula escolar" :value="old('matricula', $aluno->matricula)" required maxlength="80" />
            <x-ui.input name="codigo_interno" label="Codigo impresso do cartao" :value="old('codigo_interno', $aluno->codigo_interno)" maxlength="80" />
            <div class="form-actions field-wide">
                <x-ui.button type="submit">Salvar aluno</x-ui.button>
                <x-ui.button :href="route('portal.students.show', $aluno)" variant="neutral" wire:navigate>Cancelar</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
