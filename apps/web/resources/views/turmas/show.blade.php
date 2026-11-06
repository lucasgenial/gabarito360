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
  <x-slot:sub>{{ $turma['serie'] }} ·
      {{ ['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'][$turma['turno']] ?? $turma['turno'] }} ·
      <b>{{ $turma['total_alunos'] ?? 0 }}</b> aluno(s) · {{ $turma['ano_letivo'] }} · {{ $turma['ativo'] ? 'Ativa' : 'Inativa' }}
      @if(!empty($turma['escola_nome'])) · {{ $turma['escola_nome'] }}@endif</x-slot:sub>
    @pode('turma.gerir')
    <a class="btn btn-secondary" href="{{ route('turmas.edit',$turma['id']) }}">Editar turma</a>
    <form method="POST" action="{{ route('turmas.toggle', $turma['id']) }}"
          data-turma-confirmar="{{ $turma['ativo'] ? 'Desativar' : 'Ativar' }} esta turma?">
      @csrf <x-revisao-acesso />
      <button type="submit" class="btn {{ $turma['ativo'] ? 'btn-ghost' : 'btn-secondary' }}"
              style="color:{{ $turma['ativo'] ? 'var(--danger)' : 'var(--success)' }};{{ $turma['ativo'] ? 'border-color:var(--danger);' : '' }}">
        {{ $turma['ativo'] ? 'Desativar' : 'Ativar' }}
      </button>
    </form>
    @endpode
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif


@php($secoes = ['visao-geral'=>'Visão geral','alunos'=>'Alunos'] + (($dc1Visivel??false)?['disciplinas'=>'Disciplinas e professores']:[]) + ['provas'=>'Provas'] + (!\App\Support\Acesso::pessoal()?['acesso'=>'Acesso à turma']:[]))
@include('turmas._abas')
<section class="tu1-painel" id="visao-geral" data-turma-painel="visao-geral" aria-label="Visão geral">
@if($painel || ($painelErro??false))@include('turmas._painel')@else
<div class="kpi-grid"><div class="card kpi"><div class="kpi-label">Alunos ativos</div><div class="kpi-value">{{ $turma['total_alunos'] ?? 0 }}</div></div><div class="card kpi"><div class="kpi-label">Ano letivo</div><div class="kpi-value">{{ $turma['ano_letivo'] }}</div></div></div>
@endif
<div class="card card-pad"><h2>Acompanhamento da turma</h2><p class="field-help">Consulte as correções e os resultados das avaliações desta turma.</p><div class="tu1-acoes">    @pode('correcao.acompanhar')<a class="btn btn-primary" href="{{ route('correcao.turma', $turma['id']) }}"><x-icone nome="grafico" /> Acompanhar correção</a>@endpode
    @pode('resultado.ver')<a class="btn btn-secondary" href="{{ route('relatorios.turma', $turma['id']) }}"><x-icone nome="grafico" /> Relatório da turma</a>@endpode
</div></div>
</section>
<section class="tu1-painel" id="alunos" data-turma-painel="alunos" aria-label="Alunos">@include('turmas._alunos')</section>
@if($dc1Visivel??false)<section class="tu1-painel" id="disciplinas" data-turma-painel="disciplinas" aria-label="Disciplinas e professores">@include('disciplinas._turma')</section>@endif
<section class="tu1-painel" id="provas" data-turma-painel="provas" aria-label="Provas">@include('turmas._provas')</section>
@unless(\App\Support\Acesso::pessoal())<section class="tu1-painel" id="acesso" data-turma-painel="acesso" aria-label="Acesso à turma">@include('turmas._acesso')</section>@endunless
@endsection
