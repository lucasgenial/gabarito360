@extends('layouts.app')
@section('title','Pedidos de titulares')
@include('pedidos._estilo')
@section('breadcrumb')<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Pedidos de titulares</span>@endsection
@section('content')
@php($ctx=\App\Support\ContextoWeb::id())
<x-pagina titulo="Pedidos de titulares" subtitulo="Acompanhe somente os protocolos atribuídos ao seu contexto de atendimento." />
@if(\App\Support\Acesso::pode('pedido.abrir'))<div class="pedidos-bloco"><a class="btn btn-primary" href="{{ route('pedidos.create',[]) }}">Abrir protocolo</a></div>@endif
<div class="pedidos-grade">
@forelse($lista['data'] as $p)
<section class="card card-pad pedidos-bloco">
<h2>{{ $tipos[$p['tipo']] ?? 'Pedido' }}</h2>
<p>{{ $estados[$p['situacao']] ?? 'Situação indisponível' }}</p>
<p>{{ $p['cliente_nome'] }} · {{ $p['espaco_nome'] }}</p>
<p class="pedidos-ref">Protocolo {{ $p['referencia'] }}</p>
<a class="btn btn-outline" href="{{ route('pedidos.show',['id'=>$p['referencia']]) }}">Abrir atendimento</a>
</section>
@empty
<section class="card card-pad"><h2>Nenhum pedido atribuído</h2><p>Os protocolos designados para este acesso aparecerão aqui.</p></section>
@endforelse
</div>
<nav class="pedidos-acoes" aria-label="Paginação dos pedidos">
@if($lista['current_page']>1)<a class="btn btn-outline" href="{{ route('pedidos.index',['page'=>$lista['current_page']-1]) }}">Anterior</a>@endif
<span>Página {{ $lista['current_page'] }} de {{ $lista['last_page'] }}</span>
@if($lista['current_page']<$lista['last_page'])<a class="btn btn-outline" href="{{ route('pedidos.index',['page'=>$lista['current_page']+1]) }}">Próxima</a>@endif
</nav>
@endsection
