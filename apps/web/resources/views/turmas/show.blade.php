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
    <button class="btn btn-secondary" data-turma-abrir="modal-editar">Editar turma</button>
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
{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Desempenho médio</div>
    <div class="kpi-value">{{ $turma['media_turma'] !== null ? number_format($turma['media_turma'], 1, ',', '') : '—' }}</div>
    <div class="kpi-trend">média da turma</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Provas aplicadas</div>
    <div class="kpi-value">{{ $turma['total_provas'] ?? 0 }}</div>
    <div class="kpi-trend">nesta turma</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Alunos ativos</div>
    <div class="kpi-value">{{ $turma['total_alunos'] ?? 0 }}</div>
    <div class="kpi-trend">matriculados</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Ano letivo</div>
    <div class="kpi-value">{{ $turma['ano_letivo'] }}</div>
    <div class="kpi-trend">{{ $turma['ativo'] ? 'Ativa' : 'Inativa' }}</div>
  </div>
</div>

<div class="card card-pad"><h2>Acompanhamento da turma</h2><p class="field-help">Consulte as correções e os resultados das avaliações desta turma.</p><div class="tu1-acoes">    @pode('correcao.acompanhar')<a class="btn btn-primary" href="{{ route('correcao.turma', $turma['id']) }}"><x-icone nome="grafico" /> Acompanhar correção</a>@endpode
    @pode('resultado.ver')<a class="btn btn-secondary" href="{{ route('relatorios.turma', $turma['id']) }}"><x-icone nome="grafico" /> Relatório da turma</a>@endpode
</div></div>
</section>
<section class="tu1-painel" id="alunos" data-turma-painel="alunos" aria-label="Alunos">@include('turmas._alunos')</section>
@if($dc1Visivel??false)<section class="tu1-painel" id="disciplinas" data-turma-painel="disciplinas" aria-label="Disciplinas e professores">@include('disciplinas._turma')</section>@endif
<section class="tu1-painel" id="provas" data-turma-painel="provas" aria-label="Provas">@include('turmas._provas')</section>
@unless(\App\Support\Acesso::pessoal())<section class="tu1-painel" id="acesso" data-turma-painel="acesso" aria-label="Acesso à turma">@include('turmas._acesso')</section>@endunless
{{-- Modal de edição --}}
@pode('turma.gerir')
<div class="modal-backdrop" id="modal-editar" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h2>Editar turma</h2>
      <button type="button" class="modal-close" data-turma-fechar="modal-editar">&times;</button>
    </div>
    <form method="POST" action="{{ route('turmas.update', $turma['id']) }}">
      @csrf <x-revisao-acesso />
      @method('PUT')
      <div class="modal-body">
        <div class="form-row-2">
          <div class="field" style="grid-column:1/-1">
            <label for="ed-nome">Nome da turma *</label>
            <input class="input" type="text" id="ed-nome" name="nome" required value="{{ $turma['nome'] }}" />
          </div>
          <div class="field">
            <label for="ed-serie">Série *</label>
            <input class="input" type="text" id="ed-serie" name="serie" required value="{{ $turma['serie'] }}" />
          </div>
          <div class="field">
            <label for="ed-turno">Turno *</label>
            <select class="select" id="ed-turno" name="turno" required>
              @foreach(['manha'=>'Manhã','tarde'=>'Tarde','noite'=>'Noite','integral'=>'Integral'] as $v => $l)
                <option value="{{ $v }}" {{ $turma['turno'] === $v ? 'selected' : '' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
          @unless(\App\Support\Acesso::pessoal())
<div class="field">
            <label for="ed-escola">Escola</label>
            <select class="select" id="ed-escola" name="escola_id" @disabled($escolasErro??false)>
              @foreach($escolas as $e)
                <option value="{{ $e['id'] }}" {{ ($turma['escola_id'] ?? '') == $e['id'] ? 'selected' : '' }}>{{ $e['nome'] }}</option>
              @endforeach
            </select>
          </div>
@endunless
          <div class="field">
            <label for="ed-ano">Ano letivo</label>
            <input class="input num" type="number" id="ed-ano" name="ano_letivo"
                   value="{{ $turma['ano_letivo'] }}" min="2020" max="2099" />
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" data-turma-fechar="modal-editar">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar alterações</button>
      </div>
    </form>
  </div>
</div>


@endpode
@endsection
