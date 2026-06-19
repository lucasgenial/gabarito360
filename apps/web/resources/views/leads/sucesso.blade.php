@extends('layouts.public')
@section('title', 'Solicitação enviada — Gabarito360')

@section('content')
<div style="max-width:520px;margin:4rem auto;text-align:center;padding:2rem">
  <div style="font-size:3rem;margin-bottom:1rem">🎉</div>
  <h1 style="font-size:1.75rem;font-weight:800;color:var(--color-primary-default);margin-bottom:.75rem">Recebemos seu contato!</h1>
  <p style="color:var(--color-secondary-07);margin-bottom:2rem">
    Nossa equipe comercial entrará em contato em até <strong>2 dias úteis</strong> para montar uma proposta personalizada para a sua rede.
  </p>
  <a href="{{ route('planos.index') }}" class="btn btn-outline">Ver planos</a>
  <br><br>
  <a href="{{ route('login') }}" style="font-size:.875rem;color:var(--color-secondary-06)">Já tenho conta — Entrar</a>
</div>
@endsection
