@extends('layouts.app')
@section('tela','tela-turma')
@section('title','Editar turma')
@section('breadcrumb')
<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span><a href="{{ route('turmas.show',$turma['id']) }}">{{ $turma['nome'] }}</a><span class="sep">/</span><span>Editar turma</span>
@endsection
@section('content')
<x-pagina titulo="Editar turma"><x-slot:sub>{{ $turma['nome'] }} · {{ $turma['escola_nome'] ?? 'Meu espaço' }}</x-slot:sub></x-pagina>
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro"><ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></x-aviso>@endif
<form data-formulario-validado class="card card-pad formulario-escolar" method="POST" action="{{ route('turmas.update',$turma['id']) }}">
@csrf <x-revisao-acesso /> @method('PUT')
<div class="form-row-2">
<div class="field"><label for="nome">Nome da turma *</label><input class="input" id="nome" name="nome" required maxlength="50" value="{{ old('nome',$turma['nome']) }}" @error('nome') aria-invalid="true" aria-describedby="erro-nome" @enderror ><x-erro-campo nome="nome" /></div>
<div class="field"><label for="serie">Série *</label><input class="input" id="serie" name="serie" required maxlength="50" value="{{ old('serie',$turma['serie']) }}" @error('serie') aria-invalid="true" aria-describedby="erro-serie" @enderror ><x-erro-campo nome="serie" /></div>
<div class="field"><label for="turno">Turno *</label><select class="select" id="turno" name="turno" required @error('turno') aria-invalid="true" aria-describedby="erro-turno" @enderror >@foreach(['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'] as $valor=>$rotulo)<option value="{{ $valor }}" @selected(old('turno',$turma['turno'])===$valor)>{{ $rotulo }}</option>@endforeach</select><x-erro-campo nome="turno" /></div>
<div class="field"><label for="ano">Ano letivo *</label><input class="input" id="ano" name="ano_letivo" type="number" min="2020" max="2099" required value="{{ old('ano_letivo',$turma['ano_letivo']) }}" @error('ano_letivo') aria-invalid="true" aria-describedby="erro-ano_letivo" @enderror ><x-erro-campo nome="ano_letivo" /></div>
</div>
<div class="form-actions"><a class="btn btn-secondary" href="{{ route('turmas.show',$turma['id']) }}">Cancelar</a><button class="btn btn-primary" type="submit">Salvar alterações</button></div>
</form>
@endsection

@push('scripts')<script src="{{ asset('js/formulario-erros.js') }}" defer></script>@endpush

@push('styles')<link rel="stylesheet" href="{{ asset('css/formulario-escolar.css') }}?v={{ filemtime(public_path('css/formulario-escolar.css')) }}">@endpush
