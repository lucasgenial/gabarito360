@extends('layouts.app')
@section('title',$area?'Editar área':'Adicionar área')
@section('breadcrumb')<a href="{{ route('disciplinas.index',$retorno) }}">Disciplinas</a><span class="sep">/</span><a href="{{ route('areas.index',$retorno) }}">Áreas do conhecimento</a><span class="sep">/</span><span>{{ $area?'Editar':'Adicionar' }}</span>@endsection
@section('content')
<x-pagina :titulo="$area?'Editar área':'Adicionar área'"><x-slot:sub>Organização das disciplinas.</x-slot:sub></x-pagina>
@include('disciplinas._base')
<form class="card card-pad dc1-stack" method="POST" action="{{ $area?route('areas.update',$area['id']):route('areas.store') }}">
  @csrf @include('disciplinas._retorno') @if($area)@method('PUT')<input type="hidden" name="versao" value="{{ old('versao',$area['versao']) }}">@endif
  <div class="field"><label for="area-nome">Nome da área <span aria-hidden="true">*</span></label><input class="input" id="area-nome" name="nome" required maxlength="100" value="{{ old('nome',$area['nome']??'') }}" @error('nome') aria-invalid="true" aria-describedby="dc1-erro-nome" @enderror>@include('disciplinas._erro',['campo'=>'nome'])</div>
  <div class="dc1-grade">
    <div class="field"><label for="area-sigla">Sigla <span class="field-help">(opcional)</span></label><input class="input" id="area-sigla" name="sigla" maxlength="20" value="{{ old('sigla',$area['sigla']??'') }}" @error('sigla') aria-invalid="true" aria-describedby="dc1-erro-sigla" @enderror>@include('disciplinas._erro',['campo'=>'sigla'])</div>
    <div class="field"><label for="area-ativo">Situação</label><select class="select" id="area-ativo" name="ativo" aria-describedby="area-situacao-ajuda{{ $errors->has('ativo')?' dc1-erro-ativo':'' }}" @error('ativo') aria-invalid="true" @enderror><option value="1" @selected((string)old('ativo',$area['ativo']??1)==='1')>Ativa</option><option value="0" @selected((string)old('ativo',$area['ativo']??1)==='0')>Inativa</option></select>@include('disciplinas._erro',['campo'=>'ativo'])<p class="field-help" id="area-situacao-ajuda">Inativar impede novas associações. Disciplinas e vínculos existentes são preservados.</p></div>
  </div>
  @include('disciplinas.areas._disciplinas')
  <div class="dc1-acoes"><button class="btn btn-primary" type="submit" @disabled(session('conflito_dc1'))>{{ $area?'Salvar alterações':'Adicionar área' }}</button><a class="btn btn-ghost" href="{{ $area?route('areas.show',['id'=>$area['id']]+$retorno):route('areas.index',$retorno) }}">Cancelar</a></div>
</form>
@endsection
