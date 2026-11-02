@extends('layouts.app')
@section('title','Conferir destinatário')
@section('content')
<x-pagina titulo="Conferir destinatário"><x-slot:sub>Confira o destino e a seleção antes de preparar a solicitação.</x-slot:sub></x-pagina>
<section class="card card-pad"><h2>Destino: {{ $dados['recebimento']['destino']['nome'] }}</h2>
  <p>Tipo: {{ ['escola'=>'Escola','pessoal'=>'Pessoal','rede'=>'Rede','nucleo'=>'Núcleo'][$dados['recebimento']['destino']['tipo']] ?? 'Espaço' }}.</p>
  <p>Código válido até {{ \Carbon\Carbon::parse($dados['recebimento']['valida_ate'],'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília).</p>
</section>
@include('copias._materiais',['materiais'=>$dados['previa']['materiais']])
<form method="POST" action="{{ route('copias.material.criar') }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="_ctx" value="{{ \App\Support\ContextoWeb::id() }}">
  <input type="hidden" name="codigo" value="{{ $codigo }}"><input type="hidden" name="chave" value="{{ $chave }}">
  <input type="hidden" name="espaco_id" value="{{ $dados['previa']['espaco_id'] }}">
  <input type="hidden" name="destino_digest" value="{{ $dados['recebimento']['destino_digest'] }}">
  <input type="hidden" name="conteudo_digest" value="{{ $dados['previa']['conteudo_digest'] }}">
  @foreach($dados['previa']['materiais'] as $item)<input type="hidden" name="provas[]" value="{{ $item['origem_id'] }}">@endforeach
  <p><label><input type="checkbox" name="confirmado" value="1" required> Conferi o destino e os materiais acima.</label></p>
  <p>A solicitação ainda precisará da revisão dos textos e das autorizações de saída e entrada.</p>
  <button class="btn btn-primary" type="submit">Preparar solicitação</button>
</form>
@endsection
