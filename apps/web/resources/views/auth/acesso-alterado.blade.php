@extends('layouts.auth', ['semMenuPublico' => true])
@section('title', 'Confira seu acesso')
@section('content')
<main class="vinculo-pagina">
  <section class="card vinculo-card" aria-labelledby="acesso-alterado-titulo">
    <h1 id="acesso-alterado-titulo">Seu acesso ativo mudou</h1>
    <x-aviso tipo="atencao">Esta ação não foi executada. Confira o espaço e o perfil antes de continuar.</x-aviso>
    <p>Os dados desta tela não foram reenviados para o novo acesso. Se precisar recuperar o que digitou, volte à aba anterior antes de sair dela.</p>
    <div class="contexto-aviso-acoes">
      <a class="btn btn-primary" href="{{ route('painel') }}">Abrir painel atual</a>
      <a class="btn btn-secondary" href="{{ route('vinculos.index') }}">Selecionar outro acesso</a>
    </div>
  </section>
</main>
@endsection
