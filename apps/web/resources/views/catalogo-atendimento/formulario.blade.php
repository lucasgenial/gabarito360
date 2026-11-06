@extends('layouts.app')
@section('tela','tela-catalogo-cadastro')
@include('catalogo-atendimento._assets')
@php $titulo=$modo==='minhas-solicitacoes'?'Solicitar cadastro de instituição':(!empty($e)?'Revisar cadastro institucional':'Cadastrar referência da plataforma');$prefix=$modo==='atendimento'?'catalogo-atendimento':($modo==='gestao'?'catalogo-gestao':'catalogo-pedidos'); @endphp
@section('title',$titulo)
@section('breadcrumb')<a href="{{ route($prefix.'.index') }}">{{ $modo==='minhas-solicitacoes'?'Minhas solicitações':'Instituições' }}</a><span class="sep">/</span><span>{{ !empty($e)?'Revisar cadastro':($modo==='minhas-solicitacoes'?'Nova solicitação':'Nova referência') }}</span>@endsection
@section('content')
<x-pagina :titulo="$titulo"><x-slot:sub>{{ $modo==='minhas-solicitacoes'?'Preencha os dados que souber. A equipe confere o pedido e responde no acompanhamento.':'Confira os dados e registre o motivo da revisão. As edições e escolhas anteriores serão preservadas.' }}</x-slot:sub></x-pagina>
@include('catalogo-atendimento._erros')
<form method="post" class="card catalogo-formulario" action="{{ $modo==='minhas-solicitacoes'?route('catalogo-pedidos.enviar'):(!empty($e)?route($modo==='gestao'?'catalogo-gestao.gravar':'catalogo-atendimento.gravar',['unidade'=>$e['unidade_id']]):route('catalogo-atendimento.cadastrar')) }}">
@csrf <x-revisao-acesso />
@if($modo==='minhas-solicitacoes')<input type="hidden" name="chave" value="{{ old('chave',$chave) }}"><p class="catalogo-ajuda">Campos com * são necessários. Este pedido fica entre você e a equipe interna.</p>@endif
@if(!empty($e))<input type="hidden" name="edicao_id" value="{{ $e['edicao_id'] }}"><p class="catalogo-ajuda">Revisando: <strong>{{ $e['nome'] }}</strong>. O código e o tipo da unidade permanecem os mesmos.</p>@endif
@include('catalogo-atendimento._cadastro')
@if($modo!=='minhas-solicitacoes')@include('catalogo-atendimento._conferencia')@endif
<div class="catalogo-acoes"><a class="btn btn-ghost" href="{{ route($prefix.'.index') }}">Voltar</a><button class="btn btn-primary">{{ $modo==='minhas-solicitacoes'?'Enviar solicitação ao suporte':'Registrar revisão cadastral' }}</button></div>
</form>
@endsection