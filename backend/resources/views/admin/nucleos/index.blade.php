@extends('layouts.admin')

@section('title', 'Nucleos')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Organizacao</p>
        <h1>Nucleos</h1>
        <p>Cadastre, localize e mantenha nucleos organizacionais.</p>
    </header>

    <section class="card" aria-labelledby="novo-nucleo">
        <h2 id="novo-nucleo">Novo nucleo</h2>
        <form class="form-grid" method="POST" action="{{ route('admin.nucleos.store') }}">
            @csrf
            <div class="field">
                <label for="codigo">Codigo</label>
                <input id="codigo" name="codigo" value="{{ old('codigo') }}" required maxlength="50" aria-describedby="@error('codigo') codigo-error @enderror">
                <x-admin.field-error name="codigo" />
            </div>
            <div class="field field-wide">
                <label for="nome">Nome</label>
                <input id="nome" name="nome" value="{{ old('nome') }}" required maxlength="180" aria-describedby="@error('nome') nome-error @enderror">
                <x-admin.field-error name="nome" />
            </div>
            <div class="field">
                <label for="municipio">Municipio</label>
                <input id="municipio" name="municipio" value="{{ old('municipio') }}" maxlength="120">
            </div>
            <div class="field">
                <label for="estado">UF</label>
                <input id="estado" name="estado" value="{{ old('estado') }}" maxlength="2">
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
                <button class="button button-primary" type="submit">Criar nucleo</button>
            </div>
        </form>
    </section>

    <section class="card" aria-labelledby="lista-nucleos">
        <div class="section-heading">
            <div>
                <h2 id="lista-nucleos">Nucleos cadastrados</h2>
                <p>Resultados visiveis apenas para administracao global.</p>
            </div>
            <form class="filter-form" method="GET" action="{{ route('admin.nucleos.index') }}">
                <label class="sr-only" for="search">Buscar nucleo</label>
                <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nome ou codigo">
                <label class="sr-only" for="status">Filtrar por status</label>
                <select id="status" name="status">
                    <option value="">Todos os status</option>
                    <option value="ativo" @selected(($filters['status'] ?? '') === 'ativo')>Ativos</option>
                    <option value="inativo" @selected(($filters['status'] ?? '') === 'inativo')>Inativos</option>
                </select>
                <button class="button button-neutral" type="submit">Filtrar</button>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Codigo</th>
                        <th scope="col">Nome</th>
                        <th scope="col">Localidade</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Acoes</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($nucleos as $nucleo)
                        <tr>
                            <td>{{ $nucleo->codigo }}</td>
                            <td>{{ $nucleo->nome }}</td>
                            <td>{{ collect([$nucleo->municipio, $nucleo->estado])->filter()->join(' / ') ?: 'Nao informado' }}</td>
                            <td><x-admin.status-badge :status="$nucleo->status->value" /></td>
                            <td><a class="text-link" href="{{ route('admin.nucleos.edit', $nucleo) }}" wire:navigate>Editar {{ $nucleo->nome }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">Nenhum nucleo encontrado para os filtros informados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $nucleos->links() }}
    </section>
@endsection
