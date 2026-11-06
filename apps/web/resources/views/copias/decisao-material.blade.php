@extends('layouts.app')
@section('title','Decisão sobre materiais')
@section('content')
<x-pagina titulo="Decisão sobre materiais"><x-slot:sub>Origem: {{ $dados['origem_nome'] }} · Destino: {{ $dados['destino_nome'] }}</x-slot:sub></x-pagina>
<x-aviso>As decisões de saída e entrada são separadas. Esta etapa registra as autorizações; a cópia ainda não será iniciada.</x-aviso>
@include('copias._materiais',['materiais'=>$dados['itens']])
@if($dados['lado']==='origem' && !$dados['revisado'])
<form method="POST" action="{{ route('copias.material.revisar',['id'=>$dados['lote_id']]) }}" class="card card-pad" style="margin-top:20px;">
  @csrf <x-revisao-acesso />
  <input type="hidden" name="conteudo_digest" value="{{ $dados['conteudo_digest'] }}">
  <h2>Revisão dos textos</h2><p>Confira títulos, disciplina, série e gabaritos. Não inclua nomes, matrículas ou outros dados pessoais nos materiais.</p>
  <p><label><input type="checkbox" name="sem_dados_pessoais" value="1" required> Revisei os materiais e confirmo que não contêm dados pessoais.</label></p>
  <button class="btn btn-primary" type="submit">Registrar revisão</button>
</form>
@elseif(!$dados['aprovado'])
<form method="POST" action="{{ route('copias.material.aprovar',['id'=>$dados['lote_id']]) }}" class="card card-pad" style="margin-top:20px;">
  @csrf <x-revisao-acesso />
  <input type="hidden" name="lado" value="{{ $dados['lado'] }}"><input type="hidden" name="conteudo_digest" value="{{ $dados['conteudo_digest'] }}">
  <input type="hidden" name="valida_ate" value="{{ $validade }}">
  <p>Autorização válida até {{ \Carbon\Carbon::parse($validade,'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília).</p>
  <p><label><input type="checkbox" name="confirmado" value="1" required> Conferi os materiais e autorizo a {{ $dados['lado']==='origem'?'saída para o destino indicado':'entrada no meu espaço' }}.</label></p>
  <button class="btn btn-primary" type="submit">{{ $dados['lado']==='origem'?'Autorizar saída':'Autorizar entrada' }}</button>
</form>
@else
<section class="card card-pad" style="margin-top:20px;">
  <h2>Decisão de {{ $dados['lado']==='origem'?'saída':'entrada' }} registrada</h2>
  <p>Prazo registrado: {{ \Carbon\Carbon::parse($dados['valida_ate'],'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília). A continuidade depende de as duas autorizações permanecerem válidas.</p>
  @if($dados['lado']==='origem')
  <div class="field"><label for="referencia">Referência para compartilhar com o destinatário</label><input class="input" id="referencia" readonly value="{{ $dados['lote_id'] }}"></div>
  <p>O destinatário deve abrir “Receber materiais” no próprio contexto e informar esta referência para conferir e decidir. Nenhuma mensagem é enviada automaticamente.</p>
  @endif
</section>
@endif
<p style="margin-top:20px;"><a class="btn btn-secondary" href="{{ route('copias.operacao',['id'=>$dados['lote_id'],'lado'=>$dados['lado']]) }}">Acompanhar operação da cópia</a></p>
@endsection
