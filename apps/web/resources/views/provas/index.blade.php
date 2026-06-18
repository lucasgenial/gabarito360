@extends('layouts.app')

@section('title', 'Provas e Gabaritos')

@push('styles')
<style>
  .exam-meta { font-size:13px;color:var(--muted);margin-top:2px; }
  .mini-bar { width:76px;height:7px;background:var(--border-soft);border-radius:99px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px; }
  .mini-bar > div { height:100%; }
  .kpi-strip { display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-top:20px; }
  .kpi-strip .kv { font-family:var(--font-mono);font-size:24px;font-weight:700; }
  .kpi-strip .kl { font-size:11px;color:var(--muted);margin-top:2px; }
  @media(max-width:900px){ .kpi-strip{grid-template-columns:repeat(3,1fr);} .hide-sm{display:none;} }
  @media(max-width:600px){ .kpi-strip{grid-template-columns:repeat(2,1fr);} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('provas.index') }}" class="active">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Provas</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">Provas e gabaritos</h1>
    <p class="page-sub"><b>{{ count($provas) }}</b> prova(s) encontrada(s)</p>
  </div>
  <a href="{{ route('provas.create') }}" class="btn btn-primary">+ Nova prova</a>
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

{{-- KPIs --}}
<div class="kpi-strip">
  <div class="card card-pad" style="padding:14px 16px;">
    <div class="kv" style="color:var(--accent);">{{ $kpis['total'] ?? 0 }}</div>
    <div class="kl">Total de provas</div>
  </div>
  <div class="card card-pad" style="padding:14px 16px;">
    <div class="kv" style="color:var(--muted);">{{ $kpis['rascunho'] ?? 0 }}</div>
    <div class="kl">Rascunhos</div>
  </div>
  <div class="card card-pad" style="padding:14px 16px;">
    <div class="kv" style="color:var(--accent);">{{ $kpis['publicada'] ?? 0 }}</div>
    <div class="kl">Publicadas</div>
  </div>
  <div class="card card-pad" style="padding:14px 16px;">
    <div class="kv" style="color:#f59e0b;">{{ $kpis['correcao'] ?? 0 }}</div>
    <div class="kl">Em correção</div>
  </div>
  <div class="card card-pad" style="padding:14px 16px;">
    <div class="kv" style="color:var(--success,#168821);">{{ $kpis['corrigida'] ?? 0 }}</div>
    <div class="kl">Corrigidas</div>
  </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('provas.index') }}" style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;align-items:flex-end;">
  <div style="position:relative;flex:1;min-width:220px;">
    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input class="input" type="search" name="busca" placeholder="Buscar prova por título…"
           value="{{ $busca }}" style="padding-left:36px;" />
  </div>
  <select class="select" name="disciplina" style="min-width:160px;">
    <option value="">Todas as disciplinas</option>
    @foreach(['Matemática','Português','Ciências','História','Geografia','Inglês','Ed. Física','Arte'] as $d)
      <option value="{{ $d }}" {{ $disciplina === $d ? 'selected' : '' }}>{{ $d }}</option>
    @endforeach
  </select>
  <select class="select" name="status" style="min-width:150px;">
    <option value="">Todos os status</option>
    <option value="rascunho"    {{ $status==='rascunho'    ? 'selected' : '' }}>Rascunho</option>
    <option value="publicada"   {{ $status==='publicada'   ? 'selected' : '' }}>Publicada</option>
    <option value="em_correcao" {{ $status==='em_correcao' ? 'selected' : '' }}>Em correção</option>
    <option value="corrigida"   {{ $status==='corrigida'   ? 'selected' : '' }}>Corrigida</option>
  </select>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  @if($busca || $disciplina || $status)
    <a href="{{ route('provas.index') }}" class="btn btn-ghost">Limpar</a>
  @endif
</form>

{{-- Tabela --}}
<div class="card" style="margin-top:18px;overflow:hidden;">
  <table class="table">
    <thead>
      <tr>
        <th>Prova</th>
        <th class="hide-sm">Turmas</th>
        <th class="hide-sm">Aplicação</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse($provas as $p)
        @php
          $statusCfg = [
            'rascunho'    => ['cls'=>'badge-info',   'label'=>'Rascunho'],
            'publicada'   => ['cls'=>'badge-success', 'label'=>'Publicada'],
            'em_correcao' => ['cls'=>'',              'label'=>'Em correção',  'style'=>'background:rgba(245,158,11,.15);color:#b45309;'],
            'corrigida'   => ['cls'=>'badge-success', 'label'=>'Corrigida'],
          ];
          $sc = $statusCfg[$p['status']] ?? ['cls'=>'badge-info','label'=>$p['status']];
        @endphp
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:36px;height:36px;border-radius:8px;background:var(--accent-light);color:var(--accent-dark,#0c326f);display:grid;place-items:center;flex-shrink:0;font-size:18px;">📝</div>
              <div>
                <div style="font-weight:600;">{{ $p['titulo'] }}</div>
                <div class="exam-meta">{{ $p['disciplina'] }} · {{ $p['serie'] }} · {{ $p['num_questoes'] }} questões</div>
              </div>
            </div>
          </td>
          <td class="hide-sm">
            @if(count($p['turmas'] ?? []) > 0)
              <span style="color:var(--fg-2);font-size:13px;">{{ implode(' · ', array_slice($p['turmas'], 0, 3)) }}</span>
              @if(count($p['turmas']) > 3)
                <span style="color:var(--muted);font-size:12px;"> +{{ count($p['turmas'])-3 }}</span>
              @endif
            @else
              <span style="color:var(--muted);font-size:13px;">—</span>
            @endif
          </td>
          <td class="hide-sm" style="font-size:13px;">
            {{ $p['data_aplicacao'] ? \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m/Y') : '—' }}
          </td>
          <td>
            <span class="badge {{ $sc['cls'] }}" style="{{ $sc['style'] ?? '' }}">{{ $sc['label'] }}</span>
          </td>
          <td style="text-align:right;white-space:nowrap;">
            @if($p['status'] === 'rascunho')
              <form method="POST" action="{{ route('provas.publicar', $p['id']) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-sm btn-secondary" title="Publicar">Publicar</button>
              </form>
            @endif
            @if($p['status'] === 'em_correcao')
              <a href="{{ route('correcao.show', $p['id']) }}" class="btn btn-sm btn-secondary" style="margin-left:6px;">Acompanhar</a>
            @elseif($p['status'] === 'corrigida')
              <a href="{{ route('relatorios.prova', $p['id']) }}" class="btn btn-sm btn-secondary" style="margin-left:6px;">Relatório</a>
            @else
              <a href="{{ route('provas.show', $p['id']) }}" class="btn btn-sm btn-secondary" style="margin-left:6px;">Ver</a>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="5" style="text-align:center;padding:48px 20px;color:var(--muted);">
            <div style="font-size:36px;margin-bottom:12px;">📝</div>
            <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma prova encontrada</div>
            <p>Crie a primeira prova usando o botão "+ Nova prova".</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div style="height:48px;"></div>
@endsection
