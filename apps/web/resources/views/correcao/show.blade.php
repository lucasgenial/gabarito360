@extends('layouts.app')

@section('tela', 'tela-correcao')

@section('title', 'Acompanhar correção — ' . $prova['titulo'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('provas.index') }}">Provas</a><span class="sep">/</span>
<a href="{{ route('provas.show', $prova['id']) }}">{{ $prova['titulo'] }}</a><span class="sep">/</span>
<span>Acompanhar correção</span>
@endsection

@push('styles')
<link rel="preload" href="{{ asset('brand/gabarito360-icon.svg') }}" as="image" type="image/svg+xml">
<link rel="preload" href="{{ asset('brand/gabarito360-icon-dark.svg') }}" as="image" type="image/svg+xml">
<link rel="stylesheet" href="@ativo('css/leituras-resultados.css')">
@endpush

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">{{ $prova['titulo'] }}</h1>
    <p class="page-sub">{{ $prova['disciplina'] }} · {{ implode(' · ', array_column($prova['turmas'] ?? [], 'nome')) }}{{ $prova['data_aplicacao'] ? ' · aplicado em ' . \Carbon\Carbon::parse($prova['data_aplicacao'])->format('d/m/Y') : '' }}</p>
  </div>
  <button class="btn btn-ghost" id="btn-upload" data-correcao-acao="abrir-upload">Envio manual</button>
</div>

@php
  $corr      = $status['correcao'] ?? ['fechada' => false];
  $fechada   = !empty($corr['fechada']);
  $podeMexer = \App\Support\Acesso::podeAlterarCorrecao($fechada);
@endphp

{{-- Correção aberta/fechada --}}
<div class="card card-pad no-print" style="margin-top:16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;{{ $fechada ? 'border-color:var(--warn-fg);background:var(--warn-light);' : '' }}">
  @if($fechada)
    <div style="flex:1;min-width:220px;font-size:14px;">
      <x-icone nome="cadeado" /> <b>Correção fechada</b>{{ !empty($corr['fechada_por']) ? ' por ' . $corr['fechada_por'] : '' }}{{ !empty($corr['fechada_em']) ? ' em ' . \Carbon\Carbon::parse($corr['fechada_em'])->timezone('America/Bahia')->format('d/m/Y \à\s H:i') : '' }}.
      <div style="font-size:12px;color:var(--muted);">{{ $podeMexer ? 'Você (supervisão) ainda pode excluir e incluir correções.' : 'Para enviar, excluir ou alterar cartões, peça à coordenação para reabrir.' }}</div>
    </div>
    @pode('correcao.reabrir')
      <form method="POST" action="{{ route('correcao.reabrir', $prova['id']) }}" data-correcao-confirmar="Reabrir a correção? Professores e corretores voltam a poder enviar, excluir e alterar.">
        @csrf <x-revisao-acesso /><button type="submit" class="btn btn-secondary"><x-icone nome="cadeado" /> Reabrir correção</button>
      </form>
    @endpode
  @else
    <div style="flex:1;min-width:220px;font-size:14px;"><b>Correção aberta</b><div style="font-size:12px;color:var(--muted);">Ao terminar, feche a correção para evitar alterações.</div></div>
    @pode('correcao.fechar')
      <form method="POST" action="{{ route('correcao.fechar', $prova['id']) }}" data-correcao-confirmar="Fechar a correção? Depois disso só a coordenação/direção poderá alterar, excluir, incluir ou reabrir.">
        @csrf <x-revisao-acesso /><button type="submit" class="btn btn-secondary"><x-icone nome="cadeado" /> Fechar correção</button>
      </form>
    @endpode
  @endif
</div>

@if($podeMexer || \App\Support\Acesso::perfil() === 'aplicador')
  @pode('cartao.enviar')
    @include('correcao._camera')
  @endpode
@endif

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

<div class="progress-head">
  <div class="card card-pad" id="regiao-progresso" aria-busy="true">
    <x-carregando rotulo="Carregando indicadores…" data-carga-esqueleto role="status"/>
    <div data-carga-conteudo hidden>
    <div class="stat-row">
      <div class="correcao-pct"><b id="v-pct">—</b><span>dos alunos previstos</span></div>
      <div class="stat-grid">
        <div><div class="v" id="v-concluidos">—</div><div class="l">Correções concluídas</div></div>
        <div><div class="v" id="v-esperados">—</div><div class="l">Alunos previstos</div></div>
        <div><div class="v" id="v-sem-cartao">—</div><div class="l">Sem cartão</div></div>
        <div><div class="v" id="v-processando">—</div><div class="l">Em processamento</div></div>
        <div><div class="v" id="v-revisao">—</div><div class="l">Precisam de revisão</div></div>
        <div><div class="v" id="v-total">—</div><div class="l">Cartões recebidos</div></div>
      </div>
    </div>
    <p id="v-progresso-resumo" class="page-sub"></p><p id="v-pendencias-tecnicas" class="page-sub"></p>
    <div class="progress-track" role="progressbar" aria-label="Correções concluídas" aria-valuemin="0" aria-valuemax="100"><div id="bar"></div></div>
    </div><p data-carga-feedback role="status"></p>
  </div>
  <div class="card card-pad">
    <h3 style="font-size:16px;margin-bottom:8px;">Leituras para revisar</h3>
    <p id="ambig-resumo" style="margin-bottom:12px;">Carregando revisões…</p>
    @if($podeMexer)<a class="btn btn-primary" id="btn-revisar" href="{{ route('correcao.revisao', $prova['id']) }}" style="display:none;">Revisar leituras</a>@endif
  </div>
</div>

{{-- Alunos corrigidos: foto do cartão, nota, acertos/erros e exclusão para fotografar de novo --}}
<div class="card card-pad" style="margin-top:20px;" id="corrigidos" aria-busy="true">
  <div class="row-between" style="flex-wrap:wrap;margin-bottom:12px;">
    <h3 style="font-size:17px;">Cartões recebidos <span class="badge badge-muted" id="corr-qtd">—</span></h3>
    <input class="input" id="corr-busca" disabled type="search" placeholder="Buscar aluno…" style="max-width:240px;">
  </div>
  <x-carregando rotulo="Carregando cartões…" data-carga-esqueleto role="status"/>
  <div data-carga-conteudo hidden><ul class="corr-list" id="corr-lista"></ul></div><p data-carga-feedback role="status"></p>
</div>

<div class="g360-modal" id="foto-modal" data-correcao-fundo-foto>
  <div class="caixa">
    <div class="row-between" style="margin-bottom:10px;"><b id="foto-titulo"></b><button type="button" class="btn btn-sm btn-ghost" data-correcao-acao="fechar-foto">✕</button></div>
    <img id="foto-img" class="foto-img" alt="Foto do cartão-resposta">
    <p id="foto-erro" style="display:none;">Não foi possível abrir a foto. Feche e tente de novo.</p>
  </div>
</div>


<div class="card card-pad" style="margin-top:20px;">
  <h3 style="font-size:17px;margin-bottom:8px;">Cartões processados recentemente</h3>
  <ul class="activity" id="activity-list"><li>Carregando atividade…</li></ul>
</div>

{{-- Modal de upload --}}
<div class="modal-backdrop" id="modal-upload" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:none;align-items:center;justify-content:center;padding:24px;">
  <div class="modal" style="background:var(--surface);border-radius:var(--radius-lg);box-shadow:0 24px 64px rgba(0,0,0,.22);width:100%;max-width:480px;">
    <div style="padding:20px 24px 16px;border-bottom:1px solid var(--border-soft);display:flex;align-items:center;justify-content:space-between;">
      <h2 style="font-size:17px;font-weight:700;">Enviar cartão-resposta</h2>
      <button type="button" data-correcao-acao="fechar-upload" style="width:32px;height:32px;border-radius:50%;background:none;border:0;cursor:pointer;font-size:20px;color:var(--muted);">&times;</button>
    </div>
    <form method="POST" action="{{ route('correcao.upload', $prova['id']) }}" enctype="multipart/form-data">
      @csrf <x-revisao-acesso />
      <input type="hidden" name="envio_id" value="{{ \Illuminate\Support\Str::uuid() }}">
      <div style="padding:22px;display:flex;flex-direction:column;gap:14px;">
        <div class="field">
          <label>Imagem do cartão *</label>
          <input class="input" type="file" name="imagem" accept="image/*" required />
        </div>
        <div class="field">
          <label for="upload-aluno">Aluno (opcional — pode vincular depois)</label><p id="upload-alunos-estado" role="status"></p>
          <select class="select" name="aluno_id" id="upload-aluno">
            <option value="">— Não identificado —</option>
            @foreach($alunos as $a)
              <option value="{{ $a['id'] }}">{{ $a['nome'] }}{{ isset($a['matricula']) ? ' — ' . $a['matricula'] : '' }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div style="padding:14px 22px;border-top:1px solid var(--border-soft);display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn-ghost" data-correcao-acao="fechar-upload">Cancelar</button>
        <button type="submit" class="btn btn-primary">Enviar para leitura</button>
      </div>
    </form>
  </div>
</div>

<div style="height:48px"></div>
@include('correcao._retirar')
@endsection

@push('scripts')
<script src="@ativo('js/cartoes-miniaturas.js')"></script>
@php($g360Config = ['status'=>\App\Support\NavegacaoWeb::destino('correcao.status',[$prova['id']]),'cartoes'=>\App\Support\NavegacaoWeb::destino('correcao.cartoes',[$prova['id']]),'alunos'=>\App\Support\NavegacaoWeb::destino('correcao.alunos',[$prova['id']]),'provaId'=>$prova['id'],'podeExcluir'=>$podeMexer,'notaMax'=>$prova['nota_maxima']??10,'csrf'=>csrf_token()])
<script type="application/json" id="correcao-acompanhamento-config">@json($g360Config)</script>
<script src="@ativo('js/correcao-acompanhamento.js')"></script>
@endpush
