@extends('layouts.app')
@section('title','Conferir envio')
@section('content')
<x-pagina titulo="Conferir envio" subtitulo="O resultado do envio precisa de atenção." />
<section class="card card-pad">
@if(in_array($status,[503,429]))
<p>Não foi possível confirmar o resultado. Você pode repetir exatamente o mesmo envio, sem criar uma nova operação.</p>
<form method="POST" action="{{ $acao==='abrir'?route('pedidos.store'):route('pedidos.alterar',['id'=>$id,'acao'=>$acao]) }}">
@include('pedidos._envio')<input type="hidden" name="repetir" value="1"><input type="hidden" name="confirmado" value="1"><button class="btn btn-primary">Repetir o mesmo envio</button></form>
@elseif($status===409)<p>O protocolo ou o envio mudou. Consulte a versão atual antes de continuar.</p>
@else<p>O envio não foi aceito. Confira os campos e consulte o protocolo antes de preencher outro formulário.</p>@endif
<p><a href="{{ $id?route('pedidos.show',['id'=>$id]):route('pedidos.index',[]) }}">Consultar pedidos</a></p>
</section>
@endsection
