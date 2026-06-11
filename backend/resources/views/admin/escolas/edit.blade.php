@extends('layouts.admin')

@section('title', 'Editar escola')

@section('content')
    <header class="page-heading">
        <p class="eyebrow"><a href="{{ route('admin.escolas.index') }}" wire:navigate>Escolas</a> / {{ $escola->codigo }}</p>
        <h1>Editar escola</h1>
        <p>Vinculada ao nucleo {{ $escola->nucleo->nome }}.</p>
    </header>

    <x-ui.card>
        <form class="form-grid" method="POST" action="{{ route('admin.escolas.update', $escola) }}">
            @csrf
            @method('PATCH')
            <x-ui.input name="codigo" label="Codigo" :value="old('codigo', $escola->codigo)" required maxlength="50" />
            <x-ui.input name="nome" label="Nome" :value="old('nome', $escola->nome)" required maxlength="180" />
            <x-ui.input name="municipio" label="Municipio" :value="old('municipio', $escola->municipio)" required maxlength="120" />
            <x-ui.input name="estado" label="UF" :value="old('estado', $escola->estado)" required maxlength="2" />
            <x-ui.input name="email" label="E-mail institucional" type="email" :value="old('email', $escola->email)" maxlength="254" />
            <x-ui.input name="telefone" label="Telefone institucional" :value="old('telefone', $escola->telefone)" maxlength="30" />
            <div class="form-actions field-wide">
                <x-ui.button type="submit">Salvar alteracoes</x-ui.button>
                <x-ui.button :href="route('admin.escolas.index')" variant="neutral" wire:navigate>Voltar</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    @if ($escola->status->value === 'ativo')
        <x-ui.card labelledby="inativar-escola" variant="danger">
            <h2 id="inativar-escola">Inativar escola</h2>
            <p>O registro e seu historico serao preservados, mas novos vinculos deixarao de ser permitidos.</p>
            <form class="stack" method="POST" action="{{ route('admin.escolas.destroy', $escola) }}">
                @csrf
                @method('DELETE')
                <label class="check-field"><input name="confirmacao" type="checkbox" required> Confirmo a inativacao desta escola.</label>
                <x-ui.button type="submit" variant="danger">Inativar escola</x-ui.button>
            </form>
        </x-ui.card>
    @endif
@endsection
