@extends('layouts.app')

@section('tela', 'tela-turma')

@section('title', $turma['nome'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span>
<span>{{ $turma['nome'] }}</span>
@endsection

@section('content')

<x-pagina titulo="{{ $turma['nome'] }}">
  <x-slot:sub>{{ $turma['serie'] }} ·
      {{ ['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$turma['turno']] ?? $turma['turno'] }} ·
      <b>{{ $turma['total_alunos'] ?? 0 }}</b> aluno(s) ·
      {{ $turma['escola_nome'] ?? '' }}</x-slot:sub>
    @pode('correcao.acompanhar')<a class="btn btn-primary" href="{{ route('correcao.turma', $turma['id']) }}"><x-icone nome="grafico" /> Acompanhar correção</a>@endpode
    @pode('resultado.ver')<a class="btn btn-secondary" href="{{ route('relatorios.turma', $turma['id']) }}"><x-icone nome="grafico" /> Relatório da turma</a>@endpode
    @pode('turma.gerir')
    <button class="btn btn-secondary" onclick="abrirModal()">Editar turma</button>
    <form method="POST" action="{{ route('turmas.toggle', $turma['id']) }}"
          onsubmit="return confirm('{{ $turma['ativo'] ? 'Desativar' : 'Ativar' }} esta turma?')">
      @csrf
      <button type="submit" class="btn {{ $turma['ativo'] ? 'btn-ghost' : 'btn-secondary' }}"
              style="color:{{ $turma['ativo'] ? 'var(--danger)' : 'var(--success)' }};{{ $turma['ativo'] ? 'border-color:var(--danger);' : '' }}">
        {{ $turma['ativo'] ? 'Desativar' : 'Ativar' }}
      </button>
    </form>
    @endpode
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

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
<h2 class="section-title secao-topo">
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
  <table class="table lista-cel" id="alunos-table">
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
              <x-avatar :nome="$a['nome']" :aluno-id="$a['id']" tamanho="sm" />
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
            <div class="icone-grande"><x-icone nome="aluno" /></div>
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
<h2 class="section-title secao-topo">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
    <path d="M14 2v6h6"/>
  </svg>
  Provas da Turma
  <span class="badge" style="margin-left:6px;font-size:13px;">{{ count($turma['provas'] ?? []) }}</span>
</h2>

<div class="card" style="overflow:hidden;margin-bottom:24px;">
  @if(count($turma['provas'] ?? []) > 0)
  <table class="table lista-cel">
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
          'em_correcao' => ['cls'=>'badge-warn', 'label'=>'Em correção', 'style'=>''],
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
    <div class="icone-grande"><x-icone nome="prova" /></div>
    <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma prova aplicada</div>
    <p>As provas vinculadas a esta turma aparecerão aqui.</p>
  </div>
  @endif
</div>

{{-- Seção: Professores vinculados --}}
<h2 class="section-title secao-topo">
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
    <table class="table lista-cel" style="margin-top:0;">
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
      <div class="icone-grande"><x-icone nome="turma" /></div>
      <div style="font-size:14px;">Nenhum professor vinculado a esta turma ainda.</div>
    </div>
  @endif
</div>

{{-- Modal de edição --}}
<div class="modal-backdrop" id="modal-editar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h2>Editar turma</h2>
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
