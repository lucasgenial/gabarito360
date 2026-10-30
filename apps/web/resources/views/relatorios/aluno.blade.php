@extends('layouts.app')

{{--
  Arquivo: apps/web/resources/views/relatorios/aluno.blade.php
  Função: Boletim do aluno — todas as provas da turma com nota, acertos, média da turma e
          posição; gráfico aluno × turma. Imprimível e com envio do resumo por WhatsApp.
--}}

@php
  $fmt = fn ($n) => $n === null ? '—' : number_format((float) $n, 1, ',', '.');
  $linhasWhats = collect($provas)->map(fn ($p) => '• ' . $p['disciplina'] . ': ' . ($p['nota'] !== null ? $fmt($p['nota']) . ' (' . $p['acertos'] . '/' . $p['total'] . ')' : 'não corrigida'))->implode("\n");
  $textoWhats  = "Boletim — {$aluno['nome']} · Turma {$turma['nome']}\n" . ($turma['escola_nome'] ?? '') . "\n\n{$linhasWhats}\n\nMédia: " . $fmt($kpis['media']) . ' · média da turma: ' . $fmt($kpis['media_turma']);
@endphp

@section('title', 'Boletim — ' . $aluno['nome'])

@push('styles')
<style>
  .kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-top:16px; }
  .kpis .card { padding:14px 16px; }
  .kpis .v { font-family:var(--font-mono); font-size:26px; font-weight:700; }
  .kpis .l { font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; }
  .tab { width:100%; border-collapse:collapse; font-size:14px; }
  .tab th, .tab td { padding:9px 10px; border-bottom:1px solid var(--border-soft); text-align:center; }
  .tab th { font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
  .tab .esq { text-align:left; }
  .nota { font-family:var(--font-mono); font-weight:700; font-size:16px; }
  @media (max-width:720px) { .kpis { grid-template-columns:1fr 1fr; } .tab .opc { display:none; } }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('relatorios.turma', $turma['id']) }}">Turma {{ $turma['nome'] }}</a><span class="sep">/</span>
<span>Boletim</span>
@endsection

@section('content')
@include('relatorios._cabecalho', ['cabecalho' => $cabecalho ?? [], 'titulo' => 'Boletim de avaliações', 'subtitulo' => 'Turma ' . $turma['nome']])
<div class="row-between" style="margin-top:12px;flex-wrap:wrap;">
  <div>
    <div class="eyebrow no-print">Boletim do aluno</div>
    <h1 class="page-title" style="margin:4px 0 2px;">{{ $aluno['nome'] }}</h1>
    <p class="page-sub">{{ collect(['Turma ' . $turma['nome'], $aluno['matricula'] ? 'Matrícula ' . $aluno['matricula'] : null, $aluno['nome_responsavel'] ? 'Responsável: ' . $aluno['nome_responsavel'] : null])->filter()->implode(' · ') }}</p>
  </div>
  <div class="no-print" style="display:flex;gap:8px;flex-wrap:wrap;">
    <button type="button" class="btn btn-secondary" onclick="window.print()">🖨 Imprimir / PDF</button>
    <a class="btn btn-primary" style="background:#1fa855;border-color:#1fa855;" target="_blank" rel="noopener"
       href="https://wa.me/?text={{ rawurlencode($textoWhats) }}">📲 WhatsApp</a>
    <a class="btn btn-ghost" href="{{ route('alunos.show', $aluno['id']) }}">Cadastro</a>
  </div>
</div>

<div class="kpis">
  <div class="card"><div class="v" style="color:var(--accent);">{{ $fmt($kpis['media']) }}</div><div class="l">Média do aluno</div></div>
  <div class="card"><div class="v">{{ $fmt($kpis['media_turma']) }}</div><div class="l">Média da turma</div></div>
  <div class="card"><div class="v">{{ $kpis['feitas'] }}<span style="font-size:14px;color:var(--muted);">/{{ $kpis['provas'] }}</span></div><div class="l">Provas corrigidas</div></div>
  <div class="card"><div class="v">{{ $kpis['acertos'] }}<span style="font-size:14px;color:var(--muted);">/{{ $kpis['questoes'] }}</span></div><div class="l">Acertos</div></div>
</div>

{{-- Aluno × média da turma por prova --}}
@php $n = count($provas); $larg = max(420, $n * 110 + 40); @endphp
@if($n)
<div class="card card-pad" style="margin-top:16px;">
  <div class="row-between" style="flex-wrap:wrap;">
    <h3 style="font-size:17px;">Aluno × turma</h3>
    <span style="font-size:12px;color:var(--muted);"><span style="color:#1351b4;">■</span> aluno · <span style="color:#a7b6cc;">■</span> média da turma</span>
  </div>
  <div style="overflow-x:auto;margin-top:8px;">
    <svg viewBox="0 0 {{ $larg }} 190" width="100%" style="min-width:{{ min($larg, 560) }}px;" role="img" aria-label="Nota do aluno e média da turma por prova">
      @foreach([0, 5, 10] as $g)
        <line x1="28" x2="{{ $larg - 6 }}" y1="{{ 150 - $g * 12 }}" y2="{{ 150 - $g * 12 }}" stroke="#ddd" stroke-dasharray="{{ $g ? '3 3' : '' }}"></line>
        <text x="24" y="{{ 153 - $g * 12 }}" text-anchor="end" font-size="9" fill="#888">{{ $g }}</text>
      @endforeach
      @foreach($provas as $i => $p)
        @php
          $esc = 10 / max($p['nota_maxima'], 0.1); $x = 40 + $i * (($larg - 50) / $n);
          $ha = ($p['nota'] ?? 0) * $esc * 12; $ht = ($p['media_turma'] ?? 0) * $esc * 12;
        @endphp
        @if($p['nota'] !== null)
          <rect x="{{ $x }}" y="{{ 150 - $ha }}" width="30" height="{{ max($ha, 1) }}" rx="2" fill="#1351b4"></rect>
          <text x="{{ $x + 15 }}" y="{{ 146 - $ha }}" text-anchor="middle" font-size="10" font-weight="700" fill="currentColor">{{ $fmt($p['nota']) }}</text>
        @else
          <text x="{{ $x + 15 }}" y="146" text-anchor="middle" font-size="9" fill="#999">—</text>
        @endif
        @if($p['media_turma'] !== null)
          <rect x="{{ $x + 34 }}" y="{{ 150 - $ht }}" width="30" height="{{ max($ht, 1) }}" rx="2" fill="#a7b6cc"></rect>
          <text x="{{ $x + 49 }}" y="{{ 146 - $ht }}" text-anchor="middle" font-size="9" fill="#555">{{ $fmt($p['media_turma']) }}</text>
        @endif
        <text x="{{ $x + 32 }}" y="168" text-anchor="middle" font-size="10" fill="#555">{{ \Illuminate\Support\Str::limit($p['disciplina'], 18) }}</text>
      @endforeach
    </svg>
  </div>
</div>
@endif

<div class="card card-pad" style="margin-top:16px;">
  <h3 style="font-size:17px;margin-bottom:6px;">Provas</h3>
  <div style="overflow-x:auto;">
    <table class="tab lista-cel">
      <thead><tr><th class="esq">Prova</th><th>Nota</th><th>Acertos</th><th class="opc">Média turma</th><th>Posição</th><th class="no-print"></th></tr></thead>
      <tbody>
        @foreach($provas as $p)
          <tr>
            <td class="esq"><b>{{ $p['disciplina'] }}</b><div style="font-size:12px;color:var(--muted);">{{ $p['titulo'] }}{{ $p['lingua'] ? ' · ' . ($p['lingua'] === 'ESP' ? 'Espanhol' : 'Inglês') : '' }}</div></td>
            <td>
              @if($p['nota'] !== null)
                <span class="nota" style="color:{{ $p['nota'] >= $p['nota_maxima'] * 0.6 ? 'var(--success,#168821)' : 'var(--danger,#e52207)' }};">{{ $fmt($p['nota']) }}</span>
              @else
                <span style="color:var(--muted);">não corrigida</span>
              @endif
            </td>
            <td>{{ $p['nota'] !== null ? $p['acertos'] . '/' . $p['total'] : '—' }}</td>
            <td class="opc">{{ $fmt($p['media_turma']) }}</td>
            <td>{{ $p['posicao'] ? $p['posicao'] . 'º de ' . $p['corrigidos'] : '—' }}</td>
            <td class="no-print">@if($p['nota'] !== null)<a class="btn btn-sm btn-ghost" href="{{ route('resultados.show', [$aluno['id'], $p['id']]) }}?from=rel-turma">Acertos e erros</a>@endif</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
<div style="height:48px" class="no-print"></div>
@endsection
