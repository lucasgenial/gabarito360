@extends('layouts.app')

@section('title', 'Resultado — ' . $dados['aluno']['nome'])

@push('styles')
<style>
  .result-head { display:grid;grid-template-columns:1.4fr 1fr;gap:20px;margin-top:20px; }
  .perf-row { display:flex;align-items:center;gap:28px; }
  .perf-meta { display:grid;grid-template-columns:repeat(3,auto);gap:26px; }
  .perf-meta .v { font-family:var(--font-mono);font-size:26px;font-weight:700; }
  .perf-meta .l { font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-top:2px; }
  .answer-sheet { display:grid;grid-template-columns:repeat(auto-fill,minmax(54px,1fr));gap:8px;margin-top:14px; }
  .ans { border-radius:8px;padding:8px 4px;text-align:center;border:1.5px solid var(--border-soft);font-family:var(--font-mono);position:relative; }
  .ans .qn { font-size:11px;color:var(--muted); }
  .ans .av { font-size:16px;font-weight:700;margin-top:2px; }
  .ans.ok { background:var(--success-light);border-color:#bfe3bd; } .ans.ok .av { color:var(--success); }
  .ans.no { background:var(--danger-light);border-color:#f4c4bd; } .ans.no .av { color:var(--danger); }
  .ans.bl { background:var(--warn-light,#fdf6e3);border-color:#efdd8a; } .ans.bl .av { color:var(--warn-fg,#b45309); }
  .ans.an { background:var(--surface-2);border-color:var(--border); } .ans.an .av { color:var(--muted); }
  .sheet-legend { display:flex;gap:18px;font-size:13px;color:var(--muted);margin-top:14px;flex-wrap:wrap; }
  .sheet-legend i { width:12px;height:12px;border-radius:3px;display:inline-block;margin-right:6px;vertical-align:middle; }
  @media print { .no-print { display:none!important; } }
  @media (max-width:900px){ .result-head{grid-template-columns:1fr;} .perf-row{flex-direction:column;align-items:flex-start;gap:18px;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('provas.index') }}">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('breadcrumb')
@php
  $aluno   = $dados['aluno'];
  $prova   = $dados['prova'];
@endphp
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
@if($from === 'prova')
  <a href="{{ route('provas.index') }}">Provas</a><span class="sep">/</span>
  <a href="{{ route('relatorios.prova', $prova['id']) }}">Relatório da Prova</a><span class="sep">/</span>
  <span>{{ $aluno['nome'] }}</span>
@elseif($from === 'rel-turma' && $aluno['turma_id'])
  <a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span>
  <a href="{{ route('turmas.show', $aluno['turma_id']) }}">{{ $aluno['turma_nome'] }}</a><span class="sep">/</span>
  <a href="{{ route('relatorios.turmaProva', [$aluno['turma_id'], $prova['id']]) }}">Relatório da Prova</a><span class="sep">/</span>
  <span>{{ $aluno['nome'] }}</span>
@else
  <a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span>
  @if($aluno['turma_id'])
    <a href="{{ route('turmas.show', $aluno['turma_id']) }}">{{ $aluno['turma_nome'] }}</a><span class="sep">/</span>
  @endif
  <span>{{ $aluno['nome'] }}</span>
@endif
@endsection

@section('content')

<div class="row-between no-print" style="margin-top:12px;">
  <div style="display:flex;align-items:center;gap:16px;">
    <div class="avatar" style="width:52px;height:52px;font-size:18px;">{{ strtoupper(implode('', array_map(fn($p) => $p[0], array_slice(explode(' ', $aluno['nome']), 0, 2)))) }}</div>
    <div>
      <h1 class="page-title" style="margin:0;">{{ $aluno['nome'] }}</h1>
      <p class="page-sub">{{ $aluno['turma_nome'] }} · {{ $prova['titulo'] }}{{ $prova['data_aplicacao'] ? ' · ' . \Carbon\Carbon::parse($prova['data_aplicacao'])->format('d/m/Y') : '' }}</p>
    </div>
  </div>
  <div style="display:flex;gap:10px;">
    <button class="btn btn-secondary" onclick="window.print()">Exportar PDF</button>
    @if($dados['cartao_id'])
      <button class="btn btn-secondary" onclick="document.getElementById('modal-revisar').style.display='flex'">Revisar leitura</button>
    @endif
  </div>
</div>

<div class="result-head">
  <div class="card card-pad">
    <div class="perf-row">
      <div id="donut-nota"></div>
      <div class="perf-meta">
        <div><div class="v" id="nota-final" style="color:{{ $dados['nota']['status_aprovacao'] === 'aprovado' ? 'var(--success)' : 'var(--danger)' }};">{{ number_format($dados['nota']['nota_final'], 1, ',', '.') }}</div><div class="l">Nota final</div></div>
        <div><div class="v">{{ $dados['nota']['acertos'] }}</div><div class="l">Acertos</div></div>
        <div><div class="v">{{ $dados['nota']['total_questoes'] }}</div><div class="l">Questões</div></div>
      </div>
    </div>
    <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
      <span class="badge {{ $dados['nota']['status_aprovacao'] === 'aprovado' ? 'badge-success' : 'badge-danger' }} badge-dot">
        {{ $dados['nota']['status_aprovacao'] === 'aprovado' ? 'Aprovada' : 'Recuperação' }}
      </span>
      @if($dados['media_turma'] !== null)
        <span class="badge badge-info">
          {{ $dados['nota']['nota_final'] >= $dados['media_turma'] ? 'Acima' : 'Abaixo' }} da média da turma ({{ number_format($dados['media_turma'], 1, ',', '.') }})
        </span>
      @endif
    </div>
  </div>
  <div class="card card-pad">
    <h3 style="font-size:16px;margin-bottom:6px;">Confiança da leitura</h3>
    @if($dados['confianca_geral'] !== null)
      <p style="font-size:28px;font-weight:700;font-family:var(--font-mono);color:var(--accent);">{{ number_format($dados['confianca_geral'] * 100, 1, ',', '.') }}%</p>
      <p class="field-help">Confiança média da leitura OMR neste cartão-resposta.</p>
    @else
      <p style="color:var(--muted);font-size:14px;">Não disponível.</p>
    @endif
  </div>
</div>

<div class="card card-pad" style="margin-top:20px;">
  <div class="row-between">
    <h3 style="font-size:17px;">Folha de respostas corrigida</h3>
    @if($dados['confianca_geral'] !== null)
      <span class="badge badge-info">Leitura OMR · {{ number_format($dados['confianca_geral'] * 100, 1, ',', '.') }}% de confiança</span>
    @endif
  </div>
  <div class="answer-sheet">
    @foreach($dados['folha'] as $q)
      @php
        $cls = match($q['situacao']) { 'correta' => 'ok', 'incorreta' => 'no', 'branco' => 'bl', default => 'an' };
        $show = $q['marcada'] ?? '—';
      @endphp
      <div class="ans {{ $cls }}" title="Questão {{ $q['numero_questao'] }} · gabarito {{ $q['gabarito'] }}{{ $q['marcada'] && $q['marcada'] !== $q['gabarito'] ? ' · marcou ' . $q['marcada'] : '' }}">
        <div class="qn">{{ $q['numero_questao'] < 10 ? '0' : '' }}{{ $q['numero_questao'] }}</div>
        <div class="av">{{ $show }}</div>
      </div>
    @endforeach
  </div>
  <div class="sheet-legend">
    <span><i style="background:var(--success)"></i>Correta</span>
    <span><i style="background:var(--danger)"></i>Incorreta</span>
    <span><i style="background:var(--warn-fg,#b45309)"></i>Em branco</span>
    <span><i style="background:var(--muted)"></i>Anulada</span>
  </div>
</div>

@if($dados['cartao_id'])
<div class="modal-backdrop no-print" id="modal-revisar" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:none;align-items:center;justify-content:center;padding:24px;">
  <div class="modal" style="background:var(--surface);border-radius:var(--radius-lg);box-shadow:0 24px 64px rgba(0,0,0,.22);width:100%;max-width:420px;">
    <div style="padding:20px 24px 16px;border-bottom:1px solid var(--border-soft);display:flex;align-items:center;justify-content:space-between;">
      <h2 style="font-size:17px;font-weight:700;">Revisar leitura</h2>
      <button type="button" onclick="document.getElementById('modal-revisar').style.display='none'" style="width:32px;height:32px;border-radius:50%;background:none;border:0;cursor:pointer;font-size:20px;color:var(--muted);">&times;</button>
    </div>
    <form method="POST" action="{{ route('cartoes.revisar', $dados['cartao_id']) }}">
      @csrf
      <div style="padding:22px;display:flex;flex-direction:column;gap:14px;">
        <div class="field">
          <label>Questão *</label>
          <select class="select" name="numero_questao" required>
            @foreach($dados['folha'] as $q)
              <option value="{{ $q['numero_questao'] }}">Questão {{ $q['numero_questao'] }} (marcou: {{ $q['marcada'] ?? '—' }})</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label>Nova alternativa *</label>
          <select class="select" name="alternativa" required>
            @foreach(range('A', chr(64 + ($prova['num_alternativas'] ?? 5))) as $alt)
              <option value="{{ $alt }}">{{ $alt }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div style="padding:14px 22px;border-top:1px solid var(--border-soft);display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal-revisar').style.display='none'">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar revisão</button>
      </div>
    </form>
  </div>
</div>
@endif

<div style="height:48px"></div>
@endsection

@push('scripts')
<script>
var donut = document.getElementById('donut-nota');
donut.dataset.donut = {{ (int) round($dados['nota']['nota_final'] / max($prova['nota_maxima'], 0.1) * 100) }};
donut.dataset.color = '{{ $dados['nota']['status_aprovacao'] === 'aprovado' ? '#168821' : '#e52207' }}';
if (typeof drawDonut === 'function') drawDonut(donut);
</script>
@endpush
