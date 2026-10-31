@extends('layouts.app')

@section('title', 'Painel do Núcleo')

@push('styles')
<style>
  .trend { display:inline-flex; align-items:center; gap:6px; font-weight:700; font-size:13px; }
  .trend.up { color:var(--success); }
  .trend.down { color:var(--danger); }
  .trend.flat { color:var(--muted); }
  .bim-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(110px,100%),1fr)); gap:12px; margin-top:16px; align-items:end; }
  .bim-item { text-align:center; }
  .bim-bar { height:140px; background:var(--surface-2); border-radius:var(--radius-md); display:flex; align-items:flex-end; padding:6px; }
  .bim-bar span { width:100%; border-radius:8px; background:var(--accent); }
  .bim-item strong { display:block; margin-top:8px; font-size:13px; }
  .bim-item small { color:var(--muted); font-family:var(--font-mono); }
  .visit-list { display:grid; gap:12px; margin-top:8px; }
  .visit-item { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; padding:14px; border:1px solid var(--border-soft); border-radius:var(--radius-md); background:var(--surface-2); }
  .visit-item strong { display:block; font-size:14px; }
  .visit-item span.sub { color:var(--muted); font-size:13px; }
</style>
@endpush

@section('context-badge')
<span class="badge badge-info badge-dot">{{ $dashboard['nucleo']['nome'] ?? 'Núcleo' }}</span>
@endsection

@section('breadcrumb')
<span>Início</span><span class="sep">/</span><span>Painel</span>
@endsection

@section('content')

@php
  $kpis      = $dashboard['kpis']      ?? null;
  $escolas   = $dashboard['escolas']   ?? [];
  $bimestres = $dashboard['bimestres'] ?? [];
  $visitas   = $dashboard['visitas']   ?? [];
  $nucleo    = $dashboard['nucleo']    ?? null;
  $nomeNucleo = $nucleo['nome'] ?? 'do Núcleo';

  $statusConfig = [
    'destaque'      => ['badge' => 'badge-success', 'label' => 'Destaque'],
    'acima_meta'    => ['badge' => 'badge-success', 'label' => 'Acima da meta'],
    'monitoramento' => ['badge' => 'badge-info',    'label' => 'Monitoramento'],
    'atencao'       => ['badge' => 'badge-warn',    'label' => 'Atenção'],
    'abaixo_meta'   => ['badge' => 'badge-danger',  'label' => 'Abaixo da meta'],
  ];

  $urgenciaConfig = [
    'prioritaria' => ['badge' => 'badge-danger',  'label' => 'Prioritária'],
    'monitorar'   => ['badge' => 'badge-warn',    'label' => 'Monitorar'],
    'referencia'  => ['badge' => 'badge-success', 'label' => 'Referência'],
  ];
@endphp

<x-pagina titulo="Painel {{ $nomeNucleo }}" subtitulo="{{ $nome }} · {{ ($kpis['total_escolas'] ?? 0) == 1 ? '1 escola' : ($kpis['total_escolas'] ?? '—') . ' escolas' }} na regional · consolidação {{ now()->year }}" />

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Escolas no Núcleo</div>
    <div class="kpi-value">{{ $kpis['total_escolas'] ?? '—' }}</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Alunos</div>
    <div class="kpi-value">{{ $kpis ? number_format($kpis['total_alunos'], 0, ',', '.') : '—' }}</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Provas Realizadas</div>
    <div class="kpi-value">{{ $kpis['provas_realizadas'] ?? '—' }}</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Média do Núcleo</div>
    <div class="kpi-value">
      @if($kpis && $kpis['media_nucleo'] !== null)
        {{ number_format($kpis['media_nucleo'], 1, ',', '') }}
      @else
        —
      @endif
    </div>
    @if($kpis && $kpis['media_nucleo'] !== null)
      @php $diff = round($kpis['media_nucleo'] - $kpis['meta_rede'], 1); @endphp
      @if($diff >= 0)
        <div class="kpi-trend up">▲ {{ number_format(abs($diff), 1, ',', '') }} acima da meta</div>
      @else
        <div class="kpi-trend down">▼ {{ number_format(abs($diff), 1, ',', '') }} ponto frente à meta</div>
      @endif
    @else
      <div class="kpi-trend">● meta anual: {{ number_format($kpis['meta_rede'] ?? 7.0, 1, ',', '') }}</div>
    @endif
  </div>
</div>

{{-- Grade principal --}}
<div class="content-grid">
  {{-- Tabela comparativa de escolas --}}
  <div class="card table-wrap">
    <div class="card-pad" style="padding-bottom:0;">
      <h3 style="font-size:18px;">Escolas do núcleo</h3>
    </div>
    <table class="table lista-cel">
      <thead>
        <tr>
          <th>Escola</th>
          <th>Turmas</th>
          <th>Alunos</th>
          <th>Média</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($escolas as $e)
          @php
            $cfg   = $statusConfig[$e['status']] ?? ['badge' => 'badge-muted', 'label' => 'Sem dados'];
            $media = $e['media'] ?? null;
          @endphp
          <tr>
            <td>{{ $e['nome'] }}</td>
            <td class="num">{{ $e['total_turmas'] }}</td>
            <td class="num">{{ number_format($e['total_alunos'], 0, ',', '.') }}</td>
            <td class="num">{{ $media !== null ? number_format($media, 1, ',', '') : '—' }}</td>
            <td><span class="badge {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span></td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center;color:var(--muted);padding:24px;">
              Nenhuma escola vinculada a este núcleo.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Coluna lateral --}}
  <div class="stack-grid">
    {{-- Gráfico bimestral --}}
    <div class="card card-pad">
      <h3 style="font-size:18px;">Média do núcleo por bimestre</h3>
      <div class="bim-grid">
        @foreach($bimestres as $bim)
          @php
            $pct = $bim['media'] !== null ? min(100, ($bim['media'] / 10) * 100) : 0;
          @endphp
          <div class="bim-item">
            <div class="bim-bar">
              <span style="height:{{ $pct }}%;"></span>
            </div>
            <strong>{{ $bim['label'] }}</strong>
            <small>{{ $bim['media'] !== null ? number_format($bim['media'], 1, ',', '') : '—' }}</small>
          </div>
        @endforeach
      </div>
      <p class="field-help" style="margin-top:14px;">
        Evolução da média consolidada das escolas por bimestre no período letivo {{ now()->year }}.
      </p>
    </div>

    {{-- Visitas programadas --}}
    <div class="card card-pad">
      <h3 style="font-size:18px;">Próximas visitas</h3>
      <div class="visit-list">
        @forelse($visitas as $v)
          @php
            $ucfg = $urgenciaConfig[$v['urgencia']] ?? ['badge' => 'badge-muted', 'label' => ucfirst($v['urgencia'])];
          @endphp
          <div class="visit-item">
            <div>
              <strong>{{ $v['escola']['nome'] ?? '—' }}</strong>
              <span class="sub">{{ \Carbon\Carbon::parse($v['data_visita'])->format('d/m/Y') }} · {{ $v['tipo'] }}</span>
            </div>
            <span class="badge {{ $ucfg['badge'] }}">{{ $ucfg['label'] }}</span>
          </div>
        @empty
          <div style="color:var(--muted);font-size:13px;text-align:center;padding:16px 0;">
            Nenhuma visita agendada.
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- Ações rápidas --}}
<h3 style="font-size:17px;margin-top:32px;">Ações rápidas</h3>
<div class="quick-actions">
  <a class="qa" href="{{ route('escolas.index') }}">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 9h8M8 13h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <b>Escolas</b>
    <span>Acessar dados detalhados das unidades sob supervisão.</span>
  </a>
  <a class="qa" href="{{ route('relatorios.nucleo') }}">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 18V8m6 10V5m6 13v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M4 21h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <b>Comparar escolas</b>
    <span>Gerar quadro entre escolas, séries e desempenho por prova.</span>
  </a>
</div>

<div style="height:48px"></div>
@endsection
