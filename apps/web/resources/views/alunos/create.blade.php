@extends('layouts.app')

@section('title', 'Cadastrar Aluno')

@push('styles')
<style>
  .form-container { display:grid;grid-template-columns:280px 1fr;gap:32px;margin-top:24px;align-items:start; }
  .identity-card { text-align:center;padding:32px 24px;background:var(--surface);border-radius:var(--radius-md);border:1px solid var(--border);box-shadow:var(--shadow-sm); }
  .photo-preview { width:120px;height:120px;border-radius:50%;background:var(--accent-light);color:var(--accent-dark,#0c326f);display:grid;place-items:center;margin:0 auto 20px;font-size:36px;font-weight:800; }
  .form-grid { display:grid;grid-template-columns:1fr 1fr;gap:20px; }
  .form-actions { margin-top:32px;padding-top:24px;border-top:1px solid var(--border-soft);display:flex;gap:12px;justify-content:flex-end; }
  @media(max-width:850px){ .form-container{grid-template-columns:1fr;} .identity-card{max-width:400px;margin:0 auto;width:100%;} }
  @media(max-width:600px){ .form-grid{grid-template-columns:1fr;} }
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
@if($turma_id)
  <a href="{{ route('turmas.show', $turma_id) }}">Turma</a><span class="sep">/</span>
@endif
<span>Novo Aluno</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">Cadastrar Novo Aluno</h1>
    <p class="page-sub">Preencha os dados básicos para matricular o aluno no sistema.</p>
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
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ route('alunos.store') }}" class="form-container" enctype="multipart/form-data">
  @csrf

  {{-- Card lateral de preview --}}
  <aside class="identity-card">
    <img id="preview-foto" style="width:120px;height:120px;border-radius:50%;object-fit:cover;margin:0 auto 20px;display:none;" />
    <div class="photo-preview" id="preview-avatar">?</div>
    <div style="margin-bottom:20px;">
      <h3 id="preview-nome" style="font-size:16px;font-weight:700;color:var(--fg);margin-bottom:4px;">Novo Aluno</h3>
      <p id="preview-turma" style="font-size:13px;color:var(--muted);">Série / Turma</p>
    </div>
    <label for="foto" class="btn btn-secondary btn-sm" style="cursor:pointer;">Selecionar Foto</label>
    <input type="file" id="foto" name="foto" accept="image/*" style="display:none;" onchange="previewFoto(this)" />
    <p style="font-size:12px;color:var(--muted);line-height:1.5;margin-top:12px;">Opcional. Imagem até 4MB.</p>
  </aside>

  {{-- Formulário principal --}}
  <div class="card card-pad" style="flex:1;">
    <div class="form-grid">
      <div class="field" style="grid-column:1/-1">
        <label for="nome">Nome completo do aluno *</label>
        <input class="input" type="text" id="nome" name="nome" required
               placeholder="Ex: João da Silva Santos"
               value="{{ old('nome') }}"
               oninput="atualizarPreview()" />
        @error('nome')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="field">
        <label for="matricula">Nº de Matrícula *</label>
        <input class="input num" type="text" id="matricula" name="matricula" required
               placeholder="Ex: 2026.00001" value="{{ old('matricula') }}" />
        @error('matricula')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="field">
        <label for="data_nascimento">Data de Nascimento *</label>
        <input class="input" type="date" id="data_nascimento" name="data_nascimento" required
               value="{{ old('data_nascimento') }}" />
        @error('data_nascimento')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="field">
        <label for="cpf">CPF do Aluno (opcional)</label>
        <input class="input num" type="text" id="cpf" name="cpf" maxlength="14"
               placeholder="000.000.000-00" value="{{ old('cpf') }}" />
        @error('cpf')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="field">
        <label for="genero">Gênero</label>
        <select class="select" id="genero" name="genero">
          <option value="">Selecione...</option>
          <option value="M" {{ old('genero') === 'M' ? 'selected' : '' }}>Masculino</option>
          <option value="F" {{ old('genero') === 'F' ? 'selected' : '' }}>Feminino</option>
          <option value="O" {{ old('genero') === 'O' ? 'selected' : '' }}>Outro / Não informar</option>
        </select>
      </div>
      <div class="field" style="grid-column:1/-1">
        <label for="turma_id">Turma de destino *</label>
        <select class="select" id="turma_id" name="turma_id" required onchange="atualizarPreview()">
          <option value="">— Selecione a turma —</option>
          @foreach($turmas as $t)
            <option value="{{ $t['id'] }}"
                    data-label="{{ $t['nome'] }} — {{ $t['serie'] }}"
                    {{ (old('turma_id', $turma_id) == $t['id']) ? 'selected' : '' }}>
              {{ $t['nome'] }} — {{ $t['serie'] }}
              @if($t['escola_nome'] ?? null) ({{ $t['escola_nome'] }})@endif
            </option>
          @endforeach
        </select>
        @error('turma_id')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="field" style="grid-column:1/-1">
        <label for="nome_responsavel">Nome do responsável (pai/mãe/tutor)</label>
        <input class="input" type="text" id="nome_responsavel" name="nome_responsavel"
               placeholder="Ex: Maria de Souza Silva"
               value="{{ old('nome_responsavel') }}" />
      </div>
    </div>

    <div class="form-actions">
      <a href="{{ $turma_id ? route('turmas.show', $turma_id) : route('turmas.index') }}"
         class="btn btn-secondary">Cancelar</a>
      <button type="submit" class="btn btn-primary">Salvar Cadastro</button>
    </div>
  </div>
</form>

<div style="height:48px;"></div>
@endsection

@push('scripts')
<script>
function previewFoto(input) {
  if (!input.files || !input.files[0]) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var img = document.getElementById('preview-foto');
    img.src = e.target.result;
    img.style.display = 'block';
    document.getElementById('preview-avatar').style.display = 'none';
  };
  reader.readAsDataURL(input.files[0]);
}

function atualizarPreview() {
  var nome  = document.getElementById('nome').value;
  var sel   = document.getElementById('turma_id');
  var opt   = sel.options[sel.selectedIndex];

  var ini = nome.trim()
    ? nome.trim().split(' ').filter(Boolean).map(function(p){ return p[0].toUpperCase(); }).slice(0,2).join('')
    : '?';

  document.getElementById('preview-avatar').textContent = ini;
  document.getElementById('preview-nome').textContent   = nome || 'Novo Aluno';
  document.getElementById('preview-turma').textContent  = (opt && opt.value) ? opt.dataset.label : 'Série / Turma';
}
atualizarPreview();
</script>
@endpush
