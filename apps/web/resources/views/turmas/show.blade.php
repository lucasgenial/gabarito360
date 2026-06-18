@extends('layouts.app')

@section('title', $turma['nome'])

@push('styles')
<style>
  .kpi-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-top:22px; }
  .student-av { width:36px;height:36px;border-radius:50%;background:var(--accent-light);color:var(--accent-dark,#0c326f);display:grid;place-items:center;font-weight:700;font-size:13px;flex-shrink:0; }
  .student-name { display:flex;align-items:center;gap:12px; }
  .mini-bar { width:80px;height:7px;background:var(--border-soft);border-radius:99px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px; }
  .mini-bar > div { height:100%; }
  .section-title { font-size:18px;font-weight:700;margin:32px 0 16px;display:flex;align-items:center;gap:8px; }
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
  @media(max-width:980px){ .kpi-grid{grid-template-columns:repeat(2,1fr);} }
  @media(max-width:560px){ .kpi-grid{grid-template-columns:1fr;} .form-row-2{grid-template-columns:1fr;} }
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
<span>{{ $turma['nome'] }}</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">{{ $turma['nome'] }}</h1>
    <p class="page-sub">
      {{ $turma['serie'] }} ·
      {{ ['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$turma['turno']] ?? $turma['turno'] }} ·
      <b>{{ $turma['total_alunos'] ?? 0 }}</b> aluno(s) ·
      {{ $turma['escola_nome'] ?? '' }}
    </p>
  </div>
  <div style="display:flex;gap:10px;">
    <button class="btn btn-secondary" onclick="abrirModal()">Editar turma</button>
    <form method="POST" action="{{ route('turmas.toggle', $turma['id']) }}"
          onsubmit="return confirm('{{ $turma['ativo'] ? 'Desativar' : 'Ativar' }} esta turma?')">
      @csrf
      <button type="submit" class="btn {{ $turma['ativo'] ? 'btn-ghost' : 'btn-secondary' }}"
              style="color:{{ $turma['ativo'] ? 'var(--danger)' : 'var(--success)' }};{{ $turma['ativo'] ? 'border-color:var(--danger);' : '' }}">
        {{ $turma['ativo'] ? 'Desativar' : 'Ativar' }}
      </button>
    </form>
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
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Desempenho médio</div>
    <div class="kpi-value">{{ $turma['media_turma'] !== null ? number_format($turma['media_turma'], 1, ',', '') : '—' }}</div>
    <div class="kpi-trend">média da turma</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Provas aplicadas</div>
    <div class="kpi-value">{{ $turma['total_provas'] ?? 0 }}</div>
    <div class="kpi-trend">nesta turma</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Alunos ativos</div>
    <div class="kpi-value">{{ $turma['total_alunos'] ?? 0 }}</div>
    <div class="kpi-trend">matriculados</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Ano letivo</div>
    <div class="kpi-value">{{ $turma['ano_letivo'] }}</div>
    <div class="kpi-trend">{{ $turma['ativo'] ? 'Ativa' : 'Inativa' }}</div>
  </div>
</div>

{{-- Seção de alunos --}}
<h2 class="section-title">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
    <circle cx="9" cy="7" r="4"/>
    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
  </svg>
  Alunos da Turma
  <span class="badge" style="margin-left:6px;font-size:13px;">{{ count($turma['alunos'] ?? []) }}</span>
</h2>

<div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap;">
  <div style="position:relative;flex:1;min-width:200px;">
    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);opacity:.4;" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input id="aluno-search" class="input" type="search" placeholder="Buscar aluno por nome…"
           style="padding-left:36px;" oninput="filtrarAlunos(this.value)" />
  </div>
  <a href="{{ route('alunos.create', ['turma_id' => $turma['id']]) }}" class="btn btn-primary btn-sm">+ Adicionar aluno</a>
</div>

<div class="card" style="overflow:hidden;">
  <table class="table" id="alunos-table">
    <thead>
      <tr>
        <th>Aluno</th>
        <th>Matrícula</th>
        <th>Média geral</th>
        <th>Provas</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="alunos-tbody">
      @forelse($turma['alunos'] ?? [] as $a)
        <tr data-nome="{{ strtolower($a['nome']) }}">
          <td>
            <div class="student-name">
              @php $ini = collect(explode(' ', $a['nome']))->filter()->map(fn($p) => mb_strtoupper(mb_substr($p,0,1)))->take(2)->implode(''); @endphp
              <div class="student-av">{{ $ini }}</div>
              <strong>{{ $a['nome'] }}</strong>
            </div>
          </td>
          <td style="font-family:var(--font-mono);">{{ $a['matricula'] ?? '—' }}</td>
          <td style="font-family:var(--font-mono);">{{ ($a['media_geral'] ?? null) !== null ? number_format($a['media_geral'], 1, ',', '') : '—' }}</td>
          <td style="font-family:var(--font-mono);">{{ $a['total_provas'] ?? 0 }}</td>
          <td>
            @if($a['ativo'] ?? true)
              <span class="badge badge-success badge-dot">Ativo</span>
            @else
              <span class="badge badge-danger badge-dot">Inativo</span>
            @endif
          </td>
          <td><a href="{{ route('alunos.show', $a['id']) }}" class="btn btn-sm btn-secondary">Ver</a></td>
        </tr>
      @empty
        <tr id="empty-row">
          <td colspan="6" style="text-align:center;padding:48px 20px;color:var(--muted);">
            <div style="font-size:36px;margin-bottom:12px;">👤</div>
            <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhum aluno matriculado</div>
            <p>Os alunos aparecerão aqui após o cadastro no módulo de Alunos.</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
  <div id="no-results" style="display:none;padding:32px;text-align:center;color:var(--muted);font-size:14px;">
    Nenhum aluno corresponde à busca.
  </div>
</div>

{{-- Seção: Provas da turma --}}
<h2 class="section-title">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
    <path d="M14 2v6h6"/>
  </svg>
  Provas da Turma
  <span class="badge" style="margin-left:6px;font-size:13px;">{{ count($turma['provas'] ?? []) }}</span>
</h2>

<div class="card" style="overflow:hidden;margin-bottom:24px;">
  @if(count($turma['provas'] ?? []) > 0)
  <table class="table">
    <thead>
      <tr>
        <th>Prova</th>
        <th>Disciplina</th>
        <th>Aplicação</th>
        <th>Média</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @php
        $statusCfg = [
          'rascunho'    => ['cls'=>'badge-info',   'label'=>'Rascunho'],
          'publicada'   => ['cls'=>'badge-success', 'label'=>'Publicada'],
          'em_correcao' => ['cls'=>'',              'label'=>'Em correção',  'style'=>'background:rgba(245,158,11,.15);color:#b45309;'],
          'corrigida'   => ['cls'=>'badge-success', 'label'=>'Corrigida'],
        ];
      @endphp
      @foreach($turma['provas'] as $p)
        @php $sc = $statusCfg[$p['status']] ?? ['cls'=>'badge-info','label'=>$p['status']]; @endphp
        <tr>
          <td><strong>{{ $p['titulo'] }}</strong></td>
          <td>{{ $p['disciplina'] }}</td>
          <td>{{ $p['data_aplicacao'] ? \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m/Y') : '—' }}</td>
          <td style="font-family:var(--font-mono);">{{ $p['media'] !== null ? number_format($p['media'], 1, ',', '') : '—' }}</td>
          <td><span class="badge {{ $sc['cls'] }}" style="{{ $sc['style'] ?? '' }}">{{ $sc['label'] }}</span></td>
          <td>
            @if($p['status'] === 'em_correcao')
              <a href="{{ route('correcao.show', $p['id']) }}" class="btn btn-sm btn-secondary">Acompanhar</a>
            @elseif($p['status'] === 'corrigida')
              <a href="{{ route('relatorios.turmaProva', [$turma['id'], $p['id']]) }}" class="btn btn-sm btn-secondary">Relatório</a>
            @else
              <a href="{{ route('provas.show', $p['id']) }}" class="btn btn-sm btn-secondary">Ver</a>
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
  @else
  <div style="text-align:center;padding:48px 20px;color:var(--muted);">
    <div style="font-size:36px;margin-bottom:12px;">📝</div>
    <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma prova aplicada</div>
    <p>As provas vinculadas a esta turma aparecerão aqui.</p>
  </div>
  @endif
</div>

{{-- Seção: Professores vinculados --}}
<h2 class="section-title">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
    <circle cx="12" cy="7" r="4"/>
  </svg>
  Professores
  <span class="badge" style="margin-left:6px;font-size:13px;">{{ count($professores ?? []) }}</span>
</h2>

<div class="card card-pad" style="margin-bottom:24px;">
  {{-- Vincular professor --}}
  <form method="POST" action="{{ route('turmas.professores.store', $turma['id']) }}"
        style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    @csrf
    <select class="select" name="usuario_id" required style="flex:1;min-width:200px;">
      <option value="">— Selecione um professor —</option>
      @foreach($todosProfessores as $p)
        @php $jaVinculado = collect($professores)->contains('id', $p['id']); @endphp
        @if(!$jaVinculado)
          <option value="{{ $p['id'] }}">{{ $p['nome'] }} ({{ $p['email'] }})</option>
        @endif
      @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Vincular professor</button>
  </form>

  @if(count($professores ?? []) > 0)
    <table class="table" style="margin-top:0;">
      <thead>
        <tr>
          <th>Professor(a)</th>
          <th>E-mail</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($professores as $p)
          @php $ini = collect(explode(' ', $p['nome']))->filter()->map(fn($x) => mb_strtoupper(mb_substr($x,0,1)))->take(2)->implode(''); @endphp
          <tr>
            <td>
              <div class="student-name">
                <div class="student-av">{{ $ini }}</div>
                <strong>{{ $p['nome'] }}</strong>
              </div>
            </td>
            <td>{{ $p['email'] }}</td>
            <td>
              @if($p['ativo'] ?? true)
                <span class="badge badge-success badge-dot">Ativo</span>
              @else
                <span class="badge badge-danger badge-dot">Inativo</span>
              @endif
            </td>
            <td style="text-align:right;">
              <form method="POST"
                    action="{{ route('turmas.professores.destroy', [$turma['id'], $p['id']]) }}"
                    onsubmit="return confirm('Desvincular {{ $p['nome'] }} desta turma?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-ghost"
                        style="color:var(--danger);border-color:var(--danger);">Desvincular</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <div style="text-align:center;padding:24px 20px;color:var(--muted);">
      <div style="font-size:28px;margin-bottom:10px;">👩‍🏫</div>
      <div style="font-size:14px;">Nenhum professor vinculado a esta turma ainda.</div>
    </div>
  @endif
</div>

{{-- Modal de edição --}}
<div class="modal-backdrop" id="modal-editar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h2>Editar Turma</h2>
      <button type="button" class="modal-close" onclick="fecharModal()">&times;</button>
    </div>
    <form method="POST" action="{{ route('turmas.update', $turma['id']) }}">
      @csrf
      @method('PUT')
      <div class="modal-body">
        <div class="form-row-2">
          <div class="field" style="grid-column:1/-1">
            <label for="ed-nome">Nome da turma *</label>
            <input class="input" type="text" id="ed-nome" name="nome" required value="{{ $turma['nome'] }}" />
          </div>
          <div class="field">
            <label for="ed-serie">Série *</label>
            <input class="input" type="text" id="ed-serie" name="serie" required value="{{ $turma['serie'] }}" />
          </div>
          <div class="field">
            <label for="ed-turno">Turno *</label>
            <select class="select" id="ed-turno" name="turno" required>
              @foreach(['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'] as $v => $l)
                <option value="{{ $v }}" {{ $turma['turno'] === $v ? 'selected' : '' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="ed-escola">Escola</label>
            <select class="select" id="ed-escola" name="escola_id">
              @foreach($escolas as $e)
                <option value="{{ $e['id'] }}" {{ ($turma['escola_id'] ?? '') == $e['id'] ? 'selected' : '' }}>{{ $e['nome'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="ed-ano">Ano letivo</label>
            <input class="input num" type="number" id="ed-ano" name="ano_letivo"
                   value="{{ $turma['ano_letivo'] }}" min="2020" max="2099" />
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

function abrirModal() {
  modal.classList.add('open');
  modal.setAttribute('aria-hidden','false');
  document.body.style.overflow = 'hidden';
}
function fecharModal() {
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden','true');
  document.body.style.overflow = '';
}
modal.addEventListener('click', function(e){ if(e.target===modal) fecharModal(); });
document.addEventListener('keydown', function(e){ if(e.key==='Escape') fecharModal(); });

function filtrarAlunos(q) {
  var termo = q.toLowerCase().trim();
  var rows  = document.querySelectorAll('#alunos-tbody tr[data-nome]');
  var visiveis = 0;
  rows.forEach(function(row) {
    var show = !termo || row.dataset.nome.includes(termo);
    row.style.display = show ? '' : 'none';
    if(show) visiveis++;
  });
  document.getElementById('no-results').style.display = (visiveis === 0 && termo) ? 'block' : 'none';
}
</script>
@endpush
