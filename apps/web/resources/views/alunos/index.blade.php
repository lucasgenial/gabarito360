@extends('layouts.app')

@section('title', 'Alunos')

@push('styles')
<style>
  .student-av { width:36px;height:36px;border-radius:50%;background:var(--accent-light);color:var(--accent-dark,#0c326f);display:grid;place-items:center;font-weight:700;font-size:13px;flex-shrink:0; }
  .student-name { display:flex;align-items:center;gap:12px; }
  .kpi-strip { display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-top:20px; }
  .kpi-strip .kv { font-family:var(--font-mono);font-size:28px;font-weight:700; }
  .kpi-strip .kl { font-size:12px;color:var(--muted);margin-top:2px; }
  @media(max-width:600px){ .kpi-strip{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
<a href="{{ route('provas.index') }}">Provas</a>
@endsection

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Alunos</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">Alunos</h1>
    <p class="page-sub"><b>{{ $kpis['total_alunos'] ?? 0 }}</b> aluno(s) cadastrado(s)</p>
  </div>
  <a href="{{ route('alunos.create') }}" class="btn btn-primary">+ Cadastrar aluno</a>
</div>

@if(session('sucesso'))
  <div style="background:var(--success-light,#e3f5e1);color:var(--success,#168821);border:1px solid var(--success,#168821);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('sucesso') }}
  </div>
@endif

{{-- KPIs --}}
<div class="kpi-strip">
  <div class="card card-pad" style="padding:16px 20px;">
    <div class="kv" style="color:var(--accent);">{{ $kpis['total_alunos'] ?? 0 }}</div>
    <div class="kl">Total de alunos</div>
  </div>
  <div class="card card-pad" style="padding:16px 20px;">
    <div class="kv" style="color:var(--success,#168821);">{{ $kpis['total_ativos'] ?? 0 }}</div>
    <div class="kl">Alunos ativos</div>
  </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('alunos.index') }}" style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;align-items:flex-end;">
  <div style="position:relative;flex:1;min-width:200px;">
    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input class="input" type="search" name="busca" placeholder="Buscar aluno por nome ou matrícula…"
           value="{{ $busca }}" style="padding-left:36px;" />
  </div>
  <select class="select" name="turma_id" style="min-width:180px;">
    <option value="">Todas as turmas</option>
    @foreach($turmas as $t)
      <option value="{{ $t['id'] }}" {{ $turma_id == $t['id'] ? 'selected' : '' }}>{{ $t['nome'] }} — {{ $t['serie'] }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  @if($busca || $turma_id)
    <a href="{{ route('alunos.index') }}" class="btn btn-ghost">Limpar</a>
  @endif
</form>

{{-- Tabela --}}
<div class="card" style="margin-top:18px;overflow:hidden;">
  <table class="table">
    <thead>
      <tr>
        <th>Aluno</th>
        <th>Matrícula</th>
        <th>Turma</th>
        <th>Série</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse($alunos as $a)
        @php
          $ini = collect(explode(' ', $a['nome']))->filter()->map(fn($p) => mb_strtoupper(mb_substr($p,0,1)))->take(2)->implode('');
        @endphp
        <tr>
          <td>
            <div class="student-name">
              <div class="student-av">{{ $ini }}</div>
              <strong>{{ $a['nome'] }}</strong>
            </div>
          </td>
          <td style="font-family:var(--font-mono);">{{ $a['matricula'] }}</td>
          <td>{{ $a['turma_nome'] ?? '—' }}</td>
          <td>{{ $a['turma_serie'] ?? '—' }}</td>
          <td>
            @if($a['ativo'])
              <span class="badge badge-success badge-dot">Ativo</span>
            @else
              <span class="badge badge-danger badge-dot">Inativo</span>
            @endif
          </td>
          <td style="text-align:right;">
            <a href="{{ route('alunos.show', $a['id']) }}" class="btn btn-sm btn-secondary">Ver</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;padding:48px 20px;color:var(--muted);">
            <div style="font-size:36px;margin-bottom:12px;">👤</div>
            <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhum aluno encontrado</div>
            <p>Cadastre o primeiro aluno usando o botão "+ Cadastrar aluno".</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div style="height:48px;"></div>
@endsection
