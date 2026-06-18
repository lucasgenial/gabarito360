@extends('layouts.app')

@section('title', 'Turmas')

@push('styles')
<style>
  .turma-av { width:36px;height:36px;border-radius:8px;background:var(--accent-light);color:var(--accent-dark,#0c326f);display:grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0; }
  .turma-name { display:flex;align-items:center;gap:12px; }
  .mini-bar { width:80px;height:7px;background:var(--border-soft);border-radius:99px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px; }
  .mini-bar > div { height:100%; }
  .kpi-strip { display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:20px; }
  .kpi-strip .kv { font-family:var(--font-mono);font-size:28px;font-weight:700; }
  .kpi-strip .kl { font-size:12px;color:var(--muted);margin-top:2px; }
  /* Modal */
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
  @media(max-width:600px){ .kpi-strip{grid-template-columns:1fr;} .form-row-2{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('provas.index') }}">Provas</a>
<a href="{{ route('turmas.index') }}" class="active">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Turmas</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">Turmas</h1>
    <p class="page-sub">
      <b>{{ count($turmas) }}</b> turma(s) ·
      <b>{{ $kpis['total_alunos'] ?? 0 }}</b> alunos no total
    </p>
  </div>
  <div style="display:flex;gap:10px;">
    <button class="btn btn-secondary" onclick="abrirModal('modal-importar')">Importar planilha</button>
    <button class="btn btn-primary" onclick="abrirModal('modal-criar')">+ Nova turma</button>
  </div>
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
  <div class="card card-pad" style="padding:16px 20px;">
    <div class="kv" style="color:var(--accent);">{{ $kpis['total_turmas'] ?? 0 }}</div>
    <div class="kl">Turmas cadastradas</div>
  </div>
  <div class="card card-pad" style="padding:16px 20px;">
    <div class="kv" style="color:var(--success,#168821);">{{ $kpis['total_ativas'] ?? 0 }}</div>
    <div class="kl">Turmas ativas</div>
  </div>
  <div class="card card-pad" style="padding:16px 20px;">
    <div class="kv">{{ $kpis['total_alunos'] ?? 0 }}</div>
    <div class="kl">Alunos matriculados</div>
  </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('turmas.index') }}" style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;align-items:flex-end;">
  <div style="position:relative;flex:1;min-width:200px;">
    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input class="input" type="search" name="busca" placeholder="Buscar turma por nome…"
           value="{{ $busca }}" style="padding-left:36px;" />
  </div>
  <select class="select" name="escola_id" style="min-width:180px;">
    <option value="">Todas as escolas</option>
    @foreach($escolas as $e)
      <option value="{{ $e['id'] }}" {{ $escola_id == $e['id'] ? 'selected' : '' }}>{{ $e['nome'] }}</option>
    @endforeach
  </select>
  <input class="input" type="number" name="ano_letivo" placeholder="Ano letivo"
         value="{{ $ano_letivo }}" style="width:130px;" min="2020" max="2099" />
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  @if($busca || $escola_id || $ano_letivo)
    <a href="{{ route('turmas.index') }}" class="btn btn-ghost">Limpar</a>
  @endif
</form>

{{-- Tabela --}}
<div class="card" style="margin-top:18px;overflow:hidden;">
  <table class="table" id="turmas-table">
    <thead>
      <tr>
        <th>Turma</th>
        <th>Série</th>
        <th>Turno</th>
        <th>Escola</th>
        <th>Alunos</th>
        <th>Desempenho médio</th>
        <th>Ano letivo</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse($turmas as $t)
        <tr data-nome="{{ strtolower($t['nome']) }}">
          <td>
            <div class="turma-name">
              <div class="turma-av">{{ strtoupper(str_replace(['º ','ª '], '', $t['nome'])) }}</div>
              <strong>{{ $t['nome'] }}</strong>
            </div>
          </td>
          <td><span class="badge badge-info">{{ $t['serie'] }}</span></td>
          <td>{{ ['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$t['turno']] ?? $t['turno'] }}</td>
          <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $t['escola_nome'] ?? '' }}">
            {{ $t['escola_nome'] ?? '—' }}
          </td>
          <td style="font-family:var(--font-mono);">{{ $t['total_alunos'] ?? 0 }}</td>
          <td>
            @if(($t['media_turma'] ?? null) !== null)
              @php
                $pct = min(100, max(0, ($t['media_turma'] / 10) * 100));
                $cor = $t['media_turma'] >= 7 ? 'var(--success,#168821)' : ($t['media_turma'] >= 5 ? '#ffcd07' : 'var(--danger,#e52207)');
              @endphp
              <span class="mini-bar"><div style="width:{{ $pct }}%;background:{{ $cor }};"></div></span>
              <span style="font-family:var(--font-mono);font-weight:600;">{{ number_format($t['media_turma'], 1, ',', '') }}</span>
            @else
              <span style="color:var(--muted);">—</span>
            @endif
          </td>
          <td style="font-family:var(--font-mono);">{{ $t['ano_letivo'] }}</td>
          <td>
            @if($t['ativo'])
              <span class="badge badge-success badge-dot">Ativa</span>
            @else
              <span class="badge badge-danger badge-dot">Inativa</span>
            @endif
          </td>
          <td style="text-align:right;">
            <a href="{{ route('turmas.show', $t['id']) }}" class="btn btn-sm btn-secondary">Ver turma</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="9" style="text-align:center;padding:48px 20px;color:var(--muted);">
            <div style="font-size:36px;margin-bottom:12px;">🏫</div>
            <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma turma encontrada</div>
            <p>Crie a primeira turma usando o botão "+ Nova turma".</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

{{-- Modal: criar turma --}}
<div class="modal-backdrop" id="modal-criar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h2>Nova Turma</h2>
      <button type="button" class="modal-close" onclick="fecharModal('modal-criar')">&times;</button>
    </div>
    <form method="POST" action="{{ route('turmas.store') }}">
      @csrf
      <div class="modal-body">
        <div class="form-row-2">
          <div class="field" style="grid-column:1/-1">
            <label for="cr-nome">Nome da turma *</label>
            <input class="input" type="text" id="cr-nome" name="nome" required placeholder="Ex.: 9º A" />
          </div>
          <div class="field">
            <label for="cr-serie">Série *</label>
            <input class="input" type="text" id="cr-serie" name="serie" required placeholder="Ex.: 9º ano" />
          </div>
          <div class="field">
            <label for="cr-turno">Turno *</label>
            <select class="select" id="cr-turno" name="turno" required>
              <option value="">—</option>
              <option value="manha">Manhã</option>
              <option value="tarde">Tarde</option>
              <option value="noite">Noite</option>
              <option value="integral">Integral</option>
            </select>
          </div>
          <div class="field">
            <label for="cr-escola">Escola *</label>
            <select class="select" id="cr-escola" name="escola_id" required>
              <option value="">— Selecione —</option>
              @foreach($escolas as $e)
                <option value="{{ $e['id'] }}">{{ $e['nome'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="cr-ano">Ano letivo *</label>
            <input class="input num" type="number" id="cr-ano" name="ano_letivo"
                   required min="2020" max="2099" value="{{ date('Y') }}" />
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="fecharModal('modal-criar')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Criar turma</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal: importar planilha (placeholder) --}}
<div class="modal-backdrop" id="modal-importar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h2>Importar Planilha</h2>
      <button type="button" class="modal-close" onclick="fecharModal('modal-importar')">&times;</button>
    </div>
    <div class="modal-body" style="text-align:center;padding:40px 24px;">
      <div style="font-size:48px;margin-bottom:16px;">📊</div>
      <h3 style="font-size:16px;font-weight:700;margin-bottom:8px;">Importação em breve</h3>
      <p style="color:var(--muted);font-size:14px;">A importação de alunos via planilha XLSX estará disponível no MP-017.</p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn btn-ghost" onclick="fecharModal('modal-importar')">Fechar</button>
    </div>
  </div>
</div>

<div style="height:48px;"></div>
@endsection

@push('scripts')
<script>
function abrirModal(id) {
  var m = document.getElementById(id);
  m.classList.add('open');
  m.setAttribute('aria-hidden','false');
  document.body.style.overflow = 'hidden';
}
function fecharModal(id) {
  var m = document.getElementById(id);
  m.classList.remove('open');
  m.setAttribute('aria-hidden','true');
  document.body.style.overflow = '';
}
['modal-criar','modal-importar'].forEach(function(id){
  var m = document.getElementById(id);
  m.addEventListener('click', function(e){ if(e.target===m) fecharModal(id); });
});
document.addEventListener('keydown', function(e){
  if(e.key==='Escape'){ fecharModal('modal-criar'); fecharModal('modal-importar'); }
});
</script>
@endpush
