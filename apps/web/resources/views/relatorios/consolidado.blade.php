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
    @php $listaA = collect($areas); @endphp
    <x-grafico descricao="Média por área" :altura="260"
      :dados="['categorias' => $listaA->map(fn ($a) => \Illuminate\Support\Str::limit($a['disciplina'], 18))->values(), 'formato' => 'nota', 'maximo' => 10, 'rotulos' => true,
               'series' => [['nome' => 'Média', 'valores' => $listaA->pluck('media'),
                             'cores' => $listaA->map(fn ($a) => $a['media'] >= 6 ? '--serie-1' : '--danger')->values()]]]">
      <table class="table">
        <thead><tr><th>Área</th><th>Média</th><th>Aprovação</th><th>Notas</th></tr></thead>
        <tbody>
          @foreach($areas as $a)
            <tr><td>{{ $a['disciplina'] }}</td><td class="num">{{ $fmt($a['media']) }}</td><td class="num">{{ $a['aprovacao_pct'] }}%</td><td class="num">{{ $a['notas'] }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </x-grafico>
    <p class="field-help">Em vermelho, média abaixo de 6.</p>
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
          <li><span><a href="{{ route('relatorios.aluno', $a['id']) }}" style="color:inherit;">{{ $a['nome'] }}</a> <span style="color:var(--muted);font-size:12px;">· {{ $a['turma'] }}</span></span><b style="color:var(--danger);">{{ $fmt($a['media']) }}</b></li>
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
