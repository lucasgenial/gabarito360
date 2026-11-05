<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<meta name="description" content="{{ config('apresentacao.descricao') }}"/>
<title>{{ config('apresentacao.titulo') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}"/>
@include('layouts._marca-head', ['tituloSocial'=>config('apresentacao.titulo'),'descricaoSocial'=>config('apresentacao.descricao')])
<link rel="stylesheet" href="{{ asset('css/apresentacao-publica.css') }}?v={{ filemtime(public_path('css/apresentacao-publica.css')) }}">
</head>
<body class="area-publica tela-inicio publico-apresentacao">

<a class="pular-conteudo" href="#conteudo">Pular para o conteúdo</a>
<x-menu-publico />
<main id="conteudo">

<section class="lp-abertura" aria-labelledby="titulo">
  <div class="lp-inner lp-abertura-grade">
    <div class="lp-texto">
      <p class="publico-prelancamento">Conheça o Gabarito360 em pré-lançamento</p>
      <h1 id="titulo">Da prova impressa à nota da turma, pelo celular.</h1>
      <p>Uma plataforma de avaliação para professores e instituições: organize gabaritos e cartões, confira as leituras e transforme os resultados em acompanhamento da aprendizagem.</p>
      <div class="lp-acoes">
        <a href="{{ route('leads.index') }}" class="btn btn-primary">Falar com a gente</a>
        <a href="#como-funciona" class="btn btn-secondary">Ver como funciona</a>
      </div>
    </div>
    <figure class="lp-produto">
      <div class="lp-cartao" aria-hidden="true">
        @foreach([['01', 'B'], ['02', 'D'], ['03', 'A'], ['04', 'C'], ['05', 'E']] as [$q, $m])
          <div class="lp-linha"><b>{{ $q }}</b>@foreach(['A', 'B', 'C', 'D', 'E'] as $l)<span class="{{ $l === $m ? 'marcada' : '' }}">{{ $l }}</span>@endforeach</div>
        @endforeach
      </div>
      <div class="lp-resultado">
        <div class="lp-resultado-topo"><b>3º A</b><span>42 de 54 alunos com resultado</span></div>
        <div class="progress-track"><div style="width:78%"></div></div>
        <table>
          <caption>Resultados de exemplo</caption>
          <thead><tr><th scope="col">Aluno</th><th scope="col">Nota</th></tr></thead><tbody>
          <tr><td>Aluno de exemplo 1</td><td>8,5</td></tr>
          <tr><td>Aluno de exemplo 2</td><td>6,0</td></tr>
          <tr><td>Aluno de exemplo 3</td><td>7,5</td></tr>
          </tbody>
        </table>
        <p class="lp-revisar">5 leituras para revisar</p>
      </div>
      <figcaption>Exemplo com dados fictícios.</figcaption>
    </figure>
  </div>
</section>

<section class="lp-secao" aria-labelledby="t-rotina">
  <div class="lp-inner">
    <h2 id="t-rotina">Organização para cada momento da avaliação</h2>
    <div class="publico-rotina">
      <article><h3>Antes da aplicação</h3><p>Gabarito, turmas e cartões reunidos para preparar a avaliação com clareza.</p></article>
      <article><h3>Na hora de corrigir</h3><p>Leia as marcações por foto e concentre sua atenção nas leituras que precisam de conferência.</p></article>
      <article><h3>Na devolutiva</h3><p>Entenda os resultados por questão e organize a conversa sobre o que precisa ser retomado.</p></article>
    </div>
  </div>
</section>
<section class="lp-secao lp-alternada" id="como-funciona" aria-labelledby="t-como">
  <div class="lp-inner">
    <h2 id="t-como">Como funciona hoje</h2>
    <p class="lp-sub">Use os cartões gerados pelo sistema, a impressora e o celular para acompanhar a aplicação.</p>
    <ol class="lp-passos">
      <li><h3>Organize o gabarito</h3><p>Cadastre a avaliação, as turmas e suas respostas oficiais.</p></li>
      <li><h3>Imprima e aplique</h3><p>Gere os cartões identificados para os alunos marcarem no papel.</p></li>
      <li><h3>Envie as fotos</h3><p>Use a câmera ou envie a imagem de cada cartão completo e nítido.</p></li>
      <li><h3>Confira as leituras</h3><p>Examine as marcações sinalizadas e registre a resposta conferida.</p></li>
      <li><h3>Acompanhe os resultados</h3><p>Consulte notas, questões e relatórios para orientar a devolutiva.</p></li>
    </ol>
  </div>
</section>
<section class="lp-secao" id="recursos" aria-labelledby="t-recursos"><div class="lp-inner">
  <h2 id="t-recursos">Uma visão completa da avaliação</h2>
  @include('home._recursos')
</div></section>
<section class="lp-secao lp-alternada" id="demonstracoes" aria-labelledby="t-demos"><div class="lp-inner">
  <h2 id="t-demos">Veja o que o professor pode conferir</h2>
  <p class="lp-sub">Exemplos da rotina de uso, com dados fictícios. Estas apresentações não alteram respostas ou geram documentos.</p>
  @include('home._demonstracoes')
</div></section>
<section class="lp-secao" aria-labelledby="t-controle"><div class="lp-inner">
  <h2 id="t-controle">O professor confere. Os registros ajudam a acompanhar.</h2>
  <div class="publico-confianca">
    <article><h3>Conferência humana</h3><p>Examine o trecho da marcação e o cartão completo para revisar leituras sinalizadas.</p></article>
    <article><h3>Acesso conforme a função</h3><p>Cada pessoa acompanha o que cabe ao seu contexto e às permissões recebidas.</p></article>
    <article><h3>Leituras e revisões registradas</h3><p>Quando existe mais de uma leitura, o histórico reúne os registros preservados e as intervenções humanas.</p></article>
  </div>
</div></section>
<section class="lp-secao lp-alternada" id="planos" aria-labelledby="t-planos">
  <div class="lp-inner">
    <h2 id="t-planos">Modalidades de atendimento</h2>
    @include('planos._apresentacao', ['heading'=>'h3'])
  </div>
</section>

<section class="lp-secao" id="duvidas" aria-labelledby="t-duvidas"><div class="lp-inner">
  <h2 id="t-duvidas">Dúvidas frequentes</h2>
  @include('home._faq')
</div></section>
<section class="lp-secao lp-alternada"><div class="lp-inner publico-chamada">
  <div><h2>Leve mais clareza à próxima avaliação.</h2><p>Conheça o Gabarito360 e converse com a gente sobre a rotina da sua escola ou do seu trabalho como professor.</p></div>
  <a class="btn btn-primary" href="{{ route('leads.index') }}">Falar com a gente</a>
</div></section>
</main>
<x-rodape-publico />
<script src="{{ asset('js/apresentacao-publica.js') }}?v={{ filemtime(public_path('js/apresentacao-publica.js')) }}" defer></script>

</body>
</html>
