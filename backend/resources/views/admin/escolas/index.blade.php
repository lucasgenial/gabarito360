@extends('layouts.admin')

@section('title', 'Escolas')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Organizacao</p>
        <h1>Escolas</h1>
        <p>Cadastre e mantenha escolas dentro do seu escopo autorizado.</p>
    </header>

    <section class="card" aria-labelledby="nova-escola">
        <h2 id="nova-escola">Nova escola</h2>
        <form class="form-grid" method="POST" action="{{ route('admin.escolas.store') }}">
            @csrf
            <div class="field field-wide">
                <label for="nucleo_id">Nucleo</label>
                <select id="nucleo_id" name="nucleo_id" required>
                    <option value="">Selecione um nucleo</option>
                    @foreach ($nucleos as $nucleo)
                        <option value="{{ $nucleo->id }}" @selected(old('nucleo_id') === $nucleo->id)>{{ $nucleo->nome }}</option>
                    @endforeach
                </select>
                <x-admin.field-error name="nucleo_id" />
            </div>
            <div class="field">
                <label for="codigo">Codigo</label>
                <input id="codigo" name="codigo" value="{{ old('codigo') }}" required maxlength="50">
                <x-admin.field-error name="codigo" />
            </div>
            <div class="field">
                <label for="nome">Nome</label>
                <input id="nome" name="nome" value="{{ old('nome') }}" required maxlength="180">
                <x-admin.field-error name="nome" />
            </div>
            <div class="field">
                <label for="municipio">Municipio</label>
                <input id="municipio" name="municipio" value="{{ old('municipio') }}" required maxlength="120">
            </div>
            <div class="field">
                <label for="estado">UF</label>
                <input id="estado" name="estado" value="{{ old('estado') }}" required maxlength="2">
            </div>
            <div class="field">
                <label for="email">E-mail institucional</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="254">
            </div>
            <div class="field">
                <label for="telefone">Telefone institucional</label>
                <input id="telefone" name="telefone" value="{{ old('telefone') }}" maxlength="30">
            </div>
            <div class="form-actions field-wide">
                <button class="button button-primary" type="submit">Criar escola</button>
            </div>
        </form>
    </section>

    <section class="card" aria-labelledby="lista-escolas">
        <div class="section-heading">
            <div>
                <h2 id="lista-escolas">Escolas cadastradas</h2>
                <p>A lista ja esta limitada pelo seu escopo de acesso.</p>
            </div>
            <form class="filter-form" method="GET" action="{{ route('admin.escolas.index') }}">
                <label class="sr-only" for="search">Buscar escola</label>
                <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nome ou codigo">
                <label class="sr-only" for="status">Filtrar por status</label>
                <select id="status" name="status">
                    <option value="">Todos os status</option>
                    <option value="ativo" @selected(($filters['status'] ?? '') === 'ativo')>Ativas</option>
                    <option value="inativo" @selected(($filters['status'] ?? '') === 'inativo')>Inativas</option>
                </select>
                <button class="button button-neutral" type="submit">Filtrar</button>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Codigo</th>
                        <th scope="col">Escola</th>
                        <th scope="col">Nucleo</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Acoes</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($escolas as $escola)
                        <tr>
                            <td>{{ $escola->codigo }}</td>
                            <td>{{ $escola->nome }}<span class="cell-detail">{{ $escola->municipio }} / {{ $escola->estado }}</span></td>
                            <td>{{ $escola->nucleo->nome }}</td>
                            <td><x-admin.status-badge :status="$escola->status->value" /></td>
                            <td><a class="text-link" href="{{ route('admin.escolas.edit', $escola) }}" wire:navigate>Editar {{ $escola->nome }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">Nenhuma escola encontrada para os filtros informados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $escolas->links() }}
    </section>
@endsection
