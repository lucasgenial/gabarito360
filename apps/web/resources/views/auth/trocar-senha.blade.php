@extends('layouts.auth')

@section('title', 'Troque sua senha')

@section('content')
<main class="convite">
  <div class="convite-cartao">
    <a class="brand convite-marca"><span class="logo">G360</span> Gabarito360</a>
    <h1>Troque sua senha</h1>

    @if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

    <p class="convite-texto">Sua escola pediu que você crie uma nova senha antes de continuar.</p>

    <form method="POST" action="{{ route('trocar-senha.store') }}">
      @csrf
      @method('PUT')

      <div class="field">
        <label for="senha_atual">Senha atual</label>
        <input class="input" type="password" id="senha_atual" name="senha_atual" required autocomplete="current-password">
        @error('senha_atual')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="field">
        <label for="password">Nova senha</label>
        <input class="input" type="password" id="password" name="password" minlength="8" required autocomplete="new-password" placeholder="Mínimo 8 caracteres">
        @error('password')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="field">
        <label for="password_confirmation">Confirme a nova senha</label>
        <input class="input" type="password" id="password_confirmation" name="password_confirmation" minlength="8" required autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Salvar nova senha</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="convite-recusar">
      @csrf
      <button type="submit" class="btn btn-ghost btn-block">Sair</button>
    </form>
  </div>
</main>
@endsection
