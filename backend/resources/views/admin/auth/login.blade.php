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
            <div class="alert alert-error" role="alert">Credenciais invalidas. Revise os dados e tente novamente.</div>
        @endif

        <form class="stack" method="POST" action="{{ route('admin.login.store') }}">
            @csrf
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus aria-describedby="@error('email') email-error @enderror">
                <x-admin.field-error name="email" />
            </div>

            <div class="field">
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>

            <button class="button button-primary" type="submit">Entrar</button>
        </form>
    </section>
@endsection
