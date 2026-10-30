@extends('layouts.app')

@section('tela', 'tela-gabarito-editar')

@section('title', 'Gabarito — ' . $prova['titulo'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('provas.index') }}">Provas</a><span class="sep">/</span>
<a href="{{ route('provas.show', $prova['id']) }}">{{ $prova['titulo'] }}</a><span class="sep">/</span>
<span>Gabarito</span>
@endsection

@section('content')

<x-pagina titulo="Gabarito oficial">
  <x-slot:sub>{{ $prova['disciplina'] }} · {{ $prova['serie'] }} · {{ $prova['num_questoes'] }} questões</x-slot:sub>
  <a href="{{ route('provas.show', $prova['id']) }}" class="btn btn-ghost">← Voltar</a>
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

@if($publicado)
  <div style="background:rgba(22,136,33,.08);color:var(--success);border:1px solid rgba(22,136,33,.3);border-radius:var(--radius-md);padding:14px 18px;font-size:14px;font-weight:600;margin-top:16px;display:flex;align-items:center;gap:10px;">
    
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

    @if(!empty($prova['opcao_lingua']))
      @php $espInit = collect($questoes)->keyBy('numero_questao'); $optsEsp = array_slice(['A','B','C','D','E'], 0, $prova['num_alternativas']); @endphp
      <div class="card card-pad">
        <b style="font-size:14px;">Língua estrangeira — opção Espanhol</b>
        <div style="font-size:12px;color:var(--muted);margin:4px 0 10px;">As bolhas ao lado são a opção <b>Inglês</b>. Informe aqui as respostas de Espanhol (01 a 05).</div>
        @for($n = 1; $n <= min(5, $prova['num_questoes']); $n++)
          <div class="row-between" style="padding:4px 0;">
            <span class="num" style="font-weight:700;">{{ str_pad($n, 2, '0', STR_PAD_LEFT) }}</span>
            <select class="select" form="form-salvar" name="alternativas_espanhol[{{ $n }}]" style="width:90px;" {{ $publicado ? 'disabled' : '' }}>
              <option value="">—</option>
              @foreach($optsEsp as $o)
                <option value="{{ $o }}" {{ (($espInit[$n]['alternativa_espanhol'] ?? null) === $o) ? 'selected' : '' }}>{{ $o }}</option>
              @endforeach
            </select>
          </div>
        @endfor
      </div>
    @endif

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
