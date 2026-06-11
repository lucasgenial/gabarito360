@extends('layouts.admin')

@section('title', 'Inicio')

@section('content')
    <header class="page-heading">
        <p class="eyebrow">Organizacao</p>
        <h1>Gestao organizacional</h1>
        <p>Escolha uma area autorizada para manter a estrutura do Gabarito360.</p>
    </header>

    <div class="card-grid">
        @if ($access['nucleos'])
            <x-ui.card>
                <h2>Nucleos</h2>
                <p>Cadastre e mantenha unidades organizacionais.</p>
                <x-ui.button :href="route('admin.nucleos.index')" wire:navigate>Acessar nucleos</x-ui.button>
            </x-ui.card>
        @endif

        @if ($access['escolas'])
            <x-ui.card>
                <h2>Escolas</h2>
                <p>Gerencie escolas dentro do seu escopo autorizado.</p>
                <x-ui.button :href="route('admin.escolas.index')" wire:navigate>Acessar escolas</x-ui.button>
            </x-ui.card>
        @endif

        @if ($access['usuarios'])
            <x-ui.card>
                <h2>Usuarios</h2>
                <p>Mantenha acessos e vinculos de perfil autorizados.</p>
                <x-ui.button :href="route('admin.usuarios.index')" wire:navigate>Acessar usuarios</x-ui.button>
            </x-ui.card>
        @endif

        @if ($access['provas'])
            <x-ui.card>
                <h2>Provas e turmas</h2>
                <p>Consulte gabaritos vigentes e autorize turmas para provas publicadas.</p>
                <x-ui.button :href="route('admin.provas.index')" wire:navigate>Acessar provas</x-ui.button>
            </x-ui.card>
        @endif
    </div>
@endsection
