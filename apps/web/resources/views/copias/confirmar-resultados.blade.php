@extends('layouts.app')
@section('title','Conferir pacote e destino')
@section('content')
<x-pagina titulo="Conferir pacote e destino"><x-slot:sub>Destino: {{ $dados['recebimento']['destino']['nome'] }} · {{ $dados['recebimento']['destino']['tipo']==='escola'?'Escolar':'Pessoal' }}</x-slot:sub></x-pagina>
<p>Confira todo o conteúdo antes de preservá-lo na solicitação. Isso ainda não autoriza a saída nem a entrada.</p>
@include('copias._pacote-resultados',['pacote'=>$dados['pacote']])
<form method="POST" action="{{ route('copias.resultado.criar') }}" class="card card-pad" style="margin-top:20px;">
  @csrf <x-revisao-acesso />
  <input type="hidden" name="codigo" value="{{ $codigo }}"><input type="hidden" name="chave" value="{{ $chave }}"><input type="hidden" name="espaco_id" value="{{ $dados['espaco_id'] }}">
  <input type="hidden" name="conteudo_digest" value="{{ $dados['conteudo_digest'] }}"><input type="hidden" name="destino_digest" value="{{ $dados['recebimento']['destino_digest'] }}">
  @foreach($dados['notas'] as $id)<input type="hidden" name="notas[]" value="{{ $id }}">@endforeach
  @foreach($dados['dependencias'] as $grupo=>$ids)
  @foreach($ids as $id)<input type="hidden" name="dependencias[{{ $grupo }}][]" value="{{ $id }}">@endforeach
  @endforeach
  <p>Código válido até {{ \Carbon\Carbon::parse($dados['recebimento']['valida_ate'],'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília).</p>
  <p><label><input type="checkbox" name="confirmado" value="1" required> Conferi o destino e os resultados, respostas e materiais exibidos.</label></p>
  <p><label><input type="checkbox" name="dependencias_confirmadas" value="1" required> Confirmo a inclusão das provas, alunos e turmas listados.</label></p>
  <p><label><input type="checkbox" name="legado_reconhecido" value="1" required> Reconheço que a versão original não está comprovada e que o material observado não será tratado como gabarito histórico.</label></p>
  <button class="btn btn-primary" type="submit">Preservar pacote na solicitação</button>
</form>
@endsection
