@extends('layouts.auth')

@section('title', 'Convite para o Gabarito360')

@section('content')
<main class="convite">
  <div class="convite-cartao">
    <a class="brand convite-marca" href="{{ route('home') }}"><x-marca /></a>
    <h1>Seu convite</h1>

    @if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

    @if(!$convite)
      <p class="convite-texto">Informe o e-mail que recebeu o convite e cole o código completo da mensagem.</p>
      <form method="POST" action="{{ route('convite.conferir') }}">
        @csrf
        <div class="field"><label for="convite-email">E-mail</label><input class="input" id="convite-email" type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="convite-email-erro">@error('email')<span id="convite-email-erro" role="alert" class="field-error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="convite-codigo">Código do convite</label><input class="input" id="convite-codigo" type="text" name="token" required minlength="64" maxlength="64" autocomplete="one-time-code" autocapitalize="none" spellcheck="false" aria-describedby="convite-codigo-erro convite-codigo-ajuda" aria-invalid="{{ $errors->has('token') ? 'true' : 'false' }}"><p class="field-help" id="convite-codigo-ajuda">Copie os 64 caracteres recebidos por e-mail.</p>@error('token')<span id="convite-codigo-erro" role="alert" class="field-error">{{ $message }}</span>@enderror</div>
        <button class="btn btn-primary btn-block" type="submit">Conferir convite</button>
      </form>
      <a class="btn btn-ghost btn-block" href="{{ route('login') }}">Ir para o login</a>
    @else
      <p class="convite-texto">Olá, <b>{{ $convite['nome'] }}</b>. <b>{{ $convite['convidado_por'] }}</b> convidou você para usar o sistema.</p>

      <dl class="convite-dados">
        <div><dt>Função</dt><dd>{{ $convite['funcao'] }}</dd></div>
        <div><dt>{{ $convite['tipo_vinculo'] }}</dt><dd>{{ $convite['vinculo'] }}</dd></div>
        <div><dt>Turmas</dt><dd>{{ $convite['turmas'] }}</dd></div>
      </dl>

      <form method="POST" action="{{ route('redefinir-senha.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="field">
          <label for="password">Crie sua senha</label>
          <input class="input" type="password" id="password" name="password" minlength="8" required autocomplete="new-password" placeholder="Mínimo 8 caracteres" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-erro">
          @error('password')<span id="password-erro" role="alert" class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
          <label for="password_confirmation">Confirme a senha</label>
          <input class="input" type="password" id="password_confirmation" name="password_confirmation" minlength="8" required autocomplete="new-password">
        </div>
        <label class="convite-aceite">
          <input type="checkbox" name="aceite" value="1" required>
          <span>Confirmo que aceito atuar nesta função e vínculo.</span>
        </label>
        <button type="submit" class="btn btn-primary btn-block">Aceitar convite</button>
      </form>

      <form method="POST" action="{{ route('convite.recusar') }}" class="convite-recusar">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <button type="submit" class="btn btn-ghost btn-block">Recusar convite</button>
      </form>
    @endif
  </div>
</main>
@endsection
