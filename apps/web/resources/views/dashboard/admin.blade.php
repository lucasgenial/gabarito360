@extends('layouts.app')

@section('title', 'Painel Administrativo')

@push('styles')
<style>
  .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-top:24px; }
  .notice { margin-top:20px; padding:14px 18px; border:1px solid var(--accent); background:var(--accent-light); color:var(--accent-dark); border-radius:var(--radius-md); font-weight:700; }
  .main-grid { display:grid; grid-template-columns:1.8fr 1fr; gap:20px; margin-top:20px; }
  .chart-head, .section-head { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:10px; }
  .alert-list { display:grid; gap:12px; }
  .alert-item { display:flex; gap:12px; padding:14px; border:1px solid var(--border-soft); border-radius:var(--radius-md); background:var(--surface-2); }
  .alert-ico { width:40px; height:40px; display:grid; place-items:center; border-radius:10px; flex-shrink:0; background:var(--warn-light); color:var(--warn-fg); }
  .alert-item strong { display:block; font-size:14px; }
  .alert-item span { color:var(--muted); font-size:13px; }
  .table-card { margin-top:20px; overflow:hidden; }
  .quick-actions { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-top:20px; }
  .qa { display:flex; flex-direction:column; gap:10px; padding:18px; border:1px solid var(--border-soft); border-radius:var(--radius-md); background:var(--surface); transition:border-color .15s, box-shadow .15s; text-decoration:none; }
  .qa:hover { border-color:var(--accent); box-shadow:var(--shadow-sm); }
  .qa-ico { width:40px; height:40px; display:grid; place-items:center; border-radius:10px; background:var(--accent-light); color:var(--accent-dark); }
  .qa b { font-size:15px; color:var(--fg); }
  .qa span { font-size:13px; color:var(--muted); }
  .status-cell { white-space:nowrap; }
  .bar-row { display:flex; align-items:center; gap:12px; margin-bottom:10px; }
  .bar-label { font-size:13px; min-width:160px; color:var(--fg); }
  .bar-track { flex:1; height:10px; background:var(--surface-2); border-radius:99px; overflow:hidden; }
  .bar-fill { height:100%; border-radius:99px; background:var(--accent); transition:width .4s; }
  .bar-fill.warn { background:#ffcd07; }
  .bar-fill.danger { background:#e52207; }
  .bar-val { font-size:13px; font-weight:600; min-width:36px; text-align:right; font-family:var(--mono); color:var(--fg); }
  @media (max-width:1080px){ .kpi-grid{grid-template-columns:repeat(2,1fr);} .main-grid,.quick-actions{grid-template-columns:1fr 1fr;} }
  @media (max-width:720px){ .main-grid,.quick-actions{grid-template-columns:1fr;} .kpi-grid{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}" class="active">Painel</a>
<a href="{{ route('provas.index') }}">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('context-badge')
<span class="badge badge-info badge-dot">Rede Municipal</span>
@endsection

@section('breadcrumb')
<span>Início</span><span class="sep">/</span><span>Painel</span>
@endsection

@section('content')

@php
  $kpis    = $dashboard['kpis']         ?? null;
  $top5    = $dashboard['top5_escolas'] ?? [];
  $alertas = $dashboard['alertas']      ?? null;
  $acessos = $dashboard['ultimos_acessos'] ?? [];

  $perfisLegivel = [
    'admin_rede'  => 'Administrador',
    'dir_nucleo'  => 'Diretor de Núcleo',
    'dir_escolar' => 'Diretor Escolar',
    'coordenador' => 'Coordenador',
    'professor'   => 'Professor',
    'aluno'       => 'Aluno',
  ];

  $maxMedia = $top5 ? collect($top5)->max('media') : 10;
@endphp

<div class="row-between" style="margin-top:12px;">
  <div>
    <div class="eyebrow">Visão da rede</div>
    <h1 class="page-title">Painel administrativo</h1>
    <p class="page-sub">{{ $nome }} · Administrador · Rede Municipal · acompanhamento consolidado do período letivo {{ now()->year }}</p>
  </div>
  <a href="#" class="btn btn-primary">Gerar visão executiva</a>
</div>

<div class="notice">Sistema de Avaliação — Rede Municipal de Ensino — Período Letivo {{ now()->year }}</div>

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Total de Escolas</div>
    <div class="kpi-value">{{ $kpis ? number_format($kpis['total_escolas']) : '—' }}</div>
    @if($kpis)
    <div class="kpi-trend up">▲ {{ $kpis['total_escolas_ativas'] }} ativas na rede</div>
    @endif
  </div>
  <div class="card kpi">
    <div class="kpi-label">Total de Alunos</div>
    <div class="kpi-value">{{ $kpis ? number_format($kpis['total_alunos'], 0, ',', '.') : '—' }}</div>
    @if($kpis)
    <div class="kpi-trend up">▲ matrículas ativas</div>
    @endif
  </div>
  <div class="card kpi">
    <div class="kpi-label">Provas aplicadas este mês</div>
    <div class="kpi-value">{{ $kpis ? $kpis['provas_mes'] : '—' }}</div>
    @if($kpis)
    <div class="kpi-trend up">▲ aplicações em {{ now()->translatedFormat('F') }}</div>
    @endif
  </div>
  <div class="card kpi">
    <div class="kpi-label">Média Geral da Rede</div>
    <div class="kpi-value">
      @if($kpis && $kpis['media_rede'] !== null)
        {{ number_format($kpis['media_rede'], 1, ',', '') }}
      @else
        —
      @endif
    </div>
    @if($kpis)
    <div class="kpi-trend">● meta anual: {{ number_format($kpis['meta_rede'], 1, ',', '') }}</div>
    @endif
  </div>
</div>

{{-- Grade principal: gráfico + alertas --}}
<div class="main-grid">
  <div class="card card-pad">
    <div class="chart-head">
      <div>
        <div class="eyebrow">Status da rede</div>
        <h3 style="font-size:18px;">Desempenho médio por escola</h3>
      </div>
      <span class="badge badge-info">Top 5 da última consolidação</span>
    </div>

    @if(count($top5))
      @foreach($top5 as $escola)
        @php
          $pct = $maxMedia > 0 ? ($escola['media'] / 10) * 100 : 0;
          $cls = $escola['media'] >= 7 ? '' : ($escola['media'] >= 5 ? 'warn' : 'danger');
        @endphp
        <div class="bar-row">
          <span class="bar-label" title="{{ $escola['nome'] }}">{{ Str::limit($escola['nome'], 22) }}</span>
          <div class="bar-track">
            <div class="bar-fill {{ $cls }}" style="width:{{ $pct }}%"></div>
          </div>
          <span class="bar-val">{{ number_format($escola['media'], 1, ',', '') }}</span>
        </div>
      @endforeach
    @else
      <div class="bar-row" style="justify-content:center;">
        <span style="color:var(--muted);font-size:13px;">Nenhuma nota registrada ainda.</span>
      </div>
    @endif

    <p class="field-help" style="margin-top:12px;">
      Escolas com média ≥ 7,0 superam a meta mínima da rede.
      Escolas em amarelo ou vermelho requerem atenção pedagógica.
    </p>
  </div>

  <div class="card card-pad">
    <div class="section-head">
      <div>
        <div class="eyebrow">Alertas críticos</div>
        <h3 style="font-size:18px;">Pontos de atenção</h3>
      </div>
      @php $totalAlertas = $alertas ? ($alertas['cartoes_ambiguos'] > 0 ? 1 : 0) + ($alertas['escolas_abaixo_meta'] > 0 ? 1 : 0) + ($alertas['seges_atraso_min'] !== null ? 1 : 0) : 0; @endphp
      @if($alertas && $totalAlertas > 0)
        <span class="badge badge-warn">{{ $totalAlertas }} {{ $totalAlertas === 1 ? 'alerta' : 'alertas' }}</span>
      @else
        <span class="badge badge-success">Sem alertas</span>
      @endif
    </div>

    <div class="alert-list">
      @if($alertas && $alertas['cartoes_ambiguos'] > 0)
      <div class="alert-item">
        <div class="alert-ico">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 7v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="17" r="1.5" fill="currentColor"/><path d="M10.3 3.8 2.6 17.1a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="2"/></svg>
        </div>
        <div>
          <strong>{{ $alertas['cartoes_ambiguos'] }} {{ $alertas['cartoes_ambiguos'] === 1 ? 'cartão pendente' : 'cartões pendentes' }}</strong>
          <span>Leituras com marcação ambígua aguardando revisão central.</span>
        </div>
      </div>
      @endif

      @if($alertas && $alertas['escolas_abaixo_meta'] > 0)
      <div class="alert-item">
        <div class="alert-ico">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 16V9m5 7V5m5 11v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <strong>{{ $alertas['escolas_abaixo_meta'] }} {{ $alertas['escolas_abaixo_meta'] === 1 ? 'escola abaixo da meta' : 'escolas abaixo da meta' }}</strong>
          <span>Solicita intervenção pedagógica até o próximo conselho.</span>
        </div>
      </div>
      @endif

      @if($alertas && $alertas['seges_atraso_min'] !== null)
      <div class="alert-item">
        <div class="alert-ico">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 9h10M7 13h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <div>
          <strong>Integração SEGES com atenção</strong>
          <span>Última sincronização concluída com atraso de {{ $alertas['seges_atraso_min'] }} minuto(s).</span>
        </div>
      </div>
      @endif

      @if(!$alertas || $totalAlertas === 0)
      <div class="alert-item" style="border-color:var(--success-fg,#168821);background:var(--success-light,#e3f5e1);">
        <div class="alert-ico" style="background:var(--success-light,#e3f5e1);color:var(--success-fg,#168821);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div>
          <strong>Tudo em ordem</strong>
          <span>Nenhum alerta crítico no momento.</span>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>

{{-- Tabela de últimos acessos --}}
<div class="card table-card">
  <div class="card-pad" style="padding-bottom:0;">
    <div class="section-head">
      <div>
        <div class="eyebrow">Usuários e acessos</div>
        <h3 style="font-size:18px;">Últimos acessos registrados</h3>
      </div>
      <a href="#" class="btn btn-secondary btn-sm">Gerenciar usuários</a>
    </div>
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Perfil</th>
        <th>Escola / Unidade</th>
        <th>Último acesso</th>
        <th class="status-cell">Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($acessos as $u)
        @php
          $diff = \Carbon\Carbon::parse($u['ultimo_acesso'])->diffInMinutes(now());
          if ($diff < 10) {
            $statusBadge = 'badge-success'; $statusLabel = 'Online';
          } elseif ($diff < 1440) {
            $statusBadge = 'badge-info'; $statusLabel = 'Ativo hoje';
          } else {
            $statusBadge = 'badge-muted'; $statusLabel = 'Offline';
          }
        @endphp
        <tr>
          <td>{{ $u['nome'] }}</td>
          <td>{{ $perfisLegivel[$u['perfil']] ?? $u['perfil'] }}</td>
          <td>{{ $u['escola_nome'] ?? '—' }}</td>
          <td class="num">{{ \Carbon\Carbon::parse($u['ultimo_acesso'])->format('d/m/Y · H:i') }}</td>
          <td class="status-cell"><span class="badge {{ $statusBadge }} badge-dot">{{ $statusLabel }}</span></td>
        </tr>
      @empty
        <tr>
          <td colspan="5" style="text-align:center;color:var(--muted);padding:24px;">
            Nenhum acesso registrado ainda.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

{{-- Ações rápidas --}}
<h3 style="font-size:17px;margin-top:32px;">Ações rápidas</h3>
<div class="quick-actions">
  <a class="qa" href="{{ route('escolas.index') }}">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <b>Nova Escola</b>
    <span>Cadastrar unidade e vincular ao núcleo responsável.</span>
  </a>
  <a class="qa" href="#">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3" stroke="currentColor" stroke-width="2"/><circle cx="17" cy="10" r="2" stroke="currentColor" stroke-width="2"/><path d="M4 18c1.6-2.6 7.4-2.6 9 0M14 18c.8-1.5 3.2-2.1 5-1.4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <b>Gerenciar Usuários</b>
    <span>Revisar permissões, perfis institucionais e acessos.</span>
  </a>
  <a class="qa" href="#">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3v18M12 8v13M17 12v9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M5 21h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <b>Gerar Relatório</b>
    <span>Emitir consolidado da rede por escola, série e disciplina.</span>
  </a>
  <a class="qa" href="{{ route('configuracoes.index') }}">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/><path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V20a2 2 0 0 1-4 0v-.2a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H4a2 2 0 0 1 0-4h.2a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1l-.1-.1a2 2 0 0 1 2.8-2.8l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9V4a2 2 0 0 1 4 0v.2a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a2 2 0 0 1 2.8 2.8l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6h.2a2 2 0 0 1 0 4h-.2a1 1 0 0 0-.9.6Z" stroke="currentColor" stroke-width="1.6"/></svg>
    </div>
    <b>Configurações do Sistema</b>
    <span>Ajustar integrações, calendário e parâmetros institucionais.</span>
  </a>
</div>

<div style="height:48px"></div>
@endsection
