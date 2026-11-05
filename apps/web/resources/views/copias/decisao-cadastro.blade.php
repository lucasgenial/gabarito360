@extends('layouts.app')
@section('title','Decisão sobre cadastro')
@section('content')
<x-pagina titulo="Decisão sobre cadastro"><x-slot:sub>Origem: {{ $dados['origem_nome'] }} · Destino: {{ $dados['destino_nome'] }}</x-slot:sub></x-pagina>
<x-aviso>A evidência e as decisões de saída e entrada são ações separadas. Esta etapa não inicia a cópia.</x-aviso>
@include('copias._cadastro')
@if($dados['lado']==='destino' && $dados['conciliacao'])
<section class="card card-pad" style="margin-top:20px;">
  <h2>Conferência no destino</h2>
  <p>Os itens seguem a ordem da prévia: primeiro a turma e depois os alunos. Registros já existentes não serão sobrescritos.</p>
  @php($rotulos=['novo'=>'Novo registro','reutilizar_mapa'=>'Reutilizar cópia já vinculada','conflito_matricula'=>'Bloqueado: matrícula já existente','mapa_invalido'=>'Bloqueado: vínculo da cópia inválido','dependencia_incompativel'=>'Bloqueado: turma incompatível'])
  <ol>@foreach($dados['conciliacao']['itens'] as $i)<li>{{ $i['entidade']==='turma'?'Turma':'Aluno' }} — {{ $rotulos[$i['codigo']] ?? 'Bloqueado para conferência' }}</li>@endforeach</ol>
  @if(!$dados['conciliacao']['sem_bloqueios'])<p>Existem conflitos. A entrada não pode ser autorizada; revise os cadastros pelos procedimentos próprios, sem substituir dados automaticamente.</p>@endif
</section>
@endif
@if($dados['lado']==='origem' && !$dados['evidencia_registrada'])
<form method="POST" action="{{ route('copias.cadastro.evidencia',['id'=>$dados['lote_id']]) }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="conteudo_digest" value="{{ $dados['conteudo_digest'] }}">
  <input type="hidden" name="valida_ate" value="{{ $validade }}">
  <h2>Evidência aplicável à transferência</h2>
  <div class="field"><label for="documento">Texto da evidência</label><textarea class="input" id="documento" name="documento" rows="8" maxlength="65536" required autocomplete="off"></textarea></div>
  <p>Informe o conteúdo do documento que ampara esta seleção e este destino; um link sozinho não basta. O texto ficará restrito e não será exibido ao destinatário.</p>
  <p>Validade registrada até {{ \Carbon\Carbon::parse($validade,'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília).</p>
  <p><label><input type="checkbox" name="confirmado" value="1" required> Revisei a evidência e confirmo sua aplicabilidade ao cadastro e ao destino indicados.</label></p>
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
  <p>O destinatário deve informar esta referência em “Receber cadastro”, no próprio contexto. Nenhuma mensagem é enviada automaticamente.</p>@endif
</section>
@elseif(!$dados['tem_pendencias'] && ($dados['lado']==='origem' || $dados['conciliacao']['sem_bloqueios']))
<form method="POST" action="{{ route('copias.cadastro.aprovar',['id'=>$dados['lote_id']]) }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="lado" value="{{ $dados['lado'] }}">
  <input type="hidden" name="conteudo_digest" value="{{ $dados['conteudo_digest'] }}"><input type="hidden" name="valida_ate" value="{{ $validade }}">
  @if($dados['lado']==='destino')<input type="hidden" name="conciliacao_digest" value="{{ $dados['conciliacao']['digest'] }}">@endif
  <p>Autorização válida até {{ \Carbon\Carbon::parse($validade,'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília).</p>
  <p><label><input type="checkbox" name="confirmado" value="1" required> Conferi o cadastro{{ $dados['lado']==='destino'?' e a conferência no destino':'' }} e autorizo a {{ $dados['lado']==='origem'?'saída para o destino indicado':'entrada no meu espaço' }}.</label></p>
  <button class="btn btn-primary" type="submit">{{ $dados['lado']==='origem'?'Autorizar saída do cadastro':'Autorizar entrada do cadastro' }}</button>
</form>
@endif
<p style="margin-top:20px;"><a class="btn btn-secondary" href="{{ route('copias.operacao',['id'=>$dados['lote_id'],'lado'=>$dados['lado']]) }}">Acompanhar operação da cópia</a></p>
@endsection
