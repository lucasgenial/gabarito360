@extends('layouts.app')

@section('title', 'Painel da Escola')

@push('styles')
<style>
  .school-banner { margin-top:20px; padding:20px 24px; border:1px solid var(--accent); background:var(--accent-light); border-radius:var(--radius-lg); display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap; }
  .school-meta { color:var(--accent-dark); font-weight:700; margin-top:4px; font-size:14px; }
  .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-top:24px; }
  .content-grid { display:grid; grid-template-columns:1.7fr 1fr; gap:20px; margin-top:20px; }
  .table-wrap { overflow:hidden; }
  .team-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:14px; }
  .person-card { display:flex; align-items:center; gap:12px; padding:14px; border:1px solid var(--border-soft); border-radius:var(--radius-md); background:var(--surface-2); }
  .person-card .avatar-mini { width:42px; height:42px; border-radius:50%; display:grid; place-items:center; font-weight:700; font-size:14px; background:var(--accent-light); color:var(--accent-dark); flex-shrink:0; }
  .person-card strong { display:block; font-size:14px; }
  .person-card span { color:var(--muted); font-size:13px; }
  .list-stack { display:grid; gap:12px; margin-top:12px; }
  .list-item { display:flex; justify-content:space-between; align-items:flex-start; gap:14px; padding:14px; border:1px solid var(--border-soft); border-radius:var(--radius-md); background:var(--surface-2); }
  .list-item strong { display:block; font-size:14px; }
  .list-item span { color:var(--muted); font-size:13px; }
  .quick-actions { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-top:20px; }
  .qa { display:flex; flex-direction:column; gap:10px; padding:18px; border:1px solid var(--border-soft); border-radius:var(--radius-md); background:var(--surface); transition:border-color .15s, box-shadow .15s; text-decoration:none; }
  .qa:hover { border-color:var(--accent); box-shadow:var(--shadow-sm); }
  .qa-ico { width:40px; height:40px; display:grid; place-items:center; border-radius:10px; background:var(--accent-light); color:var(--accent-dark); }
  .qa b { font-size:15px; color:var(--fg); }
  .qa span { font-size:13px; color:var(--muted); }
  .side-stack { display:grid; gap:20px; }
  @media (max-width:1080px){ .kpi-grid{grid-template-columns:repeat(2,1fr);} .content-grid{grid-template-columns:1fr;} .team-grid{grid-template-columns:repeat(2,1fr);} }
  @media (max-width:720px){ .kpi-grid,.quick-actions{grid-template-columns:1fr;} .team-grid{grid-template-columns:1fr 1fr;} }
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
  $escola   = $dashboard['escola']          ?? null;
  $kpis     = $dashboard['kpis']            ?? null;
  $turmas   = $dashboard['turmas']          ?? [];
  $equipe   = $dashboard['equipe']          ?? [];
  $provas   = $dashboard['proximas_provas'] ?? [];

  $nomeEscola = $escola['nome'] ?? 'da Escola';

  $statusConfig = [
    'destaque'       => ['badge' => 'badge-success', 'label' => 'Destaque'],
    'bom_desempenho' => ['badge' => 'badge-success', 'label' => 'Bom desempenho'],
    'em_dia'         => ['badge' => 'badge-success', 'label' => 'Em dia'],
    'monitorar'      => ['badge' => 'badge-info',    'label' => 'Monitorar'],
    'atencao'        => ['badge' => 'badge-info',    'label' => 'Atenção'],
    'plano_apoio'    => ['badge' => 'badge-warn',    'label' => 'Plano de apoio'],
    'sem_dados'      => ['badge' => 'badge-muted',   'label' => 'Sem dados'],
  ];

  $provaStatusConfig = [
    'publicada'    => ['badge' => 'badge-info',    'label' => 'Confirmada'],
    'em_correcao'  => ['badge' => 'badge-warn',    'label' => 'Em correção'],
    'corrigida'    => ['badge' => 'badge-success', 'label' => 'Concluída'],
    'rascunho'     => ['badge' => 'badge-muted',   'label' => 'Rascunho'],
  ];

  $perfisLegivel = [
    'admin_rede'  => 'Administrador',
    'dir_nucleo'  => 'Dir. de Núcleo',
    'dir_escolar' => 'Diretor(a)',
    'coordenador' => 'Coordenador(a)',
    'professor'   => 'Professor(a)',
    'aluno'       => 'Aluno',
  ];
@endphp

<div class="row-between" style="margin-top:12px;">
  <div>
    <div class="eyebrow">Gestão escolar</div>
    <h1 class="page-title">Painel {{ $nomeEscola }}</h1>
    <p class="page-sub">{{ $nome }} · acompanhamento pedagógico da unidade · período letivo {{ now()->year }}</p>
  </div>
  <a href="{{ route('turmas.index') }}" class="btn btn-primary">Ver turmas ativas</a>
</div>

{{-- Banner da escola --}}
@if($escola)
<div class="school-banner">
  <div>
    <div class="eyebrow" style="color:var(--accent-dark);">Unidade escolar</div>
    <h3 style="font-size:24px;color:var(--accent-dark);margin-top:4px;">{{ $escola['nome'] }}</h3>
    <div class="school-meta">INEP {{ $escola['inep'] }} · Ensino Fundamental · Turnos manhã e tarde</div>
  </div>
  <span class="badge {{ $escola['ativo'] ? 'badge-info' : 'badge-muted' }}" style="font-size:13px;">
    {{ $escola['ativo'] ? 'Escola Ativa' : 'Escola Inativa' }}
  </span>
</div>
@endif

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Turmas Ativas</div>
    <div class="kpi-value">{{ $kpis['turmas_ativas'] ?? '—' }}</div>
    <div class="kpi-trend">● turmas em operação</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Total de Alunos</div>
    <div class="kpi-value">{{ $kpis ? number_format($kpis['total_alunos'], 0, ',', '.') : '—' }}</div>
    <div class="kpi-trend up">▲ matrículas ativas</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Provas Aplicadas</div>
    <div class="kpi-value">{{ $kpis['provas_aplicadas'] ?? '—' }}</div>
    <div class="kpi-trend up">▲ no período letivo</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Média da Escola</div>
    <div class="kpi-value">
      @if($kpis && $kpis['media_escola'] !== null)
        {{ number_format($kpis['media_escola'], 1, ',', '') }}
      @else
        —
      @endif
    </div>
    <div class="kpi-trend {{ ($kpis['media_escola'] ?? 0) >= 7 ? 'up' : '' }}">
      {{ ($kpis['media_escola'] ?? 0) >= 7 ? '▲ acima da meta da rede' : '● acompanhar meta da rede' }}
    </div>
  </div>
</div>

{{-- Grade principal --}}
<div class="content-grid">
  {{-- Tabela por turma --}}
  <div class="card table-wrap">
    <div class="card-pad" style="padding-bottom:0;">
      <div class="eyebrow">Desempenho por turma</div>
      <h3 style="font-size:18px;">Consolidação das últimas provas</h3>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th>Turma</th>
          <th>Professor(a)</th>
          <th>Alunos</th>
          <th>Última Prova</th>
          <th>Média</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($turmas as $t)
          @php $cfg = $statusConfig[$t['status']] ?? ['badge' => 'badge-muted', 'label' => 'N/D']; @endphp
          <tr>
            <td>{{ $t['nome'] }}</td>
            <td>{{ $t['professor'] ?? '—' }}</td>
            <td class="num">{{ $t['total_alunos'] }}</td>
            <td>{{ $t['ultima_prova'] ?? '—' }}</td>
            <td class="num">{{ $t['media'] !== null ? number_format($t['media'], 1, ',', '') : '—' }}</td>
            <td><span class="badge {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span></td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align:center;color:var(--muted);padding:24px;">
              Nenhuma turma ativa nesta escola.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Coluna lateral --}}
  <div class="side-stack">
    {{-- Equipe da escola --}}
    <div class="card card-pad">
      <div class="eyebrow">Equipe da escola</div>
      <h3 style="font-size:18px;">Gestão e docentes de referência</h3>
      <div class="team-grid">
        @forelse($equipe as $membro)
          @php
            $iniciais = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(explode(' ', $membro['nome']), 0, 2)));
            $cargo = $perfisLegivel[$membro['perfil']] ?? $membro['perfil'];
          @endphp
          <div class="person-card">
            <div class="avatar-mini">{{ $iniciais }}</div>
            <div>
              <strong>{{ Str::words($membro['nome'], 2, '') }}</strong>
              <span>{{ $cargo }}</span>
            </div>
          </div>
        @empty
          <div style="grid-column:1/-1;color:var(--muted);font-size:13px;text-align:center;padding:16px 0;">
            Nenhum membro vinculado.
          </div>
        @endforelse
      </div>
    </div>

    {{-- Calendário de provas --}}
    <div class="card card-pad">
      <div class="eyebrow">Calendário de provas próximas</div>
      <h3 style="font-size:18px;">Aplicações agendadas</h3>
      <div class="list-stack">
        @forelse($provas as $p)
          @php $pcfg = $provaStatusConfig[$p['status']] ?? ['badge' => 'badge-muted', 'label' => ucfirst($p['status'])]; @endphp
          <div class="list-item">
            <div>
              <strong>{{ \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m/Y') }} · {{ $p['titulo'] }}</strong>
              <span>{{ $p['disciplina'] }}</span>
            </div>
            <span class="badge {{ $pcfg['badge'] }}">{{ $pcfg['label'] }}</span>
          </div>
        @empty
          <div style="color:var(--muted);font-size:13px;text-align:center;padding:16px 0;">
            Nenhuma prova agendada.
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- Ações rápidas --}}
<h3 style="font-size:17px;margin-top:32px;">Ações rápidas</h3>
<div class="quick-actions">
  <a class="qa" href="{{ route('membros.index') }}">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="8" cy="8" r="3" stroke="currentColor" stroke-width="2"/><circle cx="17" cy="9" r="2.5" stroke="currentColor" stroke-width="2"/><path d="M3.5 19c1.8-3 7.2-3 9 0M13.5 18.5c.9-1.8 3.7-2.6 5.8-1.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <b>Gerenciar Equipe</b>
    <span>Atualizar funções, perfis e acessos dos profissionais.</span>
  </a>
  <a class="qa" href="{{ route('turmas.index') }}">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 9h8M8 13h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <b>Ver Turmas</b>
    <span>Acompanhar matrículas, provas e pendências por sala.</span>
  </a>
  <a class="qa" href="#">
    <div class="qa-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3v18M12 8v13M17 12v9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M5 21h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <b>Relatório da Escola</b>
    <span>Emitir consolidado da unidade para conselho e supervisão.</span>
  </a>
</div>

<div style="height:48px"></div>
@endsection
