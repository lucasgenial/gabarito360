@extends('layouts.admin')

@section('title', 'Gerenciar usuario')

@section('content')
    <header class="page-heading">
        <p class="eyebrow"><a href="{{ route('admin.usuarios.index') }}" wire:navigate>Usuarios</a> / {{ $usuario->nome }}</p>
        <h1>Gerenciar usuario</h1>
        <p>Somente dados essenciais e vinculos dentro do seu escopo sao exibidos.</p>
    </header>

    @can('update', $usuario)
        <section class="card" aria-labelledby="dados-usuario">
            <h2 id="dados-usuario">Dados de acesso</h2>
            <form class="form-grid" method="POST" action="{{ route('admin.usuarios.update', $usuario) }}">
                @csrf
                @method('PATCH')
                <div class="field">
                    <label for="nome">Nome</label>
                    <input id="nome" name="nome" value="{{ old('nome', $usuario->nome) }}" required maxlength="180">
                    <x-admin.field-error name="nome" />
                </div>
                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" required maxlength="254">
                    <x-admin.field-error name="email" />
                </div>
                <div class="form-actions field-wide">
                    <button class="button button-primary" type="submit">Salvar alteracoes</button>
                    <a class="button button-neutral" href="{{ route('admin.usuarios.index') }}" wire:navigate>Voltar</a>
                </div>
            </form>
        </section>
    @endcan

    <section class="card" aria-labelledby="perfis-usuario">
        <h2 id="perfis-usuario">Perfis ativos no seu escopo</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Perfil</th>
                        <th scope="col">Escopo</th>
                        <th scope="col"><span class="sr-only">Acoes</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuario->perfilVinculos as $vinculo)
                        <tr>
                            <td>{{ $vinculo->perfil->nome }}</td>
                            <td>{{ $vinculo->nucleo?->nome ?? $vinculo->escola?->nome ?? 'Global' }}</td>
                            <td>
                                @can('revokeProfile', [$usuario, $vinculo])
                                    <form method="POST" action="{{ route('admin.usuarios.perfis.destroy', [$usuario, $vinculo]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="button button-danger button-sm" type="submit">Revogar {{ $vinculo->perfil->nome }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty-state">Nenhum perfil ativo esta visivel no seu escopo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @can('assignProfile', $usuario)
        <section class="card" aria-labelledby="conceder-perfil">
            <h2 id="conceder-perfil">Conceder perfil</h2>
            <p class="help-text">Informe somente o nucleo ou a escola exigida pelo perfil selecionado.</p>
            <form class="form-grid" method="POST" action="{{ route('admin.usuarios.perfis.store', $usuario) }}">
                @csrf
                <div class="field">
                    <label for="perfil_id">Perfil</label>
                    <select id="perfil_id" name="perfil_id" required>
                        <option value="">Selecione um perfil</option>
                        @foreach ($options['perfis'] as $perfil)
                            <option value="{{ $perfil->id }}">{{ $perfil->nome }}</option>
                        @endforeach
                    </select>
                    <x-admin.field-error name="perfil_id" />
                </div>
                <div class="field">
                    <label for="nucleo_id">Nucleo, quando exigido</label>
                    <select id="nucleo_id" name="nucleo_id">
                        <option value="">Nao se aplica</option>
                        @foreach ($options['nucleos'] as $nucleo)
                            <option value="{{ $nucleo->id }}">{{ $nucleo->nome }}</option>
                        @endforeach
                    </select>
                    <x-admin.field-error name="nucleo_id" />
                </div>
                <div class="field">
                    <label for="escola_id">Escola, quando exigida</label>
                    <select id="escola_id" name="escola_id">
                        <option value="">Nao se aplica</option>
                        @foreach ($options['escolas'] as $escola)
                            <option value="{{ $escola->id }}">{{ $escola->nome }} / {{ $escola->nucleo->nome }}</option>
                        @endforeach
                    </select>
                    <x-admin.field-error name="escola_id" />
                </div>
                <div class="form-actions field-wide">
                    <button class="button button-primary" type="submit">Conceder perfil</button>
                </div>
            </form>
        </section>
    @endcan

    @can('delete', $usuario)
        @if ($usuario->status->value === 'ativo' && auth()->id() !== $usuario->id)
            <section class="card danger-zone" aria-labelledby="inativar-usuario">
                <h2 id="inativar-usuario">Inativar usuario</h2>
                <p>A inativacao encerra sessoes e revoga tokens e dispositivos, preservando o historico.</p>
                <form class="stack" method="POST" action="{{ route('admin.usuarios.inactivate', $usuario) }}">
                    @csrf
                    <label class="check-field"><input name="confirmacao" type="checkbox" required> Confirmo a inativacao deste usuario.</label>
                    <button class="button button-danger" type="submit">Inativar usuario</button>
                </form>
            </section>
        @endif
    @endcan
@endsection
