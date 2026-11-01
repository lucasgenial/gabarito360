@extends('layouts.auth', ['semMenuPublico' => true])
@section('title', 'Convite para um vínculo')
@section('content')
<main class="vinculo-pagina"><a class="brand" href="{{ route('home') }}"><span class="logo">G360</span> Gabarito360</a>
<section class="card vinculo-card" id="convite-vinculo" data-autenticado="{{ session('auth_token') ? '1' : '0' }}" data-trocar-senha="{{ session('auth_trocar_senha') ? '1' : '0' }}" data-consultar="{{ route('vinculos.convite.consultar') }}">
<h1>Convite para um vínculo</h1>
@if(session('erro'))<p role="alert">{{ session('erro') }}</p>@endif
<p id="convite-mensagem" role="status">Conferindo seu convite…</p>
<div id="convite-entrar" hidden><p>Entre na conta que recebeu o convite para conferir a instituição e a função.</p><a class="btn btn-primary" href="{{ route('login', ['retorno_convite' => 1]) }}">Entrar na minha conta</a></div>
<div id="convite-senha" hidden><p>Conclua a troca de senha solicitada para continuar.</p><a class="btn btn-primary" href="{{ route('trocar-senha', ['retorno_convite' => 1]) }}">Trocar senha</a></div>
<div id="convite-detalhes" hidden>
<p>Você está usando a conta <strong>{{ session('auth_email') }}</strong>.</p>
<dl class="convite-dados">
<div><dt>Instituição</dt><dd data-convite="vinculo"></dd></div><div><dt>Função</dt><dd data-convite="funcao"></dd></div>
<div><dt>Turmas</dt><dd data-convite="turmas"></dd></div><div><dt>Convidado por</dt><dd data-convite="convidado_por"></dd></div>
</dl>
<p>O aceite adiciona este vínculo à sua conta e preserva sua senha e seus outros vínculos.</p>
<form method="POST" action="{{ route('vinculos.convite.responder') }}">
@csrf<input type="hidden" name="referencia" id="convite-referencia">
<button class="btn btn-primary" name="aceitar" value="1" type="submit">Aceitar vínculo</button>
<button class="btn btn-outline" name="aceitar" value="0" type="submit">Recusar convite</button>
</form>
</div>
@if(session('auth_token'))<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost">Sair desta conta</button></form>@endif
<noscript><p>Ative o JavaScript e abra novamente o link do convite.</p></noscript>
</section></main>
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="{{ asset('js/convite-vinculo.js') }}?v={{ @filemtime(public_path('js/convite-vinculo.js')) }}"></script>
@endsection
