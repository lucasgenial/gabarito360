@extends('layouts.app')
@php($navegacaoEstrutura=true)
@section('title', 'Carregando conteúdo')
@push('styles')
<link rel="stylesheet" href="@ativo('css/estado-tela.css')">
@endpush
@section('content')
<div data-navegacao-redirecionar data-recurso="{{ json_encode($destino,JSON_THROW_ON_ERROR) }}">
@include('navegacao._esqueleto')
<noscript><link rel="stylesheet" href="@ativo('css/estado-sem-javascript.css')">
<x-estado-tela titulo="Ative o JavaScript para continuar" mensagem="Abra o registro pela lista. Não reenvie a operação anterior."><a class="btn btn-primary" href="/app/painel">Voltar ao painel</a></x-estado-tela>
</noscript></div>
@endsection