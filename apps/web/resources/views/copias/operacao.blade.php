@extends('layouts.app')
@section('title','Operação da cópia')
@section('content')
@php
  $ctx=\App\Support\ContextoWeb::id(); $params=['id'=>$dados['lote_id'],'lado'=>$dados['lado'],'_ctx'=>$ctx];
  $estados=['aguardando_autorizacao'=>'Aguardando autorizações','aguardando_auditoria'=>'Aguardando confirmação dos registros',
    'aguardando_execucao'=>'Aguardando processamento','em_processamento'=>'Em processamento',
    'aguardando_recibo'=>'Publicado; confirmação dos registros pendente','concluido'=>'Concluído','bloqueado'=>'Bloqueado para revisão'];
  $tipos=['preparacao_material'=>'Material de prova','cadastro_minimo'=>'Cadastro mínimo','resultados_identificados'=>'Histórico de resultados'];
  $decisoes=['preparacao_material'=>'copias.material.decisao','cadastro_minimo'=>'copias.cadastro.decisao','resultados_identificados'=>'copias.resultado.decisao'];
  $selecao=['preparacao_material'=>'copias.materiais','cadastro_minimo'=>'copias.cadastros','resultados_identificados'=>'copias.resultados'];
@endphp
<x-pagina titulo="Operação da cópia"><x-slot:sub>{{ $tipos[$dados['finalidade']] ?? 'Cópia' }} · {{ $dados['lado']==='origem'?'Saída deste espaço':'Entrada neste espaço' }}</x-slot:sub></x-pagina>
<div class="card card-pad">
  <h2>{{ $estados[$dados['estado']] ?? 'Em acompanhamento' }}</h2>
  <p>{{ $dados['publicados'] }} de {{ $dados['selecionados'] }} item(ns) publicado(s).</p>
  @if($dados['auditoria_pendente'])<p>Há registros aguardando confirmação. Aguarde e atualize o acompanhamento.</p>@endif
  <a class="btn btn-secondary" href="{{ route('copias.operacao',$params) }}">Atualizar situação</a>
  <a class="btn btn-secondary" href="{{ route('copias.index',['lado'=>$dados['lado'],'_ctx'=>$ctx]) }}">Voltar ao acompanhamento</a>
  @if(in_array($dados['estado'],['aguardando_autorizacao','aguardando_auditoria']))
    <a class="btn btn-secondary" href="{{ route($decisoes[$dados['finalidade']],$params) }}">Conferir decisões</a>
  @endif
</div>
@if($dados['execucao_solicitada'])<p>O pedido de processamento já foi registrado. Falhas transitórias são tentadas novamente pelo sistema. Repetir o pedido não duplica a cópia nem reinicia uma solicitação bloqueada.</p>@endif
@if($dados['estado']==='bloqueado')
  <p>A solicitação está bloqueada. Confira a origem e prepare uma nova seleção com novas decisões. Itens já publicados permanecem no destino.</p>
  @if($dados['lado']==='origem')<a class="btn btn-primary" href="{{ route($selecao[$dados['finalidade']],['_ctx'=>$ctx]) }}">Preparar nova solicitação</a>@endif
@endif
@if($dados['pode_solicitar'])
<form method="POST" action="{{ route('copias.solicitar',['id'=>$dados['lote_id']]) }}" class="card card-pad" style="margin-top:20px;">
  @csrf
  <input type="hidden" name="_ctx" value="{{ $ctx }}">
  <p>O sistema conferirá novamente as decisões, o conteúdo e os registros antes de processar. A publicação pode ocorrer em etapas.</p>
  <p><label><input type="checkbox" name="confirmado" value="1" required> Confirmo o pedido de processamento neste destino.</label></p>
  <button class="btn btn-primary" type="submit">{{ $dados['execucao_solicitada']?'Repetir pedido sem duplicar':'Solicitar processamento' }}</button>
</form>
@endif
@if($dados['pode_revogar'])
<form method="POST" action="{{ route('copias.revogar',['id'=>$dados['lote_id']]) }}" class="card card-pad" style="margin-top:20px;">
  @csrf
  <input type="hidden" name="_ctx" value="{{ $ctx }}"><input type="hidden" name="lado" value="{{ $dados['lado'] }}">
  <p>A revogação interrompe o que ainda não foi publicado. Ela não apaga cópias já publicadas; uma publicação em andamento pode terminar antes da revogação.</p>
  <p><label><input type="checkbox" name="confirmado" value="1" required> Confirmo a revogação por este espaço e compreendo que ela não desfaz publicações.</label></p>
  <button class="btn btn-secondary" type="submit">Revogar continuidade da cópia</button>
</form>
@endif
@if($dados['acervo_disponivel'])<p><a class="btn btn-primary" href="{{ route('copias.acervo',['id'=>$dados['lote_id'],'_ctx'=>$ctx]) }}">Consultar acervo publicado</a></p>@endif
@if($dados['lado']==='destino' && $dados['publicados']>0 && $dados['finalidade']!=='resultados_identificados')<p>Os materiais e cadastros publicados estão disponíveis nas áreas correspondentes deste espaço.</p>@endif
@endsection
