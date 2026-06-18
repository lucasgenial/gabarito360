@extends('layouts.app')

@section('title', 'Escolas')

@push('styles')
<style>
  .escola-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:18px; margin-top:24px; }
  .escola-card { background:var(--surface); border:1px solid var(--border-soft); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); overflow:hidden; display:flex; flex-direction:column; transition:box-shadow .15s,transform .15s; }
  .escola-card:hover { box-shadow:var(--shadow-md); transform:translateY(-1px); }
  .escola-card.inativa { opacity:.7; }
  .escola-card-head { padding:20px 22px 16px; border-bottom:1px solid var(--border-soft); display:flex; align-items:flex-start; gap:14px; }
  .escola-icon { width:44px; height:44px; border-radius:var(--radius-md); background:var(--accent-light); display:grid; place-items:center; flex-shrink:0; font-size:22px; }
  .escola-name { font-size:15px; font-weight:700; line-height:1.3; }
  .escola-code { font-family:var(--font-mono); font-size:12px; color:var(--muted); margin-top:3px; }
  .escola-card-body { padding:16px 22px; flex:1; }
  .escola-meta { display:flex; flex-direction:column; gap:8px; }
  .escola-meta-row { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--fg-2); }
  .escola-meta-row svg { flex-shrink:0; opacity:.5; }
  .escola-card-foot { padding:14px 22px; border-top:1px solid var(--border-soft); display:flex; align-items:center; justify-content:space-between; gap:12px; }
  .escola-stats { display:flex; gap:20px; }
  .escola-stat .val { font-family:var(--font-mono); font-size:18px; font-weight:700; color:var(--accent); }
  .escola-stat .lbl { font-size:11px; color:var(--muted); margin-top:2px; }
  .escola-actions { display:flex; gap:8px; }
  .kpi-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-top:20px; }
  .kpi-strip .kpi-v { font-family:var(--font-mono); font-size:28px; font-weight:700; }
  .kpi-strip .kpi-l { font-size:12px; color:var(--muted); margin-top:2px; }
  /* Modal */
  .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; display:none; align-items:center; justify-content:center; padding:24px; }
  .modal-backdrop.open { display:flex; }
  .modal { background:var(--surface); border-radius:var(--radius-lg); box-shadow:0 24px 64px rgba(0,0,0,.22); width:100%; max-width:560px; max-height:90vh; overflow-y:auto; }
  .modal-head { padding:22px 24px 18px; border-bottom:1px solid var(--border-soft); display:flex; align-items:center; justify-content:space-between; }
  .modal-head h2 { font-size:18px; font-weight:700; }
  .modal-close { width:34px; height:34px; border-radius:50%; background:none; border:0; cursor:pointer; font-size:20px; color:var(--muted); display:grid; place-items:center; }
  .modal-close:hover { background:var(--surface-2); }
  .modal-body { padding:24px; }
  .modal-foot { padding:16px 24px; border-top:1px solid var(--border-soft); display:flex; justify-content:flex-end; gap:10px; }
  .form-section { margin-bottom:20px; }
  .form-section-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); margin-bottom:12px; }
  .form-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  @media (max-width:700px){ .escola-grid{grid-template-columns:1fr;} .form-row-2{grid-template-columns:1fr;} .kpi-strip{grid-template-columns:repeat(2,1fr);} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('escolas.index') }}" class="active">Escolas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('provas.index') }}">Provas</a>
@endsection

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Escolas</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">Escolas</h1>
    <p class="page-sub">Gerencie todas as unidades escolares cadastradas no sistema</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;">
    <div style="position:relative;">
      <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input id="search-escolas" type="search" class="input" placeholder="Buscar escola…"
             style="padding-left:36px;width:240px;" value="{{ $busca }}"
             oninput="filtrarEscolas(this.value)" />
    </div>
    <button class="btn btn-primary" onclick="abrirModal()">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      Nova escola
    </button>
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
    <div class="kpi-v" style="color:var(--accent);">{{ $kpis['total_escolas'] ?? 0 }}</div>
    <div class="kpi-l">Escolas cadastradas</div>
  </div>
  <div class="card card-pad" style="padding:16px 20px;">
    <div class="kpi-v" style="color:var(--success,#168821);">{{ $kpis['total_ativas'] ?? 0 }}</div>
    <div class="kpi-l">Escolas ativas</div>
  </div>
  <div class="card card-pad" style="padding:16px 20px;">
    <div class="kpi-v">{{ $kpis['total_alunos'] ?? 0 }}</div>
    <div class="kpi-l">Alunos totais</div>
  </div>
  <div class="card card-pad" style="padding:16px 20px;">
    <div class="kpi-v">{{ $kpis['total_turmas'] ?? 0 }}</div>
    <div class="kpi-l">Turmas ativas</div>
  </div>
</div>

{{-- Grid de escolas --}}
<div class="escola-grid" id="escola-grid">
  @forelse($escolas as $e)
    <div class="escola-card {{ !$e['ativo'] ? 'inativa' : '' }}" data-nome="{{ strtolower($e['nome']) }}">
      <div class="escola-card-head">
        <div class="escola-icon">🏫</div>
        <div style="flex:1;min-width:0;">
          <div class="escola-name">{{ $e['nome'] }}</div>
          <div class="escola-code">INEP: {{ $e['inep'] }}</div>
        </div>
        <div style="margin-left:auto;flex-shrink:0;">
          @if($e['ativo'])
            <span class="badge badge-success">Ativa</span>
          @else
            <span class="badge badge-danger">Inativa</span>
          @endif
        </div>
      </div>
      <div class="escola-card-body">
        <div class="escola-meta">
          @if($e['logradouro'] ?? null)
            <div class="escola-meta-row">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              {{ $e['logradouro'] }}@if($e['cidade'] ?? null), {{ $e['cidade'] }} / {{ $e['uf'] ?? '' }}@endif
            </div>
          @endif
          @if($e['telefone'] ?? null)
            <div class="escola-meta-row">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.63 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6 6l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              {{ $e['telefone'] }}
            </div>
          @endif
          @if($e['email'] ?? null)
            <div class="escola-meta-row">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
              {{ $e['email'] }}
            </div>
          @endif
          <div class="escola-meta-row">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 9h6M9 13h6M9 17h4"/></svg>
            {{ ['estadual'=>'Estadual','municipal'=>'Municipal','federal'=>'Federal','privada'=>'Privada'][$e['tipo_rede']] ?? $e['tipo_rede'] }}
            @if($e['nucleo_nome'] ?? null) · {{ $e['nucleo_nome'] }}@endif
          </div>
        </div>
      </div>
      <div class="escola-card-foot">
        <div class="escola-stats">
          <div class="escola-stat"><div class="val">{{ $e['total_alunos'] ?? 0 }}</div><div class="lbl">Alunos</div></div>
          <div class="escola-stat"><div class="val">{{ $e['total_turmas'] ?? 0 }}</div><div class="lbl">Turmas</div></div>
          <div class="escola-stat"><div class="val">{{ $e['total_provas'] ?? 0 }}</div><div class="lbl">Provas</div></div>
        </div>
        <div class="escola-actions">
          <button class="btn btn-sm btn-secondary"
                  onclick='abrirModalEditar(@json($e))'>Editar</button>
          <a href="{{ route('escolas.show', $e['id']) }}" class="btn btn-sm btn-primary">Ver mais</a>
        </div>
      </div>
    </div>
  @empty
    <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--muted);">
      <div style="font-size:48px;margin-bottom:16px;">🏫</div>
      <h3 style="font-size:18px;font-weight:700;color:var(--fg-2);margin-bottom:8px;">Nenhuma escola encontrada</h3>
      <p>Cadastre a primeira escola usando o botão "Nova escola".</p>
    </div>
  @endforelse
</div>

{{-- Modal de criação --}}
<div class="modal-backdrop" id="modal-criar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-titulo-criar">
    <div class="modal-head">
      <h2 id="modal-titulo-criar">Nova Escola</h2>
      <button type="button" class="modal-close" onclick="fecharModal('modal-criar')">&times;</button>
    </div>
    <form method="POST" action="{{ route('escolas.store') }}">
      @csrf
      <div class="modal-body">
        <div class="form-section">
          <div class="form-section-label">Identificação</div>
          <div class="form-row-2">
            <div class="field full-modal">
              <label for="cr-nome">Nome da escola *</label>
              <input class="input" type="text" id="cr-nome" name="nome" required placeholder="Ex.: E.E. Prof. João Pessoa" />
            </div>
            <div class="field">
              <label for="cr-inep">Código INEP *</label>
              <input class="input num" type="text" id="cr-inep" name="inep" required placeholder="35000001" maxlength="8" />
            </div>
            <div class="field">
              <label for="cr-tipo">Tipo de rede *</label>
              <select class="select" id="cr-tipo" name="tipo_rede" required>
                <option value="">—</option>
                <option value="estadual">Estadual</option>
                <option value="municipal">Municipal</option>
                <option value="federal">Federal</option>
                <option value="privada">Privada</option>
              </select>
            </div>
            <div class="field">
              <label for="cr-nucleo">Núcleo</label>
              <select class="select" id="cr-nucleo" name="nucleo_id">
                <option value="">— Selecione —</option>
                @foreach($nucleos as $n)
                  <option value="{{ $n['id'] }}">{{ $n['nome'] }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="form-section">
          <div class="form-section-label">Contato e localização</div>
          <div class="form-row-2">
            <div class="field" style="grid-column:1/-1">
              <label for="cr-logradouro">Endereço</label>
              <input class="input" type="text" id="cr-logradouro" name="logradouro" placeholder="Rua, número, bairro" />
            </div>
            <div class="field">
              <label for="cr-cidade">Cidade</label>
              <input class="input" type="text" id="cr-cidade" name="cidade" />
            </div>
            <div class="field">
              <label for="cr-uf">UF</label>
              <input class="input" type="text" id="cr-uf" name="uf" maxlength="2" placeholder="SP" />
            </div>
            <div class="field">
              <label for="cr-telefone">Telefone</label>
              <input class="input" type="text" id="cr-telefone" name="telefone" placeholder="(00) 0000-0000" />
            </div>
            <div class="field">
              <label for="cr-email">E-mail</label>
              <input class="input" type="email" id="cr-email" name="email" placeholder="escola@edu.gov.br" />
            </div>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="fecharModal('modal-criar')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Criar escola</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal de edição --}}
<div class="modal-backdrop" id="modal-editar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-titulo-editar">
    <div class="modal-head">
      <h2 id="modal-titulo-editar">Editar Escola</h2>
      <button type="button" class="modal-close" onclick="fecharModal('modal-editar')">&times;</button>
    </div>
    <form method="POST" id="form-editar">
      @csrf
      @method('PUT')
      <div class="modal-body">
        <div class="form-section">
          <div class="form-section-label">Identificação</div>
          <div class="form-row-2">
            <div class="field" style="grid-column:1/-1">
              <label for="ed-nome">Nome da escola *</label>
              <input class="input" type="text" id="ed-nome" name="nome" required />
            </div>
            <div class="field">
              <label for="ed-inep">Código INEP</label>
              <input class="input num" type="text" id="ed-inep" name="inep" maxlength="8" />
            </div>
            <div class="field">
              <label for="ed-tipo">Tipo de rede *</label>
              <select class="select" id="ed-tipo" name="tipo_rede" required>
                <option value="estadual">Estadual</option>
                <option value="municipal">Municipal</option>
                <option value="federal">Federal</option>
                <option value="privada">Privada</option>
              </select>
            </div>
            <div class="field">
              <label for="ed-nucleo">Núcleo</label>
              <select class="select" id="ed-nucleo" name="nucleo_id">
                <option value="">— Selecione —</option>
                @foreach($nucleos as $n)
                  <option value="{{ $n['id'] }}">{{ $n['nome'] }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="form-section">
          <div class="form-section-label">Contato e localização</div>
          <div class="form-row-2">
            <div class="field" style="grid-column:1/-1">
              <label for="ed-logradouro">Endereço</label>
              <input class="input" type="text" id="ed-logradouro" name="logradouro" />
            </div>
            <div class="field">
              <label for="ed-cidade">Cidade</label>
              <input class="input" type="text" id="ed-cidade" name="cidade" />
            </div>
            <div class="field">
              <label for="ed-uf">UF</label>
              <input class="input" type="text" id="ed-uf" name="uf" maxlength="2" />
            </div>
            <div class="field">
              <label for="ed-telefone">Telefone</label>
              <input class="input" type="text" id="ed-telefone" name="telefone" />
            </div>
            <div class="field">
              <label for="ed-email">E-mail</label>
              <input class="input" type="email" id="ed-email" name="email" />
            </div>
          </div>
        </div>
        <div class="form-section">
          <div class="form-section-label">Zona de risco</div>
          <form method="POST" id="form-toggle-inline" style="margin-top:8px;">
            @csrf
          </form>
          <button type="submit" form="form-toggle-inline" class="btn btn-ghost btn-sm" id="btn-toggle"
                  style="color:var(--danger);border-color:var(--danger);">
            Desativar escola
          </button>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="fecharModal('modal-editar')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar alterações</button>
      </div>
    </form>
  </div>
</div>

<div style="height:48px"></div>
@endsection

@push('scripts')
<script>
function abrirModal() {
  var m = document.getElementById('modal-criar');
  m.classList.add('open');
  m.setAttribute('aria-hidden','false');
  document.body.style.overflow = 'hidden';
}

function abrirModalEditar(escola) {
  var m = document.getElementById('modal-editar');
  var f = document.getElementById('form-editar');
  var base = '/escolas/' + escola.id;

  f.action = base;
  document.getElementById('ed-nome').value      = escola.nome || '';
  document.getElementById('ed-inep').value      = escola.inep || '';
  document.getElementById('ed-tipo').value      = escola.tipo_rede || '';
  document.getElementById('ed-nucleo').value    = escola.nucleo_id || '';
  document.getElementById('ed-logradouro').value= escola.logradouro || '';
  document.getElementById('ed-cidade').value    = escola.cidade || '';
  document.getElementById('ed-uf').value        = escola.uf || '';
  document.getElementById('ed-telefone').value  = escola.telefone || '';
  document.getElementById('ed-email').value     = escola.email || '';

  var toggleForm = document.getElementById('form-toggle-inline');
  toggleForm.action = base + '/toggle';
  var btnToggle = document.getElementById('btn-toggle');
  btnToggle.textContent = escola.ativo ? 'Desativar escola' : 'Ativar escola';

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

function filtrarEscolas(busca) {
  var termo = busca.toLowerCase().trim();
  var cards = document.querySelectorAll('#escola-grid .escola-card');
  cards.forEach(function(card) {
    card.style.display = (!termo || card.dataset.nome.includes(termo)) ? '' : 'none';
  });
}

// Fecha modal ao clicar no backdrop
['modal-criar','modal-editar'].forEach(function(id) {
  var m = document.getElementById(id);
  m.addEventListener('click', function(e) {
    if (e.target === m) fecharModal(id);
  });
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    fecharModal('modal-criar');
    fecharModal('modal-editar');
  }
});
</script>
@endpush
