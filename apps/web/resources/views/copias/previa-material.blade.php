@extends('layouts.app')
@section('title','Prévia dos materiais')
@section('breadcrumb')<a href="{{ route('copias.index') }}">Cópias</a><span class="sep">/</span><span>Prévia dos materiais</span>@endsection
@section('content')
<x-pagina titulo="Prévia dos materiais"><x-slot:sub>Confira os dados incluídos na seleção.</x-slot:sub></x-pagina>
<x-aviso>Esta consulta não autoriza nem inicia uma cópia. Os textos precisam de revisão antes das decisões de origem e destino.</x-aviso>
@include('copias._materiais',['materiais'=>$dados['materiais']])
<form method="POST" action="{{ route('copias.material.preparar') }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="_ctx" value="{{ \App\Support\ContextoWeb::id() }}">
  <input type="hidden" name="espaco_id" value="{{ $dados['espaco_id'] }}">
  @foreach($dados['materiais'] as $item)<input type="hidden" name="provas[]" value="{{ $item['origem_id'] }}">@endforeach
  <div class="field"><label for="codigo">Código fornecido pelo destinatário</label><input class="input" name="codigo" id="codigo" required minlength="64" maxlength="64" autocomplete="off" spellcheck="false"></div>
  @include('copias._codigo-destino',['modalidade'=>'material'])
  <button class="btn btn-primary" type="submit">Conferir destinatário</button>
</form>
<a class="btn btn-secondary" style="margin-top:20px;" href="{{ route('copias.materiais',['espaco_id'=>$dados['espaco_id'],'_ctx'=>\App\Support\ContextoWeb::id()]) }}">Voltar à seleção</a>
@endsection
