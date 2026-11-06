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
<fieldset class="tu1-grupo"><legend>Identificação da turma</legend><div class="form-row-2">
<div class="field"><label for="nome">Nome da turma *</label><input class="input" id="nome" name="nome" required maxlength="50" value="{{ old('nome',$turma['nome']) }}" @error('nome') aria-invalid="true" aria-describedby="erro-nome" @enderror ><x-erro-campo nome="nome" /></div>
<div class="field"><label for="serie">Série *</label><input class="input" id="serie" name="serie" required maxlength="50" value="{{ old('serie',$turma['serie']) }}" @error('serie') aria-invalid="true" aria-describedby="erro-serie" @enderror ><x-erro-campo nome="serie" /></div>
<div class="field"><label for="ano">Ano letivo *</label><input class="input" id="ano" name="ano_letivo" type="number" min="2020" max="2099" required value="{{ old('ano_letivo',$turma['ano_letivo']) }}" @error('ano_letivo') aria-invalid="true" aria-describedby="erro-ano_letivo" @enderror ><x-erro-campo nome="ano_letivo" /></div></div></fieldset>
<fieldset class="tu1-grupo"><legend>Organização</legend><div class="form-row-2">
<div class="field"><label for="turno">Turno *</label><select class="select" id="turno" name="turno" required @error('turno') aria-invalid="true" aria-describedby="erro-turno" @enderror >@foreach(['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'] as $valor=>$rotulo)<option value="{{ $valor }}" @selected(old('turno',$turma['turno'])===$valor)>{{ $rotulo }}</option>@endforeach</select><x-erro-campo nome="turno" /></div>

<div class="field"><label for="etapa">Etapa de ensino (opcional)</label><select class="select" id="etapa" name="etapa_ensino" @error('etapa_ensino') aria-invalid="true" aria-describedby="erro-etapa_ensino" @enderror><option value="">Não informada</option>@foreach(['educacao_infantil'=>'Educação infantil','ensino_fundamental'=>'Ensino fundamental','ensino_medio'=>'Ensino médio','educacao_profissional'=>'Educação profissional','ensino_superior'=>'Ensino superior','eja'=>'Educação de jovens e adultos','outra'=>'Outra'] as $valor=>$rotulo)<option value="{{ $valor }}" @selected(old('etapa_ensino',$turma['etapa_ensino']??'')===$valor)>{{ $rotulo }}</option>@endforeach</select><x-erro-campo nome="etapa_ensino" /></div>
<div class="field"><label for="sala">Sala (opcional)</label><input class="input" id="sala" name="sala" maxlength="50" value="{{ old('sala',$turma['sala']??'') }}" @error('sala') aria-invalid="true" aria-describedby="erro-sala" @enderror><x-erro-campo nome="sala" /></div>
<div class="field"><label for="capacidade">Capacidade planejada (opcional)</label><input class="input" id="capacidade" name="capacidade" type="number" min="1" max="1000" step="1" value="{{ old('capacidade',$turma['capacidade']??'') }}" aria-describedby="capacidade-ajuda @error('capacidade') erro-capacidade @enderror" @error('capacidade') aria-invalid="true" @enderror><p id="capacidade-ajuda" class="field-help">Quantidade máxima planejada de alunos.</p><x-erro-campo nome="capacidade" /></div>
</div></fieldset>
<fieldset class="tu1-grupo"><legend>Observações</legend><div class="field"><label for="observacoes">Organização da turma (opcional)</label><textarea class="input" id="observacoes" name="observacoes" rows="4" maxlength="1000" aria-describedby="observacoes-ajuda @error('observacoes') erro-observacoes @enderror" @error('observacoes') aria-invalid="true" @enderror>{{ old('observacoes',$turma['observacoes']??'') }}</textarea><p class="field-help" id="observacoes-ajuda">Informações sobre a organização desta turma. Até 1.000 caracteres.</p><x-erro-campo nome="observacoes" /></div></fieldset>

<div class="form-actions"><a class="btn btn-secondary" href="{{ route('turmas.show',$turma['id']) }}"><x-icone nome="fechar" /> Cancelar</a><button class="btn btn-primary" type="submit"><x-icone nome="salvar" /> Salvar alterações</button></div>
</form>
@endsection

@push('scripts')<script src="{{ asset('js/formulario-erros.js') }}" defer></script>@endpush

@push('styles')<link rel="stylesheet" href="{{ asset('css/formulario-escolar.css') }}?v={{ filemtime(public_path('css/formulario-escolar.css')) }}">@endpush
