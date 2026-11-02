@extends('layouts.app')
@section('title', 'Cópias entre espaços')
@section('breadcrumb')<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Cópias</span>@endsection
@section('content')
@php
  $lista=$dados['lista'];
  $tipos=['preparacao_material'=>'Material de prova','cadastro_minimo'=>'Cadastro mínimo','resultados_identificados'=>'Histórico de resultados'];
  $estados=['aguardando_autorizacao'=>'Aguardando autorizações','aguardando_auditoria'=>'Aguardando confirmação dos registros',
    'aguardando_execucao'=>'Aguardando processamento','em_processamento'=>'Em processamento',
    'aguardando_recibo'=>'Publicado; confirmação dos registros pendente','concluido'=>'Concluído','bloqueado'=>'Bloqueado para revisão'];
  $filtros=['lado'=>$dados['lado'],'espaco_id'=>$dados['espaco_id'],'_ctx'=>\App\Support\ContextoWeb::id()];
@endphp
<x-pagina titulo="Cópias entre espaços"><x-slot:sub>Acompanhe as solicitações do espaço selecionado.</x-slot:sub></x-pagina>
@if(\App\Support\Acesso::pode('copia.autorizar_origem'))<a class="btn btn-primary" href="{{ route('copias.materiais',['_ctx'=>\App\Support\ContextoWeb::id()]) }}">Selecionar materiais</a> <a class="btn btn-secondary" href="{{ route('copias.cadastros',['_ctx'=>\App\Support\ContextoWeb::id()]) }}">Selecionar cadastro</a> <a class="btn btn-secondary" href="{{ route('copias.resultados',['_ctx'=>\App\Support\ContextoWeb::id()]) }}">Selecionar resultados</a>@endif
@if(\App\Support\Acesso::pode('copia.autorizar_destino'))<a class="btn btn-secondary" href="{{ route('copias.material.receber',['_ctx'=>\App\Support\ContextoWeb::id()]) }}">Receber materiais</a> <a class="btn btn-secondary" href="{{ route('copias.cadastro.receber',['_ctx'=>\App\Support\ContextoWeb::id()]) }}">Receber cadastro</a> <a class="btn btn-secondary" href="{{ route('copias.resultado.receber',['_ctx'=>\App\Support\ContextoWeb::id()]) }}">Receber resultados</a>@endif
<form method="GET" action="{{ route('copias.index') }}" class="card card-pad" style="margin-top:20px;">
  <input type="hidden" name="_ctx" value="{{ \App\Support\ContextoWeb::id() }}">
  <div class="form-row-2">
    <div class="field"><label for="espaco_id">Espaço</label><select class="select" id="espaco_id" name="espaco_id" @disabled(!$dados['espacos'])>
      @forelse($dados['espacos'] as $e)<option value="{{ $e['id'] }}" @selected($dados['espaco_id']===$e['id'])>{{ $e['nome'] }}</option>
      @empty<option>Nenhum espaço disponível</option>@endforelse
    </select></div>
    <div class="field"><label for="lado">Direção</label><select class="select" id="lado" name="lado">
      @foreach($dados['lados'] as $lado)<option value="{{ $lado }}" @selected($dados['lado']===$lado)>{{ $lado==='origem'?'Saída deste espaço':'Entrada neste espaço' }}</option>@endforeach
    </select></div>
  </div>
  <button class="btn btn-secondary" type="submit">Atualizar acompanhamento</button>
</form>
@if($dados['lado']==='destino')<p style="margin-top:16px;">As entradas aparecem aqui depois que este espaço registra sua decisão.</p>@endif
<div class="card card-pad" style="margin-top:20px;overflow-x:auto;">
  <table class="table"><caption style="text-align:left;margin-bottom:16px;">{{ $lista['total'] }} solicitação(ões) neste acompanhamento</caption>
    <thead><tr><th scope="col">Conteúdo</th><th scope="col">Criado em</th><th scope="col">Situação</th><th scope="col">Publicação</th></tr></thead>
    <tbody>@forelse($lista['data'] as $c)
      <tr><td>{{ $tipos[$c['finalidade']] ?? 'Cópia' }}</td>
        <td>{{ \Carbon\Carbon::parse($c['criado_em'],'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
        <td>{{ $estados[$c['estado']] ?? 'Em acompanhamento' }}
          @if($c['auditoria_pendente'])<div class="text-muted">Há registros aguardando confirmação.</div>@endif
        </td><td>{{ $c['publicados'] }} de {{ $c['selecionados'] }} item(ns)</td></tr>
    @empty<tr><td colspan="4">Nenhuma solicitação encontrada para este espaço e direção.</td></tr>@endforelse</tbody>
  </table>
  @if($lista['last_page']>1)<nav aria-label="Paginação das cópias" style="margin-top:16px;">
    @if($lista['current_page']>1)<a class="btn btn-secondary" href="{{ route('copias.index',array_merge($filtros,['page'=>$lista['current_page']-1])) }}">Anterior</a>@endif
    <span>Página {{ $lista['current_page'] }} de {{ $lista['last_page'] }}</span>
    @if($lista['current_page']<$lista['last_page'])<a class="btn btn-secondary" href="{{ route('copias.index',array_merge($filtros,['page'=>$lista['current_page']+1])) }}">Próxima</a>@endif
  </nav>@endif
</div>
@endsection
