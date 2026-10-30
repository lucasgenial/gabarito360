@extends('layouts.app')

@section('tela', 'tela-correcao-turma')

{{--
  Arquivo: apps/web/resources/views/correcao/turma.blade.php
  Função: Acompanhamento da correção por turma (MP-038, mockup acompanhar-correcao-turma.html):
          progresso de cada prova da turma e o quadro alunos × provas com notas, quem falta
          e cartões para revisar.
--}}

@php
  $turma  = $dados['turma'];
  $resumo = $dados['resumo'];
  $provas = $dados['provas'];
  $alunos = $dados['alunos'];
  $pct    = $resumo['esperados'] > 0 ? round($resumo['lidos'] / $resumo['esperados'] * 100) : 0;
  $fmt    = fn ($n) => number_format((float) $n, 1, ',', '.');
  $statusProva = ['publicada' => ['badge-warn', 'publicada'], 'em_correcao' => ['badge-info', 'em correção'], 'corrigida' => ['badge-success', 'corrigida']];
@endphp

@section('title', 'Correção — ' . $turma['nome'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span>
<a href="{{ route('turmas.show', $turma['id']) }}">{{ $turma['nome'] }}</a><span class="sep">/</span>
<span>Correção</span>
@endsection

@section('content')
<div class="row-between" style="margin-top:12px;flex-wrap:wrap;">
  <div>
    <div class="eyebrow">Acompanhar correção da turma</div>
    <h1 class="page-title">{{ $turma['nome'] }}</h1>
    <p class="page-sub">{{ collect([$turma['escola_nome'], $turma['serie'], $resumo['alunos'] . ' alunos ativos'])->filter()->implode(' · ') }}</p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <a class="btn btn-secondary" href="{{ route('relatorios.turma', $turma['id']) }}"><x-icone nome="grafico" /> Relatório da turma</a>
    <a class="btn btn-primary" href="{{ route('correcao.index') }}"><x-icone nome="camera" /> Corrigir cartões</a>
  </div>
</div>

<div class="kpis">
  <div class="card"><div class="v" style="color:var(--accent);">{{ $resumo['lidos'] }}<span style="font-size:15px;color:var(--muted);">/{{ $resumo['esperados'] }}</span></div><div class="l">Cartões lidos · {{ $pct }}%</div></div>
  <div class="card"><div class="v">{{ $resumo['faltam'] }}</div><div class="l">Faltam corrigir</div></div>
  <div class="card"><div class="v" style="color:var(--warn-fg);">{{ $resumo['ambiguos'] }}</div><div class="l">Para revisar</div></div>
  <div class="card"><div class="v" style="color:var(--danger);">{{ $resumo['sem_aluno'] }}</div><div class="l">Cartões sem aluno</div></div>
</div>

{{-- Provas da turma --}}
@if(count($provas))
  <div class="provas-grid">
    @foreach($provas as $p)
      @php
        [$badge, $rot] = $statusProva[$p['status']] ?? ['badge-muted', $p['status']];
        $pp = $resumo['alunos'] > 0 ? round($p['lidos'] / $resumo['alunos'] * 100) : 0;
      @endphp
      <div class="prova-card">
        <div class="row-between"><b>{{ $p['disciplina'] }}</b><span class="badge {{ $badge }}">{{ $rot }}</span></div>
        <div class="meta">{{ $p['titulo'] }}{{ $p['data_aplicacao'] ? ' · ' . \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m') : '' }}</div>
        <div class="barra"><span style="width:{{ $pp }}%"></span></div>
        <div class="meta">
          {{ $p['lidos'] }} de {{ $resumo['alunos'] }} lidos · faltam {{ $p['faltam'] }}
          @if($p['ambiguos']) · <span style="color:var(--warn-fg);">{{ $p['ambiguos'] }} p/ revisar</span>@endif
          @if($p['sem_aluno']) · <span style="color:var(--danger);">{{ $p['sem_aluno'] }} sem aluno</span>@endif
          @if($p['media'] !== null) · média <b>{{ $fmt($p['media']) }}</b>@endif
        </div>
        <div class="acoes">
          @pode('cartao.enviar')<a class="btn btn-sm btn-primary" href="{{ route('correcao.show', $p['id']) }}#camera"><x-icone nome="camera" /> Corrigir</a>@endpode
          @pode('cartao.imprimir')<a class="btn btn-sm btn-secondary" target="_blank" href="{{ route('correcao.pdf', $p['id']) }}?turma_id={{ $turma['id'] }}"><x-icone nome="imprimir" /> Cartões</a>@endpode
          <a class="btn btn-sm btn-ghost" href="{{ route('correcao.show', $p['id']) }}">Acompanhar</a>
          @if($p['lidos'] > 0)
            <a class="btn btn-sm btn-ghost" href="{{ route('relatorios.turmaProva', [$turma['id'], $p['id']]) }}"><x-icone nome="grafico" /> Relatório</a>
          @endif
        </div>
      </div>
    @endforeach
  </div>
@else
  <div class="card card-pad" style="margin-top:16px;text-align:center;color:var(--muted);">
    Nenhuma prova publicada para esta turma ainda.
  </div>
@endif

{{-- Quadro alunos × provas --}}
@if(count($provas) && count($alunos))
<div class="card card-pad" style="margin-top:16px;">
  <div class="row-between" style="flex-wrap:wrap;">
    <h3 style="font-size:17px;">Alunos × provas</h3>
    <label style="font-size:13px;display:flex;gap:6px;align-items:center;cursor:pointer;">
      <input type="checkbox" id="so-falta"> Mostrar só quem falta corrigir
    </label>
  </div>
  <div class="quadro-wrap quadro-cel">
    <table class="quadro">
      <thead>
        <tr>
          <th class="aluno"><span>Aluno</span></th>
          @foreach($provas as $p)<th title="{{ $p['titulo'] }}">{{ $p['disciplina'] }}</th>@endforeach
          <th>Média</th>
        </tr>
      </thead>
      <tbody>
        @foreach($alunos as $a)
          @php
            $notas = collect($a['celulas'])->filter()->pluck('nota_final')->filter(fn ($n) => $n !== null);
            $falta = collect($a['celulas'])->contains(fn ($c) => $c === null);
          @endphp
          <tr data-falta="{{ $falta ? 1 : 0 }}">
            <td class="aluno" title="{{ $a['nome'] }}"><a href="{{ route('alunos.show', $a['id']) }}" style="color:inherit;">{{ $a['nome'] }}</a></td>
            @foreach($provas as $p)
              @php $c = $a['celulas'][$p['id']] ?? null; @endphp
              <td>
                @if(!$c)
                  <span class="cel falta" title="Ainda não corrigido">—</span>
                @elseif($c['status'] === 'ambiguo')
                  <a class="cel amb" href="{{ route('correcao.show', $p['id']) }}" title="Cartão com marcação para revisar">revisar</a>
                @elseif($c['nota_final'] !== null)
                  <a class="cel {{ $c['nota_final'] >= $p['nota_maxima'] * 0.6 ? 'ok' : 'baixa' }}"
                     href="{{ route('resultados.show', [$a['id'], $p['id']]) }}?from=rel-turma"
                     title="{{ $c['acertos'] }}/{{ $c['total'] }} acertos — ver acertos e erros">{{ $fmt($c['nota_final']) }}</a>
                @else
                  <span class="cel falta">lendo</span>
                @endif
              </td>
            @endforeach
            <td><b>{{ $notas->isNotEmpty() ? $fmt($notas->avg()) : '—' }}</b></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <p style="font-size:12px;color:var(--muted);margin-top:8px;">Toque na nota para ver os acertos e erros do aluno. "—" = cartão ainda não corrigido.</p>
</div>
@endif

<div style="height:48px"></div>
@endsection

@push('scripts')
<script>
(function () {
  var chk = document.getElementById('so-falta');
  if (!chk) return;
  chk.addEventListener('change', function () {
    document.querySelectorAll('.quadro tbody tr').forEach(function (tr) {
      tr.style.display = chk.checked && tr.dataset.falta !== '1' ? 'none' : '';
    });
  });
})();
</script>
@endpush
