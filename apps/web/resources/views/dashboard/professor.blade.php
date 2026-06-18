@extends('layouts.app')

@section('title', 'Painel do Professor')

@push('styles')
<style>
  .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-top:24px; }
  .main-grid { display:grid; grid-template-columns:1.8fr 1fr; gap:20px; margin-top:20px; }
  .side-stack { display:grid; gap:20px; }
  .section-title { font-size:17px; margin-bottom:14px; font-weight:700; }
  .proof-actions { display:flex; flex-wrap:wrap; gap:8px; }
  .proof-actions .btn { padding:6px 10px; font-size:12px; }
  .ranking-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
  .rank-list { list-style:none; padding:0; margin:0; }
  .rank-list li { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; border:1px solid var(--border-soft); border-radius:var(--radius-md); background:var(--surface); }
  .rank-list li + li { margin-top:10px; }
  .rank-pos { width:28px; height:28px; border-radius:50%; display:grid; place-items:center; flex-shrink:0; background:var(--surface-2); color:var(--accent-dark); font-size:12px; font-weight:700; }
  .rank-pos.danger { background:var(--danger-light,#fde8e4); color:var(--danger,#e52207); }
  .rank-pos.warn { background:var(--warn-light,#fff5c2); color:var(--warn-fg,#b06800); }
  .student-line { display:flex; align-items:center; gap:10px; min-width:0; }
  .student-line b { font-size:14px; display:block; }
  .student-line span { font-size:12px; color:var(--muted); }
  .attention-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
  .attention-grid article { padding:16px; border:1px solid var(--border-soft); border-radius:var(--radius-md); background:var(--surface); }
  .student-card-top { display:flex; align-items:center; justify-content:space-between; gap:10px; }
  .student-avatar { width:42px; height:42px; border-radius:50%; display:grid; place-items:center; background:var(--accent-light); color:var(--accent-dark); font-weight:700; font-size:14px; }
  .trend { font-size:13px; font-weight:700; }
  .trend.down { color:var(--danger,#e52207); }
  .trend.flat { color:var(--warn-fg,#b06800); }
  .quick-actions { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-top:20px; }
  .qa { display:flex; flex-direction:column; gap:12px; padding:18px; border-radius:var(--radius-lg); border:1px solid var(--border-soft); background:var(--surface); text-decoration:none; }
  .qa:hover { border-color:var(--accent); box-shadow:var(--shadow-sm); }
  .qa-ico { width:42px; height:42px; border-radius:10px; display:grid; place-items:center; background:var(--accent-light); color:var(--accent-dark); }
  .qa b { color:var(--fg); font-size:15px; }
  .qa span { color:var(--muted); font-size:13px; }
  @media (max-width:1100px){ .kpi-grid{grid-template-columns:repeat(2,1fr);} .main-grid,.ranking-grid{grid-template-columns:1fr;} .attention-grid{grid-template-columns:1fr 1fr;} }
  @media (max-width:640px){ .kpi-grid,.quick-actions,.attention-grid{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}" class="active">Painel</a>
<a href="{{ route('provas.index') }}">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('context-badge')
@php
  $prof = $dashboard['professor'] ?? null;
  $ctxLabel = implode(' · ', array_filter([
    $prof['disciplina'] ?? null,
    isset($prof['total_turmas']) ? $prof['total_turmas'].' '.($prof['total_turmas'] == 1 ? 'turma' : 'turmas') : null,
  ]));
@endphp
<span class="badge badge-info badge-dot">{{ $ctxLabel ?: 'Professor' }}</span>
@endsection

@section('breadcrumb')
<span>Início</span><span class="sep">/</span><span>Painel</span>
@endsection

@section('content')

@php
  $kpis    = $dashboard['kpis']           ?? null;
  $provas  = $dashboard['provas']         ?? [];
  $top5    = $dashboard['ranking_top5']   ?? [];
  $bottom3 = $dashboard['ranking_bottom3'] ?? [];
  $atencao = $dashboard['alunos_atencao'] ?? [];
  $prof    = $dashboard['professor']      ?? null;

  $primeiroNome = explode(' ', $nome)[0];

  $statusConfig = [
    'rascunho'    => ['badge' => 'badge-muted',   'label' => 'rascunho'],
    'publicada'   => ['badge' => 'badge-warn',    'label' => 'agendada'],
    'em_correcao' => ['badge' => 'badge-info',    'label' => 'em correção'],
    'corrigida'   => ['badge' => 'badge-success', 'label' => 'concluída'],
  ];
@endphp

<div class="row-between" style="margin-top:12px;">
  <div>
    <div class="eyebrow">Docência</div>
    <h1 class="page-title">Bom dia, {{ $primeiroNome }} 👋</h1>
    <p class="page-sub">Suas turmas
      @if($prof && $prof['disciplina']) · {{ $prof['disciplina'] }}@endif
      @if($prof && $prof['total_turmas']) · {{ $prof['total_turmas'] }} {{ $prof['total_turmas'] == 1 ? 'turma ativa' : 'turmas ativas' }}@endif
    </p>
  </div>
  <a href="{{ route('provas.index') }}" class="btn btn-primary">+ Nova Prova</a>
</div>

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Minhas provas</div>
    <div class="kpi-value">{{ $kpis['minhas_provas'] ?? '—' }}</div>
    <div class="kpi-trend up">▲ no período letivo</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Cartões p/ corrigir</div>
    <div class="kpi-value" style="{{ ($kpis['cartoes_corrigir'] ?? 0) > 0 ? 'color:var(--warn-fg,#b06800)' : '' }}">
      {{ $kpis['cartoes_corrigir'] ?? '—' }}
    </div>
    <div class="kpi-trend" style="{{ ($kpis['cartoes_corrigir'] ?? 0) > 0 ? 'color:var(--warn-fg,#b06800)' : '' }}">
      {{ ($kpis['cartoes_corrigir'] ?? 0) > 0 ? '● leituras pendentes' : '● tudo corrigido' }}
    </div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Minhas turmas</div>
    <div class="kpi-value">{{ $kpis['minhas_turmas'] ?? '—' }}</div>
    <div class="kpi-trend">● turmas ativas</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Média das turmas</div>
    <div class="kpi-value">
      @if($kpis && $kpis['media_turmas'] !== null)
        {{ number_format($kpis['media_turmas'], 1, ',', '') }}
      @else
        —
      @endif
    </div>
    <div class="kpi-trend {{ ($kpis['media_turmas'] ?? 0) >= 7 ? 'up' : 'down' }}">
      {{ ($kpis['media_turmas'] ?? 0) >= 7 ? '▲ acima da meta' : '▼ acompanhar meta' }}
    </div>
  </div>
</div>

{{-- Grade principal --}}
<div class="main-grid">
  {{-- Tabela de provas --}}
  <div class="card card-pad">
    <div class="row-between" style="margin-bottom:12px;">
      <h2 class="section-title" style="margin-bottom:0;">Minhas provas</h2>
      <span class="badge badge-muted">Ano letivo {{ now()->year }}</span>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th>Prova</th>
          <th>Turma</th>
          <th>Data</th>
          <th>Alunos</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($provas as $p)
          @php $cfg = $statusConfig[$p['status']] ?? ['badge' => 'badge-muted', 'label' => $p['status']]; @endphp
          <tr>
            <td>{{ $p['titulo'] }}</td>
            <td>{{ $p['turma'] }}</td>
            <td class="num">{{ $p['data_aplicacao'] ? \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m/Y') : '—' }}</td>
            <td class="num">{{ $p['total_alunos'] }}</td>
            <td><span class="badge {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span></td>
            <td>
              <div class="proof-actions">
                <a class="btn btn-secondary btn-sm" href="{{ route('provas.gabarito.show', $p['id']) }}">Ver gabarito</a>
                <a class="btn btn-ghost btn-sm" href="#">Resultados</a>
                <a class="btn btn-ghost btn-sm" href="#">Acompanhar</a>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align:center;color:var(--muted);padding:24px;">
              Nenhuma prova cadastrada ainda.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Coluna lateral --}}
  <div class="side-stack">
    {{-- Ranking --}}
    <div class="card card-pad">
      <h2 class="section-title">Ranking de desempenho da turma</h2>
      <div class="ranking-grid">
        <div>
          <div class="eyebrow" style="margin-bottom:10px;">Top 5</div>
          <ul class="rank-list">
            @forelse($top5 as $i => $a)
              <li>
                <div class="student-line">
                  <div class="rank-pos">{{ $i + 1 }}</div>
                  <div>
                    <b>{{ Str::words($a['nome'], 2, '') }}</b>
                    <span>{{ $a['turma'] }}</span>
                  </div>
                </div>
                <strong class="num">{{ number_format($a['media'], 1, ',', '') }}</strong>
              </li>
            @empty
              <li style="justify-content:center;border:0;"><span style="color:var(--muted);font-size:13px;">Sem dados</span></li>
            @endforelse
          </ul>
        </div>
        <div>
          <div class="eyebrow" style="margin-bottom:10px;color:var(--danger,#e52207);">Bottom 3</div>
          <ul class="rank-list">
            @forelse($bottom3 as $i => $a)
              @php $cls = $a['media'] < 5 ? 'danger' : 'warn'; @endphp
              <li>
                <div class="student-line">
                  <div class="rank-pos {{ $cls }}">{{ $i + 1 }}</div>
                  <div>
                    <b>{{ Str::words($a['nome'], 2, '') }}</b>
                    <span>{{ $a['turma'] }}</span>
                  </div>
                </div>
                <strong class="num">{{ number_format($a['media'], 1, ',', '') }}</strong>
              </li>
            @empty
              <li style="justify-content:center;border:0;"><span style="color:var(--muted);font-size:13px;">Sem dados</span></li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>

    {{-- Alunos que precisam de atenção --}}
    <div class="card card-pad">
      <h2 class="section-title">Alunos que precisam de atenção</h2>
      @if(count($atencao))
        <div class="attention-grid">
          @foreach($atencao as $a)
            @php
              $ini = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(explode(' ', $a['nome']), 0, 2)));
              $badgeCls = $a['media'] < 5 ? 'badge-danger' : 'badge-warn';
              $badgeLabel = $a['media'] < 5 ? 'abaixo da média' : 'precisa reforço';
            @endphp
            <article>
              <div class="student-card-top">
                <div class="student-avatar">{{ $ini }}</div>
                <span class="trend down">↓ {{ number_format($a['media'], 1, ',', '') }}</span>
              </div>
              <div style="margin-top:12px;">
                <b>{{ Str::words($a['nome'], 2, '') }}</b>
                <div class="page-sub" style="font-size:13px;">{{ $a['turma'] }} · nota atual {{ number_format($a['media'], 1, ',', '') }}</div>
              </div>
              <div style="margin-top:10px;"><span class="badge {{ $badgeCls }}">{{ $badgeLabel }}</span></div>
            </article>
          @endforeach
        </div>
      @else
        <div style="color:var(--muted);font-size:13px;text-align:center;padding:16px 0;">
          Todos os alunos estão acima da média.
        </div>
      @endif
    </div>
  </div>
</div>

{{-- Ações rápidas --}}
<h2 class="section-title" style="margin-top:28px;">Ações rápidas</h2>
<div class="quick-actions">
  <a class="qa" href="{{ route('provas.index') }}">
    <div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg></div>
    <b>Nova Prova</b>
    <span>Monte gabarito e organize aplicação por turma.</span>
  </a>
  <a class="qa" href="#">
    <div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="4" width="14" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 9h8M8 13h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></div>
    <b>Capturar Cartões</b>
    <span>Abra o fluxo de leitura dos cartões da próxima turma.</span>
  </a>
  <a class="qa" href="{{ route('turmas.index') }}">
    <div class="qa-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM16 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM3.5 19a4.5 4.5 0 0 1 9 0M13 19a3.5 3.5 0 0 1 7 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></div>
    <b>Ver Turmas</b>
    <span>Acompanhe médias, frequência e desempenho por classe.</span>
  </a>
</div>

<div style="height:48px"></div>
@endsection
