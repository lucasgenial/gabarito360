@extends('layouts.app')
@section('title',$p?'Configurar turmas e disciplinas':'Nova prova')
@section('tela','tela-prova-curricular')
@section('breadcrumb')<a href="{{ route('provas.index') }}">Provas</a><span class="sep">/</span>@if($p)<a href="{{ route('provas.show',$p['id']) }}">{{ $p['titulo'] }}</a><span class="sep">/</span>@endif<span>{{ $p?'Configurar':'Nova prova' }}</span>@endsection
@push('styles')<link rel="stylesheet" href="@ativo('css/disciplinas.css')"><link rel="stylesheet" href="@ativo('css/prova-curricular.css')">@endpush
@section('content')
<x-pagina :titulo="$p?'Configurar turmas e disciplinas':'Criar prova'"><x-slot:sub>{{ $p?$p['titulo']:'Selecione as turmas e as disciplinas antes de preencher os dados da prova.' }}</x-slot:sub></x-pagina>
<form method="POST" action="{{ $p?route('provas.curriculo.salvar',$p['id']):route('provas.store') }}" class="pc-form dc1-stack" data-prova-curriculo>
  @csrf <x-revisao-acesso />

  @if($errors->any() || $mensagens->any())<x-aviso tipo="erro"><strong>Confira o preenchimento.</strong><ul>@foreach(array_unique(array_merge($errors->all(),$mensagens->all())) as $erro)<li>{{ $erro }}</li>@endforeach</ul></x-aviso>@endif
  @include('provas.curriculo._editor')
  @unless($p) @include('provas.curriculo._dados') @endunless
  <div class="pc-acoes pc-final">
    <button class="btn btn-primary" type="submit" data-curriculo-salvar @disabled(!$pronto)>{{ $p?'Salvar turmas e disciplinas':'Criar prova e preencher gabarito' }}</button>
    <a class="btn btn-ghost" href="{{ $p?route('provas.show',['id'=>$p['id']]):route('provas.index',[]) }}">Cancelar</a>
  </div>
</form>
@endsection
@push('scripts')<script src="@ativo('js/prova-curricular.js')" defer></script>@endpush
