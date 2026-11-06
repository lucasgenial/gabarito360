@extends('layouts.app')
@section('title','Prévia do cadastro')
@section('content')
<x-pagina titulo="Prévia do cadastro"><x-slot:sub>Simulação para destino {{ $dados['tipo_destino']==='escola'?'escolar':'pessoal' }}.</x-slot:sub></x-pagina>
<x-aviso>Esta prévia não identifica um destinatário, não autoriza nem inicia uma cópia. A solicitação ainda exigirá evidência aplicável e decisões separadas de origem e destino.</x-aviso>
@include('copias._cadastro')
<form method="POST" action="{{ route('copias.cadastro.preparar') }}" class="card card-pad" style="margin-top:20px;">
  @csrf <x-revisao-acesso /><input type="hidden" name="espaco_id" value="{{ $dados['origem_espaco_id'] }}">
  @foreach($dados['turmas'] as $t)<input type="hidden" name="turmas[]" value="{{ $t['origem_id'] }}">@endforeach
  @foreach($dados['alunos'] as $a)<input type="hidden" name="alunos[]" value="{{ $a['origem_id'] }}">@endforeach
  <div class="field"><label for="codigo">Código de cadastro fornecido pelo destinatário</label><input class="input" id="codigo" name="codigo" required minlength="64" maxlength="64" autocomplete="off" spellcheck="false"></div>
  @include('copias._codigo-destino',['modalidade'=>'cadastro'])
  <button class="btn btn-primary" type="submit">Conferir destino do cadastro</button>
</form>
<a class="btn btn-secondary" style="margin-top:20px;" href="{{ route('copias.cadastros',['espaco_id'=>$dados['origem_espaco_id'],'turma_id'=>$dados['turmas'][0]['origem_id']]) }}">Voltar e fazer nova seleção</a>
@endsection
