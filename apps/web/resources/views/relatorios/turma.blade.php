@extends('layouts.app')

@section('tela', 'tela-rel-turma')

{{--
  Arquivo: apps/web/resources/views/relatorios/turma.blade.php
  Função: Relatório consolidado da turma em todas as provas — média e aprovação por área,
          comparação com as outras turmas da escola e ranking dos alunos. Imprimível.
--}}

@php
  $fmt  = fn ($n) => $n === null ? '—' : number_format((float) $n, 1, ',', '.');
  // Turmas que aparecem no comparativo (esta primeiro)
  $turmasComp = collect($provas)->flatMap(fn ($p) => $p['por_turma'])->pluck('turma', 'turma_id')->sortKeys();
  $turmasComp = collect([$turma['id'] => $turma['nome']])->union($turmasComp);
@endphp

@section('title', 'Relatório da turma ' . $turma['nome'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('turmas.show', $turma['id']) }}">{{ $turma['nome'] }}</a><span class="sep">/</span>
<span>Relatório da turma</span>
@endsection

@section('content')
@include('relatorios._cabecalho', ['cabecalho' => $cabecalho ?? [], 'titulo' => 'Relatório consolidado da turma', 'subtitulo' => 'Turma ' . $turma['nome']])
<div class="row-between" style="margin-top:12px;flex-wrap:wrap;">
  <div>
    <h1 class="page-title">Turma {{ $turma['nome'] }}</h1>
    <p class="page-sub">{{ collect([$turma['escola_nome'], $turma['serie'], $kpis['alunos'] . ' alunos', $kpis['provas'] . ' provas'])->filter()->implode(' · ') }}</p>
  </div>
  <div class="no-print" style="display:flex;gap:8px;flex-wrap:wrap;">
    <a class="btn btn-ghost" href="{{ route('correcao.turma', $turma['id']) }}"><x-icone nome="grafico" /> Acompanhar correção</a>
    <button type="button" class="btn btn-secondary" onclick="window.print()"><x-icone nome="imprimir" /> Imprimir / PDF</button>
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
  <h3 style="font-size:17px;">Média por área</h3>
  @php
    $grupos = count($provas);
    $listaP = collect($provas);
    $seriesT = $turmasComp->map(fn ($tnome, $tid) => [
        'nome'    => $tnome . ($tid == $turma['id'] ? ' (esta)' : ''),
        'valores' => $listaP->map(function ($p) use ($tid) {
            $m = collect($p['por_turma'])->firstWhere('turma_id', $tid)['media'] ?? null;
            return $m === null ? null : round($m * 10 / max($p['nota_maxima'], 0.1), 1);
        })->values(),
    ])->values();
  @endphp
  @if($grupos)
    <div class="grafico-rolagem">
      <div class="grafico-largo" style="min-width:{{ min(max(520, $grupos * ($turmasComp->count() * 26 + 40) + 40), 640) }}px;">
        <x-grafico descricao="Média por área e turma" :altura="280"
          :dados="['categorias' => $listaP->map(fn ($p) => \Illuminate\Support\Str::limit($p['disciplina'], 22))->values(), 'formato' => 'nota', 'maximo' => 10,
                   'rotulos' => $turmasComp->count() <= 3 && $grupos <= 5, 'series' => $seriesT]" />
      </div>
    </div>
    <p class="field-help">Notas na escala de 0 a 10.</p>
  @else
    <p class="field-help">Nenhuma prova publicada para a turma.</p>
  @endif
</div>

{{-- Resumo por prova --}}
<div class="card card-pad" style="margin-top:16px;">
  <h3 style="font-size:17px;margin-bottom:6px;">Resumo por prova</h3>
  <div>
    <x-tabela titulo="Resultados por prova" modo="lista"><table class="tabela-padrao tab "><caption class="sr-only">Resultados por prova</caption>
      <thead><tr><th scope="col" class="esq">Prova</th><th scope="col">Corrigidos</th><th scope="col">Média</th><th scope="col">Aprovação</th><th scope="col">Maior</th><th scope="col">Menor</th><th scope="col" class="no-print"></th></tr></thead>
      <tbody>
        @foreach($provas as $p)
          <tr>
            <td class="tabela-principal esq" data-rotulo="Prova"><div class="tabela-valor"><b>{{ $p['disciplina'] }}</b><div style="font-size:12px;color:var(--muted);">{{ $p['titulo'] }}</div></div></td>
            <td data-rotulo="Corrigidos"><div class="tabela-valor">{{ $p['corrigidos'] }}/{{ $kpis['alunos'] }}</div></td>
            <td data-rotulo="Média"><div class="tabela-valor"><b>{{ $fmt($p['media']) }}</b></div></td>
            <td data-rotulo="Aprovação"><div class="tabela-valor">{{ $p['aprovacao_pct'] !== null ? $p['aprovacao_pct'] . '%' : '—' }}</div></td>
            <td data-rotulo="Maior"><div class="tabela-valor">{{ $fmt($p['maior']) }}</div></td>
            <td data-rotulo="Menor"><div class="tabela-valor">{{ $fmt($p['menor']) }}</div></td>
            <td class="tabela-acoes no-print" data-rotulo="Ações"><div class="tabela-valor">@if($p['corrigidos'])<a class="btn btn-sm btn-ghost" href="{{ route('relatorios.turmaProva', [$turma['id'], $p['id']]) }}">Análise por questão</a>@endif</div></td>
          </tr>
        @endforeach
      </tbody>
    </table></x-tabela>
  </div>
</div>

{{-- Ranking --}}
<div class="card card-pad" style="margin-top:16px;">
  <h3 style="font-size:17px;margin-bottom:6px;">Ranking dos alunos</h3>
  <div class="quadro-cel" style="overflow-x:auto;">
    <x-tabela titulo="Notas por aluno e prova" modo="matriz"><table class="tabela-padrao tabela-matriz tab"><caption class="sr-only">Notas por aluno e prova</caption>
      <thead>
        <tr><th scope="col">#</th><th scope="col" class="esq fixa">Aluno</th>@foreach($provas as $p)<th scope="col" title="{{ $p['titulo'] }}">{{ $p['disciplina'] }}</th>@endforeach<th scope="col">Média</th></tr>
      </thead>
      <tbody>
        @foreach($ranking as $a)
          <tr>
            <td data-rotulo="Posição"><b>{{ $a['posicao'] ? $a['posicao'] . 'º' : '—' }}</b></td>
            <td class="tabela-principal esq fixa" data-rotulo="Aluno"><a href="{{ route('relatorios.aluno', $a['id']) }}" style="color:inherit;">{{ $a['nome'] }}</a></td>
            @foreach($provas as $p)
              @php $n = $a['notas'][$p['id']] ?? null; @endphp
              <td data-rotulo="{{ $p['disciplina'] }}">
                @if($n === null)<span style="color:var(--muted);">—</span>
                @else<a class="nota {{ $n >= $p['nota_maxima'] * 0.6 ? 'ok' : 'baixa' }}" href="{{ route('resultados.show', [$a['id'], $p['id']]) }}?from=rel-turma" style="text-decoration:none;">{{ $fmt($n) }}</a>@endif
              </td>
            @endforeach
            <td data-rotulo="Média"><b>{{ $fmt($a['media']) }}</b></td>
          </tr>
        @endforeach
      </tbody>
    </table></x-tabela>
  </div>
  <p class="no-print" style="font-size:12px;color:var(--muted);margin-top:8px;">Toque no nome para o boletim do aluno e na nota para os acertos e erros.</p>
</div>

<div style="height:48px" class="no-print"></div>
@endsection
