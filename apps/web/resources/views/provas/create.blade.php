@extends('layouts.app')

@section('title', 'Nova Prova')

@push('styles')
<style>
  .editor-grid { display:grid;grid-template-columns:320px 1fr;gap:20px;margin-top:20px;align-items:start; }
  .sticky { position:sticky;top:84px; }
  .stepper { display:flex;gap:0;margin-bottom:4px; }
  .step { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted);font-weight:600; }
  .step .dot { width:24px;height:24px;border-radius:50%;background:var(--surface-2);display:grid;place-items:center;font-size:12px; }
  .step.active .dot { background:var(--accent);color:#fff; }
  .step.done .dot { background:var(--success,#168821);color:#fff; }
  .step .line { width:40px;height:2px;background:var(--border);margin:0 10px; }
  .preview-prova { background:var(--surface-2,#f4f6f9);border-radius:var(--radius-md);padding:16px;margin-top:16px;font-size:13px; }
  .preview-prova .pk { color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px; }
  .preview-prova .pv { font-weight:600;color:var(--fg); }
  @media(max-width:900px){ .editor-grid{grid-template-columns:1fr;} .sticky{position:static;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('provas.index') }}" class="active">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('provas.index') }}">Provas</a><span class="sep">/</span>
<span>Nova prova</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">Criar prova e gabarito oficial</h1>
    <p class="page-sub">Defina os dados da prova e as turmas onde será aplicada</p>
  </div>
</div>

@if(session('erro'))
  <div style="background:var(--danger-light,#fde8e4);color:var(--danger,#e52207);border:1px solid var(--danger,#e52207);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('erro') }}
  </div>
@endif
@if($errors->any())
  <div style="background:var(--danger-light,#fde8e4);color:var(--danger,#e52207);border:1px solid var(--danger,#e52207);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;margin-top:16px;">
    <ul style="margin:0;padding-left:18px;">
      @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul>
  </div>
@endif

{{-- Stepper --}}
<div class="stepper" style="margin-top:20px;">
  <div class="step active"><span class="dot">1</span> Dados</div>
  <span class="line"></span>
  <div class="step"><span class="dot">2</span> Gabarito oficial</div>
  <span class="line"></span>
  <div class="step"><span class="dot">3</span> Cartão &amp; turmas</div>
</div>

<form method="POST" action="{{ route('provas.store') }}" class="editor-grid">
  @csrf

  {{-- Sidebar de preview --}}
  <aside class="sticky">
    <div class="card card-pad">
      <div style="font-size:14px;font-weight:700;margin-bottom:12px;">Resumo da prova</div>
      <div class="preview-prova">
        <div class="pk">Título</div>
        <div class="pv" id="prev-titulo">—</div>
        <div class="pk" style="margin-top:10px;">Disciplina</div>
        <div class="pv" id="prev-disc">—</div>
        <div class="pk" style="margin-top:10px;">Série</div>
        <div class="pv" id="prev-serie">—</div>
        <div class="pk" style="margin-top:10px;">Questões</div>
        <div class="pv" id="prev-q">—</div>
        <div class="pk" style="margin-top:10px;">Alternativas</div>
        <div class="pv" id="prev-alt">—</div>
        <div class="pk" style="margin-top:10px;">Aplicação</div>
        <div class="pv" id="prev-data">Não definida</div>
      </div>
      <div style="margin-top:16px;padding:12px;background:var(--accent-light);border-radius:var(--radius-sm);font-size:12px;color:var(--accent-dark,#0c326f);">
        <strong>Etapa 2:</strong> Após salvar, você será levado à tela de gabarito para marcar as respostas corretas.
      </div>
    </div>
  </aside>

  {{-- Formulário --}}
  <div class="stack">
    {{-- Dados básicos --}}
    <div class="card card-pad">
      <div style="font-size:14px;font-weight:700;margin-bottom:16px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Dados da prova</div>
      <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="field" style="grid-column:1/-1">
          <label for="titulo">Título da prova *</label>
          <input class="input" type="text" id="titulo" name="titulo" required
                 placeholder="Ex: Avaliação Bimestral — Matemática"
                 value="{{ old('titulo') }}" oninput="atualizar()" />
          @error('titulo')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
          <label for="disciplina">Disciplina *</label>
          <select class="select" id="disciplina" name="disciplina" required onchange="atualizar()">
            <option value="">— Selecione —</option>
            @foreach(['Matemática','Português','Ciências','História','Geografia','Inglês','Ed. Física','Arte','Física','Química','Biologia','Filosofia','Sociologia'] as $d)
              <option value="{{ $d }}" {{ old('disciplina') === $d ? 'selected' : '' }}>{{ $d }}</option>
            @endforeach
          </select>
          @error('disciplina')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
          <label for="serie">Série / ano *</label>
          <input class="input" type="text" id="serie" name="serie" required
                 placeholder="Ex: 9º ano — EF II"
                 value="{{ old('serie') }}" oninput="atualizar()" />
          @error('serie')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
          <label for="num_questoes">Nº de questões *</label>
          <input class="input num" type="number" id="num_questoes" name="num_questoes" required
                 min="1" max="100" value="{{ old('num_questoes', 20) }}" oninput="atualizar()" />
          @error('num_questoes')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
          <label for="num_alternativas">Alternativas por questão *</label>
          <select class="select" id="num_alternativas" name="num_alternativas" required onchange="atualizar()">
            <option value="3" {{ old('num_alternativas','5') == '3' ? 'selected' : '' }}>3 alternativas (A, B, C)</option>
            <option value="4" {{ old('num_alternativas','5') == '4' ? 'selected' : '' }}>4 alternativas (A, B, C, D)</option>
            <option value="5" {{ old('num_alternativas','5') == '5' ? 'selected' : '' }}>5 alternativas (A, B, C, D, E)</option>
          </select>
          @error('num_alternativas')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
          <label for="data_aplicacao">Data de aplicação</label>
          <input class="input" type="date" id="data_aplicacao" name="data_aplicacao"
                 value="{{ old('data_aplicacao') }}" onchange="atualizar()" />
        </div>
        <div class="field">
          <label for="escola_id">Escola *</label>
          <select class="select" id="escola_id" name="escola_id" required>
            <option value="">— Selecione —</option>
            @foreach($escolas as $e)
              <option value="{{ $e['id'] }}" {{ old('escola_id') == $e['id'] ? 'selected' : '' }}>{{ $e['nome'] }}</option>
            @endforeach
          </select>
          @error('escola_id')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
          <label for="nota_maxima">Nota máxima</label>
          <input class="input num" type="number" id="nota_maxima" name="nota_maxima"
                 step="0.5" min="0.5" max="100" value="{{ old('nota_maxima', 10) }}" />
        </div>
      </div>
    </div>

    {{-- Turmas --}}
    <div class="card card-pad">
      <div style="font-size:14px;font-weight:700;margin-bottom:4px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Turmas onde será aplicada</div>
      <p style="font-size:13px;color:var(--muted);margin-bottom:16px;">Selecione uma ou mais turmas. Você pode alterar isso depois.</p>
      @forelse($turmas as $t)
        <label style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-soft);cursor:pointer;">
          <input type="checkbox" name="turma_ids[]" value="{{ $t['id'] }}"
                 {{ in_array($t['id'], (array)old('turma_ids', [])) ? 'checked' : '' }}
                 style="width:18px;height:18px;flex-shrink:0;" />
          <div>
            <div style="font-weight:600;">{{ $t['nome'] }} — {{ $t['serie'] }}</div>
            <div style="font-size:12px;color:var(--muted);">{{ $t['escola_nome'] ?? '' }} · {{ ['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$t['turno']] ?? '' }} · {{ $t['ano_letivo'] }}</div>
          </div>
        </label>
      @empty
        <div style="text-align:center;padding:24px;color:var(--muted);font-size:14px;">
          Nenhuma turma ativa. Crie turmas primeiro no módulo de Turmas.
        </div>
      @endforelse
    </div>

    {{-- Padrões avançados --}}
    <div class="card card-pad">
      <div style="font-size:14px;font-weight:700;margin-bottom:16px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Configurações avançadas</div>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <label style="display:flex;align-items:center;gap:12px;cursor:pointer;font-size:14px;">
          <input type="checkbox" name="anular_se_todas" value="1"
                 {{ old('anular_se_todas') ? 'checked' : '' }}
                 style="width:18px;height:18px;" />
          <div>
            <div style="font-weight:600;">Anular questão se todas marcadas</div>
            <div style="font-size:12px;color:var(--muted);">Anula a questão quando o aluno marca mais de uma alternativa</div>
          </div>
        </label>
        <label style="display:flex;align-items:center;gap:12px;cursor:pointer;font-size:14px;">
          <input type="checkbox" name="gerar_cartao_pdf" value="1" checked
                 style="width:18px;height:18px;" />
          <div>
            <div style="font-weight:600;">Gerar cartão-resposta em PDF</div>
            <div style="font-size:12px;color:var(--muted);">Gera automaticamente o cartão para impressão após publicar</div>
          </div>
        </label>
      </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end;padding-top:8px;">
      <a href="{{ route('provas.index') }}" class="btn btn-ghost">Cancelar</a>
      <button type="submit" class="btn btn-primary">Salvar rascunho →</button>
    </div>
  </div>
</form>

<div style="height:48px;"></div>
@endsection

@push('scripts')
<script>
function atualizar() {
  document.getElementById('prev-titulo').textContent = document.getElementById('titulo').value || '—';
  var disc = document.getElementById('disciplina');
  document.getElementById('prev-disc').textContent = disc.options[disc.selectedIndex].text || '—';
  document.getElementById('prev-serie').textContent = document.getElementById('serie').value || '—';
  document.getElementById('prev-q').textContent = document.getElementById('num_questoes').value + ' questões';
  var alt = document.getElementById('num_alternativas');
  document.getElementById('prev-alt').textContent = alt.options[alt.selectedIndex].text;
  var data = document.getElementById('data_aplicacao').value;
  document.getElementById('prev-data').textContent = data
    ? new Date(data + 'T12:00:00').toLocaleDateString('pt-BR')
    : 'Não definida';
}
atualizar();
</script>
@endpush
