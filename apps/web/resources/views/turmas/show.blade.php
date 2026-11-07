@extends('layouts.app')

@section('tela', 'tela-turma')

@section('title', $turma['nome'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span>
<span>{{ $turma['nome'] }}</span>
@endsection

@section('content')

<x-pagina titulo="{{ $turma['nome'] }}">
  <x-slot:imagem>@pode('turma.gerir')<button type="button" class="imagem-perfil-abrir" data-abrir-imagem-turma aria-label="Alterar imagem da turma"><x-avatar-turma :turma="$turma" tamanho="lg" /></button>@else<x-avatar-turma :turma="$turma" tamanho="lg" />@endpode</x-slot:imagem>
  <x-slot:sub>{{ $turma['serie'] }} ·
      {{ ['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$turma['turno']] ?? $turma['turno'] }} ·
      <b>{{ $turma['total_alunos'] ?? 0 }}</b> aluno(s) · {{ $turma['ano_letivo'] }} · {{ $turma['ativo'] ? 'Em uso' : 'Arquivada' }}
      @if(!empty($turma['escola_nome'])) · {{ $turma['escola_nome'] }}@endif</x-slot:sub>
    @if($painel)
    <button class="btn btn-secondary tu1-icone-acao tu1r3-acao tu1r3-acao-filtro" type="button" data-painel-abrir aria-label="Filtrar indicadores" title="Filtrar indicadores"><x-icone nome="filtro" /></button>
    <button class="btn btn-secondary tu1-icone-acao tu1r3-acao tu1r3-acao-imprimir" type="button" data-documento-abrir="imprimir" aria-label="Imprimir painel da turma" title="Imprimir painel da turma"><x-icone nome="imprimir" /></button>
    <button class="btn btn-secondary tu1-icone-acao tu1r3-acao tu1r3-acao-pdf" type="button" data-documento-abrir="pdf" aria-label="Gerar PDF do painel da turma" title="Gerar PDF do painel da turma"><x-icone nome="pdf" /></button>
    @endif
    @pode('turma.gerir')
    <a class="btn btn-secondary tu1r3-acao tu1r3-acao-editar" aria-label="Editar turma" title="Editar turma" href="{{ route('turmas.edit',$turma['id']) }}"><x-icone nome="editar" /> Editar turma</a>
    @endpode
    @pode('turma.desativar')<x-arquivamento tipo="turmas" :registro="$turma" />
    @endpode
</x-pagina>
@php
$recortes=[];
if(!empty($painelAplicado['painel_disciplina']))$recortes[]='Disciplina: '.($painel['recorte']['disciplina']??'');
if(!empty($painelAplicado['painel_professor']))$recortes[]='Professor autor: '.($painel['recorte']['professor']??'');
if(!empty($painelAplicado['painel_inicio']))$recortes[]='A partir de '.\Carbon\Carbon::parse($painelAplicado['painel_inicio'])->format('d/m/Y');
if(!empty($painelAplicado['painel_fim']))$recortes[]='Até '.\Carbon\Carbon::parse($painelAplicado['painel_fim'])->format('d/m/Y');
@endphp
@if($recortes)<p class="tu1r3-recorte"><em>Dados filtrados · {{ implode(' · ',$recortes) }}</em></p>@endif


@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif


@php($secoes = ['visao-geral'=>'Visão geral'] + (\App\Support\Acesso::pode('aluno.ver')?['alunos'=>'Alunos']:[]) + (($dc1Visivel??false)?['disciplinas'=>'Disciplinas e professores']:[]) + (\App\Support\Acesso::pode('prova.ver')?['provas'=>'Provas']:[]) + (!\App\Support\Acesso::pessoal()?['acesso'=>'Acesso à turma']:[]))
@include('turmas._abas')
<section class="tu1-painel" id="visao-geral" data-turma-painel="visao-geral" aria-label="Visão geral">
@include('turmas._organizar_painel',['paginaPainel'=>'turma','turmaPainel'=>$turma['id']])
@if($painel || ($painelErro??false))@include('turmas._painel')@else
<div class="kpi-grid"><div class="card kpi"><div class="kpi-label">Alunos ativos</div><div class="kpi-value">{{ $turma['total_alunos'] ?? 0 }}</div></div><div class="card kpi"><div class="kpi-label">Ano letivo</div><div class="kpi-value">{{ $turma['ano_letivo'] }}</div></div></div>
@endif
@if(\App\Support\Acesso::pode('correcao.acompanhar') || \App\Support\Acesso::pode('relatorio.turma'))
<div class="card card-pad"><h2>Acompanhamento da turma</h2><p class="field-help">Consulte as correções e os resultados das avaliações desta turma.</p><div class="tu1-acoes">    @pode('correcao.acompanhar')<a class="btn btn-primary" href="{{ route('correcao.turma', $turma['id']) }}"><x-icone nome="grafico" /> Acompanhar correção</a>@endpode
    @pode('relatorio.turma')<a class="btn btn-secondary" href="{{ route('relatorios.turma', $turma['id']) }}"><x-icone nome="grafico" /> Relatório da turma</a>@endpode
</div></div>
@endif
@include('turmas._modelos', ['paginaModelos'=>'turma'])
</section>
<section class="tu1-painel" id="alunos" data-turma-painel="alunos" aria-label="Alunos">@include('turmas._alunos')</section>
@if($dc1Visivel??false)<section class="tu1-painel" id="disciplinas" data-turma-painel="disciplinas" aria-label="Disciplinas e professores">@include('disciplinas._turma')</section>@endif
<section class="tu1-painel" id="provas" data-turma-painel="provas" aria-label="Provas">@include('turmas._provas')</section>
@unless(\App\Support\Acesso::pessoal())<section class="tu1-painel" id="acesso" data-turma-painel="acesso" aria-label="Acesso à turma">@include('turmas._acesso')</section>@endunless
@pode('turma.gerir')@include('turmas._imagem')@endpode
@endsection
