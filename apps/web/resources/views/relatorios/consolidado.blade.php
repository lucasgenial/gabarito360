@extends('layouts.app')

@section('tela', 'tela-rel-consolidado')

{{--
  Arquivo: apps/web/resources/views/relatorios/consolidado.blade.php
  Função: Relatório consolidado de escola, núcleo ou rede (MP-034): indicadores, média por
          área, comparativo entre as unidades do nível abaixo (turmas ou escolas) e, na
          escola, destaques e alunos que precisam de atenção. Médias na escala 0–10.
--}}

@php
  $rotNivel = ['escola' => 'Relatório da escola', 'nucleo' => 'Relatório do núcleo', 'rede' => 'Relatório da rede'][$nivel] ?? 'Relatório';
  $fmt = fn ($n) => $n === null ? '—' : number_format((float) $n, 1, ',', '.');
@endphp

@section('title', $rotNivel . (isset($unidade) ? ' — ' . $unidade['nome'] : ''))

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>{{ $rotNivel }}</span>
@endsection

@section('content')

@if(!empty($escolher))
  {{-- Admin com mais de uma rede: escolher qual --}}
  <h1 class="page-title" style="margin-top:12px;">{{ $rotNivel }}</h1>
  <p class="page-sub">Escolha a rede:</p>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
    @foreach($escolher as $r)<a class="btn btn-secondary" href="{{ route('relatorios.rede', $r['id']) }}">{{ $r['nome'] }}</a>@endforeach
  </div>
@else

@include('relatorios._cabecalho', ['cabecalho' => $cabecalho ?? [], 'titulo' => $rotNivel, 'subtitulo' => $nivel === 'escola' ? null : $unidade['nome']])

<div class="row-between" style="margin-top:12px;flex-wrap:wrap;">
  <div>
    <div class="eyebrow">{{ $rotNivel }}</div>
    <h1 class="page-title">{{ $unidade['nome'] }}</h1>
    <p class="page-sub">{{ collect([$unidade['detalhe'] ?? null, $nivel !== 'escola' ? $kpis['escolas'] . ' escolas' : null, $kpis['alunos'] . ' alunos', $kpis['provas'] . ' provas'])->filter()->implode(' · ') }}</p>
  </div>
  <div class="no-print" style="display:flex;gap:8px;flex-wrap:wrap;">
    @if($nivel === 'rede' && !empty($redes) && count($redes) > 1)
      @foreach($redes as $r)@if($r['id'] != $unidade['id'])<a class="btn btn-ghost" href="{{ route('relatorios.rede', $r['id']) }}">{{ $r['nome'] }}</a>@endif @endforeach
    @endif
    <button type="button" class="btn btn-secondary" onclick="window.print()"><x-icone nome="imprimir" /> Imprimir / PDF</button>
  </div>
</div>

<div class="kpis">
  <div class="card"><div class="v" style="color:var(--accent);">{{ $fmt($kpis['media']) }}</div><div class="l">Média geral (0–10)</div></div>
  <div class="card"><div class="v">{{ $kpis['aprovacao_pct'] !== null ? $kpis['aprovacao_pct'] . '%' : '—' }}</div><div class="l">Aprovação (≥ 6,0)</div></div>
  <div class="card"><div class="v">{{ $kpis['notas'] }}</div><div class="l">Notas lançadas</div></div>
  <div class="card"><div class="v">{{ $kpis['alunos_com_nota'] }}<span style="font-size:14px;color:var(--muted);">/{{ $kpis['alunos'] }}</span></div><div class="l">Alunos avaliados</div></div>
</div>

{{-- Média por área --}}
<div class="card card-pad" style="margin-top:16px;">
  <h3 style="font-size:17px;">Média por área</h3>
  @if(count($areas))
    @php $na = count($areas); $larg = max(420, $na * 110 + 40); @endphp
    <svg viewBox="0 0 {{ $larg }} 190" width="100%" role="img" aria-label="Média por área" style="margin-top:8px;">
      @foreach([0, 5, 10] as $g)
        <line x1="28" x2="{{ $larg - 6 }}" y1="{{ 150 - $g * 12 }}" y2="{{ 150 - $g * 12 }}" stroke="#ddd" stroke-dasharray="{{ $g ? '3 3' : '' }}"></line>
        <text x="24" y="{{ 153 - $g * 12 }}" text-anchor="end" font-size="9" fill="#888">{{ $g }}</text>
      @endforeach
      @foreach($areas as $i => $a)
        @php $x = 40 + $i * (($larg - 50) / $na); $h = $a['media'] * 12; @endphp
        <rect x="{{ $x + 10 }}" y="{{ 150 - $h }}" width="44" height="{{ max($h, 1) }}" rx="3" fill="{{ $a['media'] >= 6 ? '#168821' : '#e52207' }}" opacity=".85"></rect>
        <text x="{{ $x + 32 }}" y="{{ 145 - $h }}" text-anchor="middle" font-size="10" font-weight="700" fill="currentColor">{{ $fmt($a['media']) }}</text>
        <text x="{{ $x + 32 }}" y="166" text-anchor="middle" font-size="10" fill="#555">{{ \Illuminate\Support\Str::limit($a['disciplina'], 18) }}</text>
        <text x="{{ $x + 32 }}" y="179" text-anchor="middle" font-size="8.5" fill="#888">{{ $a['aprovacao_pct'] }}% aprov. · {{ $a['notas'] }} notas</text>
      @endforeach
    </svg>
  @else
    <p class="field-help" style="margin-top:6px;">Sem notas lançadas ainda.</p>
  @endif
</div>

{{-- Comparativo entre turmas/escolas --}}
<div class="card card-pad" style="margin-top:16px;">
  <h3 style="font-size:17px;margin-bottom:6px;">Comparativo entre {{ $grupo === 'turma' ? 'turmas' : 'escolas' }}</h3>
  @php $disciplinas = collect($areas)->pluck('disciplina'); @endphp
  <div class="quadro-cel" style="overflow-x:auto;">
    <table class="tab">
      <thead>
        <tr>
          <th>#</th><th class="esq fixa">{{ $grupo === 'turma' ? 'Turma' : 'Escola' }}</th>
          @foreach($disciplinas as $d)<th>{{ \Illuminate\Support\Str::limit($d, 14) }}</th>@endforeach
          <th>Média</th><th>Aprov.</th><th>Alunos</th>
        </tr>
      </thead>
      <tbody>
        @foreach($grupos as $i => $g)
          <tr>
            <td>{{ $g['media'] !== null ? ($i + 1) . 'º' : '—' }}</td>
            <td class="esq fixa">
              @if($grupo === 'turma')
                <a href="{{ route('relatorios.turma', $g['id']) }}" style="color:inherit;"><b>{{ $g['nome'] }}</b></a>
              @else
                <a href="{{ route('relatorios.escola', $g['id']) }}" style="color:inherit;"><b>{{ $g['nome'] }}</b></a>
              @endif
            </td>
            @foreach($disciplinas as $d)
              @php $v = $g['por_area'][$d] ?? null; @endphp
              <td>@if($v !== null)<span class="m {{ $v >= 6 ? 'ok' : 'baixa' }}">{{ $fmt($v) }}</span>@else<span style="color:var(--muted);">—</span>@endif</td>
            @endforeach
            <td><b>{{ $fmt($g['media']) }}</b></td>
            <td>{{ $g['aprovacao_pct'] !== null ? $g['aprovacao_pct'] . '%' : '—' }}</td>
            <td>{{ $g['alunos'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <p class="no-print" style="font-size:12px;color:var(--muted);margin-top:8px;">Toque no nome para abrir o relatório {{ $grupo === 'turma' ? 'da turma' : 'da escola' }}.</p>
</div>

@if($nivel === 'escola')
  <div class="duas">
    <div class="card card-pad" style="margin-top:0;">
      <h3 style="font-size:16px;color:#168821;">Destaques</h3>
      <ul style="list-style:none;padding:0;margin:6px 0 0;">
        @forelse($destaques as $a)
          <li><span><a href="{{ route('relatorios.aluno', $a['id']) }}" style="color:inherit;">{{ $a['nome'] }}</a> <span style="color:var(--muted);font-size:12px;">· {{ $a['turma'] }}</span></span><b>{{ $fmt($a['media']) }}</b></li>
        @empty
          <li style="color:var(--muted);">Sem notas ainda.</li>
        @endforelse
      </ul>
    </div>
    <div class="card card-pad" style="margin-top:0;">
      <h3 style="font-size:16px;color:#e52207;">Precisam de atenção (média abaixo de 6,0)</h3>
      <ul style="list-style:none;padding:0;margin:6px 0 0;">
        @forelse($atencao as $a)
          <li><span><a href="{{ route('relatorios.aluno', $a['id']) }}" style="color:inherit;">{{ $a['nome'] }}</a> <span style="color:var(--muted);font-size:12px;">· {{ $a['turma'] }}</span></span><b style="color:#e52207;">{{ $fmt($a['media']) }}</b></li>
        @empty
          <li style="color:var(--muted);">Nenhum aluno abaixo de 6,0.</li>
        @endforelse
      </ul>
    </div>
  </div>
@endif

@endif
<div style="height:48px" class="no-print"></div>
@endsection
