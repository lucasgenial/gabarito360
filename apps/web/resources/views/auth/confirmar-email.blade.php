@extends('layouts.auth', ['semMenuPublico' => true])
@section('title', 'Confirmar e-mail')
@section('content')
<main class="vinculo-pagina">
  <a class="brand" href="{{ route('home') }}"><x-marca /></a>
  <section class="card vinculo-card" aria-labelledby="confirmar-email-titulo">
    <h1 id="confirmar-email-titulo">Confirmar novo e-mail</h1>
    <p>Cole o código enviado ao novo endereço. O e-mail atual continua valendo até você confirmar.</p>
    @if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
    <form method="POST" action="{{ route('email.confirmar.store') }}">
      @csrf <x-revisao-acesso />
      <div class="field"><label for="email-codigo">Código de confirmação</label><input class="input" id="email-codigo" type="text" name="token" required minlength="64" maxlength="64" autocomplete="one-time-code" autocapitalize="none" spellcheck="false" aria-describedby="email-codigo-erro email-codigo-ajuda" aria-invalid="{{ $errors->has('token') ? 'true' : 'false' }}"><p class="field-help" id="email-codigo-ajuda">Copie os 64 caracteres recebidos por e-mail. O código vale por 24 horas.</p>@error('token')<span id="email-codigo-erro" role="alert" class="field-error">{{ $message }}</span>@enderror</div>
      <button class="btn btn-primary btn-block" type="submit">Confirmar novo e-mail</button>
    </form>
    <a class="btn btn-ghost" href="{{ route('login') }}">Voltar</a>
  </section>
</main>
@endsection
