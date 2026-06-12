@extends('layouts.admin')

@section('title', 'Novo aluno')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">{{ $turma->nome }}</p>
        <h1>Novo aluno</h1>
        <p>Cadastre os dados minimos e realize a matricula na turma.</p>
    </header>

    <x-ui.card labelledby="cadastro-aluno">
        <h2 id="cadastro-aluno">Dados essenciais</h2>
        <form class="form-grid" method="POST" action="{{ route('portal.students.store', $turma) }}">
            @csrf
            <input type="hidden" name="escola_id" value="{{ $turma->escola_id }}">
            <x-ui.input name="nome" label="Nome completo" :value="old('nome')" required maxlength="180" wide />
            <x-ui.input name="matricula" label="Matricula escolar" :value="old('matricula')" required maxlength="80" />
            <x-ui.input name="codigo_interno" label="Codigo impresso do cartao, se houver" :value="old('codigo_interno')" maxlength="80" help="Informe o codigo que ja veio no cartao impresso." />
            <div class="form-actions field-wide">
                <x-ui.button type="submit">Cadastrar e matricular</x-ui.button>
                <x-ui.button :href="route('portal.classes.show', $turma)" variant="neutral" wire:navigate>Cancelar</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
