@extends('layouts.app')
@section('title', 'Minha Assinatura')

@push('styles')
<style>
.assinatura-wrap {
  max-width: 720px;
  margin: 0 auto;
}
.assinatura-wrap h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .35rem .9rem;
  border-radius: 99px;
  font-size: .85rem;
  font-weight: 700;
}
.status-trial     { background: var(--color-primary-02); color: var(--color-primary-default); }
.status-ativa     { background: var(--color-success-01); color: var(--color-success-04); }
.status-atrasada  { background: var(--color-warning-01); color: var(--color-warning-04); }
.status-cancelada { background: var(--color-secondary-02); color: var(--color-secondary-07); }

.assinatura-card {
  background: var(--color-secondary-01);
  border: 1px solid var(--color-secondary-03);
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}
.assinatura-card h2 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: var(--color-secondary-08);
}
.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .75rem 1.5rem;
}
.info-item label {
  font-size: .75rem;
  color: var(--color-secondary-06);
  text-transform: uppercase;
  letter-spacing: .04em;
  display: block;
  margin-bottom: .2rem;
}
.info-item span {
  font-weight: 600;
  font-size: .95rem;
}

.historico-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .875rem;
}
.historico-table th {
  text-align: left;
  padding: .5rem .75rem;
  border-bottom: 2px solid var(--color-secondary-03);
  font-size: .75rem;
  text-transform: uppercase;
  color: var(--color-secondary-06);
}
.historico-table td {
  padding: .625rem .75rem;
  border-bottom: 1px solid var(--color-secondary-02);
}
.historico-table tr:last-child td { border-bottom: none; }

.pgto-aprovado  { color: var(--color-success-03); font-weight: 600; }
.pgto-pendente  { color: var(--color-warning-03); font-weight: 600; }
.pgto-rejeitado { color: var(--color-danger-03); font-weight: 600; }

.alerta-atrasada {
  background: var(--color-warning-01);
  border: 1px solid var(--color-warning-02);
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}
.alerta-atrasada p { margin: 0; color: var(--color-warning-05); }
.alerta-atrasada a { color: var(--color-warning-05); font-weight: 700; }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a> › Assinatura e Cobrança
@endsection

@section('content')
<div class="assinatura-wrap">
  <h1>Assinatura e Cobrança</h1>

  @if(session('sucesso'))
    <div class="alert alert-success" style="margin-bottom:1rem">{{ session('sucesso') }}</div>
  @endif

  @if(!$assinatura)
    <div class="assinatura-card" style="text-align:center;padding:2.5rem">
      <p style="color:var(--color-secondary-06);margin-bottom:1.5rem">Você ainda não possui uma assinatura ativa.</p>
      <a href="{{ route('planos.index') }}" class="btn btn-primary">Ver planos disponíveis</a>
    </div>
  @else

    @if($assinatura['status'] === 'atrasada')
    <div class="alerta-atrasada">
      <p>⚠️ Sua assinatura está <strong>atrasada</strong>. Regularize o pagamento para restaurar o acesso completo.
      <a href="{{ route('checkout.index') }}">Renovar agora →</a></p>
    </div>
    @endif

    <div class="assinatura-card">
      <h2>Status da assinatura</h2>
      <div style="margin-bottom:1rem">
        @php
          $statusLabels = [
            'trial'     => '⏱ Trial',
            'ativa'     => '✅ Ativa',
            'atrasada'  => '⚠️ Atrasada',
            'cancelada' => '✗ Cancelada',
          ];
        @endphp
        <span class="status-badge status-{{ $assinatura['status'] }}">
          {{ $statusLabels[$assinatura['status']] ?? $assinatura['status'] }}
        </span>
      </div>

      <div class="info-grid">
        <div class="info-item">
          <label>Plano</label>
          <span>{{ $assinatura['plano']['nome'] }}</span>
        </div>
        <div class="info-item">
          <label>Licenças contratadas</label>
          <span>{{ $assinatura['licencas_contratadas'] }}</span>
        </div>
        @if($assinatura['trial_termina_em'] && $assinatura['status'] === 'trial')
        <div class="info-item">
          <label>Trial termina em</label>
          <span>{{ \Carbon\Carbon::parse($assinatura['trial_termina_em'])->format('d/m/Y') }}</span>
        </div>
        @endif
        @if($assinatura['proxima_cobranca_em'])
        <div class="info-item">
          <label>Próxima cobrança</label>
          <span>{{ \Carbon\Carbon::parse($assinatura['proxima_cobranca_em'])->format('d/m/Y') }}</span>
        </div>
        @endif
        @if($assinatura['plano']['preco_fixo'] !== null)
        <div class="info-item">
          <label>Valor</label>
          <span>R$ {{ number_format($assinatura['plano']['preco_fixo'], 2, ',', '.') }}/{{ $assinatura['plano']['periodicidade'] }}</span>
        </div>
        @elseif($assinatura['plano']['preco_por_licenca'] !== null)
        <div class="info-item">
          <label>Valor</label>
          <span>R$ {{ number_format($assinatura['plano']['preco_por_licenca'] * $assinatura['licencas_contratadas'], 2, ',', '.') }}/mês</span>
        </div>
        @endif
      </div>
    </div>

    {{-- Histórico de pagamentos --}}
    <div class="assinatura-card">
      <h2>Histórico de pagamentos</h2>
      @if(empty($assinatura['historico_pagamentos']))
        <p style="color:var(--color-secondary-06);font-size:.875rem">Nenhum pagamento registrado ainda.</p>
      @else
        <table class="historico-table lista-cel">
          <thead>
            <tr>
              <th>Data</th>
              <th>Valor</th>
              <th>Método</th>
              <th>Status</th>
              <th>ID</th>
            </tr>
          </thead>
          <tbody>
            @foreach($assinatura['historico_pagamentos'] as $p)
            <tr>
              <td>{{ $p['pago_em'] ? \Carbon\Carbon::parse($p['pago_em'])->format('d/m/Y') : '—' }}</td>
              <td>R$ {{ number_format($p['valor'], 2, ',', '.') }}</td>
              <td>{{ ucfirst($p['metodo'] ?? '—') }}</td>
              <td class="pgto-{{ $p['status'] }}">{{ ucfirst($p['status']) }}</td>
              <td style="font-size:.75rem;color:var(--color-secondary-06)">{{ $p['gateway_payment_id'] ?? '—' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>

    {{-- Ações --}}
    @if(in_array($assinatura['status'], ['trial', 'ativa']))
    <div class="assinatura-card">
      <h2>Ações</h2>
      <div style="display:flex;gap:1rem;flex-wrap:wrap">
        <a href="{{ route('checkout.index') }}" class="btn btn-outline">Trocar plano</a>
        <form method="POST" action="{{ route('assinatura.cancelar') }}"
          onsubmit="return confirm('Tem certeza que deseja cancelar a assinatura? O acesso será suspenso imediatamente.')">
          @csrf
          <button type="submit" class="btn" style="background:var(--color-danger-01);color:var(--color-danger-04);border-color:var(--color-danger-02)">
            Cancelar assinatura
          </button>
        </form>
      </div>
      <p style="font-size:.8rem;color:var(--color-secondary-06);margin-top:.75rem">
        Ao cancelar, seus dados são preservados por 90 dias. O acesso é suspenso imediatamente.
      </p>
    </div>
    @endif

  @endif
</div>
@endsection
