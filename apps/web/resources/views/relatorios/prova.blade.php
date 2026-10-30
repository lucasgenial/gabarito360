@extends('layouts.app')

@section('tela', 'tela-rel-prova')

@section('title', 'Relatório — ' . $prova['titulo'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('provas.index') }}">Provas</a><span class="sep">/</span>
<a href="{{ route('provas.show', $prova['id']) }}">{{ $prova['titulo'] }}</a><span class="sep">/</span>
<span>Relatório</span>
@endsection

@section('content')
@include('relatorios._cabecalho', ['cabecalho' => $cabecalho ?? [], 'titulo' => 'Relatório da prova · ' . $prova['titulo'], 'subtitulo' => $prova['disciplina']])

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">{{ $prova['titulo'] }}</h1>
    <p class="page-sub">{{ $prova['disciplina'] }} · {{ implode(' · ', $prova['turmas'] ?? []) }}{{ $prova['data_aplicacao'] ? ' · aplicada em ' . \Carbon\Carbon::parse($prova['data_aplicacao'])->format('d/m/Y') : '' }} · {{ $kpis['cartoes_lidos'] }}/{{ $kpis['cartoes_total'] }} cartões corrigidos</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;">
    <button class="btn btn-secondary no-print" onclick="window.print()"><x-icone nome="imprimir" /> Imprimir / PDF</button>
    <span class="badge {{ $prova['status'] === 'corrigida' ? 'badge-success' : 'badge-info' }} badge-dot">
      {{ ['rascunho'=>'Rascunho','publicada'=>'Publicada','em_correcao'=>'Em correção','corrigida'=>'Corrigida'][$prova['status']] ?? $prova['status'] }}
    </span>
  </div>
</div>

<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Média da prova</div>
    <div class="kpi-value">{{ number_format($kpis['media'], 1, ',', '.') }}</div>
    <div class="kpi-trend {{ $kpis['media'] >= $kpis['meta_rede'] ? 'up' : 'down' }}">
      {{ $kpis['media'] >= $kpis['meta_rede'] ? '▲ acima' : '▼ abaixo' }} da meta da rede ({{ number_format($kpis['meta_rede'], 1, ',', '.') }})
    </div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Aprovação</div>
    <div class="kpi-value">{{ $kpis['aprovacao_pct'] }}%</div>
    <div class="kpi-trend">{{ $kpis['aprovados'] }} de {{ $kpis['total_alunos'] }} alunos aprovados</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Cartões corrigidos</div>
    <div class="kpi-value">{{ $kpis['cartoes_lidos'] }}/{{ $kpis['cartoes_total'] }}</div>
    <div class="kpi-trend {{ $kpis['cartoes_total'] > 0 && $kpis['cartoes_lidos'] === $kpis['cartoes_total'] ? 'up' : '' }}">
      {{ $kpis['cartoes_total'] > 0 ? round($kpis['cartoes_lidos'] / $kpis['cartoes_total'] * 100) : 0 }}% do total
    </div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Pendências de leitura</div>
    <div class="kpi-value">{{ $kpis['pendencias'] }}</div>
    <div class="kpi-trend">{{ $kpis['pendencias'] === 0 ? 'nenhuma marcação ambígua' : 'marcações ambíguas pendentes' }}</div>
  </div>
</div>

<div class="result-head">
  <div class="card card-pad">
    @include('relatorios._distribuicao-notas', ['alunos' => $alunos, 'notaMaxima' => $prova['nota_maxima']])
  </div>
  <div class="card card-pad">
    <h3 style="font-size:16px;margin-bottom:6px;text-align:center;">Aproveitamento médio</h3>
    <div style="display:flex;justify-content:center;margin-top:8px;">
      <div id="donut-media"></div>
    </div>
    <p style="text-align:center;color:var(--muted);font-size:14px;margin-top:8px;">Nota média {{ number_format($kpis['media'], 1, ',', '.') }} de {{ number_format($prova['nota_maxima'], 1, ',', '.') }} · meta da rede {{ number_format($kpis['meta_rede'], 1, ',', '.') }}</p>
  </div>
</div>

@include('relatorios._analise-questoes', ['questoes' => $questoes ?? []])

<div class="card" style="margin-top:20px;overflow:hidden;">
  <div class="card-pad" style="padding-bottom:0;">
    <h3 style="font-size:17px;">Resultado por aluno</h3>
  </div>
  <table class="table lista-cel">
    <thead><tr><th>Aluno</th><th>Turma</th><th>Nota</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse($alunos as $a)
        <tr>
          <td>
            <div class="student-name">
              <span class="av">{{ strtoupper(implode('', array_map(fn($p) => $p[0], array_slice(explode(' ', $a['aluno_nome']), 0, 2)))) }}</span>
              <b>{{ $a['aluno_nome'] }}</b>
            </div>
          </td>
          <td><span class="badge badge-info">{{ $a['turma_nome'] }}</span></td>
          <td class="num"><b>{{ number_format($a['nota_final'], 1, ',', '.') }}</b></td>
          <td>
            <span class="badge {{ $a['status_aprovacao'] === 'aprovado' ? 'badge-success' : 'badge-warn' }} badge-dot">
              {{ $a['status_aprovacao'] === 'aprovado' ? 'Aprovado' : 'Recuperação' }}
            </span>
          </td>
          <td style="text-align:right;">
            <a href="{{ route('resultados.show', [$a['aluno_id'], $prova['id']]) }}?from=prova" class="btn btn-result btn-sm" aria-label="Ver prova">Ver prova</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--muted);">Nenhum resultado disponível ainda.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div style="height:48px"></div>
@endsection

@push('scripts')
<script>
var donut = document.getElementById('donut-media');
donut.dataset.donut = {{ (int) round($kpis['media'] / max($prova['nota_maxima'], 0.1) * 100) }};
donut.dataset.color = '{{ $kpis['media'] >= $kpis['meta_rede'] ? '#168821' : '#1351b4' }}';
if (typeof drawDonut === 'function') drawDonut(donut);
</script>
@endpush
