@extends('layouts.app')
@section('title','Conferir instituição')
@section('breadcrumb')<a href="{{ route('instituicoes.index') }}">Minhas instituições</a><span class="sep">/</span><a href="{{ route('instituicoes.buscar') }}">Buscar</a><span class="sep">/</span><span>Conferir instituição</span>@endsection
@section('content')
<x-pagina titulo="Conferir instituição"><x-slot:sub>Confirme a referência antes de adicioná-la à sua lista.</x-slot:sub></x-pagina>
<article class="card card-pad" style="margin-top:20px;">@include('instituicoes._identidade')</article>
<div class="card card-pad" style="margin-top:20px;">
  <h2>Atuação declarada no espaço pessoal</h2>
  <p>Esta escolha registra sua declaração de atuação. Ela não cria contrato, não confirma vínculo profissional e não libera dados, turmas ou usuários da instituição. Nenhuma notificação será enviada à escola.</p>
  @if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
  @if($errors->any())<x-aviso tipo="erro">Confirme a declaração para adicionar a instituição.</x-aviso>@endif
  @if($e['elegivel'] && \App\Support\Acesso::pode('catalogo.referencia_pessoal.gerir'))
  <form method="POST" action="{{ route('instituicoes.selecionar') }}">@csrf
    <input type="hidden" name="unidade_id" value="{{ $e['unidade_id'] }}"><input type="hidden" name="edicao_id" value="{{ $e['edicao_id'] }}">
    <label style="display:flex;gap:10px;align-items:flex-start;margin:20px 0;"><input type="checkbox" name="confirmacao" value="1" required>Confirmo esta instituição como referência de atuação no meu espaço pessoal.</label>
    <button class="btn btn-primary" type="submit">Adicionar às minhas instituições</button>
  </form>
  @elseif(!$e['elegivel'])<p>A situação desta referência não permite uma nova seleção.</p>
  @else<p>Seu acesso permite consultar o catálogo, mas não adicionar referências pessoais.</p>@endif
  <a class="btn btn-ghost" style="margin-top:12px;" href="{{ route('instituicoes.buscar') }}">Voltar à busca</a>
</div>
@endsection
