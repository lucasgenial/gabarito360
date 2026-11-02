@extends('layouts.app')
@section('title','Acervo de resultados')
@section('content')
<x-pagina titulo="Acervo de resultados"><x-slot:sub>Conteúdo publicado em {{ \Carbon\Carbon::parse($dados['publicado_em'],'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília).</x-slot:sub></x-pagina>
<p>Este pacote preserva o conteúdo recebido. Alterações posteriores na origem não atualizam o acervo.</p>
@include('copias._pacote-resultados',['pacote'=>$dados['pacote']])
<a class="btn btn-secondary" href="{{ route('copias.operacao',['id'=>$dados['lote_id'],'lado'=>'destino','_ctx'=>\App\Support\ContextoWeb::id()]) }}">Voltar à operação</a>
@endsection
