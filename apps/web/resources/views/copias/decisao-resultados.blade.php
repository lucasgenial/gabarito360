@extends('layouts.app')
@section('title','Decisão sobre resultados')
@section('content')
<x-pagina titulo="Decisão sobre resultados"><x-slot:sub>Origem: {{ $dados['origem_nome'] }} · Destino: {{ $dados['destino_nome'] }}</x-slot:sub></x-pagina>
<x-aviso>A evidência e as decisões de saída e entrada são ações separadas. Esta etapa não inicia a cópia.</x-aviso>
@include('copias._pacote-resultados',['pacote'=>$dados['pacote']])
@if($dados['lado']==='origem' && !$dados['evidencia_registrada'])
<form method="POST" action="{{ route('copias.resultado.evidencia',['id'=>$dados['lote_id']]) }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="conteudo_digest" value="{{ $dados['conteudo_digest'] }}">
  <input type="hidden" name="valida_ate" value="{{ $validade }}">
  <h2>Evidência aplicável à transferência</h2>
  <div class="field"><label for="documento">Texto da evidência</label><textarea class="input" id="documento" name="documento" rows="8" maxlength="65536" required autocomplete="off"></textarea></div>
  <div class="field"><label for="guarda">Guarda definida no contrato</label><textarea class="input" id="guarda" name="guarda" rows="3" maxlength="2000" required autocomplete="off"></textarea></div>
  <div class="field"><label for="preservacao">Condições de preservação excepcional</label><textarea class="input" id="preservacao" name="preservacao" rows="3" maxlength="2000" required autocomplete="off"></textarea></div>
  <p>Informe os termos aplicáveis. O prazo desta autorização não substitui o prazo de guarda contratual.</p>
  <p>Informe o conteúdo do documento que ampara esta seleção e este destino; um link sozinho não basta. O texto ficará restrito e não será exibido ao destinatário.</p>
  <p>Validade desta evidência para autorizar a transferência até {{ \Carbon\Carbon::parse($validade,'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília).</p>
  <p><label><input type="checkbox" name="confirmado" value="1" required> Revisei a evidência e confirmo sua aplicabilidade ao pacote e ao destino indicados.</label></p>
  <p><label><input type="checkbox" name="legado_reconhecido" value="1" required> Reconheço o legado sem versão original comprovada e que o material observado não comprova o gabarito original.</label></p>
  <button class="btn btn-primary" type="submit">Registrar evidência</button>
</form>
@elseif(!$dados['evidencia_valida'])
<x-aviso tipo="erro">A evidência registrada não está mais válida. Prepare uma nova solicitação após revisar as condições.</x-aviso>
@elseif($dados['decisao_registrada'])
<section class="card card-pad" style="margin-top:20px;">
  <h2>Decisão de {{ $dados['lado']==='origem'?'saída':'entrada' }} registrada</h2>
  <p>Prazo: {{ \Carbon\Carbon::parse($dados['valida_ate'],'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília).</p>
  @if(!$dados['decisao_valida'])<p>A decisão não está mais válida. Prepare uma nova solicitação.</p>
  @elseif($dados['lado']==='origem')<div class="field"><label for="referencia">Referência para compartilhar com o destinatário</label><input class="input" id="referencia" readonly value="{{ $dados['lote_id'] }}"></div>
  <p>O destinatário deve informar esta referência em “Receber resultados”, no próprio contexto. Nenhuma mensagem é enviada automaticamente.</p>@endif
</section>
@else
<form method="POST" action="{{ route('copias.resultado.aprovar',['id'=>$dados['lote_id']]) }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="lado" value="{{ $dados['lado'] }}">
  <input type="hidden" name="conteudo_digest" value="{{ $dados['conteudo_digest'] }}"><input type="hidden" name="valida_ate" value="{{ $validade }}">

  <p>Autorização válida até {{ \Carbon\Carbon::parse($validade,'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília).</p>
  <p><label><input type="checkbox" name="confirmado" value="1" required> Conferi o pacote completo e autorizo a {{ $dados['lado']==='origem'?'saída para o destino indicado':'entrada no meu espaço' }}.</label></p>
  <p><label><input type="checkbox" name="legado_reconhecido" value="1" required> Reconheço o legado sem versão original comprovada e que o material observado não comprova o gabarito original.</label></p>
  <button class="btn btn-primary" type="submit">{{ $dados['lado']==='origem'?'Autorizar saída dos resultados':'Autorizar entrada dos resultados' }}</button>
</form>
@endif
<p style="margin-top:20px;"><a class="btn btn-secondary" href="{{ route('copias.operacao',['id'=>$dados['lote_id'],'lado'=>$dados['lado']]) }}">Acompanhar operação da cópia</a></p>
@endsection
