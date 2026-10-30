@extends('layouts.app')
@section('title', 'Checkout — Assinar Plano')

@push('styles')
<style>
.checkout-wrap {
  max-width: 560px;
  margin: 2rem auto;
}
.checkout-wrap h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}
.plano-selecionado {
  background: var(--color-primary-02);
  border: 1px solid var(--color-primary-03);
  border-radius: 8px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}
.plano-selecionado h3 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-primary-default);
  margin-bottom: .25rem;
}
.plano-selecionado .preco-resumo {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-primary-default);
}
.plano-selecionado .preco-resumo small {
  font-size: .85rem;
  font-weight: 400;
  color: var(--color-secondary-06);
}
.licencas-box {
  margin-bottom: 1.25rem;
}
.licencas-box label {
  font-weight: 600;
  display: block;
  margin-bottom: .4rem;
}
.licencas-input {
  display: flex;
  align-items: center;
  gap: .75rem;
}
.licencas-input input[type=number] {
  width: 80px;
}
#valor-total {
  font-weight: 700;
  font-size: 1.1rem;
  color: var(--color-primary-default);
}
.trial-notice {
  background: var(--color-success-01);
  border: 1px solid var(--color-success-02);
  border-radius: 6px;
  padding: .75rem 1rem;
  font-size: .875rem;
  color: var(--color-success-04);
  margin-bottom: 1.5rem;
}
.mp-badge {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: .8rem;
  color: var(--color-secondary-06);
  margin-top: 1rem;
}
.plano-radio-list {
  list-style: none;
  padding: 0;
  margin: 0 0 1.5rem;
}
.plano-radio-list li {
  border: 1px solid var(--color-secondary-03);
  border-radius: 6px;
  padding: .875rem 1rem;
  margin-bottom: .5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: .75rem;
  transition: border-color .15s;
}
.plano-radio-list li:hover { border-color: var(--color-primary-default); }
.plano-radio-list li input { cursor: pointer; }
.plano-radio-label { flex: 1; }
.plano-radio-label strong { display: block; }
.plano-radio-label span { font-size: .85rem; color: var(--color-secondary-06); }
.plano-radio-preco { font-weight: 700; color: var(--color-primary-default); }
</style>
@endpush

@section('content')
<div class="breadcrumb">
  <a href="{{ route('painel') }}">Início</a> › <a href="{{ route('planos.index') }}">Planos</a> › Checkout
</div>

<div class="checkout-wrap">
  <h1>Assinar um plano</h1>

  <div class="trial-notice">
    ✅ Você tem <strong>14 dias gratuitos</strong> antes de qualquer cobrança. Cancele a qualquer momento.
  </div>

  @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:1rem">
      @foreach($errors->all() as $e)<p style="margin:.25rem 0">{{ $e }}</p>@endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('checkout.store') }}">
    @csrf

    @if($plano)
      {{-- Plano já pré-selecionado via query string --}}
      <input type="hidden" name="plano_id" value="{{ $plano['id'] }}">

      <div class="plano-selecionado">
        <h3>{{ $plano['nome'] }}</h3>
        @if($plano['preco_fixo'] !== null)
          <div class="preco-resumo">
            R$ {{ number_format($plano['preco_fixo'], 2, ',', '.') }}
            <small>/{{ $plano['periodicidade'] === 'anual' ? 'ano' : 'mês' }}</small>
          </div>
        @elseif($plano['preco_por_licenca'] !== null)
          <div id="valor-total">R$ 0,00</div>
          <div style="font-size:.8rem;color:var(--color-secondary-06)">R$ {{ number_format($plano['preco_por_licenca'], 2, ',', '.') }} × nº de licenças / mês</div>
        @endif
      </div>

      @if($plano['preco_por_licenca'] !== null)
      <div class="licencas-box">
        <label for="licencas">Número de licenças (1–{{ $plano['licencas_max'] ?? 10 }})</label>
        <div class="licencas-input">
          <input type="number" id="licencas" name="licencas" value="1"
            min="{{ $plano['licencas_min'] ?? 1 }}"
            max="{{ $plano['licencas_max'] ?? 10 }}"
            class="form-control"
            onchange="calcularTotal({{ $plano['preco_por_licenca'] }})">
          <span>licenças</span>
          <span id="valor-total">R$ {{ number_format($plano['preco_por_licenca'], 2, ',', '.') }}/mês</span>
        </div>
      </div>
      @else
        <input type="hidden" name="licencas" value="1">
      @endif

    @else
      {{-- Seleção de plano na própria tela --}}
      <ul class="plano-radio-list">
        @foreach($planos->filter(fn($p) => $p['modo'] === 'autoatendimento') as $p)
        <li onclick="document.getElementById('plano_{{ $p['id'] }}').click(); atualizarSelecao({{ $p['preco_fixo'] ?? 0 }}, '{{ $p['preco_por_licenca'] !== null ? 'licenca' : 'fixo' }}', {{ $p['preco_por_licenca'] ?? 0 }})">
          <input type="radio" id="plano_{{ $p['id'] }}" name="plano_id" value="{{ $p['id'] }}" {{ $loop->first ? 'checked' : '' }}>
          <div class="plano-radio-label">
            <strong>{{ $p['nome'] }}</strong>
            <span>{{ $p['periodicidade'] === 'anual' ? 'Cobrança anual' : 'Cobrança mensal' }}</span>
          </div>
          <div class="plano-radio-preco">
            @if($p['preco_fixo'] !== null)
              R$ {{ number_format($p['preco_fixo'], 2, ',', '.') }}
            @else
              R$ {{ number_format($p['preco_por_licenca'], 2, ',', '.') }}/licença
            @endif
          </div>
        </li>
        @endforeach
      </ul>
      <input type="hidden" name="licencas" value="1">
    @endif

    <button type="submit" class="btn btn-primary" style="width:100%;padding:.875rem">
      Continuar para pagamento →
    </button>

    <div class="mp-badge">
      🔒 Pagamento processado com segurança pelo <strong>Mercado Pago</strong>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
function calcularTotal(precoPorLicenca) {
  const n = parseInt(document.getElementById('licencas').value, 10) || 1;
  const total = (precoPorLicenca * n).toFixed(2).replace('.', ',');
  document.getElementById('valor-total').textContent = 'R$ ' + total + '/mês';
}

function atualizarSelecao(precoFixo, tipo, precoPorLicenca) {
  // Placeholder para atualizar resumo ao trocar plano sem recarregar
}
</script>
@endpush
