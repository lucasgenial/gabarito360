@extends('layouts.app')
@section('title','Conferir destino do cadastro')
@section('content')
<x-pagina titulo="Conferir destino do cadastro"><x-slot:sub>Destino: {{ $dados['recebimento']['destino']['nome'] }}</x-slot:sub></x-pagina>
<x-aviso>Tipo real do destino: {{ $dados['recebimento']['destino']['tipo']==='escola'?'escolar':'pessoal' }}. Confira novamente os campos abaixo. Preparar a solicitação não autoriza a transferência.</x-aviso>
@include('copias._cadastro',['dados'=>$dados['previa']])
<form method="POST" action="{{ route('copias.cadastro.criar') }}" class="card card-pad" style="margin-top:20px;">
  @csrf
  <input type="hidden" name="codigo" value="{{ $codigo }}"><input type="hidden" name="chave" value="{{ $chave }}">
  <input type="hidden" name="espaco_id" value="{{ $dados['previa']['origem_espaco_id'] }}">
  <input type="hidden" name="destino_digest" value="{{ $dados['recebimento']['destino_digest'] }}"><input type="hidden" name="conteudo_digest" value="{{ $dados['previa']['conteudo_digest'] }}">
  @foreach($dados['previa']['turmas'] as $t)<input type="hidden" name="turmas[]" value="{{ $t['origem_id'] }}">@endforeach
  @foreach($dados['previa']['alunos'] as $a)<input type="hidden" name="alunos[]" value="{{ $a['origem_id'] }}">@endforeach
  <p>Código válido até {{ \Carbon\Carbon::parse($dados['recebimento']['valida_ate'],'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília).</p>
  <p><label><input type="checkbox" name="confirmado" value="1" required> Conferi o destino e o cadastro que será incluído.</label></p>
  <button class="btn btn-primary" type="submit">Preparar solicitação de cadastro</button>
</form>
@endsection
