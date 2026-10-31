@extends('layouts.app')
@section('title', 'Pagamento não concluído')

@section('content')
<div style="max-width:520px;margin:4rem auto;text-align:center;padding:2rem">
  <h1 style="font-size:1.75rem;font-weight:800;color:var(--color-danger-03);margin-bottom:.75rem">Pagamento não concluído</h1>
  <p style="color:var(--color-secondary-07);margin-bottom:2rem">
    Houve um problema ao processar seu pagamento. Nenhum valor foi cobrado.
    Você pode tentar novamente ou escolher outra forma de pagamento.
  </p>
  <a href="{{ route('checkout.index') }}" class="btn btn-primary">Tentar novamente</a>
  <br><br>
  <a href="{{ route('leads.index') }}" style="font-size:.875rem;color:var(--color-secondary-06)">Fale com nossa equipe</a>
</div>
@endsection
