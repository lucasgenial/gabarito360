@extends('layouts.app')
@section('title','Protocolo registrado')
@section('content')
<x-pagina titulo="Protocolo registrado" subtitulo="O responsável designado encontrará o pedido na própria lista de atendimento." />
<section class="card card-pad"><p style="overflow-wrap:anywhere">Referência: {{ $recibo['referencia'] }}</p><p>Este recibo confirma a abertura. Ele não concede acesso ao conteúdo do pedido.</p>
<a class="btn btn-primary" href="{{ route('pedidos.index',[]) }}">Meus pedidos atribuídos</a></section>
@endsection
