@extends('layouts.app')
@section('title','Resultado da exportação')
@include('pedidos._estilo')
@section('content')
<x-pagina titulo="Resultado da operação" subtitulo="Consulte o estado atual antes de realizar outra operação." />
<section class="card card-pad pedidos-bloco">
@if($resultado['status']===200)
<h2>{{ ['conceder'=>'Autorização registrada','revogar'=>'Autorização revogada','gerar'=>'Arquivo gerado'][$acao] }}</h2>
<p class="pedidos-ref">Referência: {{ $resultado['recibo']['referencia'] }}</p>
@if(isset($resultado['recibo']['versao']))<p>Versão: {{ $resultado['recibo']['versao'] }}</p>@endif
@if($acao==='conceder')<p>Forneça esta referência ao responsável interno pelo protocolo. Ela não dispensa autenticação nem concede acesso por si só.</p>@endif
@if($acao==='gerar')<a class="btn btn-primary" href="{{ route('pedidos.exportacoes',['referencia'=>$resultado['recibo']['referencia']]) }}">Conferir arquivo</a>@endif
@elseif(in_array($resultado['status'],[503,429]))
<p>Não foi possível confirmar o resultado. Repita o mesmo envio para evitar duplicação.</p>
<form method="POST" action="{{ route('pedidos.exportacoes.enviar',['acao'=>$acao]) }}">@include('pedidos._envio')
<input type="hidden" name="referencia" value="{{ $referencia }}"><input type="hidden" name="repetir" value="1"><input type="hidden" name="confirmado" value="1"><button class="btn btn-primary">Repetir o mesmo envio</button></form>
@else<p>Operação não aceita. Consulte as referências e a validade antes de preencher um novo formulário.</p>@endif
<p><a href="{{ route('pedidos.exportacoes',[]) }}">Voltar às exportações</a></p></section>
@endsection
