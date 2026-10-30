@extends('layouts.app')

@section('tela', 'tela-gabarito')

@section('title', 'Gabarito oficial — ' . $prova['titulo'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('provas.index') }}">Provas</a><span class="sep">/</span>
<a href="{{ route('provas.show', $prova['id']) }}">{{ $prova['titulo'] }}</a><span class="sep">/</span>
<span>Gabarito oficial</span>
@endsection

@section('content')

<div class="row-between no-print" style="margin-top:12px;">
  <div>
    <h1 class="page-title">Gabarito oficial</h1>
    <p class="page-sub">{{ $prova['disciplina'] }} · {{ implode(' · ', array_column($prova['turmas'] ?? [], 'nome')) ?: $prova['serie'] }}{{ $prova['data_aplicacao'] ? ' · aplicação em ' . \Carbon\Carbon::parse($prova['data_aplicacao'])->format('d/m/Y') : '' }}</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;">
    <span class="badge badge-success badge-dot">Publicado</span>
    <button class="btn btn-secondary" onclick="window.print()">Exportar PDF</button>
    <a href="{{ route('provas.show', $prova['id']) }}" class="btn btn-ghost">← Voltar</a>
  </div>
</div>

@if(session('sucesso'))
  <div class="no-print" style="background:var(--success-light);color:var(--success);border:1px solid var(--success);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('sucesso') }}
  </div>
@endif

<div class="editor-grid">
  {{-- Sidebar --}}
  <div class="sticky stack">
    <div class="card card-pad">
      <h3 style="font-size:15px;margin-bottom:6px;font-weight:700;">Dados da prova</h3>
      <div class="info-row"><span>Disciplina</span><b>{{ $prova['disciplina'] }}</b></div>
      <div class="info-row"><span>Série / turmas</span><b>{{ $prova['serie'] }}</b></div>
      <div class="info-row"><span>Nº de questões</span><b class="num" style="font-family:var(--font-mono);">{{ $prova['num_questoes'] }}</b></div>
      <div class="info-row"><span>Alternativas</span><b>A –
        @php $letters = ['A','B','C','D','E']; echo $letters[($prova['num_alternativas']-1)]; @endphp
      </b></div>
      @if($prova['data_aplicacao'])
      <div class="info-row"><span>Aplicação</span><b class="num">{{ \Carbon\Carbon::parse($prova['data_aplicacao'])->format('d/m/Y') }}</b></div>
      @endif
      @if($gabarito && $gabarito['publicado_em'])
      <div class="info-row"><span>Publicado em</span><b>{{ \Carbon\Carbon::parse($gabarito['publicado_em'])->format('d/m/Y H:i') }}</b></div>
      @endif
    </div>
    <div class="card card-pad no-print">
      <p style="font-size:13px;color:var(--muted);margin:0;">Este é o gabarito oficial publicado para a rede. A leitura dos cartões-resposta usa este padrão para corrigir automaticamente as provas aplicadas.</p>
    </div>
  </div>

  {{-- Gabarito --}}
  <div class="card gabarito-card">
    <div class="row-between" style="margin-bottom:4px;">
      <h3 style="font-size:17px;font-weight:700;">Folha de respostas oficial</h3>
      <span class="badge badge-info">
        @php $opts = array_slice(['A','B','C','D','E'], 0, $prova['num_alternativas']); @endphp
        {{ implode(' · ', $opts) }}
      </span>
    </div>

    @if(count($questoes) === 0)
      <div style="text-align:center;padding:48px 20px;color:var(--muted);">
        <div class="icone-grande"><x-icone nome="prova" /></div>
        <div style="font-size:14px;">Nenhuma questão preenchida no gabarito.</div>
      </div>
    @else
      <div class="q-grid" id="gabarito"></div>
    @endif
  </div>
</div>

<div style="height:48px;"></div>
@endsection

@push('scripts')
<script>
var QUESTOES = @json($questoes);
var NUM_ALT  = {{ (int) $prova['num_alternativas'] }};
var OPTS     = ['A','B','C','D','E'].slice(0, NUM_ALT);

var grid = document.getElementById('gabarito');
if (grid && QUESTOES.length > 0) {
  QUESTOES.forEach(function(q) {
    var row = document.createElement('div');
    row.className = 'q-row';

    var bubbles = OPTS.map(function(o) {
      var cls = 'bubble';
      if (q.anulada) cls += ' anulada';
      else if (q.alternativa === o) cls += ' marked';
      return '<div class="' + cls + '">' + o + '</div>';
    }).join('');

    var num = q.numero_questao;
    row.innerHTML = '<span class="q-num">' + (num < 10 ? '0' : '') + num + '</span><div class="bubbles">' + bubbles + '</div>';
    grid.appendChild(row);
  });
}
</script>
@endpush
