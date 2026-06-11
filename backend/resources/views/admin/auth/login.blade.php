@extends('layouts.guest')

@section('title', 'Entrar no painel')

@section('content')
    <section class="auth-card" aria-labelledby="login-title">
        <div class="brand auth-brand">
            <span class="brand-mark" aria-hidden="true">G360</span>
            <span>Gabarito360</span>
        </div>

        <div class="page-heading">
            <p class="eyebrow">Painel administrativo</p>
            <h1 id="login-title">Acesse sua conta</h1>
            <p>Use suas credenciais institucionais para continuar.</p>
        </div>

        @if ($errors->any())
            <x-ui.alert variant="error">
                Credenciais invalidas. Revise os dados e tente novamente.
            </x-ui.alert>
        @endif

        <form class="stack" method="POST" action="{{ route('admin.login.store') }}">
            @csrf
            <x-ui.input name="email" label="E-mail" type="email" :value="old('email')" autocomplete="username" required autofocus />
            <x-ui.input name="password" label="Senha" type="password" autocomplete="current-password" required />
            <x-ui.button type="submit">Entrar</x-ui.button>
        </form>
    </section>
@endsection
