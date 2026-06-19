@extends('layouts.app')
@section('title', 'Pagamento Confirmado')

@section('content')
<div style="max-width:520px;margin:4rem auto;text-align:center;padding:2rem">
  <div style="font-size:3rem;margin-bottom:1rem">✅</div>
  <h1 style="font-size:1.75rem;font-weight:800;color:var(--color-success-03);margin-bottom:.75rem">Pagamento confirmado!</h1>
  <p style="color:var(--color-secondary-07);margin-bottom:2rem">
    Sua assinatura foi ativada. Você já pode usar todas as funcionalidades do plano.
    @if($payment_id)
      <br><small>ID do pagamento: <code>{{ $payment_id }}</code></small>
    @endif
  </p>
  <a href="{{ route('painel') }}" class="btn btn-primary">Ir para o painel →</a>
  <br><br>
  <a href="{{ route('assinatura.index') }}" style="font-size:.875rem;color:var(--color-secondary-06)">Ver detalhes da assinatura</a>
</div>
@endsection
