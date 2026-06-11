@extends('layouts.admin')

@section('title', 'Usuarios')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Acesso</p>
        <h1>Usuarios</h1>
        <p>Crie acessos com um perfil inicial dentro do seu escopo autorizado.</p>
    </header>

    <section class="card" aria-labelledby="novo-usuario">
        <h2 id="novo-usuario">Novo usuario</h2>
        <p class="help-text">Informe somente o nucleo ou a escola exigida pelo perfil selecionado.</p>
        <form class="form-grid" method="POST" action="{{ route('admin.usuarios.store') }}">
            @csrf
            <div class="field">
                <label for="nome">Nome</label>
                <input id="nome" name="nome" value="{{ old('nome') }}" required maxlength="180">
                <x-admin.field-error name="nome" />
            </div>
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="254">
                <x-admin.field-error name="email" />
            </div>
            <div class="field">
                <label for="password">Senha inicial</label>
                <input id="password" name="password" type="password" required minlength="12" autocomplete="new-password">
                <x-admin.field-error name="password" />
            </div>
            <div class="field">
                <label for="password_confirmation">Confirmar senha inicial</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" autocomplete="new-password">
            </div>
            <div class="field">
                <label for="perfil_id">Perfil inicial</label>
                <select id="perfil_id" name="perfil_id" required>
                    <option value="">Selecione um perfil</option>
                    @foreach ($options['perfis'] as $perfil)
                        <option value="{{ $perfil->id }}" @selected(old('perfil_id') === $perfil->id)>{{ $perfil->nome }}</option>
                    @endforeach
                </select>
                <x-admin.field-error name="perfil_id" />
            </div>
            <div class="field">
                <label for="nucleo_id">Nucleo, quando exigido</label>
                <select id="nucleo_id" name="nucleo_id">
                    <option value="">Nao se aplica</option>
                    @foreach ($options['nucleos'] as $nucleo)
                        <option value="{{ $nucleo->id }}" @selected(old('nucleo_id') === $nucleo->id)>{{ $nucleo->nome }}</option>
                    @endforeach
                </select>
                <x-admin.field-error name="nucleo_id" />
            </div>
            <div class="field">
                <label for="escola_id">Escola, quando exigida</label>
                <select id="escola_id" name="escola_id">
                    <option value="">Nao se aplica</option>
                    @foreach ($options['escolas'] as $escola)
                        <option value="{{ $escola->id }}" @selected(old('escola_id') === $escola->id)>{{ $escola->nome }} / {{ $escola->nucleo->nome }}</option>
                    @endforeach
                </select>
                <x-admin.field-error name="escola_id" />
            </div>
            <div class="form-actions field-wide">
                <button class="button button-primary" type="submit">Criar usuario</button>
            </div>
        </form>
    </section>

    <section class="card" aria-labelledby="lista-usuarios">
        <div class="section-heading">
            <div>
                <h2 id="lista-usuarios">Usuarios autorizados</h2>
                <p>Documentos e telefones nao sao exibidos nesta lista.</p>
            </div>
            <form class="filter-form" method="GET" action="{{ route('admin.usuarios.index') }}">
                <label class="sr-only" for="search">Buscar usuario</label>
                <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nome ou e-mail">
                <label class="sr-only" for="status">Filtrar por status</label>
                <select id="status" name="status">
                    <option value="">Todos os status</option>
                    <option value="ativo" @selected(($filters['status'] ?? '') === 'ativo')>Ativos</option>
                    <option value="inativo" @selected(($filters['status'] ?? '') === 'inativo')>Inativos</option>
                    <option value="bloqueado" @selected(($filters['status'] ?? '') === 'bloqueado')>Bloqueados</option>
                </select>
                <button class="button button-neutral" type="submit">Filtrar</button>
            </form>
        </div>

        <div class="table-wrap">
            <table>
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
                                        <span class="badge badge-info">{{ $vinculo->perfil->nome }}</span>
                                    @empty
                                        <span class="cell-detail">Nenhum perfil visivel</span>
                                    @endforelse
                                </div>
                            </td>
                            <td><x-admin.status-badge :status="$usuario->status->value" /></td>
                            <td><a class="text-link" href="{{ route('admin.usuarios.edit', $usuario) }}" wire:navigate>Gerenciar {{ $usuario->nome }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state">Nenhum usuario encontrado para os filtros informados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $usuarios->links() }}
    </section>
@endsection
