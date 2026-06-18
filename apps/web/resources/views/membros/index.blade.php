@extends('layouts.app')

@section('title', 'Membros da Equipe')

@push('styles')
<style>
  .kpi-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
  .kpi-card-role { background:var(--surface); border:1px solid var(--border-soft); border-radius:var(--radius-lg); padding:18px; box-shadow:var(--shadow-sm); display:flex; flex-direction:column; gap:12px; }
  .kpi-role { font-size:14px; font-weight:700; }
  .kpi-num { font-family:var(--font-mono); font-size:26px; font-weight:700; color:var(--accent); line-height:1; }
  .kpi-meta { font-size:12px; color:var(--muted); }
  .badge-purple { background:#efe7ff; color:#6d28d9; }
  .badge-amber  { background:#fff4d6; color:#a16207; }
  .member-avatar { width:38px; height:38px; border-radius:50%; display:grid; place-items:center; font-weight:700; font-size:13px; background:var(--accent-light); color:var(--accent-dark); flex-shrink:0; }
  .member-cell { display:flex; align-items:center; gap:12px; }
  .member-name { font-weight:700; font-size:14px; }
  .member-email { font-size:12px; color:var(--muted); }
  .filter-bar { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:20px; }
  .filter-bar .input { flex:1; min-width:200px; max-width:360px; }
  @media (max-width:900px){ .kpi-strip{grid-template-columns:repeat(2,1fr);} }
  @media (max-width:560px){ .kpi-strip{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('membros.index') }}" class="active">Equipe</a>
<a href="{{ route('provas.index') }}">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('breadcrumb')
<span>Início</span><span class="sep">/</span><span>Membros da Equipe</span>
@endsection

@section('content')

@php
  $perfilLabels = [
    'admin_rede'  => 'Administrador da Rede',
    'dir_nucleo'  => 'Diretor de Núcleo',
    'dir_escolar' => 'Diretor Escolar',
    'coordenador' => 'Coordenador Pedagógico',
    'professor'   => 'Professor',
    'aluno'       => 'Aluno',
  ];
  $badgeMap = [
    'admin_rede'  => 'badge-info',
    'dir_nucleo'  => 'badge-warn',
    'dir_escolar' => 'badge-purple',
    'coordenador' => 'badge-amber',
    'professor'   => 'badge-info',
    'aluno'       => 'badge-success',
  ];
@endphp

<div class="row-between" style="margin-top:12px;">
  <div>
    <div class="eyebrow">Administração</div>
    <h1 class="page-title">Membros da Equipe</h1>
    <p class="page-sub">Gerencie os usuários e perfis de acesso do sistema.</p>
  </div>
  <a href="{{ route('membros.create') }}" class="btn btn-primary">+ Novo Membro</a>
</div>

@if(session('sucesso'))
  <div style="background:var(--success-light,#e3f5e1);color:var(--success,#168821);border:1px solid var(--success,#168821);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('sucesso') }}
  </div>
@endif
@if(session('erro'))
  <div style="background:var(--danger-light,#fde8e4);color:var(--danger,#e52207);border:1px solid var(--danger,#e52207);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('erro') }}
  </div>
@endif

{{-- KPIs por perfil --}}
<div class="kpi-strip" style="margin-top:20px;">
  @foreach(['professor','coordenador','dir_escolar','admin_rede'] as $pf)
    <div class="kpi-card-role">
      <div class="row-between">
        <div class="kpi-role">{{ $perfilLabels[$pf] }}</div>
        <span class="badge {{ $badgeMap[$pf] }}">{{ $pf }}</span>
      </div>
      <div class="kpi-num">{{ $totais[$pf] ?? 0 }}</div>
      <div class="kpi-meta">{{ ($totais[$pf] ?? 0) === 1 ? 'membro' : 'membros' }}</div>
    </div>
  @endforeach
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('membros.index') }}" class="filter-bar">
  <input class="input" type="text" name="busca" placeholder="Buscar por nome ou e-mail…" value="{{ $filtroBusc }}">
  <select class="select" name="perfil" style="width:auto;">
    <option value="">Todos os perfis</option>
    @foreach($perfis as $key => $cfg)
      <option value="{{ $key }}" {{ $filtroPerf === $key ? 'selected' : '' }}>{{ $cfg['label'] }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  @if($filtroBusc || $filtroPerf)
    <a href="{{ route('membros.index') }}" class="btn btn-ghost">Limpar</a>
  @endif
</form>

{{-- Tabela de membros agrupada por perfil --}}
@if($agrupados->isEmpty())
  <div style="text-align:center;padding:40px;color:var(--muted);">
    Nenhum membro encontrado.
  </div>
@else
  @foreach($perfis as $perfilKey => $perfilCfg)
    @if($agrupados->has($perfilKey))
      @php $membros = $agrupados->get($perfilKey); @endphp
      <div class="card card-pad" style="margin-bottom:20px;">
        <div class="row-between" style="margin-bottom:14px;">
          <h2 style="font-size:16px;font-weight:700;">{{ $perfilCfg['label'] }}</h2>
          <span class="badge {{ $perfilCfg['badge'] }}">{{ $membros->count() }} {{ $membros->count() === 1 ? 'membro' : 'membros' }}</span>
        </div>
        <table class="table">
          <thead>
            <tr>
              <th>Membro</th>
              <th>E-mail</th>
              <th>Desde</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            @foreach($membros as $m)
              @php
                $ini = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(array_filter(explode(' ', $m['nome'])), 0, 2)));
              @endphp
              <tr>
                <td>
                  <div class="member-cell">
                    <div class="member-avatar">{{ $ini }}</div>
                    <div>
                      <div class="member-name">{{ $m['nome'] }}</div>
                    </div>
                  </div>
                </td>
                <td class="mono" style="font-size:13px;">{{ $m['email'] }}</td>
                <td class="num">{{ $m['created_at'] ? \Carbon\Carbon::parse($m['created_at'])->format('d/m/Y') : '—' }}</td>
                <td>
                  @if($m['ativo'])
                    <span class="badge badge-success">Ativo</span>
                  @else
                    <span class="badge badge-danger">Inativo</span>
                  @endif
                </td>
                <td>
                  <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <a href="{{ route('membros.edit', $m['id']) }}" class="btn btn-secondary btn-sm">Editar</a>
                    <form method="POST" action="{{ route('membros.toggle', $m['id']) }}" style="display:inline;">
                      @csrf
                      <button type="submit" class="btn btn-ghost btn-sm">
                        {{ $m['ativo'] ? 'Desativar' : 'Ativar' }}
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  @endforeach
@endif

<div style="height:48px"></div>
@endsection
