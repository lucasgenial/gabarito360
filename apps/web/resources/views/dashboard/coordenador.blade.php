@extends('layouts.app')

@section('title', 'Painel do Coordenador')

@push('styles')
<style>
  .table td strong { display:block; color:var(--fg); }
  .table td small { color:var(--muted); font-size:12px; }
  .attention-list, .agenda-list { list-style:none; padding:0; margin:0; }
  .attention-list li, .agenda-list li { display:flex; gap:12px; align-items:flex-start; padding:14px 0; border-bottom:1px solid var(--border-soft); }
  .attention-list li:last-child, .agenda-list li:last-child { border-bottom:0; }
  .student-mark, .agenda-ico { width:40px; height:40px; border-radius:12px; display:grid; place-items:center; flex-shrink:0; font-weight:700; font-size:14px; }
  .student-mark { background:var(--surface-2); color:var(--accent-dark); }
  .student-mark.danger { background:var(--danger-light); color:var(--danger); }
  .student-mark.warn { background:var(--warn-light); color:var(--warn-fg); }
  .student-meta, .agenda-meta { min-width:0; }
  .student-meta b, .agenda-meta b { display:block; font-size:14px; }
  .student-meta span, .agenda-meta span { color:var(--muted); font-size:13px; }
  .agenda-date { font-family:var(--font-mono); font-size:12px; color:var(--accent-dark); margin-top:4px; }
  .agenda-ico { background:var(--accent-light); color:var(--accent-dark); }
  .agenda-ico.success { background:var(--success-light); color:var(--success); }
  .agenda-ico.warn { background:var(--warn-light); color:var(--warn-fg); }
</style>
@endpush

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

<x-pagina titulo="Painel da escola" subtitulo="{{ $nome }} · Coordenador(a) · {{ $escola['nome'] ?? '' }}">
  <a href="{{ route('correcao.index') }}" class="btn btn-primary"><x-icone nome="camera" /> Corrigir cartões</a>
  @pode('prova.criar')<a href="{{ route('provas.create') }}" class="btn btn-secondary">+ Criar prova</a>@endpode
</x-pagina>

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Provas em andamento</div>
    <div class="kpi-value">{{ $kpis['provas_andamento'] ?? '—' }}</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Cartões pendentes de leitura</div>
    <div class="kpi-value" style="{{ ($kpis['cartoes_pendentes'] ?? 0) > 0 ? 'color:var(--warn-fg)' : '' }}">
      {{ $kpis['cartoes_pendentes'] ?? '—' }}
    </div>
    <div class="kpi-trend" style="{{ ($kpis['cartoes_pendentes'] ?? 0) > 0 ? 'color:var(--warn-fg)' : '' }}">
      {{ ($kpis['cartoes_pendentes'] ?? 0) > 0 ? '● revisão necessária' : '● tudo em dia' }}
    </div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Alunos abaixo da média</div>
    <div class="kpi-value" style="{{ ($kpis['alunos_baixa_media'] ?? 0) > 0 ? 'color:var(--danger)' : '' }}">
      {{ $kpis['alunos_baixa_media'] ?? '—' }}
    </div>
    <div class="kpi-trend {{ ($kpis['alunos_baixa_media'] ?? 0) > 0 ? 'down' : '' }}">
      {{ ($kpis['alunos_baixa_media'] ?? 0) > 0 ? '▼ acompanhamento necessário' : '● nenhum aluno em atenção' }}
    </div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Próximas provas</div>
    <div class="kpi-value">{{ $kpis['proximas_provas'] ?? '—' }}</div>
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
    @include('dashboard._provas-ciclo', ['provas' => $provas])
  </div>

  {{-- Coluna lateral --}}
  <div class="side-stack">
    {{-- Alunos em atenção --}}
    <div class="card card-pad">
      <h2 class="section-title">Alunos em atenção</h2>
      <ul class="attention-list">
        @forelse($alunos as $a)
          @php

            $cls = $a['media'] < 5 ? 'danger' : 'warn';
            $badgeCls = $a['media'] < 5 ? 'badge-danger' : 'badge-warn';
          @endphp
          <li>
            <x-avatar :nome="$a['nome']" :aluno-id="$a['id'] ?? $a['aluno_id'] ?? null" />
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
  <a class="qa" href="{{ route('provas.create') }}">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
    </div>
    <b>Criar Prova</b>
    <span>Cadastre avaliação e gere cartões para aplicação.</span>
  </a>
  <a class="qa" href="{{ route('correcao.index') }}">
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
    <b>Turmas</b>
    <span>Acesse desempenho, matrículas e alunos em atenção.</span>
  </a>
</div>

<div style="height:48px"></div>
@endsection
