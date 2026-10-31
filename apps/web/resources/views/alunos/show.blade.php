@extends('layouts.app')

@section('tela', 'tela-aluno')

@section('title', $aluno['nome'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span>
@if($aluno['turma_id'] ?? null)
  <a href="{{ route('turmas.show', $aluno['turma_id']) }}">{{ $aluno['turma_nome'] ?? 'Turma' }}</a><span class="sep">/</span>
@endif
<span>{{ $aluno['nome'] }}</span>
@endsection

@section('content')

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

{{-- Header do perfil --}}
<div class="profile-header">
  <x-avatar :nome="$aluno['nome']" :aluno-id="$aluno['id']" tamanho="lg" />
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
    @pode('resultado.ver')<a class="btn btn-primary" href="{{ route('relatorios.aluno', $aluno['id']) }}"><x-icone nome="prova" /> Boletim</a>@endpode
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
        <span style="color:var(--success);">Ativo</span>
      @else
        <span style="color:var(--danger);">Inativo</span>
      @endif
    </div>
    <div style="font-size:13px;color:var(--muted);margin-top:4px;">Status</div>
  </div>
</div>

{{-- Histórico de avaliações (placeholder — MP-019) --}}
<h2 class="section-title secao-topo">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
    <polyline points="7 10 12 15 17 10"/>
    <line x1="12" y1="15" x2="12" y2="3"/>
  </svg>
  Histórico de Avaliações
</h2>
<div class="card card-pad" style="text-align:center;padding:48px 20px;color:var(--muted);">
  <div class="icone-grande"><x-icone nome="prova" /></div>
  <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Sem avaliações registradas</div>
  <p>O histórico de avaliações do aluno estará disponível após a aplicação de provas (MP-019).</p>
</div>

{{-- Modal de edição --}}
<div class="modal-backdrop" id="modal-editar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h2>Editar aluno</h2>
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
          <div class="field">
            <label for="ed-cpf">CPF</label>
            <input class="input num" type="text" id="ed-cpf" name="cpf" maxlength="11" placeholder="Apenas números" value="{{ $aluno['cpf'] ?? '' }}" />
          </div>
          <div class="field">
            <label for="ed-genero">Gênero</label>
            <select class="select" id="ed-genero" name="genero">
              <option value="">Selecione...</option>
              <option value="M" {{ ($aluno['genero'] ?? '') === 'M' ? 'selected' : '' }}>Masculino</option>
              <option value="F" {{ ($aluno['genero'] ?? '') === 'F' ? 'selected' : '' }}>Feminino</option>
              <option value="O" {{ ($aluno['genero'] ?? '') === 'O' ? 'selected' : '' }}>Outro / Não informar</option>
            </select>
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
