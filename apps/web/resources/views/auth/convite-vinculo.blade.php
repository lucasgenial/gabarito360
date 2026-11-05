@extends('layouts.auth', ['semMenuPublico' => true])
@section('title', 'Convite para um vínculo')
@section('content')
<main class="vinculo-pagina"><a class="brand" href="{{ route('home') }}"><x-marca /></a>
<section class="card vinculo-card" id="convite-vinculo" data-autenticado="{{ session('auth_token') ? '1' : '0' }}" data-trocar-senha="{{ session('auth_trocar_senha') ? '1' : '0' }}" aria-labelledby="convite-vinculo-titulo">
  <h1 id="convite-vinculo-titulo">Convite para um vínculo</h1>
  @if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
  @if(!session('auth_token'))
    <p>Entre na conta que recebeu o convite. Depois, cole o código recebido por e-mail.</p>
    <a class="btn btn-primary" href="{{ route('login', ['retorno_convite' => 1]) }}">Entrar na minha conta</a>
  @elseif(session('auth_trocar_senha'))
    <p>Conclua a troca de senha solicitada para continuar.</p>
    <a class="btn btn-primary" href="{{ route('trocar-senha', ['retorno_convite' => 1]) }}">Trocar senha</a>
  @elseif(empty($convite))
    <p>Você está usando a conta <strong>{{ session('auth_email') }}</strong>.</p>
    <form method="POST" action="{{ route('vinculos.convite.consultar') }}">
      @csrf
      <div class="field"><label for="vinculo-codigo">Código do convite</label><input class="input" id="vinculo-codigo" type="text" name="codigo" required maxlength="84" autocomplete="one-time-code" autocapitalize="none" spellcheck="false" aria-describedby="vinculo-codigo-erro vinculo-codigo-ajuda" aria-invalid="{{ $errors->has('codigo') ? 'true' : 'false' }}"><p class="field-help" id="vinculo-codigo-ajuda">Cole o código completo recebido no e-mail.</p>@error('codigo')<span id="vinculo-codigo-erro" role="alert" class="field-error">Não foi possível conferir o código. Copie-o completo ou peça um novo convite.</span>@enderror</div>
      <button class="btn btn-primary btn-block" type="submit">Conferir convite</button>
    </form>
  @else
    <p>Confira os dados antes de aceitar ou recusar.</p>
    <p>Você está usando a conta <strong>{{ session('auth_email') }}</strong>.</p>
    <dl class="convite-dados">
      <div><dt>Instituição</dt><dd>{{ $convite['vinculo'] ?? '—' }}</dd></div>
      <div><dt>Função</dt><dd>{{ $convite['funcao'] ?? '—' }}</dd></div>
      <div><dt>Turmas</dt><dd>{{ $convite['turmas'] ?? '—' }}</dd></div>
      <div><dt>Convidado por</dt><dd>{{ $convite['convidado_por'] ?? '—' }}</dd></div>
    </dl>
    <p>O aceite adiciona este vínculo à sua conta e preserva sua senha e seus outros vínculos.</p>
    <form method="POST" action="{{ route('vinculos.convite.responder') }}">
      @csrf<input type="hidden" name="referencia" value="{{ $referencia }}">
      <button class="btn btn-primary" name="aceitar" value="1" type="submit">Aceitar vínculo</button>
      <button class="btn btn-outline" name="aceitar" value="0" type="submit">Recusar convite</button>
    </form>
  @endif
  @if(session('auth_token'))<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost" type="submit">Sair desta conta</button></form>@endif
</section></main>
<script src="{{ asset('js/convite-vinculo.js') }}?v={{ @filemtime(public_path('js/convite-vinculo.js')) }}"></script>
@endsection
