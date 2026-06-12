@extends('layouts.admin')

@section('title', 'Meu perfil')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Conta</p>
        <h1>Meu perfil</h1>
        <p>Dados da conta, vinculos vigentes e sessoes mobile registradas.</p>
    </header>

    <div class="content-grid">
        <x-ui.card labelledby="dados-conta">
            <h2 id="dados-conta">Dados da conta</h2>
            <form class="form-grid" method="POST" action="{{ route('portal.profile.update') }}">
                @csrf
                @method('PATCH')
                <x-ui.input name="nome" label="Nome" :value="old('nome', $user->nome)" required maxlength="180" />
                <x-ui.input name="email" label="E-mail" type="email" :value="old('email', $user->email)" required maxlength="254" />
                <div class="form-actions field-wide">
                    <x-ui.button type="submit">Salvar perfil</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card labelledby="vinculos-vigentes">
            <h2 id="vinculos-vigentes">Vinculos vigentes</h2>
            <div class="stack">
                @forelse ($user->perfilVinculos->whereNull('fim_at') as $link)
                    <div>
                        <strong>{{ $link->perfil->nome }}</strong>
                        <span class="cell-detail">{{ $link->escola?->nome ?? $link->nucleo?->nome ?? 'Escopo global' }}</span>
                    </div>
                @empty
                    <x-ui.empty-state title="Nenhum perfil vigente" compact>Solicite apoio ao administrador.</x-ui.empty-state>
                @endforelse
            </div>
        </x-ui.card>
    </div>

    <x-ui.card labelledby="sessoes-mobile">
        <h2 id="sessoes-mobile">Dispositivos e sessoes mobile</h2>
        <x-ui.table caption="Dispositivos mobile registrados">
            <thead>
                <tr>
                    <th scope="col">Dispositivo</th>
                    <th scope="col">Versao</th>
                    <th scope="col">Ultimo acesso</th>
                    <th scope="col">Situacao</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($user->dispositivosMobile as $device)
                    <tr>
                        <td>{{ $device->modelo_dispositivo ?? 'Android registrado' }}<span class="cell-detail">{{ $device->identificador }}</span></td>
                        <td>{{ $device->versao_app }}</td>
                        <td>{{ $device->ultimo_acesso_at?->format('d/m/Y H:i') ?? 'Sem acesso registrado' }}</td>
                        <td><x-admin.status-badge :status="$device->revogado_at ? 'revogado' : 'ativo'" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-ui.empty-state title="Nenhum dispositivo registrado" compact>O registro ocorre no fluxo de autenticacao mobile.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>
@endsection
