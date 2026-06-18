@extends('layouts.app')

@section('title', 'Relatório da turma — ' . $prova['titulo'])

@push('styles')
<style>
  .kpi-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-top:22px; }
  .result-head { display:grid;grid-template-columns:1.4fr 1fr;gap:20px;margin-top:20px; }
  .student-name { display:flex;align-items:center;gap:12px; }
  .student-name .av { width:36px;height:36px;border-radius:50%;background:var(--accent-light);color:var(--accent-dark,#0c326f);display:grid;place-items:center;font-weight:700;font-size:13px;flex-shrink:0; }
  .btn-result { background:var(--success-light);color:var(--success); }
  .btn-result:hover { filter:brightness(0.95); }
  .mini-bar { width:100px;height:7px;background:var(--surface-2);border-radius:99px;overflow:hidden;margin-right:8px;display:inline-block;vertical-align:middle; }
  .mini-bar > div { height:100%; }
  @media (max-width:980px){ .kpi-grid{grid-template-columns:repeat(2,1fr);} .result-head{grid-template-columns:1fr;} }
  @media (max-width:560px){ .kpi-grid{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('provas.index') }}">Provas</a>
<a href="{{ route('turmas.index') }}" class="active">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span>
<a href="{{ route('turmas.show', $turma['id']) }}">{{ $turma['nome'] }}</a><span class="sep">/</span>
<span>Relatório da prova</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">{{ $prova['titulo'] }}</h1>
    <p class="page-sub">{{ $prova['disciplina'] }} · {{ $turma['nome'] }}{{ $prova['data_aplicacao'] ? ' · aplicada em ' . \Carbon\Carbon::parse($prova['data_aplicacao'])->format('d/m/Y') : '' }} · {{ $kpis['cartoes_total'] > 0 ? round($kpis['cartoes_lidos'] / $kpis['cartoes_total'] * 100) : 0 }}% corrigido</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;">
    <button class="btn btn-secondary" onclick="window.print()">Exportar PDF</button>
    <span class="badge {{ $prova['status'] === 'corrigida' ? 'badge-success' : 'badge-info' }} badge-dot">
      {{ ['rascunho'=>'Rascunho','publicada'=>'Publicada','em_correcao'=>'Em correção','corrigida'=>'Corrigida'][$prova['status']] ?? $prova['status'] }}
    </span>
  </div>
</div>

<div class="kpi-grid">
  <div class="card kpi">
    <div class="kpi-label">Média da turma</div>
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
    <div class="kpi-label">Participação</div>
    <div class="kpi-value">{{ $kpis['cartoes_total'] > 0 ? round($kpis['cartoes_lidos'] / $kpis['cartoes_total'] * 100) : 0 }}%</div>
    <div class="kpi-trend">{{ $kpis['total_alunos'] }} alunos com resultado</div>
  </div>
  <div class="card kpi">
    <div class="kpi-label">Destaque</div>
    <div class="kpi-value">{{ $kpis['maior_nota'] !== null ? number_format($kpis['maior_nota'], 1, ',', '.') : '—' }}</div>
    <div class="kpi-trend">maior nota da turma</div>
  </div>
</div>

<div class="result-head">
  <div class="card card-pad">
    <h3 style="font-size:16px;margin-bottom:6px;">Desempenho por tema (Turma)</h3>
    <p class="field-help">A análise de acertos por tema/assunto será disponibilizada em uma versão futura, quando as questões puderem ser classificadas por tema no cadastro da prova.</p>
  </div>
  <div class="card card-pad">
    <h3 style="font-size:16px;margin-bottom:6px;text-align:center;">Aproveitamento da turma</h3>
    <div style="display:flex;justify-content:center;margin-top:8px;">
      <div id="donut-media"></div>
    </div>
    <p style="text-align:center;color:var(--muted);font-size:14px;margin-top:8px;">Nota média {{ number_format($kpis['media'], 1, ',', '.') }} de {{ number_format($prova['nota_maxima'], 1, ',', '.') }}</p>
  </div>
</div>

<div class="card" style="margin-top:20px;overflow:hidden;">
  <div class="card-pad" style="padding-bottom:0;">
    <h3 style="font-size:17px;">Resultado detalhado por aluno</h3>
  </div>
  <table class="table">
    <thead><tr><th>Aluno</th><th>Nota</th><th>Desempenho</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse($alunos as $a)
        @php
          $pct = $prova['nota_maxima'] > 0 ? round($a['nota_final'] / $prova['nota_maxima'] * 100) : 0;
          $cor = $pct >= 70 ? 'var(--success)' : ($pct >= 50 ? 'var(--warn-fg,#b45309)' : 'var(--danger)');
        @endphp
        <tr>
          <td>
            <div class="student-name">
              <span class="av">{{ strtoupper(implode('', array_map(fn($p) => $p[0], array_slice(explode(' ', $a['aluno_nome']), 0, 2)))) }}</span>
              <b>{{ $a['aluno_nome'] }}</b>
            </div>
          </td>
          <td class="num"><b>{{ number_format($a['nota_final'], 1, ',', '.') }}</b></td>
          <td>
            <div style="display:flex;align-items:center;">
              <span class="mini-bar"><div style="width:{{ $pct }}%;background:{{ $cor }};"></div></span>
              <span>{{ $pct }}%</span>
            </div>
          </td>
          <td>
            <span class="badge {{ $a['status_aprovacao'] === 'aprovado' ? 'badge-success' : 'badge-warn' }} badge-dot">
              {{ $a['status_aprovacao'] === 'aprovado' ? 'Aprovado' : 'Recuperação' }}
            </span>
          </td>
          <td style="text-align:right;">
            <a href="{{ route('resultados.show', [$a['aluno_id'], $prova['id']]) }}?from=rel-turma&turma={{ $turma['id'] }}" class="btn btn-result btn-sm">Ver detalhes</a>
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
donut.dataset.color = {{ $kpis['media'] >= $kpis['meta_rede'] ? "'#168821'" : "'#1351b4'" }};
if (typeof drawDonut === 'function') drawDonut(donut);
</script>
@endpush
