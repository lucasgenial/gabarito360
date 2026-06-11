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
            <article class="card">
                <h2>Nucleos</h2>
                <p>Cadastre e mantenha unidades organizacionais.</p>
                <a class="button button-primary" href="{{ route('admin.nucleos.index') }}" wire:navigate>Acessar nucleos</a>
            </article>
        @endif

        @if ($access['escolas'])
            <article class="card">
                <h2>Escolas</h2>
                <p>Gerencie escolas dentro do seu escopo autorizado.</p>
                <a class="button button-primary" href="{{ route('admin.escolas.index') }}" wire:navigate>Acessar escolas</a>
            </article>
        @endif

        @if ($access['usuarios'])
            <article class="card">
                <h2>Usuarios</h2>
                <p>Mantenha acessos e vinculos de perfil autorizados.</p>
                <a class="button button-primary" href="{{ route('admin.usuarios.index') }}" wire:navigate>Acessar usuarios</a>
            </article>
        @endif
    </div>
@endsection
