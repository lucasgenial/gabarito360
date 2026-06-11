@extends('layouts.admin')

@section('title', 'Gerenciar usuario')

@section('content')
    <header class="page-heading">
        <p class="eyebrow"><a href="{{ route('admin.usuarios.index') }}" wire:navigate>Usuarios</a> / {{ $usuario->nome }}</p>
        <h1>Gerenciar usuario</h1>
        <p>Somente dados essenciais e vinculos dentro do seu escopo sao exibidos.</p>
    </header>

    @can('update', $usuario)
        <x-ui.card labelledby="dados-usuario">
            <h2 id="dados-usuario">Dados de acesso</h2>
            <form class="form-grid" method="POST" action="{{ route('admin.usuarios.update', $usuario) }}">
                @csrf
                @method('PATCH')
                <x-ui.input name="nome" label="Nome" :value="old('nome', $usuario->nome)" required maxlength="180" />
                <x-ui.input name="email" label="E-mail" type="email" :value="old('email', $usuario->email)" required maxlength="254" />
                <div class="form-actions field-wide">
                    <x-ui.button type="submit">Salvar alteracoes</x-ui.button>
                    <x-ui.button :href="route('admin.usuarios.index')" variant="neutral" wire:navigate>Voltar</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endcan

    <x-ui.card labelledby="perfis-usuario">
        <h2 id="perfis-usuario">Perfis ativos no seu escopo</h2>
        <x-ui.table caption="Perfis ativos do usuario">
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
                                        <x-ui.button type="submit" variant="danger" size="sm">Revogar {{ $vinculo->perfil->nome }}</x-ui.button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-ui.empty-state title="Nenhum perfil ativo" compact>Nenhum perfil esta visivel no seu escopo.</x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
        </x-ui.table>
    </x-ui.card>

    @can('assignProfile', $usuario)
        <x-ui.card labelledby="conceder-perfil">
            <h2 id="conceder-perfil">Conceder perfil</h2>
            <p class="help-text">Informe somente o nucleo ou a escola exigida pelo perfil selecionado.</p>
            <form class="form-grid" method="POST" action="{{ route('admin.usuarios.perfis.store', $usuario) }}">
                @csrf
                <x-ui.select name="perfil_id" label="Perfil" required>
                        <option value="">Selecione um perfil</option>
                        @foreach ($options['perfis'] as $perfil)
                            <option value="{{ $perfil->id }}">{{ $perfil->nome }}</option>
                        @endforeach
                </x-ui.select>
                <x-ui.select name="nucleo_id" label="Nucleo, quando exigido">
                        <option value="">Nao se aplica</option>
                        @foreach ($options['nucleos'] as $nucleo)
                            <option value="{{ $nucleo->id }}">{{ $nucleo->nome }}</option>
                        @endforeach
                </x-ui.select>
                <x-ui.select name="escola_id" label="Escola, quando exigida">
                        <option value="">Nao se aplica</option>
                        @foreach ($options['escolas'] as $escola)
                            <option value="{{ $escola->id }}">{{ $escola->nome }} / {{ $escola->nucleo->nome }}</option>
                        @endforeach
                </x-ui.select>
                <div class="form-actions field-wide">
                    <x-ui.button type="submit">Conceder perfil</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endcan

    @can('delete', $usuario)
        @if ($usuario->status->value === 'ativo' && auth()->id() !== $usuario->id)
            <x-ui.card labelledby="inativar-usuario" variant="danger">
                <h2 id="inativar-usuario">Inativar usuario</h2>
                <p>A inativacao encerra sessoes e revoga tokens e dispositivos, preservando o historico.</p>
                <form class="stack" method="POST" action="{{ route('admin.usuarios.inactivate', $usuario) }}">
                    @csrf
                    <label class="check-field"><input name="confirmacao" type="checkbox" required> Confirmo a inativacao deste usuario.</label>
                    <x-ui.button type="submit" variant="danger">Inativar usuario</x-ui.button>
                </form>
            </x-ui.card>
        @endif
    @endcan
@endsection
