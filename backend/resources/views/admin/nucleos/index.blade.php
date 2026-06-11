@extends('layouts.admin')

@section('title', 'Nucleos')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Organizacao</p>
        <h1>Nucleos</h1>
        <p>Cadastre, localize e mantenha nucleos organizacionais.</p>
    </header>

    <x-ui.card labelledby="novo-nucleo">
        <h2 id="novo-nucleo">Novo nucleo</h2>
        <form class="form-grid" method="POST" action="{{ route('admin.nucleos.store') }}">
            @csrf
            <x-ui.input name="codigo" label="Codigo" :value="old('codigo')" required maxlength="50" />
            <x-ui.input name="nome" label="Nome" :value="old('nome')" required maxlength="180" wide />
            <x-ui.input name="municipio" label="Municipio" :value="old('municipio')" maxlength="120" />
            <x-ui.input name="estado" label="UF" :value="old('estado')" maxlength="2" />
            <x-ui.input name="email" label="E-mail institucional" type="email" :value="old('email')" maxlength="254" />
            <x-ui.input name="telefone" label="Telefone institucional" :value="old('telefone')" maxlength="30" />
            <div class="form-actions field-wide">
                <x-ui.button type="submit">Criar nucleo</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card labelledby="lista-nucleos">
        <div class="section-heading">
            <div>
                <h2 id="lista-nucleos">Nucleos cadastrados</h2>
                <p>Resultados visiveis apenas para administracao global.</p>
            </div>
            <form class="filter-form" method="GET" action="{{ route('admin.nucleos.index') }}">
                <x-ui.input name="search" label="Buscar nucleo" :value="$filters['search'] ?? ''" placeholder="Nome ou codigo" label-hidden />
                <x-ui.select name="status" label="Filtrar por status" label-hidden>
                    <option value="">Todos os status</option>
                    <option value="ativo" @selected(($filters['status'] ?? '') === 'ativo')>Ativos</option>
                    <option value="inativo" @selected(($filters['status'] ?? '') === 'inativo')>Inativos</option>
                </x-ui.select>
                <x-ui.button type="submit" variant="neutral">Filtrar</x-ui.button>
            </form>
        </div>

        <x-ui.table caption="Nucleos cadastrados">
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
                        <tr><td colspan="5"><x-ui.empty-state title="Nenhum nucleo encontrado" compact>Revise os filtros informados.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
        </x-ui.table>
        {{ $nucleos->links() }}
    </x-ui.card>
@endsection
