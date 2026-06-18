@extends('layouts.app')

@section('title', 'Painel do Coordenador')

@push('styles')
<style>
  .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-top:24px; }
  .content-grid { display:grid; grid-template-columns:1.8fr 1fr; gap:20px; margin-top:20px; }
  .section-title { font-size:17px; margin-bottom:14px; font-weight:700; }
  .table td strong { display:block; color:var(--fg); }
  .table td small { color:var(--muted); font-size:12px; }
  .attention-list, .agenda-list { list-style:none; padding:0; margin:0; }
  .attention-list li, .agenda-list li { display:flex; gap:12px; align-items:flex-start; padding:14px 0; border-bottom:1px solid var(--border-soft); }
  .attention-list li:last-child, .agenda-list li:last-child { border-bottom:0; }
  .student-mark, .agenda-ico { width:40px; height:40px; border-radius:12px; display:grid; place-items:center; flex-shrink:0; font-weight:700; font-size:14px; }
  .student-mark { background:var(--surface-2); color:var(--accent-dark); }
  .student-mark.danger { background:var(--danger-light,#fde8e4); color:var(--danger,#e52207); }
  .student-mark.warn { background:var(--warn-light,#fff5c2); color:var(--warn-fg,#b06800); }
  .student-meta, .agenda-meta { min-width:0; }
  .student-meta b, .agenda-meta b { display:block; font-size:14px; }
  .student-meta span, .agenda-meta span { color:var(--muted); font-size:13px; }
  .agenda-date { font-family:var(--font-mono,monospace); font-size:12px; color:var(--accent-dark); margin-top:4px; }
  .agenda-ico { background:var(--accent-light); color:var(--accent-dark); }
  .agenda-ico.success { background:var(--success-light,#e3f5e1); color:var(--success,#168821); }
  .agenda-ico.warn { background:var(--warn-light,#fff5c2); color:var(--warn-fg,#b06800); }
  .side-stack { display:grid; gap:20px; }
  .quick-actions { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-top:20px; }
  .qa { display:flex; flex-direction:column; gap:12px; padding:18px; border-radius:var(--radius-lg); border:1px solid var(--border-soft); background:var(--surface); text-decoration:none; }
  .qa:hover { border-color:var(--accent); box-shadow:var(--shadow-sm); }
  .qa-ico { width:42px; height:42px; border-radius:10px; display:grid; place-items:center; background:var(--accent-light); color:var(--accent-dark); }
  .qa b { color:var(--fg); font-size:15px; }
  .qa span { color:var(--muted); font-size:13px; }
  @media (max-width:1080px){ .kpi-grid{grid-template-columns:repeat(2,1fr);} .content-grid{grid-template-columns:1fr;} }
  @media (max-width:640px){ .kpi-grid,.quick-actions{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}" class="active">Painel</a>
<a href="{{ route('provas.index') }}">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('context-badge')
<span class="badge badge-info badge-dot">{{ $dashboard['escola']['nome'] ?? 'Escola' }}</span>
@endsection

@section('breadcrumb')
<span>Início</span><span class="sep">/</span><span>Painel</span>
@endsection

@section('content')

@php
  $kpis    = $dashboard['kpis']           ?? null;
  $provas  = $dashboard['provas']         ?? [];
  $alunos  = $dashboard['alunos_atencao'] ?? [];
  $agenda  = $dashboard['agenda']         ?? [];
  $escola  = $dashboard['escola']         ?? null;

  $iniciais = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(explode(' ', $nome), 0, 2)));

  $statusConfig = [
    'publicada'   => ['badge' => 'badge-warn',    'label' => 'aguardando'],
    'em_correcao' => ['badge' => 'badge-info',    'label' => 'em correção'],
    'corrigida'   => ['badge' => 'badge-success', 'label' => 'concluído'],
    'rascunho'    => ['badge' => 'badge-muted',   'label' => 'rascunho'],
  ];

  $agendaIcons = [
    'publicada'   => ['cls' => '',       'svg' => '<rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="2"/>'],
    'corrigida'   => ['cls' => 'success','svg' => '<path d="M5 12.5l4.2 4.2L19 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>'],
    'em_correcao' => ['cls' => 'warn',   'svg' => '<path d="M12 8v5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><circle cx="12" cy="16.5" r="1.2" fill="currentColor"/><path d="M10.2 4.8L3.7 16a2 2 0 0 0 1.7 3h13.2a2 2 0 0 0 1.7-3L13.8 4.8a2 2 0 0 0-3.6 0Z" stroke="currentColor" stroke-width="2"/>'],
    'rascunho'    => ['cls' => '',       'svg' => '<path d="M7 4h10l2 3v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2"/><path d="M9 11h6M9 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'],
  ];
@endphp

<div class="row-between" style="margin-top:12px;">
  <div>
    <div class="eyebrow">Coordenação pedagógica</div>
    <h1 class="page-title">Painel da escola</h1>
    <p class="page-sub">{{ $nome }} ({{ $iniciais }}) · Coordenador(a) · {{ $escola['nome'] ?? '' }}</p>
  </div>
  <a href="{{ route('provas.index') }}" class="btn btn-primary">Criar prova</a>
</div>

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Provas em andamento</div>
    <div class="kpi-value">{{ $kpis['provas_andamento'] ?? '—' }}</div>
    <div class="kpi-trend up">▲ no período letivo</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Cartões pendentes de leitura</div>
    <div class="kpi-value" style="{{ ($kpis['cartoes_pendentes'] ?? 0) > 0 ? 'color:var(--warn-fg,#b06800)' : '' }}">
      {{ $kpis['cartoes_pendentes'] ?? '—' }}
    </div>
    <div class="kpi-trend" style="{{ ($kpis['cartoes_pendentes'] ?? 0) > 0 ? 'color:var(--warn-fg,#b06800)' : '' }}">
      {{ ($kpis['cartoes_pendentes'] ?? 0) > 0 ? '● revisão necessária' : '● tudo em dia' }}
    </div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Alunos abaixo da média</div>
    <div class="kpi-value" style="{{ ($kpis['alunos_baixa_media'] ?? 0) > 0 ? 'color:var(--danger,#e52207)' : '' }}">
      {{ $kpis['alunos_baixa_media'] ?? '—' }}
    </div>
    <div class="kpi-trend {{ ($kpis['alunos_baixa_media'] ?? 0) > 0 ? 'down' : '' }}">
      {{ ($kpis['alunos_baixa_media'] ?? 0) > 0 ? '▼ acompanhamento necessário' : '● todos acima da média' }}
    </div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Próximas provas</div>
    <div class="kpi-value">{{ $kpis['proximas_provas'] ?? '—' }}</div>
    <div class="kpi-trend">● até os próximos 7 dias</div>
  </div>
</div>

{{-- Grade principal --}}
<div class="content-grid">
  {{-- Tabela de provas em andamento --}}
  <div class="card card-pad">
    <div class="row-between" style="margin-bottom:12px;">
      <h2 class="section-title" style="margin-bottom:0;">Provas em andamento</h2>
      <span class="badge badge-muted">Atualizado às {{ now()->format('H:i') }}</span>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th>Disciplina</th>
          <th>Professor(a)</th>
          <th>Turma</th>
          <th>Alunos</th>
          <th>Lidos</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($provas as $p)
          @php $cfg = $statusConfig[$p['status']] ?? ['badge' => 'badge-muted', 'label' => $p['status']]; @endphp
          <tr>
            <td>
              <strong>{{ $p['disciplina'] }}</strong>
              <small>{{ $p['titulo'] }}</small>
            </td>
            <td>{{ $p['professor'] ?? '—' }}</td>
            <td>{{ $p['turmas'] }}</td>
            <td class="num">{{ $p['total_alunos'] }}</td>
            <td class="num">{{ $p['cartoes_lidos'] }}</td>
            <td><span class="badge {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span></td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align:center;color:var(--muted);padding:24px;">
              Nenhuma prova em andamento.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Coluna lateral --}}
  <div class="side-stack">
    {{-- Alunos em atenção --}}
    <div class="card card-pad">
      <h2 class="section-title">Alunos em atenção</h2>
      <ul class="attention-list">
        @forelse($alunos as $a)
          @php
            $ini = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(explode(' ', $a['nome']), 0, 2)));
            $cls = $a['media'] < 5 ? 'danger' : 'warn';
            $badgeCls = $a['media'] < 5 ? 'badge-danger' : 'badge-warn';
          @endphp
          <li>
            <div class="student-mark {{ $cls }}">{{ $ini }}</div>
            <div class="student-meta">
              <b>{{ $a['nome'] }} · {{ $a['turma'] }}</b>
              <span>{{ $a['disciplina'] }} · média atual</span>
              <div style="margin-top:6px;">
                <span class="badge {{ $badgeCls }}">{{ number_format($a['media'], 1, ',', '') }}</span>
              </div>
            </div>
          </li>
        @empty
          <li style="justify-content:center;border-bottom:0;">
            <span style="color:var(--muted);font-size:13px;">Nenhum aluno abaixo da média.</span>
          </li>
        @endforelse
      </ul>
    </div>

    {{-- Agenda da semana --}}
    <div class="card card-pad">
      <h2 class="section-title">Agenda da semana</h2>
      <ul class="agenda-list">
        @forelse($agenda as $ag)
          @php
            $icon = $agendaIcons[$ag['status']] ?? $agendaIcons['publicada'];
          @endphp
          <li>
            <div class="agenda-ico {{ $icon['cls'] }}">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                {!! $icon['svg'] !!}
              </svg>
            </div>
            <div class="agenda-meta">
              <b>{{ $ag['disciplina'] }} · {{ $ag['titulo'] }}</b>
              <span>Aplicação agendada</span>
              <div class="agenda-date">{{ \Carbon\Carbon::parse($ag['data_aplicacao'])->format('d/m/Y') }}</div>
            </div>
          </li>
        @empty
          <li style="justify-content:center;border-bottom:0;">
            <span style="color:var(--muted);font-size:13px;">Nenhum evento esta semana.</span>
          </li>
        @endforelse
      </ul>
    </div>
  </div>
</div>

{{-- Ações rápidas --}}
<h2 class="section-title" style="margin-top:28px;">Ações rápidas</h2>
<div class="quick-actions">
  <a class="qa" href="{{ route('provas.index') }}">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
    </div>
    <b>Criar Prova</b>
    <span>Cadastre avaliação e gere cartões para aplicação.</span>
  </a>
  <a class="qa" href="#">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 18h16M7 15l3-3 3 2 4-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <b>Acompanhar Correções</b>
    <span>Veja pendências, cartões lidos e status por turma.</span>
  </a>
  <a class="qa" href="{{ route('turmas.index') }}">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM16 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM3.5 19a4.5 4.5 0 0 1 9 0M13 19a3.5 3.5 0 0 1 7 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <b>Ver Turmas</b>
    <span>Acesse desempenho, matrículas e alunos em atenção.</span>
  </a>
</div>

<div style="height:48px"></div>
@endsection
