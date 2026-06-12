@extends('layouts.admin')

@section('title', 'Equipe da escola')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">{{ $escola->nome }}</p>
        <h1>Equipe e perfis</h1>
        <p>Membros com vinculo vigente visivel para o seu escopo administrativo.</p>
    </header>

    <div class="form-actions">
        <x-ui.button :href="route('portal.schools.team.create', $escola)" wire:navigate>Novo membro</x-ui.button>
    </div>

    <x-ui.card>
        <x-ui.table caption="Equipe da escola">
            <thead>
                <tr>
                    <th scope="col">Membro</th>
                    <th scope="col">Perfis vigentes</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="sr-only">Acoes</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr>
                        <td>{{ $member->nome }}<span class="cell-detail">{{ $member->email }}</span></td>
                        <td>
                            <div class="badge-list">
                                @foreach ($member->perfilVinculos as $link)
                                    <x-ui.badge variant="info">{{ $link->perfil->nome }}</x-ui.badge>
                                @endforeach
                            </div>
                        </td>
                        <td><x-admin.status-badge :status="$member->status->value" /></td>
                        <td><a class="text-link" href="{{ route('portal.schools.team.edit', [$escola, $member]) }}" wire:navigate>Gerenciar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-ui.empty-state title="Nenhum membro encontrado" compact>Cadastre a equipe no fluxo administrativo.</x-ui.empty-state></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
        <x-ui.pagination :paginator="$members" />
    </x-ui.card>
@endsection
