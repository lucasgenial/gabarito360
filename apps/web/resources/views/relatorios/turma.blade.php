@extends('layouts.app')

{{--
  Arquivo: apps/web/resources/views/relatorios/turma.blade.php
  Função: Relatório consolidado da turma em todas as provas — média e aprovação por área,
          comparação com as outras turmas da escola e ranking dos alunos. Imprimível.
--}}

@php
  $fmt  = fn ($n) => $n === null ? '—' : number_format((float) $n, 1, ',', '.');
  $cores = ['#1351b4', '#c27c0e', '#168821', '#7b3fa0', '#0e7c86', '#b7410e'];
  // Turmas que aparecem no comparativo (esta primeiro)
  $turmasComp = collect($provas)->flatMap(fn ($p) => $p['por_turma'])->pluck('turma', 'turma_id')->sortKeys();
  $turmasComp = collect([$turma['id'] => $turma['nome']])->union($turmasComp);
  $corTurma = $turmasComp->keys()->values()->flip()->map(fn ($i) => $cores[$i % count($cores)]);
@endphp

@section('title', 'Relatório da turma ' . $turma['nome'])

@push('styles')
<style>
  .kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-top:16px; }
  .kpis .card { padding:14px 16px; }
  .kpis .v { font-family:var(--font-mono); font-size:26px; font-weight:700; }
  .kpis .l { font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; }
  .tab { width:100%; border-collapse:collapse; font-size:14px; }
  .tab th, .tab td { padding:8px 10px; border-bottom:1px solid var(--border-soft); text-align:center; white-space:nowrap; }
  .tab th { font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
  .tab td.esq, .tab th.esq { text-align:left; }
  .nota { font-family:var(--font-mono); font-weight:700; padding:2px 7px; border-radius:5px; }
  .nota.ok { background:var(--success-light,#e3f5e1); color:var(--success,#168821); }
  .nota.baixa { background:var(--danger-light,#fde8e4); color:var(--danger,#e52207); }
  .legenda { display:flex; gap:14px; flex-wrap:wrap; font-size:12px; color:var(--muted); }
  .legenda i { display:inline-block; width:10px; height:10px; border-radius:2px; margin-right:5px; vertical-align:middle; }
  @media (max-width:720px) { .kpis { grid-template-columns:1fr 1fr; } }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('turmas.show', $turma['id']) }}">{{ $turma['nome'] }}</a><span class="sep">/</span>
<span>Relatório da turma</span>
@endsection

@section('content')
@include('relatorios._cabecalho', ['cabecalho' => $cabecalho ?? [], 'titulo' => 'Relatório consolidado da turma', 'subtitulo' => 'Turma ' . $turma['nome']])
<div class="row-between" style="margin-top:12px;flex-wrap:wrap;">
  <div>
    <div class="eyebrow">Relatório consolidado</div>
    <h1 class="page-title">Turma {{ $turma['nome'] }}</h1>
    <p class="page-sub">{{ collect([$turma['escola_nome'], $turma['serie'], $kpis['alunos'] . ' alunos', $kpis['provas'] . ' provas'])->filter()->implode(' · ') }}</p>
  </div>
  <div class="no-print" style="display:flex;gap:8px;flex-wrap:wrap;">
    <a class="btn btn-ghost" href="{{ route('correcao.turma', $turma['id']) }}">📊 Acompanhar correção</a>
    <button type="button" class="btn btn-secondary" onclick="window.print()">🖨 Imprimir / PDF</button>
  </div>
</div>

<div class="kpis">
  <div class="card"><div class="v" style="color:var(--accent);">{{ $fmt($kpis['media_geral']) }}</div><div class="l">Média geral</div></div>
  <div class="card"><div class="v">{{ $kpis['notas_lancadas'] }}<span style="font-size:14px;color:var(--muted);">/{{ $kpis['notas_esperadas'] }}</span></div><div class="l">Notas lançadas</div></div>
  <div class="card"><div class="v">{{ $kpis['alunos_completos'] }}</div><div class="l">Alunos com todas as provas</div></div>
  <div class="card"><div class="v">{{ $kpis['provas'] }}</div><div class="l">Provas</div></div>
</div>

{{-- Médias por área: esta turma × outras turmas da escola --}}
<div class="card card-pad" style="margin-top:16px;">
  <div class="row-between" style="flex-wrap:wrap;">
    <h3 style="font-size:17px;">Média por área</h3>
    <div class="legenda">@foreach($turmasComp as $id => $nome)<span><i style="background:{{ $corTurma[$id] }}"></i>{{ $nome }}{{ $id == $turma['id'] ? ' (esta)' : '' }}</span>@endforeach</div>
  </div>
  @php
    $grupos = count($provas); $nT = max(1, $turmasComp->count());
    $larg = max(520, $grupos * ($nT * 26 + 40) + 40);
  @endphp
  @if($grupos)
    <div style="overflow-x:auto;margin-top:10px;">
      <svg viewBox="0 0 {{ $larg }} 200" width="100%" style="min-width:{{ min($larg, 640) }}px;" role="img" aria-label="Média por área e turma">
        @foreach([0, 5, 10] as $g)
          <line x1="30" x2="{{ $larg - 6 }}" y1="{{ 160 - $g * 14 }}" y2="{{ 160 - $g * 14 }}" stroke="#ddd" stroke-dasharray="{{ $g ? '3 3' : '' }}"></line>
          <text x="26" y="{{ 163 - $g * 14 }}" text-anchor="end" font-size="9" fill="#888">{{ $g }}</text>
        @endforeach
        @foreach($provas as $gi => $p)
          @php
            $x0 = 40 + $gi * (($larg - 50) / $grupos);
            $medias = collect($p['por_turma'])->keyBy('turma_id');
          @endphp
          @foreach($turmasComp as $tid => $tnome)
            @php $m = $medias[$tid]['media'] ?? null; $esc = 10 / max($p['nota_maxima'], 0.1); $h = $m !== null ? $m * $esc * 14 : 0; $x = $x0 + $loop->index * 26; @endphp
            @if($m !== null)
              <rect x="{{ $x }}" y="{{ 160 - $h }}" width="22" height="{{ max($h, 1) }}" rx="2" fill="{{ $corTurma[$tid] }}"><title>{{ $tnome }} — {{ $p['disciplina'] }}: {{ $fmt($m) }}</title></rect>
              <text x="{{ $x + 11 }}" y="{{ 156 - $h }}" text-anchor="middle" font-size="9" font-weight="700" fill="currentColor">{{ $fmt($m) }}</text>
            @endif
          @endforeach
          <text x="{{ $x0 + ($nT * 26) / 2 - 2 }}" y="178" text-anchor="middle" font-size="10" fill="#555">{{ \Illuminate\Support\Str::limit($p['disciplina'], 22) }}</text>
        @endforeach
      </svg>
    </div>
  @else
    <p class="field-help">Nenhuma prova publicada para a turma.</p>
  @endif
</div>

{{-- Resumo por prova --}}
<div class="card card-pad" style="margin-top:16px;">
  <h3 style="font-size:17px;margin-bottom:6px;">Resumo por prova</h3>
  <div>
    <table class="tab lista-cel">
      <thead><tr><th class="esq">Prova</th><th>Corrigidos</th><th>Média</th><th>Aprovação</th><th>Maior</th><th>Menor</th><th class="no-print"></th></tr></thead>
      <tbody>
        @foreach($provas as $p)
          <tr>
            <td class="esq"><b>{{ $p['disciplina'] }}</b><div style="font-size:12px;color:var(--muted);">{{ $p['titulo'] }}</div></td>
            <td>{{ $p['corrigidos'] }}/{{ $kpis['alunos'] }}</td>
            <td><b>{{ $fmt($p['media']) }}</b></td>
            <td>{{ $p['aprovacao_pct'] !== null ? $p['aprovacao_pct'] . '%' : '—' }}</td>
            <td>{{ $fmt($p['maior']) }}</td>
            <td>{{ $fmt($p['menor']) }}</td>
            <td class="no-print">@if($p['corrigidos'])<a class="btn btn-sm btn-ghost" href="{{ route('relatorios.turmaProva', [$turma['id'], $p['id']]) }}">Análise por questão</a>@endif</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- Ranking --}}
<div class="card card-pad" style="margin-top:16px;">
  <h3 style="font-size:17px;margin-bottom:6px;">Ranking dos alunos</h3>
  <div class="quadro-cel" style="overflow-x:auto;">
    <table class="tab">
      <thead>
        <tr><th>#</th><th class="esq fixa">Aluno</th>@foreach($provas as $p)<th title="{{ $p['titulo'] }}">{{ \Illuminate\Support\Str::limit($p['disciplina'], 14) }}</th>@endforeach<th>Média</th></tr>
      </thead>
      <tbody>
        @foreach($ranking as $a)
          <tr>
            <td><b>{{ $a['posicao'] ? $a['posicao'] . 'º' : '—' }}</b></td>
            <td class="esq fixa"><a href="{{ route('relatorios.aluno', $a['id']) }}" style="color:inherit;">{{ $a['nome'] }}</a></td>
            @foreach($provas as $p)
              @php $n = $a['notas'][$p['id']] ?? null; @endphp
              <td>
                @if($n === null)<span style="color:var(--muted);">—</span>
                @else<a class="nota {{ $n >= $p['nota_maxima'] * 0.6 ? 'ok' : 'baixa' }}" href="{{ route('resultados.show', [$a['id'], $p['id']]) }}?from=rel-turma" style="text-decoration:none;">{{ $fmt($n) }}</a>@endif
              </td>
            @endforeach
            <td><b>{{ $fmt($a['media']) }}</b></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <p class="no-print" style="font-size:12px;color:var(--muted);margin-top:8px;">Toque no nome para o boletim do aluno e na nota para os acertos e erros.</p>
</div>

<div style="height:48px" class="no-print"></div>
@endsection
