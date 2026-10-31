@extends('layouts.public')
@section('title', 'Mensagem recebida — Gabarito360')
@section('description', 'Sua mensagem foi recebida pela equipe do Gabarito360.')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="publico-status">
  <h1>Recebemos sua mensagem</h1>
  <p>Você receberá uma confirmação no e-mail informado. Nossa equipe vai responder por esse endereço.</p>
  <div class="lp-acoes">
    <a href="{{ route('home') }}" class="btn btn-primary">Voltar ao início</a>
    <a href="{{ route('planos.index') }}" class="btn btn-secondary">Conhecer os planos</a>
  </div>
  <a href="{{ route('login') }}">Já tenho conta — Entrar</a>
</section>
@endsection
