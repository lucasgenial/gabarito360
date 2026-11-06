@extends('layouts.auth', ['semMenuPublico' => true])
@section('title', 'Turma indisponível')
@section('content')
<main class="vinculo-pagina">
  <a class="brand" href="{{ route('home') }}"><x-marca /></a>
  <section class="card vinculo-card" aria-labelledby="turma-indisponivel-titulo">
    <h1 id="turma-indisponivel-titulo">Não foi possível carregar esta turma agora</h1>
    <p role="status">Tente novamente. Os dados da turma não foram alterados.</p>
    <div class="contexto-aviso-acoes">
      <a class="btn btn-primary" href="{{ route('turmas.show', ['id' => request()->route('id')]) }}">Tentar novamente</a>
      <a class="btn btn-secondary" href="{{ route('turmas.index') }}">Voltar às turmas</a>
    </div>
  </section>
</main>
@endsection
