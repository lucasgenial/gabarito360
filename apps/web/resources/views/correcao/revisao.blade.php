@extends('layouts.app')

@section('tela', 'tela-revisao')

@section('title', 'Revisar leituras — ' . $prova['titulo'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('correcao.show', $prova['id']) }}">{{ $prova['titulo'] }}</a><span class="sep">/</span>
<span>Revisar leituras</span>
@endsection

@section('content')

<x-pagina titulo="Revisar leituras">
  <x-slot:sub>{{ $prova['disciplina'] }}</x-slot:sub>
</x-pagina>

<div class="rev-estacao" id="rev-estacao">
  <div class="card card-pad rev-cartao"><div id="rev-conteudo"></div></div>
  <aside class="card rev-documento-painel" id="rev-documento-painel" aria-labelledby="rev-painel-titulo">
    <header class="rev-modal-cabecalho"><div><h2 id="rev-painel-titulo">Cartão-resposta completo</h2><p id="rev-painel-contexto"></p></div></header>
    <div id="rev-documento-desktop" class="rev-documento-destino"><div id="rev-leitor"></div></div>
  </aside>
</div>
<dialog id="rev-cartao-modal" class="rev-cartao-modal" aria-labelledby="rev-cartao-titulo" aria-describedby="rev-cartao-contexto">
  <header class="rev-modal-cabecalho">
    <div><h2 id="rev-cartao-titulo">Cartão-resposta</h2><p id="rev-cartao-contexto"></p></div>
    <button type="button" class="btn btn-secondary" id="rev-cartao-fechar" autofocus aria-label="Fechar cartão">Fechar</button>
  </header>
  <div id="rev-documento-mobile" class="rev-documento-destino"></div>
</dialog>

@endsection

@push('scripts')
<script src="{{ asset('js/leitor-cartao.js') }}?v={{ filemtime(public_path('js/leitor-cartao.js')) }}"></script>
@php($g360Config = ['itens'=>$itens,'alternativas'=>$prova['num_alternativas']??5,'voltar'=>\App\Support\NavegacaoWeb::destino('correcao.show',[$prova['id']]),'csrf'=>csrf_token()])
<script type="application/json" id="correcao-revisao-config">@json($g360Config)</script>
<script src="{{ asset('js/correcao-revisao.js') }}?v={{ filemtime(public_path('js/correcao-revisao.js')) }}"></script>
@endpush
