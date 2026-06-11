@extends('layouts.admin')

@section('title', 'Editar escola')

@section('content')
    <header class="page-heading">
        <p class="eyebrow"><a href="{{ route('admin.escolas.index') }}" wire:navigate>Escolas</a> / {{ $escola->codigo }}</p>
        <h1>Editar escola</h1>
        <p>Vinculada ao nucleo {{ $escola->nucleo->nome }}.</p>
    </header>

    <section class="card">
        <form class="form-grid" method="POST" action="{{ route('admin.escolas.update', $escola) }}">
            @csrf
            @method('PATCH')
            <div class="field">
                <label for="codigo">Codigo</label>
                <input id="codigo" name="codigo" value="{{ old('codigo', $escola->codigo) }}" required maxlength="50">
                <x-admin.field-error name="codigo" />
            </div>
            <div class="field">
                <label for="nome">Nome</label>
                <input id="nome" name="nome" value="{{ old('nome', $escola->nome) }}" required maxlength="180">
                <x-admin.field-error name="nome" />
            </div>
            <div class="field">
                <label for="municipio">Municipio</label>
                <input id="municipio" name="municipio" value="{{ old('municipio', $escola->municipio) }}" required maxlength="120">
            </div>
            <div class="field">
                <label for="estado">UF</label>
                <input id="estado" name="estado" value="{{ old('estado', $escola->estado) }}" required maxlength="2">
            </div>
            <div class="field">
                <label for="email">E-mail institucional</label>
                <input id="email" name="email" type="email" value="{{ old('email', $escola->email) }}" maxlength="254">
            </div>
            <div class="field">
                <label for="telefone">Telefone institucional</label>
                <input id="telefone" name="telefone" value="{{ old('telefone', $escola->telefone) }}" maxlength="30">
            </div>
            <div class="form-actions field-wide">
                <button class="button button-primary" type="submit">Salvar alteracoes</button>
                <a class="button button-neutral" href="{{ route('admin.escolas.index') }}" wire:navigate>Voltar</a>
            </div>
        </form>
    </section>

    @if ($escola->status->value === 'ativo')
        <section class="card danger-zone" aria-labelledby="inativar-escola">
            <h2 id="inativar-escola">Inativar escola</h2>
            <p>O registro e seu historico serao preservados, mas novos vinculos deixarao de ser permitidos.</p>
            <form class="stack" method="POST" action="{{ route('admin.escolas.destroy', $escola) }}">
                @csrf
                @method('DELETE')
                <label class="check-field"><input name="confirmacao" type="checkbox" required> Confirmo a inativacao desta escola.</label>
                <button class="button button-danger" type="submit">Inativar escola</button>
            </form>
        </section>
    @endif
@endsection
