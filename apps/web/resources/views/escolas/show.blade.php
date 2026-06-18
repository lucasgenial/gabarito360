@extends('layouts.app')

@section('title', $escola['nome'])

@push('styles')
<style>
  /* Hero */
  .escola-hero { background:linear-gradient(135deg,var(--accent) 0%,var(--accent-dark,#0c326f) 100%); color:#fff; padding:32px 0 0; }
  .escola-hero-inner { display:flex; align-items:flex-start; gap:20px; }
  .escola-hero-icon { width:60px;height:60px;background:rgba(255,255,255,.18);border-radius:var(--radius-md);display:grid;place-items:center;font-size:30px;flex-shrink:0;border:1.5px solid rgba(255,255,255,.3); }
  .escola-hero-info { flex:1;min-width:0; }
  .escola-hero-info h1 { font-size:22px;font-weight:800;line-height:1.2; }
  .escola-hero-meta { display:flex;flex-wrap:wrap;gap:14px;margin-top:8px;font-size:13px;opacity:.88; }
  .escola-hero-meta span { display:flex;align-items:center;gap:5px; }
  .hero-actions { display:flex;gap:8px;margin-top:16px;flex-wrap:wrap; }
  .btn-hero { background:rgba(255,255,255,.2);color:#fff;border:1.5px solid rgba(255,255,255,.35);font-size:13px;padding:7px 16px;border-radius:var(--radius-sm);cursor:pointer;font-weight:600;text-decoration:none;transition:background .15s;display:inline-flex;align-items:center;gap:6px; }
  .btn-hero:hover { background:rgba(255,255,255,.32);color:#fff;text-decoration:none; }
  .btn-hero-solid { background:#fff;color:var(--accent-dark,#0c326f);border:1.5px solid #fff; }
  .btn-hero-solid:hover { background:rgba(255,255,255,.88); }
  /* Breadcrumb no hero */
  .hero-breadcrumb { display:flex;align-items:center;gap:6px;font-size:13px;color:rgba(255,255,255,.7);padding-bottom:6px; }
  .hero-breadcrumb a { color:rgba(255,255,255,.8);text-decoration:none; }
  .hero-breadcrumb a:hover { color:#fff; }
  /* Tabs no hero */
  .tabs-row { display:flex;gap:0;padding:0;margin-top:20px; }
  .tab-btn { padding:10px 20px;font-size:14px;font-weight:600;color:rgba(255,255,255,.7);background:transparent;border:none;border-bottom:3px solid transparent;cursor:pointer;transition:color .15s,border-color .15s;white-space:nowrap; }
  .tab-btn.active { color:#fff;border-bottom-color:#fff; }
  .tab-btn:hover:not(.active) { color:rgba(255,255,255,.9); }
  /* Panels */
  .tab-panel { display:none; }
  .tab-panel.active { display:block; }
  /* KPIs */
  .kpi-strip { display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin:24px 0; }
  .kpi-card { background:var(--surface);border:1px solid var(--border-soft);border-radius:var(--radius-md);padding:16px 18px;box-shadow:var(--shadow-sm); }
  .kpi-label { font-size:12px;color:var(--muted);font-weight:500;text-transform:uppercase;letter-spacing:.04em; }
  .kpi-value { font-family:var(--font-mono);font-size:26px;font-weight:700;color:var(--accent);margin:4px 0 2px; }
  .kpi-delta { font-size:12px; }
  .kpi-delta.up { color:var(--success,#168821); }
  .kpi-delta.neutral { color:var(--muted); }
  /* Detail grid */
  .detail-grid { display:grid;grid-template-columns:2fr 1fr;gap:18px;margin-bottom:20px; }
  /* Info card */
  .info-card { background:var(--surface);border:1px solid var(--border-soft);border-radius:var(--radius-md);padding:20px 22px;box-shadow:var(--shadow-sm); }
  .info-card h3 { font-size:15px;font-weight:700;margin-bottom:14px;color:var(--fg); }
  .info-row { display:flex;gap:10px;align-items:flex-start;padding:9px 0;border-bottom:1px solid var(--border-soft);font-size:13px; }
  .info-row:last-child { border:0; }
  .info-row .lbl { color:var(--muted);min-width:110px;flex-shrink:0;font-weight:500; }
  .info-row .val { color:var(--fg);font-weight:500; }
  /* Bar chart */
  .bar-chart { display:flex;flex-direction:column;gap:10px; }
  .bar-row { display:flex;align-items:center;gap:10px;font-size:13px; }
  .bar-label { min-width:90px;color:var(--muted);font-size:12px;text-align:right; }
  .bar-track { flex:1;height:10px;background:var(--border-soft);border-radius:99px;overflow:hidden; }
  .bar-fill { height:100%;border-radius:99px;background:var(--accent);transition:width .6s ease; }
  .bar-val { min-width:32px;font-family:var(--font-mono);font-size:12px;color:var(--fg-2);font-weight:600; }
  /* Sparkline */
  .spark-labels { display:flex;justify-content:space-between;font-size:11px;color:var(--muted);margin-top:6px; }
  /* Table */
  .data-table { width:100%;border-collapse:collapse;font-size:13px; }
  .data-table th { text-align:left;padding:10px 12px;background:var(--surface-2,#f4f6f9);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);border-bottom:1px solid var(--border); }
  .data-table td { padding:11px 12px;border-bottom:1px solid var(--border-soft);color:var(--fg-2);vertical-align:middle; }
  .data-table tr:last-child td { border:0; }
  .data-table tr:hover td { background:rgba(0,0,0,.02); }
  /* Prova row */
  .prova-row { display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border-soft); }
  .prova-row:last-child { border:0; }
  .prova-ico { width:36px;height:36px;border-radius:8px;background:var(--accent-light);color:var(--accent-dark,#0c326f);display:grid;place-items:center;flex-shrink:0;font-size:18px; }
  .prova-info { flex:1;min-width:0; }
  .prova-name { font-size:14px;font-weight:600; }
  .prova-meta { font-size:12px;color:var(--muted);margin-top:2px; }
  .prova-score { font-family:var(--font-mono);font-size:16px;font-weight:700;color:var(--accent); }
  /* Team grid */
  .team-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px; }
  .team-card { background:var(--surface);border:1px solid var(--border-soft);border-radius:var(--radius-md);padding:16px;display:flex;gap:12px;align-items:center;justify-content:space-between; }
  .team-avatar { width:42px;height:42px;border-radius:50%;background:var(--accent-light);color:var(--accent-dark,#0c326f);display:grid;place-items:center;font-weight:700;font-size:15px;flex-shrink:0; }
  .team-name { font-size:14px;font-weight:600; }
  .team-role { font-size:12px;color:var(--muted);margin-top:2px; }
  .team-section-label { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:12px; }
  /* Donut */
  .donut-wrap { display:flex;align-items:center;gap:18px; }
  .donut-legend { display:flex;flex-direction:column;gap:8px;font-size:13px; }
  .donut-item { display:flex;align-items:center;gap:8px; }
  .donut-dot { width:10px;height:10px;border-radius:50%;flex-shrink:0; }
  @media(max-width:900px){ .kpi-strip{grid-template-columns:repeat(3,1fr);} .detail-grid{grid-template-columns:1fr;} }
  @media(max-width:560px){ .kpi-strip{grid-template-columns:repeat(2,1fr);} .tabs-row{overflow-x:auto;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('escolas.index') }}" class="active">Escolas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('provas.index') }}">Provas</a>
@endsection

{{-- Hero (fora do layout padrão de breadcrumb/content) --}}
@section('breadcrumb')@endsection

@section('content')
{{-- Hero --}}
<div class="escola-hero" style="margin:-24px -var(--container-pad,0) 0;padding-left:var(--container-pad,24px);padding-right:var(--container-pad,24px);">
  <div style="max-width:var(--content-max,1200px);margin:0 auto;">
    <div class="hero-breadcrumb">
      <a href="{{ route('escolas.index') }}">Escolas</a>
      <span style="opacity:.5;">/</span>
      <span>{{ $escola['nome'] }}</span>
    </div>
    <div class="escola-hero-inner">
      <div class="escola-hero-icon">🏫</div>
      <div class="escola-hero-info">
        <h1>{{ $escola['nome'] }}</h1>
        <div class="escola-hero-meta">
          <span>
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            INEP: <strong>{{ $escola['inep'] }}</strong>
          </span>
          @if($escola['logradouro'] ?? null)
          <span>
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            {{ $escola['logradouro'] }}@if($escola['cidade'] ?? null), {{ $escola['cidade'] }} / {{ $escola['uf'] ?? '' }}@endif
          </span>
          @endif
          <span>
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
            <strong>{{ $escola['ativo'] ? 'Ativa' : 'Inativa' }}</strong>
          </span>
          <span>
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            {{ ['estadual'=>'Estadual','municipal'=>'Municipal','federal'=>'Federal','privada'=>'Privada'][$escola['tipo_rede']] ?? $escola['tipo_rede'] }}
            @if($escola['nucleo_nome'] ?? null) · {{ $escola['nucleo_nome'] }}@endif
          </span>
        </div>
        <div class="hero-actions">
          <a href="{{ route('escolas.index') }}" class="btn-hero btn-hero-solid">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Voltar
          </a>
          <button class="btn-hero" onclick="document.getElementById('tab-btn-turmas').click()">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
            Ver turmas
          </button>
          <button class="btn-hero" onclick="document.getElementById('tab-btn-equipe').click()">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Equipe
          </button>
        </div>
      </div>
    </div>

    <div class="tabs-row" role="tablist" style="margin-top:20px;">
      <button class="tab-btn active" role="tab" id="tab-btn-visao" data-tab="visao-geral">Visão Geral</button>
      <button class="tab-btn" role="tab" id="tab-btn-turmas" data-tab="turmas">Turmas</button>
      <button class="tab-btn" role="tab" id="tab-btn-equipe" data-tab="equipe">Equipe</button>
    </div>
  </div>
</div>

@if(session('sucesso'))
  <div style="background:var(--success-light,#e3f5e1);color:var(--success,#168821);border:1px solid var(--success,#168821);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:20px;">
    {{ session('sucesso') }}
  </div>
@endif
@if(session('erro'))
  <div style="background:var(--danger-light,#fde8e4);color:var(--danger,#e52207);border:1px solid var(--danger,#e52207);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:20px;">
    {{ session('erro') }}
  </div>
@endif

{{-- ═══ TAB: Visão Geral ═══════════════════════════════════════ --}}
<div class="tab-panel active" id="tab-visao-geral">
  <div class="kpi-strip">
    <div class="kpi-card">
      <div class="kpi-label">Alunos ativos</div>
      <div class="kpi-value">{{ $escola['total_alunos'] ?? 0 }}</div>
      <div class="kpi-delta neutral">matrículas</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Turmas</div>
      <div class="kpi-value">{{ $escola['total_turmas'] ?? 0 }}</div>
      <div class="kpi-delta neutral">cadastradas</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Provas aplicadas</div>
      <div class="kpi-value">{{ $escola['total_provas'] ?? 0 }}</div>
      <div class="kpi-delta neutral">no sistema</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Tipo de rede</div>
      <div class="kpi-value" style="font-size:16px;margin-top:8px;">
        {{ ['estadual'=>'Estadual','municipal'=>'Municipal','federal'=>'Federal','privada'=>'Privada'][$escola['tipo_rede']] ?? $escola['tipo_rede'] }}
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Status</div>
      <div style="margin-top:10px;">
        @if($escola['ativo'])
          <span class="badge badge-success" style="font-size:14px;padding:6px 14px;">Ativa</span>
        @else
          <span class="badge badge-danger" style="font-size:14px;padding:6px 14px;">Inativa</span>
        @endif
      </div>
    </div>
  </div>

  <div class="detail-grid">
    {{-- Coluna principal: dados da escola --}}
    <div style="display:flex;flex-direction:column;gap:18px;">
      <div class="info-card">
        <h3>Dados da escola</h3>
        <div class="info-row">
          <span class="lbl">Nome</span>
          <span class="val">{{ $escola['nome'] }}</span>
        </div>
        <div class="info-row">
          <span class="lbl">INEP</span>
          <span class="val" style="font-family:var(--font-mono);">{{ $escola['inep'] }}</span>
        </div>
        @if($escola['logradouro'] ?? null)
        <div class="info-row">
          <span class="lbl">Endereço</span>
          <span class="val">{{ $escola['logradouro'] }}@if($escola['cidade'] ?? null), {{ $escola['cidade'] }} / {{ $escola['uf'] ?? '' }}@endif</span>
        </div>
        @endif
        @if($escola['telefone'] ?? null)
        <div class="info-row">
          <span class="lbl">Telefone</span>
          <span class="val">{{ $escola['telefone'] }}</span>
        </div>
        @endif
        @if($escola['email'] ?? null)
        <div class="info-row">
          <span class="lbl">E-mail</span>
          <span class="val">{{ $escola['email'] }}</span>
        </div>
        @endif
        <div class="info-row">
          <span class="lbl">Rede</span>
          <span class="val">{{ ['estadual'=>'Estadual','municipal'=>'Municipal','federal'=>'Federal','privada'=>'Privada'][$escola['tipo_rede']] ?? $escola['tipo_rede'] }}</span>
        </div>
        @if($escola['nucleo_nome'] ?? null)
        <div class="info-row">
          <span class="lbl">Núcleo</span>
          <span class="val">{{ $escola['nucleo_nome'] }}</span>
        </div>
        @endif
        <div class="info-row">
          <span class="lbl">Status</span>
          <span class="val">
            @if($escola['ativo'])
              <span class="badge badge-success">Ativa</span>
            @else
              <span class="badge badge-danger">Inativa</span>
            @endif
          </span>
        </div>
      </div>

      {{-- Turmas resumo --}}
      @if(count($escola['turmas'] ?? []) > 0)
      <div class="info-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
          <h3 style="margin:0;">Turmas cadastradas</h3>
          <button class="btn btn-sm btn-secondary" onclick="document.getElementById('tab-btn-turmas').click()">Ver todas</button>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Turma</th>
              <th>Turno</th>
              <th>Alunos</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach(array_slice($escola['turmas'], 0, 5) as $t)
            <tr>
              <td><strong>{{ $t['nome'] }}</strong> <span style="color:var(--muted);font-size:12px;">{{ $t['ano'] ?? '' }}</span></td>
              <td>{{ ucfirst($t['turno'] ?? '—') }}</td>
              <td style="font-family:var(--font-mono);">{{ $t['total_alunos'] ?? 0 }}</td>
              <td>
                @if($t['ativo'] ?? true)
                  <span class="badge badge-success">Ativa</span>
                @else
                  <span class="badge badge-danger">Inativa</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
        @if(count($escola['turmas']) > 5)
          <div style="padding:10px 0 0;font-size:13px;color:var(--muted);">
            Exibindo 5 de {{ count($escola['turmas']) }} turmas.
            <button class="btn btn-sm btn-ghost" style="padding:2px 10px;" onclick="document.getElementById('tab-btn-turmas').click()">Ver todas</button>
          </div>
        @endif
      </div>
      @endif
    </div>

    {{-- Coluna lateral: equipe resumo --}}
    <div style="display:flex;flex-direction:column;gap:18px;">
      @if(count($escola['equipe'] ?? []) > 0)
      <div class="info-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
          <h3 style="margin:0;">Equipe</h3>
          <button class="btn btn-sm btn-secondary" onclick="document.getElementById('tab-btn-equipe').click()">Ver todos</button>
        </div>
        @foreach(array_slice($escola['equipe'], 0, 4) as $m)
          @php
            $ini = collect(explode(' ', $m['nome']))->filter()->map(fn($p) => mb_strtoupper(mb_substr($p,0,1)))->take(2)->implode('');
            $labels = ['admin_rede'=>'Admin Rede','dir_nucleo'=>'Dir. Núcleo','dir_escolar'=>'Dir. Escolar','coordenador'=>'Coordenador','professor'=>'Professor','aluno'=>'Aluno'];
          @endphp
          <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-soft);">
            <div class="team-avatar" style="width:36px;height:36px;font-size:13px;">{{ $ini }}</div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:14px;font-weight:600;line-height:1.2;">{{ $m['nome'] }}</div>
              <div style="font-size:12px;color:var(--muted);">{{ $labels[$m['perfil']] ?? $m['perfil'] }}</div>
            </div>
            @if(!($m['ativo'] ?? true))
              <span class="badge badge-danger" style="font-size:10px;">Inativo</span>
            @endif
          </div>
        @endforeach
        @if(count($escola['equipe']) > 4)
          <div style="padding-top:10px;font-size:13px;color:var(--muted);">
            +{{ count($escola['equipe']) - 4 }} membro(s).
            <button class="btn btn-sm btn-ghost" style="padding:2px 10px;" onclick="document.getElementById('tab-btn-equipe').click()">Ver todos</button>
          </div>
        @endif
      </div>
      @else
      <div class="info-card" style="text-align:center;padding:32px 20px;">
        <div style="font-size:36px;margin-bottom:12px;">👥</div>
        <div style="font-size:14px;color:var(--muted);">Nenhum membro vinculado a esta escola.</div>
        <a href="{{ route('membros.create') }}" class="btn btn-sm btn-primary" style="margin-top:14px;">Adicionar membro</a>
      </div>
      @endif

      <div class="info-card" style="text-align:center;padding:24px;">
        <div style="font-size:28px;margin-bottom:10px;">⚙️</div>
        <div style="font-size:13px;font-weight:600;margin-bottom:6px;">Ações rápidas</div>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px;">
          <a href="{{ route('escolas.index') }}" class="btn btn-secondary btn-sm">← Voltar para escolas</a>
          <button onclick="document.getElementById('modal-editar').style.display='flex';document.body.style.overflow='hidden';"
                  class="btn btn-ghost btn-sm">Editar dados da escola</button>
          <form method="POST" action="{{ route('escolas.toggle', $escola['id']) }}"
                onsubmit="return confirm('{{ $escola['ativo'] ? 'Desativar' : 'Ativar' }} esta escola?')">
            @csrf
            <button type="submit" class="btn btn-sm"
                    style="width:100%;color:{{ $escola['ativo'] ? 'var(--danger)' : 'var(--success)' }};border-color:{{ $escola['ativo'] ? 'var(--danger)' : 'var(--success)' }};background:transparent;">
              {{ $escola['ativo'] ? 'Desativar escola' : 'Ativar escola' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ═══ TAB: Turmas ════════════════════════════════════════════ --}}
<div class="tab-panel" id="tab-turmas">
  <div class="info-card" style="margin-top:4px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <h3 style="margin:0;">Turmas cadastradas</h3>
      <a href="{{ route('turmas.index') }}" class="btn btn-sm btn-primary">+ Nova turma</a>
    </div>
    @if(count($escola['turmas'] ?? []) > 0)
    <table class="data-table">
      <thead>
        <tr>
          <th>Turma</th>
          <th>Ano</th>
          <th>Turno</th>
          <th>Alunos</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($escola['turmas'] as $t)
        <tr>
          <td><strong>{{ $t['nome'] }}</strong></td>
          <td>{{ $t['ano'] ?? '—' }}</td>
          <td>{{ ucfirst($t['turno'] ?? '—') }}</td>
          <td style="font-family:var(--font-mono);">{{ $t['total_alunos'] ?? 0 }}</td>
          <td>
            @if($t['ativo'] ?? true)
              <span class="badge badge-success">Ativa</span>
            @else
              <span class="badge badge-danger">Inativa</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <div style="text-align:center;padding:48px 20px;color:var(--muted);">
      <div style="font-size:40px;margin-bottom:14px;">🏫</div>
      <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma turma cadastrada</div>
      <p>As turmas desta escola aparecerão aqui após o cadastro no módulo de Turmas.</p>
    </div>
    @endif
  </div>
</div>

{{-- ═══ TAB: Equipe ════════════════════════════════════════════ --}}
<div class="tab-panel" id="tab-equipe">
  <div style="display:flex;align-items:center;justify-content:space-between;margin:4px 0 18px;">
    <h3 style="font-size:16px;font-weight:700;">Equipe pedagógica</h3>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('membros.index') }}" class="btn btn-sm btn-secondary">Gerenciar membros</a>
      <a href="{{ route('membros.create') }}" class="btn btn-sm btn-primary">+ Adicionar membro</a>
    </div>
  </div>

  @php
    $perfisOrdem = ['admin_rede','dir_escolar','coordenador','professor','aluno'];
    $labels = ['admin_rede'=>'Admin Rede','dir_nucleo'=>'Dir. Núcleo','dir_escolar'=>'Direção Escolar','coordenador'=>'Coordenação','professor'=>'Professores','aluno'=>'Alunos'];
    $equipeAgrupada = collect($escola['equipe'] ?? [])->groupBy('perfil');
  @endphp

  @if($equipeAgrupada->isEmpty())
    <div class="info-card" style="text-align:center;padding:60px 20px;color:var(--muted);">
      <div style="font-size:48px;margin-bottom:16px;">👥</div>
      <h3 style="font-size:18px;font-weight:700;color:var(--fg-2);margin-bottom:8px;">Equipe vazia</h3>
      <p>Nenhum membro vinculado a esta escola ainda.</p>
      <a href="{{ route('membros.create') }}" class="btn btn-primary" style="margin-top:16px;">Adicionar primeiro membro</a>
    </div>
  @else
    @foreach($perfisOrdem as $perfil)
      @if($equipeAgrupada->has($perfil))
        <div style="margin-bottom:24px;">
          <div class="team-section-label">{{ $labels[$perfil] ?? $perfil }}</div>
          <div class="team-grid">
            @foreach($equipeAgrupada[$perfil] as $m)
              @php
                $ini = collect(explode(' ', $m['nome']))->filter()->map(fn($p) => mb_strtoupper(mb_substr($p,0,1)))->take(2)->implode('');
              @endphp
              <div class="team-card">
                <div style="display:flex;align-items:center;gap:12px;">
                  <div class="team-avatar">{{ $ini }}</div>
                  <div>
                    <div class="team-name">{{ $m['nome'] }}</div>
                    <div class="team-role">{{ $m['email'] }}</div>
                    @if(!($m['ativo'] ?? true))
                      <span class="badge badge-danger" style="font-size:10px;margin-top:4px;">Inativo</span>
                    @endif
                  </div>
                </div>
                <a href="{{ route('membros.edit', $m['id']) }}" class="btn btn-sm btn-secondary">Editar</a>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    @endforeach
  @endif
</div>

{{-- Modal edição inline --}}
<div class="modal-backdrop" id="modal-editar" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:none;align-items:center;justify-content:center;padding:24px;">
  <div style="background:var(--surface);border-radius:var(--radius-lg);box-shadow:0 24px 64px rgba(0,0,0,.22);width:100%;max-width:520px;max-height:90vh;overflow-y:auto;">
    <div style="padding:22px 24px 18px;border-bottom:1px solid var(--border-soft);display:flex;align-items:center;justify-content:space-between;">
      <h2 style="font-size:18px;font-weight:700;">Editar escola</h2>
      <button type="button" onclick="document.getElementById('modal-editar').style.display='none';document.body.style.overflow='';"
              style="width:34px;height:34px;border-radius:50%;background:none;border:0;cursor:pointer;font-size:20px;color:var(--muted);display:grid;place-items:center;">&times;</button>
    </div>
    <form method="POST" action="{{ route('escolas.update', $escola['id']) }}">
      @csrf
      @method('PUT')
      <div style="padding:24px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div class="field" style="grid-column:1/-1">
            <label for="ed-nome">Nome *</label>
            <input class="input" type="text" id="ed-nome" name="nome" value="{{ $escola['nome'] }}" required />
          </div>
          <div class="field">
            <label for="ed-inep">INEP</label>
            <input class="input num" type="text" id="ed-inep" name="inep" value="{{ $escola['inep'] }}" maxlength="8" />
          </div>
          <div class="field">
            <label for="ed-tipo">Tipo de rede *</label>
            <select class="select" id="ed-tipo" name="tipo_rede" required>
              @foreach(['estadual'=>'Estadual','municipal'=>'Municipal','federal'=>'Federal','privada'=>'Privada'] as $v => $l)
                <option value="{{ $v }}" {{ ($escola['tipo_rede'] ?? '') === $v ? 'selected' : '' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="ed-nucleo">Núcleo</label>
            <select class="select" id="ed-nucleo" name="nucleo_id">
              <option value="">— Selecione —</option>
              @foreach($nucleos as $n)
                <option value="{{ $n['id'] }}" {{ ($escola['nucleo_id'] ?? '') == $n['id'] ? 'selected' : '' }}>{{ $n['nome'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="field" style="grid-column:1/-1">
            <label for="ed-logradouro">Endereço</label>
            <input class="input" type="text" id="ed-logradouro" name="logradouro" value="{{ $escola['logradouro'] ?? '' }}" />
          </div>
          <div class="field">
            <label for="ed-cidade">Cidade</label>
            <input class="input" type="text" id="ed-cidade" name="cidade" value="{{ $escola['cidade'] ?? '' }}" />
          </div>
          <div class="field">
            <label for="ed-uf">UF</label>
            <input class="input" type="text" id="ed-uf" name="uf" value="{{ $escola['uf'] ?? '' }}" maxlength="2" />
          </div>
          <div class="field">
            <label for="ed-tel">Telefone</label>
            <input class="input" type="text" id="ed-tel" name="telefone" value="{{ $escola['telefone'] ?? '' }}" />
          </div>
          <div class="field">
            <label for="ed-email">E-mail</label>
            <input class="input" type="email" id="ed-email" name="email" value="{{ $escola['email'] ?? '' }}" />
          </div>
        </div>
      </div>
      <div style="padding:16px 24px;border-top:1px solid var(--border-soft);display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn-ghost"
                onclick="document.getElementById('modal-editar').style.display='none';document.body.style.overflow='';">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar alterações</button>
      </div>
    </form>
  </div>
</div>

<div style="height:48px;"></div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.tab-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
  });
});

var modal = document.getElementById('modal-editar');
modal.addEventListener('click', function(e) {
  if (e.target === modal) { modal.style.display='none'; document.body.style.overflow=''; }
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && modal.style.display !== 'none') { modal.style.display='none'; document.body.style.overflow=''; }
});
</script>
@endpush
