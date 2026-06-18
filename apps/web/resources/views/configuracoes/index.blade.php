@extends('layouts.app')

@section('title', 'Configurações')

@push('styles')
<style>
  .section-card { background:var(--surface);border:1px solid var(--border-soft);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden;margin-top:24px; }
  .section-head { padding:20px 24px 16px;border-bottom:1px solid var(--border-soft); }
  .section-head h2 { font-size:16px;font-weight:700; }
  .section-head p { font-size:13px;color:var(--muted);margin-top:2px; }
  .section-body { padding:24px; }
  .form-row { display:grid;grid-template-columns:1fr 1fr 1fr;gap:0 20px; }
  @media (max-width:900px){ .form-row{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('provas.index') }}">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<span>Configurações</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;margin-bottom:0;">
  <div>
    <h1 class="page-title">Configurações</h1>
    <p class="page-sub">Parâmetros gerais aplicados a toda a rede de ensino</p>
  </div>
</div>

@if(session('sucesso'))
  <div style="background:var(--success-light,#e3f5e1);color:var(--success,#168821);border:1px solid var(--success,#168821);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('sucesso') }}
  </div>
@endif
@if(session('erro'))
  <div style="background:var(--danger-light,#fde8e4);color:var(--danger,#e52207);border:1px solid var(--danger,#e52207);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('erro') }}
  </div>
@endif

<div class="section-card">
  <div class="section-head">
    <h2>Parâmetros da rede — {{ $config['nome'] }}</h2>
    <p>Usados no cálculo de aprovação dos alunos e nos alertas do painel administrativo</p>
  </div>
  <div class="section-body">
    <form method="POST" action="{{ route('configuracoes.update') }}">
      @csrf
      @method('PUT')
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

<div style="height:48px;"></div>
@endsection
