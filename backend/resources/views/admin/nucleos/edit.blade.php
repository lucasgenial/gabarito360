@extends('layouts.admin')

@section('title', 'Editar nucleo')

@section('content')
    <header class="page-heading">
        <p class="eyebrow"><a href="{{ route('admin.nucleos.index') }}" wire:navigate>Nucleos</a> / {{ $nucleo->codigo }}</p>
        <h1>Editar nucleo</h1>
        <p>O codigo e o status possuem fluxos protegidos e nao podem ser alterados diretamente.</p>
    </header>

    <section class="card">
        <form class="form-grid" method="POST" action="{{ route('admin.nucleos.update', $nucleo) }}">
            @csrf
            @method('PATCH')
            <div class="field field-wide">
                <label for="nome">Nome</label>
                <input id="nome" name="nome" value="{{ old('nome', $nucleo->nome) }}" required maxlength="180">
                <x-admin.field-error name="nome" />
            </div>
            <div class="field">
                <label for="municipio">Municipio</label>
                <input id="municipio" name="municipio" value="{{ old('municipio', $nucleo->municipio) }}" maxlength="120">
            </div>
            <div class="field">
                <label for="estado">UF</label>
                <input id="estado" name="estado" value="{{ old('estado', $nucleo->estado) }}" maxlength="2">
            </div>
            <div class="field">
                <label for="email">E-mail institucional</label>
                <input id="email" name="email" type="email" value="{{ old('email', $nucleo->email) }}" maxlength="254">
            </div>
            <div class="field">
                <label for="telefone">Telefone institucional</label>
                <input id="telefone" name="telefone" value="{{ old('telefone', $nucleo->telefone) }}" maxlength="30">
            </div>
            <div class="form-actions field-wide">
                <button class="button button-primary" type="submit">Salvar alteracoes</button>
                <a class="button button-neutral" href="{{ route('admin.nucleos.index') }}" wire:navigate>Voltar</a>
            </div>
        </form>
    </section>

    @if ($nucleo->status->value === 'ativo')
        <section class="card danger-zone" aria-labelledby="inativar-nucleo">
            <h2 id="inativar-nucleo">Inativar nucleo</h2>
            <p>O registro e seu historico serao preservados, mas novos vinculos deixarao de ser permitidos.</p>
            <form class="stack" method="POST" action="{{ route('admin.nucleos.destroy', $nucleo) }}">
                @csrf
                @method('DELETE')
                <label class="check-field"><input name="confirmacao" type="checkbox" required> Confirmo a inativacao deste nucleo.</label>
                <button class="button button-danger" type="submit">Inativar nucleo</button>
            </form>
        </section>
    @endif
@endsection
