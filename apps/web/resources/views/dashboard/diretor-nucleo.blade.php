@extends('layouts.app')

@section('title', 'Painel do Núcleo')

@push('styles')
<style>
  .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-top:24px; }
  .content-grid { display:grid; grid-template-columns:1.7fr 1fr; gap:20px; margin-top:20px; }
  .stack-grid { display:grid; gap:20px; }
  .table-wrap { overflow:hidden; }
  .trend { display:inline-flex; align-items:center; gap:6px; font-weight:700; font-size:13px; }
  .trend.up { color:var(--success,#168821); }
  .trend.down { color:var(--danger,#e52207); }
  .trend.flat { color:var(--muted); }
  .bim-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-top:16px; align-items:end; }
  .bim-item { text-align:center; }
  .bim-bar { height:140px; background:var(--surface-2); border-radius:var(--radius-md); display:flex; align-items:flex-end; padding:6px; }
  .bim-bar span { width:100%; border-radius:8px; background:linear-gradient(180deg, var(--accent), var(--accent-dark,#0c326f)); }
  .bim-item strong { display:block; margin-top:8px; font-size:13px; }
  .bim-item small { color:var(--muted); font-family:var(--font-mono,monospace); }
  .visit-list { display:grid; gap:12px; margin-top:8px; }
  .visit-item { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; padding:14px; border:1px solid var(--border-soft); border-radius:var(--radius-md); background:var(--surface-2); }
  .visit-item strong { display:block; font-size:14px; }
  .visit-item span.sub { color:var(--muted); font-size:13px; }
  .quick-actions { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-top:20px; }
  .qa { display:flex; flex-direction:column; gap:10px; padding:18px; border:1px solid var(--border-soft); border-radius:var(--radius-md); background:var(--surface); transition:border-color .15s, box-shadow .15s; text-decoration:none; }
  .qa:hover { border-color:var(--accent); box-shadow:var(--shadow-sm); }
  .qa-ico { width:40px; height:40px; display:grid; place-items:center; border-radius:10px; background:var(--accent-light); color:var(--accent-dark); }
  .qa b { font-size:15px; color:var(--fg); }
  .qa span { font-size:13px; color:var(--muted); }
  @media (max-width:1080px){ .kpi-grid{grid-template-columns:repeat(2,1fr);} .content-grid{grid-template-columns:1fr;} }
  @media (max-width:720px){ .kpi-grid,.quick-actions,.bim-grid{grid-template-columns:1fr 1fr;} }
  @media (max-width:480px){ .kpi-grid,.quick-actions,.bim-grid{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}" class="active">Painel</a>
<a href="{{ route('provas.index') }}">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

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

<div class="row-between" style="margin-top:12px;">
  <div>
    <div class="eyebrow">Acompanhamento regional</div>
    <h1 class="page-title">Painel {{ $nomeNucleo }}</h1>
    <p class="page-sub">{{ $nome }} · supervisão de {{ $kpis['total_escolas'] ?? '—' }} escolas da regional · consolidação {{ now()->year }}</p>
  </div>
  <a href="#" class="btn btn-primary">Agendar reunião de acompanhamento</a>
</div>

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Escolas no Núcleo</div>
    <div class="kpi-value">{{ $kpis['total_escolas'] ?? '—' }}</div>
    <div class="kpi-trend">● calendário da regional</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Alunos</div>
    <div class="kpi-value">{{ $kpis ? number_format($kpis['total_alunos'], 0, ',', '.') : '—' }}</div>
    <div class="kpi-trend up">▲ matrículas ativas</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Provas Realizadas</div>
    <div class="kpi-value">{{ $kpis['provas_realizadas'] ?? '—' }}</div>
    <div class="kpi-trend up">▲ no período letivo</div>
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
      <div class="eyebrow">Mapa de desempenho escolar</div>
      <h3 style="font-size:18px;">Comparativo das escolas do núcleo</h3>
    </div>
    <table class="table">
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
            $cfg   = $statusConfig[$e['status']] ?? ['badge' => 'badge-muted', 'label' => 'N/D'];
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
      <div class="eyebrow">Evolução bimestral</div>
      <h3 style="font-size:18px;">Média consolidada do núcleo</h3>
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
      <div class="eyebrow">Visitas programadas</div>
      <h3 style="font-size:18px;">Próximas agendas em campo</h3>
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
    <b>Ver todas as Escolas</b>
    <span>Acessar dados detalhados das unidades sob supervisão.</span>
  </a>
  <a class="qa" href="#">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 18V8m6 10V5m6 13v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M4 21h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <b>Relatório Comparativo</b>
    <span>Gerar quadro entre escolas, séries e desempenho por prova.</span>
  </a>
  <a class="qa" href="#">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 3v4M16 3v4M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 14v4M10 16h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <b>Agendar Visita</b>
    <span>Registrar visitas técnicas e acompanhamento pedagógico.</span>
  </a>
</div>

<div style="height:48px"></div>
@endsection
