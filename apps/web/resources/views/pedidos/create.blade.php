@extends('layouts.app')
@section('title','Abrir protocolo')
@include('pedidos._estilo')
@section('breadcrumb')<a href="{{ route('pedidos.index',[]) }}">Pedidos de titulares</a><span class="sep">/</span><span>Abrir protocolo</span>@endsection
@section('content')
@php($ctx=\App\Support\ContextoWeb::id())
<x-pagina titulo="Abrir protocolo" subtitulo="Designe o atendimento a um responsável. Isso não concede acesso aos dados escolares." />
<section class="card card-pad pedidos-bloco">
<h2>1. Escolha a instituição</h2>
<form class="pedidos-form" method="GET" action="{{ route('pedidos.create') }}">

<div class="form-group"><label for="cliente">Cliente</label><select class="input" id="cliente" name="cliente_id" required>
<option value="">Selecione</option>
@if($opcoes['cliente'])<option value="{{ $opcoes['cliente']['id'] }}" selected>{{ $opcoes['cliente']['nome'] }}</option>@endif
@foreach($opcoes['clientes']['data'] as $c)@if(($opcoes['cliente']['id'] ?? null)!==$c['id'])<option value="{{ $c['id'] }}">{{ $c['nome'] }}</option>@endif @endforeach
</select></div><button class="btn btn-outline">Carregar espaços</button>
</form>
</section>
<section class="card card-pad pedidos-bloco"><h2>Opções disponíveis</h2><p>Se necessário, navegue pelas opções antes de preencher o relato.</p>
@foreach(['clientes'=>'Clientes','espacos'=>'Espaços','responsaveis'=>'Responsáveis'] as $grupo=>$rotulo)
@if($opcoes[$grupo])
<nav class="pedidos-acoes" aria-label="Paginação de {{ $rotulo }}"><span>{{ $rotulo }}: página {{ $opcoes[$grupo]['current_page'] }} de {{ $opcoes[$grupo]['last_page'] }}</span>
@foreach([-1=>'Anterior',1=>'Próxima'] as $delta=>$texto)
@php($pagina=$opcoes[$grupo]['current_page']+$delta)
@if($pagina>=1 && $pagina<=$opcoes[$grupo]['last_page'])<a href="{{ route('pedidos.create',array_merge($filtros,[$grupo.'_page'=>$pagina])) }}">{{ $texto }}</a>@endif
@endforeach</nav>
@endif
@endforeach
</section>
@if($opcoes['cliente'])
<section class="card card-pad pedidos-bloco"><h2>2. Registre o pedido · {{ $opcoes['cliente']['nome'] }}</h2>
<form class="pedidos-form" method="POST" action="{{ route('pedidos.store') }}">
@include('pedidos._envio')
<input type="hidden" name="cliente_id" value="{{ $opcoes['cliente']['id'] }}">
<div class="form-group"><label for="espaco">Espaço</label><select class="input" id="espaco" name="espaco_id" required><option value="">Selecione</option>@foreach($opcoes['espacos']['data'] as $e)<option value="{{ $e['id'] }}">{{ $e['nome'] }}</option>@endforeach</select></div>
<div class="form-group"><label for="responsavel">Responsável pelo atendimento</label><select class="input" id="responsavel" name="responsavel_acesso_id" required><option value="">Selecione</option>@foreach($opcoes['responsaveis']['data'] as $a)<option value="{{ $a['id'] }}">{{ $a['nome'] }} · {{ \App\Support\Acesso::rotulo($a['perfil']) }}</option>@endforeach</select></div>
<div class="form-group"><label for="tipo">Tipo de pedido</label><select class="input" id="tipo" name="tipo">@foreach($tipos as $valor=>$rotulo)<option value="{{ $valor }}">{{ $rotulo }}</option>@endforeach</select></div>
<div class="form-group"><label for="identificacao">Identificação declarada</label><input class="input" id="identificacao" name="identificacao_declarada" required maxlength="200" aria-describedby="identificacao-ajuda"><p id="identificacao-ajuda">Use somente o necessário para localizar o pedido. A identidade ainda será conferida.</p></div>
<div class="form-group"><label for="relato">Relato do pedido</label><textarea class="input" id="relato" name="relato" required maxlength="2000"></textarea></div>
@include('pedidos._confirmacao',['sufixo'=>'abertura'])
<button class="btn btn-primary">Registrar e atribuir</button>
</form></section>
@endif
@endsection
