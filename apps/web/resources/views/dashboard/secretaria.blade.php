@extends('layouts.app')

@section('title', 'Painel da Secretaria')

@push('styles')
<style>
</style>
@endpush

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

<x-pagina titulo="Painel {{ $nomeSecret }}" subtitulo="{{ $nome }} · supervisão de {{ $kpis['total_redes'] ?? '—' }} redes · consolidação {{ now()->year }}" />

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Redes Vinculadas</div>
    <div class="kpi-value">{{ $kpis['total_redes'] ?? '—' }}</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Alunos</div>
    <div class="kpi-value">{{ $kpis ? number_format($kpis['total_alunos'], 0, ',', '.') : '—' }}</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Provas Realizadas</div>
    <div class="kpi-value">{{ $kpis['provas_realizadas'] ?? '—' }}</div>
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
  </div>
</div>

{{-- Comparativo de redes --}}
<div class="card table-wrap">
  <div class="card-pad" style="padding-bottom:0;">
    <h3 style="font-size:18px;">Redes</h3>
  </div>
  <x-tabela :interna="true" :integrada="true" titulo="Redes da secretaria" modo="lista"><table class="tabela-padrao table "><caption class="sr-only">Redes da secretaria</caption>
    <thead>
      <tr>
        <th scope="col">Rede</th>
        <th scope="col">Modalidade</th>
        <th scope="col">Escolas</th>
        <th scope="col">Alunos</th>
        <th scope="col">Média</th>
      </tr>
    </thead>
    <tbody>
      @forelse($redes as $r)
        @php
          $cfg   = $modalidadeConfig[$r['modalidade']] ?? ['badge' => 'badge-muted', 'label' => 'N/D'];
          $media = $r['media'] ?? null;
        @endphp
        <tr>
          <td class="tabela-principal" data-rotulo="Rede"><div class="tabela-valor">{{ $r['nome'] }}</div></td>
          <td data-rotulo="Modalidade"><div class="tabela-valor"><span class="badge {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span></div></td>
          <td class="num" data-rotulo="Escolas"><div class="tabela-valor">{{ $r['total_escolas'] }}</div></td>
          <td class="num" data-rotulo="Alunos"><div class="tabela-valor">{{ number_format($r['total_alunos'], 0, ',', '.') }}</div></td>
          <td class="num" data-rotulo="Média"><div class="tabela-valor">{{ $media !== null ? number_format($media, 1, ',', '') : '—' }}</div></td>
        </tr>
      @empty
        <tr>
          <td colspan="5" style="text-align:center;color:var(--muted);padding:24px;">
            Nenhuma rede vinculada a esta secretaria.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table></x-tabela>
</div>

<div style="height:48px"></div>
@endsection
