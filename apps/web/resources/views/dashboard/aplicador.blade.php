@extends('layouts.app')

@section('title', 'Painel do Aplicador')

@push('styles')
<style>
  .kpi-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:18px; margin-top:24px; }
  .table-wrap { overflow:hidden; margin-top:20px; }
  .upload-form { display:flex; align-items:center; gap:8px; }
  .upload-form input[type=file] { font-size:12px; max-width:180px; }
  @media (max-width:480px){ .kpi-grid{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}" class="active">Painel</a>
@endsection

@section('context-badge')
<span class="badge badge-info badge-dot">{{ $dashboard['escola']['nome'] ?? 'Aplicador' }}</span>
@endsection

@section('breadcrumb')
<span>Início</span><span class="sep">/</span><span>Painel</span>
@endsection

@section('content')

@php
  $kpis  = $dashboard['kpis'] ?? null;
  $provas = $dashboard['provas_hoje'] ?? [];
  $primeiroNome = explode(' ', $nome)[0];
@endphp

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

<div class="row-between" style="margin-top:12px;">
  <div>
    <div class="eyebrow">Aplicação de Provas</div>
    <h1 class="page-title">Bom dia, {{ $primeiroNome }}</h1>
    <p class="page-sub">Provas agendadas para hoje em {{ $dashboard['escola']['nome'] ?? 'sua escola' }}.</p>
  </div>
</div>

<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Provas Hoje</div>
    <div class="kpi-value">{{ $kpis['provas_hoje'] ?? '—' }}</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Cartões Enviados</div>
    <div class="kpi-value">{{ $kpis['cartoes_enviados'] ?? '—' }}</div>
  </div>
</div>

<div class="card table-wrap">
  <div class="card-pad" style="padding-bottom:0;">
    <div class="eyebrow">Aplicação do dia</div>
    <h3 style="font-size:18px;">Provas para enviar cartões</h3>
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>Prova</th>
        <th>Disciplina</th>
        <th>Turmas</th>
        <th>Alunos</th>
        <th>Cartões enviados</th>
        <th>Enviar cartão</th>
      </tr>
    </thead>
    <tbody>
      @forelse($provas as $p)
        <tr>
          <td>{{ $p['titulo'] }}</td>
          <td>{{ $p['disciplina'] }}</td>
          <td>{{ $p['turmas'] }}</td>
          <td class="num">{{ $p['total_alunos'] }}</td>
          <td class="num">{{ $p['cartoes_enviados'] }}</td>
          <td>
            <form class="upload-form" method="POST" action="{{ route('correcao.upload', $p['id']) }}" enctype="multipart/form-data">
              @csrf
              <input type="file" name="imagem" accept="image/*" required />
              <button type="submit" class="btn btn-secondary" style="padding:6px 10px;font-size:12px;">Enviar</button>
            </form>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;color:var(--muted);padding:24px;">
            Nenhuma prova agendada para hoje.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div style="height:48px"></div>
@endsection
