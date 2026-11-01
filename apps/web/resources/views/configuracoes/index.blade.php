@extends('layouts.app')

@section('tela', 'tela-config')

@section('title', 'Configurações')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<span>Configurações</span>
@endsection

@section('content')

<x-pagina titulo="Configurações">
  <x-slot:sub>Parâmetros gerais aplicados a toda a rede de ensino</x-slot:sub>
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

@if(count($redes ?? []) > 1)
<form method="GET" action="{{ route('configuracoes.index') }}" class="section-card">
  <div class="section-body field">
    <label for="rede_id">Rede de ensino</label>
    <select id="rede_id" name="rede_id" class="input" required>
      <option value="">Selecione a rede</option>
      @foreach($redes as $rede)
      <option value="{{ $rede['id'] }}" @selected((int) request('rede_id') === (int) $rede['id'])>{{ $rede['nome'] }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn btn-primary">Abrir configurações</button>
  </div>
</form>
@endif
@if($config)
<div class="section-card">
  <div class="section-head">
    <h2>Parâmetros da rede — {{ $config['nome'] }}</h2>
    <p>Usados no cálculo de aprovação dos alunos e nos alertas do painel administrativo</p>
  </div>
  <div class="section-body">
    <form method="POST" action="{{ route('configuracoes.update') }}">
      @csrf
      @method('PUT')
      <input type="hidden" name="rede_id" value="{{ $config['rede_id'] }}" />
      <div class="form-row">
        <div class="field">
          <label for="meta_media">Meta de nota da rede *</label>
          <input id="meta_media" name="meta_media" type="number" step="0.1" min="0" max="10" class="input num"
                 required value="{{ old('meta_media', $config['meta_media']) }}" />
          <p class="field-help">Nota média de referência exibida nos relatórios e dashboards (ex.: 7,0).</p>
        </div>
        <div class="field">
          <label for="meta_minima">Nota mínima de aprovação *</label>
          <input id="meta_minima" name="meta_minima" type="number" step="0.1" min="0" max="10" class="input num"
                 required value="{{ old('meta_minima', $config['meta_minima']) }}" />
          <p class="field-help">Abaixo deste valor (proporcional à nota máxima da prova) o aluno fica em recuperação.</p>
        </div>
        <div class="field">
          <label for="limiar_seges_min">Limiar de alerta SEGES (minutos) *</label>
          <input id="limiar_seges_min" name="limiar_seges_min" type="number" step="1" min="1" max="1440" class="input num"
                 required value="{{ old('limiar_seges_min', $config['limiar_seges_min']) }}" />
          <p class="field-help">Atraso, em minutos, para a sincronização SEGES ser sinalizada como atenção no painel.</p>
        </div>
      </div>
      <div style="margin-top:8px;">
        <button type="submit" class="btn btn-primary">Salvar configurações</button>
      </div>
    </form>
  </div>
</div>

@endif
<div style="height:48px;"></div>
@endsection
