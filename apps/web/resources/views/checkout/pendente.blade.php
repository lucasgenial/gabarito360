@extends('layouts.app')
@section('title', 'Pagamento em Processamento')

@section('content')
<div style="max-width:520px;margin:4rem auto;text-align:center;padding:2rem">
  <div style="font-size:3rem;margin-bottom:1rem">⏳</div>
  <h1 style="font-size:1.75rem;font-weight:800;color:var(--color-warning-03);margin-bottom:.75rem">Pagamento em processamento</h1>
  <p style="color:var(--color-secondary-07);margin-bottom:2rem">
    Seu pagamento está sendo processado. Você receberá um e-mail de confirmação assim que ele for aprovado.
    Isso pode levar alguns minutos para boleto ou PIX.
  </p>
  <a href="{{ route('assinatura.index') }}" class="btn btn-primary">Ver status da assinatura</a>
  <br><br>
  <a href="{{ route('painel') }}" style="font-size:.875rem;color:var(--color-secondary-06)">Ir para o painel</a>
</div>
@endsection
