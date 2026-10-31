@extends('layouts.app')

@section('title', 'Painel do Aplicador')

@push('styles')
<style>
  .upload-form { display:flex; align-items:center; gap:8px; }
  .upload-form input[type=file] { font-size:12px; max-width:180px; }
</style>
@endpush

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

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

<x-pagina titulo="{{ \App\Support\Acesso::saudacao() }}, {{ $primeiroNome }}" subtitulo="Provas agendadas para hoje em {{ $dashboard['escola']['nome'] ?? 'sua escola' }}." />

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
    <h3 style="font-size:18px;">Provas para enviar cartões</h3>
  </div>
  <table class="table lista-cel">
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
            @if(in_array($p['status'], ['publicada', 'em_correcao', 'corrigida'], true))
              <a class="btn btn-primary" href="{{ route('correcao.show', $p['id']) }}#camera"><x-icone nome="camera" /> Fotografar</a>
            @else
              <span class="badge badge-muted">aguardando gabarito</span>
            @endif
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
