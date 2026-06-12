@extends('layouts.admin')

@section('title', 'Nova prova')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Avaliacoes</p>
        <h1>Nova prova</h1>
        <p>Crie o rascunho inicial. Questoes, gabarito e publicacao continuam no fluxo controlado existente.</p>
    </header>

    <x-ui.card labelledby="dados-prova">
        <h2 id="dados-prova">Configuracao inicial</h2>
        <x-ui.alert title="Proprietario da prova">Selecione somente um nucleo ou uma escola.</x-ui.alert>
        <form class="form-grid" method="POST" action="{{ route('portal.exams.store') }}">
            @csrf
            <x-ui.select name="nucleo_id" label="Nucleo proprietario">
                <option value="">Nao se aplica</option>
                @foreach ($nuclei as $nucleus)
                    <option value="{{ $nucleus->id }}" @selected(old('nucleo_id') === $nucleus->id)>{{ $nucleus->nome }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="escola_id" label="Escola proprietaria">
                <option value="">Nao se aplica</option>
                @foreach ($schools as $school)
                    <option value="{{ $school->id }}" @selected(old('escola_id') === $school->id)>{{ $school->nome }} / {{ $school->nucleo->nome }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="modelo_cartao_id" label="Modelo de cartao homologado" required wide>
                <option value="">Selecione um modelo</option>
                @foreach ($models as $model)
                    <option value="{{ $model->id }}" @selected(old('modelo_cartao_id') === $model->id)>
                        {{ $model->nome }} v{{ $model->versao }} / {{ $model->quantidade_questoes }} questoes
                    </option>
                @endforeach
            </x-ui.select>
            <x-ui.input name="codigo" label="Codigo" :value="old('codigo')" required maxlength="60" />
            <x-ui.input name="titulo" label="Titulo" :value="old('titulo')" required maxlength="180" />
            <x-ui.input name="tipo" label="Tipo" :value="old('tipo', 'diagnostico')" required maxlength="50" />
            <x-ui.input name="nivel" label="Nivel" :value="old('nivel')" maxlength="80" />
            <x-ui.input name="ano_referencia" label="Ano de referencia" type="number" :value="old('ano_referencia', now()->year)" min="2000" max="2100" />
            <x-ui.input name="quantidade_questoes" label="Quantidade de questoes" type="number" :value="old('quantidade_questoes', 20)" required min="1" max="500" />
            <x-ui.input name="quantidade_alternativas" label="Quantidade de alternativas" type="number" :value="old('quantidade_alternativas', 5)" required min="2" max="10" />
            @foreach (old('alternativas', ['A', 'B', 'C', 'D', 'E']) as $index => $alternative)
                <x-ui.input name="alternativas[]" label="Alternativa {{ $index + 1 }}" :value="$alternative" required maxlength="1" />
            @endforeach
            <x-ui.textarea name="descricao" label="Descricao" :value="old('descricao')" rows="4" maxlength="5000" wide />
            <div class="form-actions field-wide">
                <x-ui.button type="submit">Criar rascunho</x-ui.button>
                <x-ui.button :href="route('portal.exams.index')" variant="neutral" wire:navigate>Cancelar</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
