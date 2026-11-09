@extends('layouts.app')
@php($navegacaoEstrutura=true)
@section('title', 'Carregando conteúdo')
@push('styles')
<link rel="stylesheet" href="@ativo('css/estado-tela.css')">
<script src="@ativo('js/estado-tela.js')"></script>
@endpush
@section('content')
<div data-navegacao-estrutura data-consulta="/{{ $meta['consulta'] }}" data-pagina="/{{ $meta['pagina'] }}" data-lista="{{ $meta['lista'] }}" data-exige-selecao="{{ $meta['obrigatorios'] ? '1' : '0' }}">
<div class="estado-pagina" data-entrada-vinculo><x-estado-tela titulo="Abrindo tela…" :carregando="true" /></div>
@include('navegacao._esqueleto')
<noscript><link rel="stylesheet" href="@ativo('css/estado-sem-javascript.css')">
<x-estado-tela titulo="Ative o JavaScript para continuar" mensagem="Ele é necessário para abrir e atualizar os registros. Nenhuma ação foi reenviada.">
<a class="btn btn-primary" href="{{ $meta['lista'] }}">Ir para a lista</a>
@if($meta['lista']!=='/app/painel')<a class="btn btn-secondary" href="/app/painel">Voltar ao painel</a>@endif
</x-estado-tela></noscript>
</div>
@endsection