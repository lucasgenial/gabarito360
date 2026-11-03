@extends('layouts.app')
@section('tela', 'tela-prova-copiar')
@section('title', 'Copiar prova')
@section('breadcrumb')
<a href="{{ route('provas.index') }}">Provas</a><span class="sep">/</span>
<a href="{{ route('provas.show',$prova['id']) }}">{{ $prova['titulo'] }}</a><span class="sep">/</span><span>Copiar prova</span>
@endsection
@section('content')
<x-pagina titulo="Copiar prova"><x-slot:sub>Crie um rascunho independente para outra turma ou uma nova versão da avaliação.</x-slot:sub></x-pagina>
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
<div class="cp1-origem">
  <p>Origem: <strong>{{ $prova['titulo'] }}</strong> · {{ $prova['disciplina'] }} · {{ $prova['serie'] }}.</p>
  <p>A nova versão reaproveita as configurações e o gabarito, sem turmas ou resultados. A original permanece igual.</p>
  <details class="cp1-detalhes">
    <summary>Ver o que será copiado</summary>
    <div>
      <p>O gabarito preenchido, quando existente, será copiado sem publicação para você revisar.</p>
      <p>Turmas, alunos, cartões, respostas, notas e datas de aplicação não serão copiados.</p>
    </div>
  </details>
</div>
<div class="cp1-grade">
  <form method="POST" action="{{ route('provas.copiar.store',$prova['id']) }}" class="card card-pad cp1-formulario">
    <h2>Prepare a nova versão</h2>
    @csrf
    <input type="hidden" name="chave" value="{{ old('chave',$chave) }}">
    <div class="field">
      <label for="titulo">Título da nova prova *</label>
      <input class="input" id="titulo" name="titulo" required maxlength="255" value="{{ old('titulo',\Illuminate\Support\Str::limit($prova['titulo'],240,'').' — cópia') }}">
      @error('titulo')<span class="field-error">{{ $message }}</span>@enderror
      @error('chave')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <p>Depois de criar, revise o gabarito e escolha as turmas que receberão esta versão.</p>
    <div class="cp1-acoes">
      <button type="submit" class="btn btn-primary">Criar cópia em rascunho</button>
      <a class="btn btn-secondary" href="{{ route('provas.show',$prova['id']) }}">Cancelar</a>
    </div>
  </form>
</div>
@endsection
