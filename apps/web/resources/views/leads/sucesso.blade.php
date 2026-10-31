@extends('layouts.public')
@section('title', 'Solicitação enviada — Gabarito360')

@section('content')
<div style="max-width:520px;margin:4rem auto;text-align:center;padding:2rem">
  <h1 style="font-size:1.75rem;font-weight:800;color:var(--color-primary-default);margin-bottom:.75rem">Recebemos seu contato!</h1>
  <p style="color:var(--color-secondary-07);margin-bottom:2rem">
    Enviamos uma confirmação para o seu e-mail e vamos responder assim que possível.
  </p>
  <a href="{{ route('planos.index') }}" class="btn btn-outline">Ver planos</a>
  <br><br>
  <a href="{{ route('login') }}" style="font-size:.875rem;color:var(--color-secondary-06)">Já tenho conta — Entrar</a>
</div>
@endsection
