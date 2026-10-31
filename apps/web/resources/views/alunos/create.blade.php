@extends('layouts.app')

@section('tela', 'tela-aluno-novo')

@section('title', 'Cadastrar Aluno')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span>
@if($turma_id)
  <a href="{{ route('turmas.show', $turma_id) }}">Turma</a><span class="sep">/</span>
@endif
<span>Novo Aluno</span>
@endsection

@section('content')

<x-pagina titulo="Cadastrar aluno">
  <x-slot:sub>Preencha os dados básicos para matricular o aluno no sistema.</x-slot:sub>
</x-pagina>

@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if($errors->any())
  <div style="background:var(--danger-light);color:var(--danger);border:1px solid var(--danger);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;margin-top:16px;">
    <ul style="margin:0;padding-left:18px;">
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ route('alunos.store') }}" class="form-container">
  @csrf

  {{-- Card lateral de preview --}}
  <aside class="identity-card">
    <x-avatar :nome="old('nome', '')" tamanho="xl" id="preview-avatar" />
    <div style="margin-bottom:20px;">
      <h3 id="preview-nome" style="font-size:16px;font-weight:700;color:var(--fg);margin-bottom:4px;">Novo Aluno</h3>
      <p id="preview-turma" style="font-size:13px;color:var(--muted);">Série / Turma</p>
    </div>
    <p style="font-size:12px;color:var(--muted);line-height:1.5;">O aluno será representado pelas iniciais do nome.</p>
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
function atualizarPreview() {
  var nome  = document.getElementById('nome').value;
  var sel   = document.getElementById('turma_id');
  var opt   = sel.options[sel.selectedIndex];

  var ini = nome.trim()
    ? nome.trim().split(/\s+/u).filter(Boolean).map(function(p){ return Array.from(p)[0].toLocaleUpperCase('pt-BR'); }).slice(0,2).join('')
    : '?';

  document.getElementById('preview-avatar').textContent = ini;
  document.getElementById('preview-nome').textContent   = nome || 'Novo Aluno';
  document.getElementById('preview-turma').textContent  = (opt && opt.value) ? opt.dataset.label : 'Série / Turma';
}
atualizarPreview();
</script>
@endpush
