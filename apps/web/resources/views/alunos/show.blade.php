@extends('layouts.app')

@section('title', $aluno['nome'])

@push('styles')
<style>
  .profile-header { display:flex;align-items:flex-start;gap:24px;margin-top:24px;flex-wrap:wrap; }
  .profile-avatar { width:100px;height:100px;border-radius:50%;background:var(--accent-light);color:var(--accent-dark,#0c326f);display:grid;place-items:center;font-size:36px;font-weight:800;flex-shrink:0;border:4px solid var(--surface);box-shadow:var(--shadow-sm); }
  .kpi-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:22px; }
  .section-title { font-size:18px;font-weight:700;margin:32px 0 16px;display:flex;align-items:center;gap:8px; }
  .info-list { list-style:none;margin-top:12px; }
  .info-list li { margin-bottom:8px;font-size:15px;color:var(--fg-2); }
  .info-list b { color:var(--fg);min-width:120px;display:inline-block; }
  .mini-bar { width:100px;height:8px;background:var(--border-soft);border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px; }
  .mini-bar > div { height:100%; }
  /* Modal editar */
  .modal-backdrop { position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:none;align-items:center;justify-content:center;padding:24px; }
  .modal-backdrop.open { display:flex; }
  .modal { background:var(--surface);border-radius:var(--radius-lg);box-shadow:0 24px 64px rgba(0,0,0,.22);width:100%;max-width:520px;max-height:90vh;overflow-y:auto; }
  .modal-head { padding:22px 24px 18px;border-bottom:1px solid var(--border-soft);display:flex;align-items:center;justify-content:space-between; }
  .modal-head h2 { font-size:18px;font-weight:700; }
  .modal-close { width:34px;height:34px;border-radius:50%;background:none;border:0;cursor:pointer;font-size:20px;color:var(--muted);display:grid;place-items:center; }
  .modal-close:hover { background:var(--surface-2); }
  .modal-body { padding:24px; }
  .modal-foot { padding:16px 24px;border-top:1px solid var(--border-soft);display:flex;justify-content:flex-end;gap:10px; }
  .form-row-2 { display:grid;grid-template-columns:1fr 1fr;gap:14px; }
  @media(max-width:768px){ .kpi-grid{grid-template-columns:1fr;} .profile-header{flex-direction:column;align-items:center;text-align:center;} .form-row-2{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('turmas.index') }}" class="active">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
<a href="{{ route('provas.index') }}">Provas</a>
@endsection

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span>
@if($aluno['turma_id'] ?? null)
  <a href="{{ route('turmas.show', $aluno['turma_id']) }}">{{ $aluno['turma_nome'] ?? 'Turma' }}</a><span class="sep">/</span>
@endif
<span>{{ $aluno['nome'] }}</span>
@endsection

@section('content')

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

{{-- Header do perfil --}}
<div class="profile-header">
  @php
    $ini = collect(explode(' ', $aluno['nome']))->filter()->map(fn($p) => mb_strtoupper(mb_substr($p,0,1)))->take(2)->implode('');
  @endphp
  <div class="profile-avatar">{{ $ini }}</div>
  <div style="flex:1;">
    <h1 class="page-title" style="margin:0;">{{ $aluno['nome'] }}</h1>
    <p class="page-sub">
      {{ $aluno['turma_serie'] ?? '' }}
      @if($aluno['turma_nome'] ?? null) · Turma {{ $aluno['turma_nome'] }}@endif
    </p>
    <ul class="info-list">
      <li><b>Matrícula:</b> {{ $aluno['matricula'] }}</li>
      @if($aluno['data_nascimento'] ?? null)
        <li><b>Nascimento:</b> {{ \Carbon\Carbon::parse($aluno['data_nascimento'])->format('d/m/Y') }}</li>
      @endif
      @if($aluno['nome_responsavel'] ?? null)
        <li><b>Responsável:</b> {{ $aluno['nome_responsavel'] }}</li>
      @endif
    </ul>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <button class="btn btn-secondary" onclick="abrirModal()">Editar dados</button>
    <form method="POST" action="{{ route('alunos.toggle', $aluno['id']) }}"
          onsubmit="return confirm('{{ $aluno['ativo'] ? 'Desativar' : 'Ativar' }} este aluno?')">
      @csrf
      <button type="submit" class="btn btn-ghost"
              style="color:{{ $aluno['ativo'] ? 'var(--danger)' : 'var(--success)' }};{{ $aluno['ativo'] ? 'border-color:var(--danger);' : '' }}">
        {{ $aluno['ativo'] ? 'Desativar' : 'Ativar' }}
      </button>
    </form>
  </div>
</div>

{{-- KPIs básicos --}}
<div class="kpi-grid">
  <div class="card" style="text-align:center;padding:20px;">
    <div style="font-family:var(--font-mono);font-size:28px;font-weight:800;color:var(--accent);">—</div>
    <div style="font-size:13px;color:var(--muted);margin-top:4px;">Média Geral</div>
  </div>
  <div class="card" style="text-align:center;padding:20px;">
    <div style="font-family:var(--font-mono);font-size:28px;font-weight:800;color:var(--accent);">0</div>
    <div style="font-size:13px;color:var(--muted);margin-top:4px;">Provas Realizadas</div>
  </div>
  <div class="card" style="text-align:center;padding:20px;">
    <div style="font-family:var(--font-mono);font-size:28px;font-weight:800;color:var(--accent);">
      @if($aluno['ativo'])
        <span style="color:var(--success,#168821);">Ativo</span>
      @else
        <span style="color:var(--danger);">Inativo</span>
      @endif
    </div>
    <div style="font-size:13px;color:var(--muted);margin-top:4px;">Status</div>
  </div>
</div>

{{-- Histórico de avaliações (placeholder — MP-019) --}}
<h2 class="section-title">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
    <polyline points="7 10 12 15 17 10"/>
    <line x1="12" y1="15" x2="12" y2="3"/>
  </svg>
  Histórico de Avaliações
</h2>
<div class="card card-pad" style="text-align:center;padding:48px 20px;color:var(--muted);">
  <div style="font-size:40px;margin-bottom:16px;">📝</div>
  <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Sem avaliações registradas</div>
  <p>O histórico de avaliações do aluno estará disponível após a aplicação de provas (MP-019).</p>
</div>

{{-- Modal de edição --}}
<div class="modal-backdrop" id="modal-editar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h2>Editar Aluno</h2>
      <button type="button" class="modal-close" onclick="fecharModal()">&times;</button>
    </div>
    <form method="POST" action="{{ route('alunos.update', $aluno['id']) }}">
      @csrf
      @method('PUT')
      <div class="modal-body">
        <div class="form-row-2">
          <div class="field" style="grid-column:1/-1">
            <label for="ed-nome">Nome completo *</label>
            <input class="input" type="text" id="ed-nome" name="nome" required value="{{ $aluno['nome'] }}" />
          </div>
          <div class="field">
            <label for="ed-matricula">Matrícula *</label>
            <input class="input num" type="text" id="ed-matricula" name="matricula" required value="{{ $aluno['matricula'] }}" />
          </div>
          <div class="field">
            <label for="ed-nasc">Nascimento</label>
            <input class="input" type="date" id="ed-nasc" name="data_nascimento" value="{{ $aluno['data_nascimento'] ?? '' }}" />
          </div>
          <div class="field" style="grid-column:1/-1">
            <label for="ed-turma">Turma</label>
            <select class="select" id="ed-turma" name="turma_id">
              @foreach($turmas as $t)
                <option value="{{ $t['id'] }}" {{ ($aluno['turma_id'] ?? '') == $t['id'] ? 'selected' : '' }}>
                  {{ $t['nome'] }} — {{ $t['serie'] }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="field" style="grid-column:1/-1">
            <label for="ed-responsavel">Responsável</label>
            <input class="input" type="text" id="ed-responsavel" name="nome_responsavel" value="{{ $aluno['nome_responsavel'] ?? '' }}" />
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="fecharModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar alterações</button>
      </div>
    </form>
  </div>
</div>

<div style="height:48px;"></div>
@endsection

@push('scripts')
<script>
var modal = document.getElementById('modal-editar');
function abrirModal() { modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; }
function fecharModal() { modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }
modal.addEventListener('click', function(e){ if(e.target===modal) fecharModal(); });
document.addEventListener('keydown', function(e){ if(e.key==='Escape') fecharModal(); });
</script>
@endpush
