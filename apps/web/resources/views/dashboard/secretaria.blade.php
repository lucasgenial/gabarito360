@extends('layouts.app')

@section('title', 'Painel da Secretaria')

@push('styles')
<style>
  .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-top:24px; }
  .table-wrap { overflow:hidden; margin-top:20px; }
  @media (max-width:1080px){ .kpi-grid{grid-template-columns:repeat(2,1fr);} }
  @media (max-width:480px){ .kpi-grid{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}" class="active">Painel</a>
@endsection

@section('context-badge')
<span class="badge badge-info badge-dot">{{ $dashboard['secretaria']['nome'] ?? 'Secretaria' }}</span>
@endsection

@section('breadcrumb')
<span>Início</span><span class="sep">/</span><span>Painel</span>
@endsection

@section('content')

@php
  $kpis        = $dashboard['kpis']  ?? null;
  $redes       = $dashboard['redes'] ?? [];
  $secretaria  = $dashboard['secretaria'] ?? null;
  $nomeSecret  = $secretaria['nome'] ?? 'da Secretaria';

  $modalidadeConfig = [
    'institucional' => ['badge' => 'badge-info',  'label' => 'Institucional'],
    'individual'    => ['badge' => 'badge-muted', 'label' => 'Individual'],
  ];
@endphp

<div class="row-between" style="margin-top:12px;">
  <div>
    <div class="eyebrow">Acompanhamento estadual</div>
    <h1 class="page-title">Painel {{ $nomeSecret }}</h1>
    <p class="page-sub">{{ $nome }} · supervisão de {{ $kpis['total_redes'] ?? '—' }} redes · consolidação {{ now()->year }}</p>
  </div>
</div>

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Redes Vinculadas</div>
    <div class="kpi-value">{{ $kpis['total_redes'] ?? '—' }}</div>
    <div class="kpi-trend">● sob esta secretaria</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Alunos</div>
    <div class="kpi-value">{{ $kpis ? number_format($kpis['total_alunos'], 0, ',', '.') : '—' }}</div>
    <div class="kpi-trend up">▲ matrículas ativas</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Provas Realizadas</div>
    <div class="kpi-value">{{ $kpis['provas_realizadas'] ?? '—' }}</div>
    <div class="kpi-trend up">▲ no período letivo</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Média Consolidada</div>
    <div class="kpi-value">
      @if($kpis && $kpis['media_secretaria'] !== null)
        {{ number_format($kpis['media_secretaria'], 1, ',', '') }}
      @else
        —
      @endif
    </div>
    <div class="kpi-trend">● todas as redes</div>
  </div>
</div>

{{-- Comparativo de redes --}}
<div class="card table-wrap">
  <div class="card-pad" style="padding-bottom:0;">
    <div class="eyebrow">Mapa de desempenho</div>
    <h3 style="font-size:18px;">Comparativo das redes</h3>
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>Rede</th>
        <th>Modalidade</th>
        <th>Escolas</th>
        <th>Alunos</th>
        <th>Média</th>
      </tr>
    </thead>
    <tbody>
      @forelse($redes as $r)
        @php
          $cfg   = $modalidadeConfig[$r['modalidade']] ?? ['badge' => 'badge-muted', 'label' => 'N/D'];
          $media = $r['media'] ?? null;
        @endphp
        <tr>
          <td>{{ $r['nome'] }}</td>
          <td><span class="badge {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span></td>
          <td class="num">{{ $r['total_escolas'] }}</td>
          <td class="num">{{ number_format($r['total_alunos'], 0, ',', '.') }}</td>
          <td class="num">{{ $media !== null ? number_format($media, 1, ',', '') : '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" style="text-align:center;color:var(--muted);padding:24px;">
            Nenhuma rede vinculada a esta secretaria.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div style="height:48px"></div>
@endsection
