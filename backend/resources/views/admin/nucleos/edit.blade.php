@extends('layouts.admin')

@section('title', 'Editar nucleo')

@section('content')
    <header class="page-heading">
        <p class="eyebrow"><a href="{{ route('admin.nucleos.index') }}" wire:navigate>Nucleos</a> / {{ $nucleo->codigo }}</p>
        <h1>Editar nucleo</h1>
        <p>O codigo e o status possuem fluxos protegidos e nao podem ser alterados diretamente.</p>
    </header>

    <x-ui.card>
        <form class="form-grid" method="POST" action="{{ route('admin.nucleos.update', $nucleo) }}">
            @csrf
            @method('PATCH')
            <x-ui.input name="nome" label="Nome" :value="old('nome', $nucleo->nome)" required maxlength="180" wide />
            <x-ui.input name="municipio" label="Municipio" :value="old('municipio', $nucleo->municipio)" maxlength="120" />
            <x-ui.input name="estado" label="UF" :value="old('estado', $nucleo->estado)" maxlength="2" />
            <x-ui.input name="email" label="E-mail institucional" type="email" :value="old('email', $nucleo->email)" maxlength="254" />
            <x-ui.input name="telefone" label="Telefone institucional" :value="old('telefone', $nucleo->telefone)" maxlength="30" />
            <div class="form-actions field-wide">
                <x-ui.button type="submit">Salvar alteracoes</x-ui.button>
                <x-ui.button :href="route('admin.nucleos.index')" variant="neutral" wire:navigate>Voltar</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    @if ($nucleo->status->value === 'ativo')
        <x-ui.card labelledby="inativar-nucleo" variant="danger">
            <h2 id="inativar-nucleo">Inativar nucleo</h2>
            <p>O registro e seu historico serao preservados, mas novos vinculos deixarao de ser permitidos.</p>
            <form class="stack" method="POST" action="{{ route('admin.nucleos.destroy', $nucleo) }}">
                @csrf
                @method('DELETE')
                <label class="check-field"><input name="confirmacao" type="checkbox" required> Confirmo a inativacao deste nucleo.</label>
                <x-ui.button type="submit" variant="danger">Inativar nucleo</x-ui.button>
            </form>
        </x-ui.card>
    @endif
@endsection
