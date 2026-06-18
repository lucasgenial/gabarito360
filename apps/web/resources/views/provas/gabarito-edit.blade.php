@extends('layouts.app')

@section('title', 'Gabarito — ' . $prova['titulo'])

@push('styles')
<style>
  .editor-grid { display:grid;grid-template-columns:300px 1fr;gap:20px;margin-top:20px;align-items:start; }
  .sticky { position:sticky;top:84px; }
  .stepper { display:flex;gap:0;margin-bottom:4px;flex-wrap:wrap; }
  .step { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted);font-weight:600; }
  .step .dot { width:24px;height:24px;border-radius:50%;background:var(--surface-2);display:grid;place-items:center;font-size:12px; }
  .step.active .dot { background:var(--accent);color:#fff; }
  .step.done .dot { background:var(--success,#168821);color:#fff; }
  .step .line { width:40px;height:2px;background:var(--border);margin:0 10px; }
  .progress-track { height:8px;background:var(--surface-2);border-radius:99px;overflow:hidden;margin-top:8px; }
  .progress-track > div { height:100%;background:var(--success,#168821);width:0;transition:width .25s; }
  .gabarito-card { padding:22px; }
  .q-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:8px 28px;margin-top:16px; }
  .q-row { display:flex;align-items:center;gap:14px;padding:8px 10px;border-radius:8px; }
  .q-row:hover { background:var(--surface-2); }
  .q-row[data-answer] .q-num { color:var(--accent); }
  .q-num { font-family:var(--font-mono);font-size:14px;font-weight:700;color:var(--muted);width:28px;flex-shrink:0; }
  .bubbles { display:flex;gap:8px; }
  .bubble { width:36px;height:36px;border-radius:50%;border:2px solid var(--border);display:grid;place-items:center;font-size:14px;font-weight:700;color:var(--muted);background:var(--surface);user-select:none;cursor:pointer;transition:border-color .15s,background .15s,color .15s; }
  .bubble:hover { border-color:var(--accent);color:var(--accent); }
  .bubble.marked { background:var(--accent);border-color:var(--accent);color:#fff; }
  @media(max-width:900px){ .editor-grid{grid-template-columns:1fr;} .sticky{position:static;} .q-grid{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('provas.index') }}" class="active">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('provas.index') }}">Provas</a><span class="sep">/</span>
<a href="{{ route('provas.show', $prova['id']) }}">{{ $prova['titulo'] }}</a><span class="sep">/</span>
<span>Gabarito</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">Gabarito oficial</h1>
    <p class="page-sub">{{ $prova['disciplina'] }} · {{ $prova['serie'] }} · {{ $prova['num_questoes'] }} questões</p>
  </div>
  <a href="{{ route('provas.show', $prova['id']) }}" class="btn btn-ghost">← Voltar</a>
</div>

@if(session('sucesso'))
  <div style="background:var(--success-light,#e3f5e1);color:var(--success,#168821);border:1px solid var(--success,#168821);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('sucesso') }}
  </div>
@endif
@if(session('erro'))
  <div style="background:var(--danger-light,#fde8e4);color:var(--danger,#e52207);border:1px solid var(--danger,#e52207);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('erro') }}
  </div>
@endif

@if($publicado)
  <div style="background:rgba(22,136,33,.08);color:var(--success,#168821);border:1px solid rgba(22,136,33,.3);border-radius:var(--radius-md);padding:14px 18px;font-size:14px;font-weight:600;margin-top:16px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:20px;">🔒</span>
    <div>Gabarito publicado — não pode ser editado. <a href="{{ route('provas.gabarito.show', $prova['id']) }}" class="btn btn-sm btn-secondary" style="margin-left:8px;">Ver gabarito</a></div>
  </div>
@endif

{{-- Stepper --}}
<div class="stepper" style="margin-top:20px;">
  <div class="step done"><span class="dot">✓</span> Dados</div>
  <span class="line"></span>
  <div class="step active"><span class="dot">2</span> Gabarito oficial</div>
  <span class="line"></span>
  <div class="step"><span class="dot">3</span> Publicar</div>
</div>

<div class="editor-grid">
  {{-- Sidebar --}}
  <aside class="sticky stack">
    <div class="card card-pad">
      <h3 style="font-size:15px;margin-bottom:10px;font-weight:700;">Dados da prova</h3>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-soft);font-size:13px;"><span style="color:var(--muted);">Disciplina</span><b>{{ $prova['disciplina'] }}</b></div>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-soft);font-size:13px;"><span style="color:var(--muted);">Série</span><b>{{ $prova['serie'] }}</b></div>
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-soft);font-size:13px;"><span style="color:var(--muted);">Questões</span><b style="font-family:var(--font-mono);">{{ $prova['num_questoes'] }}</b></div>
      <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;"><span style="color:var(--muted);">Alternativas</span><b>{{ $prova['num_alternativas'] }} por questão</b></div>
    </div>

    <div class="card card-pad">
      <div class="row-between"><b style="font-size:14px;">Gabarito preenchido</b><span class="num" data-gabarito-count style="font-weight:700;color:var(--accent);">0 / {{ $prova['num_questoes'] }}</span></div>
      <div class="progress-track" style="margin-top:8px;"><div data-gabarito-bar></div></div>
      <div style="font-size:12px;color:var(--muted);margin-top:8px;">Preencha todas as questões para poder publicar.</div>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;">
      @if(!$publicado)
        <button type="button" id="btn-salvar" class="btn btn-secondary" onclick="salvarGabarito()">Salvar rascunho</button>
        <button type="button" id="btn-publicar" class="btn btn-primary" onclick="publicarGabarito()" disabled>Publicar gabarito</button>
      @else
        <a href="{{ route('provas.gabarito.show', $prova['id']) }}" class="btn btn-primary">Ver gabarito publicado</a>
      @endif
      <a href="{{ route('provas.show', $prova['id']) }}" class="btn btn-ghost">← Voltar à prova</a>
    </div>
  </aside>

  {{-- Editor de bolhas --}}
  <div class="card gabarito-card">
    <div class="row-between" style="margin-bottom:4px;">
      <h3 style="font-size:17px;font-weight:700;">Marque a resposta correta de cada questão</h3>
      <span class="badge badge-info">
        @php
          $opts = ['A','B','C','D','E'];
          $labels = array_slice($opts, 0, $prova['num_alternativas']);
        @endphp
        {{ implode(' · ', $labels) }}
      </span>
    </div>
    <p style="font-size:13px;color:var(--muted);margin-bottom:0;">Clique na bolha correspondente à alternativa correta de cada questão.</p>
    <div class="q-grid" data-gabarito id="gabarito"></div>
  </div>
</div>

{{-- Formulários ocultos para submit via JS --}}
<form id="form-salvar" method="POST" action="{{ route('provas.gabarito.salvar', $prova['id']) }}" style="display:none;">
  @csrf
  <div id="inputs-salvar"></div>
</form>
<form id="form-publicar" method="POST" action="{{ route('provas.gabarito.publicar', $prova['id']) }}" style="display:none;">
  @csrf
</form>

<div style="height:48px;"></div>
@endsection

@push('scripts')
<script>
var NUM_QUESTOES   = {{ (int) $prova['num_questoes'] }};
var NUM_ALT        = {{ (int) $prova['num_alternativas'] }};
var PUBLICADO      = {{ $publicado ? 'true' : 'false' }};
var QUESTOES_INIT  = @json($questoes);

var OPTS = ['A','B','C','D','E'].slice(0, NUM_ALT);

// Mapa numero_questao → alternativa para estado inicial
var respostas = {};
QUESTOES_INIT.forEach(function(q) {
  respostas[q.numero_questao] = q.alternativa;
});

// Renderiza grid
var grid = document.getElementById('gabarito');
for (var q = 1; q <= NUM_QUESTOES; q++) {
  var row = document.createElement('div');
  row.className = 'q-row';
  row.setAttribute('data-q', q);
  if (respostas[q]) row.setAttribute('data-answer', respostas[q]);

  var bubbles = OPTS.map(function(o) {
    var marked = respostas[q] === o ? ' marked' : '';
    return '<div class="bubble' + marked + '" data-opt="' + o + '">' + o + '</div>';
  }).join('');

  row.innerHTML = '<span class="q-num">' + (q < 10 ? '0' : '') + q + '</span><div class="bubbles">' + bubbles + '</div>';
  grid.appendChild(row);
}

// Editor de bolhas (mesma lógica do app.js do mockup)
if (!PUBLICADO) {
  grid.addEventListener('click', function(e) {
    var bubble = e.target.closest('.bubble');
    if (!bubble) return;
    var row = bubble.closest('.q-row');
    row.querySelectorAll('.bubble').forEach(function(b) { b.classList.remove('marked'); });
    bubble.classList.add('marked');
    row.setAttribute('data-answer', bubble.dataset.opt);
    atualizarContador();
  });
}

function atualizarContador() {
  var rows  = grid.querySelectorAll('.q-row');
  var done  = 0;
  rows.forEach(function(r) { if (r.getAttribute('data-answer')) done++; });
  var out = document.querySelector('[data-gabarito-count]');
  if (out) out.textContent = done + ' / ' + rows.length;
  var bar = document.querySelector('[data-gabarito-bar]');
  if (bar) bar.style.width = (done / rows.length * 100) + '%';
  var btnPublicar = document.getElementById('btn-publicar');
  if (btnPublicar) btnPublicar.disabled = (done < rows.length);
}
atualizarContador();

function coletarRespostas() {
  var questoes = [];
  grid.querySelectorAll('.q-row').forEach(function(row) {
    var ans = row.getAttribute('data-answer');
    if (ans) {
      questoes.push({ numero_questao: parseInt(row.dataset.q), alternativa: ans });
    }
  });
  return questoes;
}

function salvarGabarito() {
  var questoes = coletarRespostas();
  var container = document.getElementById('inputs-salvar');
  container.innerHTML = '';
  questoes.forEach(function(q, i) {
    container.innerHTML +=
      '<input type="hidden" name="alternativas[' + q.numero_questao + ']" value="' + q.alternativa + '">';
  });
  document.getElementById('form-salvar').submit();
}

function publicarGabarito() {
  var questoes = coletarRespostas();
  if (questoes.length < NUM_QUESTOES) {
    alert('Preencha todas as ' + NUM_QUESTOES + ' questões antes de publicar.');
    return;
  }
  if (!confirm('Publicar o gabarito? Após a publicação ele não poderá ser editado.')) return;
  // Salva primeiro, depois publica
  var container = document.getElementById('inputs-salvar');
  container.innerHTML = '';
  questoes.forEach(function(q) {
    container.innerHTML +=
      '<input type="hidden" name="alternativas[' + q.numero_questao + ']" value="' + q.alternativa + '">';
  });
  // Redireciona para publicar via form oculto após salvar
  // Usa form de publicar diretamente — o controller publica pelo API que valida completude
  document.getElementById('form-publicar').submit();
}
</script>
@endpush
