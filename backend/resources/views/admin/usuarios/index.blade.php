@extends('layouts.admin')

@section('title', 'Usuarios')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Acesso</p>
        <h1>Usuarios</h1>
        <p>Crie acessos com um perfil inicial dentro do seu escopo autorizado.</p>
    </header>

    <x-ui.card labelledby="novo-usuario">
        <h2 id="novo-usuario">Novo usuario</h2>
        <p class="help-text">Informe somente o nucleo ou a escola exigida pelo perfil selecionado.</p>
        <form class="form-grid" method="POST" action="{{ route('admin.usuarios.store') }}">
            @csrf
            <x-ui.input name="nome" label="Nome" :value="old('nome')" required maxlength="180" />
            <x-ui.input name="email" label="E-mail" type="email" :value="old('email')" required maxlength="254" />
            <x-ui.input name="password" label="Senha inicial" type="password" required minlength="12" autocomplete="new-password" />
            <x-ui.input name="password_confirmation" label="Confirmar senha inicial" type="password" required minlength="12" autocomplete="new-password" />
            <x-ui.select name="perfil_id" label="Perfil inicial" required>
                    <option value="">Selecione um perfil</option>
                    @foreach ($options['perfis'] as $perfil)
                        <option value="{{ $perfil->id }}" @selected(old('perfil_id') === $perfil->id)>{{ $perfil->nome }}</option>
                    @endforeach
            </x-ui.select>
            <x-ui.select name="nucleo_id" label="Nucleo, quando exigido">
                    <option value="">Nao se aplica</option>
                    @foreach ($options['nucleos'] as $nucleo)
                        <option value="{{ $nucleo->id }}" @selected(old('nucleo_id') === $nucleo->id)>{{ $nucleo->nome }}</option>
                    @endforeach
            </x-ui.select>
            <x-ui.select name="escola_id" label="Escola, quando exigida">
                    <option value="">Nao se aplica</option>
                    @foreach ($options['escolas'] as $escola)
                        <option value="{{ $escola->id }}" @selected(old('escola_id') === $escola->id)>{{ $escola->nome }} / {{ $escola->nucleo->nome }}</option>
                    @endforeach
            </x-ui.select>
            <div class="form-actions field-wide">
                <x-ui.button type="submit">Criar usuario</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card labelledby="lista-usuarios">
        <div class="section-heading">
            <div>
                <h2 id="lista-usuarios">Usuarios autorizados</h2>
                <p>Documentos e telefones nao sao exibidos nesta lista.</p>
            </div>
            <form class="filter-form" method="GET" action="{{ route('admin.usuarios.index') }}">
                <x-ui.input name="search" label="Buscar usuario" :value="$filters['search'] ?? ''" placeholder="Nome ou e-mail" label-hidden />
                <x-ui.select name="status" label="Filtrar por status" label-hidden>
                    <option value="">Todos os status</option>
                    <option value="ativo" @selected(($filters['status'] ?? '') === 'ativo')>Ativos</option>
                    <option value="inativo" @selected(($filters['status'] ?? '') === 'inativo')>Inativos</option>
                    <option value="bloqueado" @selected(($filters['status'] ?? '') === 'bloqueado')>Bloqueados</option>
                </x-ui.select>
                <x-ui.button type="submit" variant="neutral">Filtrar</x-ui.button>
            </form>
        </div>

        <x-ui.table caption="Usuarios autorizados">
                <thead>
                    <tr>
                        <th scope="col">Usuario</th>
                        <th scope="col">Perfis visiveis</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Acoes</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td>{{ $usuario->nome }}<span class="cell-detail">{{ $usuario->email }}</span></td>
                            <td>
                                <div class="badge-list">
                                    @forelse ($usuario->perfilVinculos as $vinculo)
                                        <x-ui.badge variant="info">{{ $vinculo->perfil->nome }}</x-ui.badge>
                                    @empty
                                        <span class="cell-detail">Nenhum perfil visivel</span>
                                    @endforelse
                                </div>
                            </td>
                            <td><x-admin.status-badge :status="$usuario->status->value" /></td>
                            <td><a class="text-link" href="{{ route('admin.usuarios.edit', $usuario) }}" wire:navigate>Gerenciar {{ $usuario->nome }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-ui.empty-state title="Nenhum usuario encontrado" compact>Revise os filtros informados.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
        </x-ui.table>
        <x-ui.pagination :paginator="$usuarios" />
    </x-ui.card>
@endsection
