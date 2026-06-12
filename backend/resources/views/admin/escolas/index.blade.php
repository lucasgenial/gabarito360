@extends('layouts.admin')

@section('title', 'Escolas')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Organizacao</p>
        <h1>Escolas</h1>
        <p>Cadastre e mantenha escolas dentro do seu escopo autorizado.</p>
    </header>

    <x-ui.card labelledby="nova-escola">
        <h2 id="nova-escola">Nova escola</h2>
        <form class="form-grid" method="POST" action="{{ route('admin.escolas.store') }}">
            @csrf
            <x-ui.select name="nucleo_id" label="Nucleo" required wide>
                    <option value="">Selecione um nucleo</option>
                    @foreach ($nucleos as $nucleo)
                        <option value="{{ $nucleo->id }}" @selected(old('nucleo_id') === $nucleo->id)>{{ $nucleo->nome }}</option>
                    @endforeach
            </x-ui.select>
            <x-ui.input name="codigo" label="Codigo" :value="old('codigo')" required maxlength="50" />
            <x-ui.input name="nome" label="Nome" :value="old('nome')" required maxlength="180" />
            <x-ui.input name="municipio" label="Municipio" :value="old('municipio')" required maxlength="120" />
            <x-ui.input name="estado" label="UF" :value="old('estado')" required maxlength="2" />
            <x-ui.input name="email" label="E-mail institucional" type="email" :value="old('email')" maxlength="254" />
            <x-ui.input name="telefone" label="Telefone institucional" :value="old('telefone')" maxlength="30" />
            <div class="form-actions field-wide">
                <x-ui.button type="submit">Criar escola</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card labelledby="lista-escolas">
        <div class="section-heading">
            <div>
                <h2 id="lista-escolas">Escolas cadastradas</h2>
                <p>A lista ja esta limitada pelo seu escopo de acesso.</p>
            </div>
            <form class="filter-form" method="GET" action="{{ route('admin.escolas.index') }}">
                <x-ui.input name="search" label="Buscar escola" :value="$filters['search'] ?? ''" placeholder="Nome ou codigo" label-hidden />
                <x-ui.select name="status" label="Filtrar por status" label-hidden>
                    <option value="">Todos os status</option>
                    <option value="ativo" @selected(($filters['status'] ?? '') === 'ativo')>Ativas</option>
                    <option value="inativo" @selected(($filters['status'] ?? '') === 'inativo')>Inativas</option>
                </x-ui.select>
                <x-ui.button type="submit" variant="neutral">Filtrar</x-ui.button>
            </form>
        </div>

        <x-ui.table caption="Escolas cadastradas">
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
                        <tr><td colspan="5"><x-ui.empty-state title="Nenhuma escola encontrada" compact>Revise os filtros informados.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
        </x-ui.table>
        <x-ui.pagination :paginator="$escolas" />
    </x-ui.card>
@endsection
