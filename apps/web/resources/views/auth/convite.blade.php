@extends('layouts.auth')

@section('title', 'Convite para o Gabarito360')

@section('content')
<main class="convite">
  <div class="convite-cartao">
    <a class="brand convite-marca"><span class="logo">G360</span> Gabarito360</a>
    <h1>Seu convite</h1>

    @if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

    @if(!$convite)
      <p class="convite-texto">Este convite é inválido ou venceu. Peça um novo a quem criou sua conta.</p>
      <a class="btn btn-primary" href="{{ route('login') }}">Ir para o login</a>
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
          <input class="input" type="password" id="password" name="password" minlength="8" required autocomplete="new-password" placeholder="Mínimo 8 caracteres">
          @error('password')<span class="field-error">{{ $message }}</span>@enderror
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
