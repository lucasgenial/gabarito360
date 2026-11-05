@extends('layouts.public')
@section('title', 'Modalidades de atendimento — Gabarito360')
@section('description', config('apresentacao.descricao'))
@section('classe_publica', 'publico-apresentacao')
@section('content')
<section class="publico-abertura" aria-labelledby="modalidades">
  <p class="publico-prelancamento">Gabarito360 em pré-lançamento</p>
  <h1 id="modalidades">Modalidades de atendimento</h1>
</section>
@include('planos._apresentacao', ['heading'=>'h2'])
<section class="lp-secao" aria-labelledby="visao-produto"><h2 id="visao-produto">Conheça os recursos e a evolução do produto</h2>
  <p class="publico-modalidades-intro">A modalidade organiza o contexto de uso. Ela não define, por si só, a inclusão de recursos futuros.</p>
  @include('home._recursos')
</section>
<section class="lp-secao" aria-labelledby="t-duvidas"><h2 id="t-duvidas">Dúvidas frequentes</h2>@include('home._faq')</section>
<section class="publico-contato"><div><h2>Vamos conversar sobre sua rotina?</h2><p>Conte como você ou sua instituição realiza as avaliações.</p></div><a class="btn btn-primary" href="{{ route('leads.index') }}">Falar com a gente</a></section>
@endsection
