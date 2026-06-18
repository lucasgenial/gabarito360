@extends('layouts.app')

@section('title', 'Acompanhar correção — ' . $prova['titulo'])

@push('styles')
<style>
  .progress-head { display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px; }
  .stat-row { display:flex;gap:28px;align-items:center;flex-wrap:wrap; }
  .stat-grid { display:grid;grid-template-columns:repeat(2,auto);gap:18px 28px; }
  .stat-grid .v { font-family:var(--font-mono);font-size:26px;font-weight:700; }
  .stat-grid .l { font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-top:2px; }
  .progress-track { height:8px;background:var(--surface-2);border-radius:99px;overflow:hidden;margin-top:18px; }
  .progress-track > div { height:100%;background:var(--accent);width:0;transition:width .25s; }
  .exam-meta { font-size:13px;color:var(--muted);margin-top:2px; }
  .activity li { display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--border-soft);font-size:14px;align-items:center; }
  .activity li:last-child { border:0; }
  .act-ico { width:34px;height:34px;border-radius:8px;display:grid;place-items:center;flex-shrink:0; }
  .act-time { color:var(--muted);font-size:12px;margin-top:2px; }
  .ambig-list li { display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border-soft);font-size:14px; }
  .ambig-list li:last-child { border:0; }
  .resolve-actions { display:flex;gap:6px;flex-shrink:0;flex-wrap:wrap; }
  @media(max-width:900px){ .progress-head{grid-template-columns:1fr;} }
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
<span>Acompanhar correção</span>
@endsection

@section('content')

<div class="row-between" style="margin-top:12px;">
  <div>
    <h1 class="page-title">{{ $prova['titulo'] }}</h1>
    <p class="page-sub">{{ $prova['disciplina'] }} · {{ implode(' · ', array_column($prova['turmas'] ?? [], 'nome')) }}{{ $prova['data_aplicacao'] ? ' · aplicado em ' . \Carbon\Carbon::parse($prova['data_aplicacao'])->format('d/m/Y') : '' }}</p>
  </div>
  <button class="btn btn-secondary" id="btn-upload" onclick="abrirUpload()">+ Enviar cartão-resposta</button>
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

<div class="progress-head">
  <div class="card card-pad">
    <div class="stat-row">
      <div id="donut"></div>
      <div class="stat-grid">
        <div><div class="v" id="v-lidos" style="color:var(--accent);">{{ $status['lidos'] }}</div><div class="l">Cartões lidos</div></div>
        <div><div class="v" id="v-pendentes" style="color:var(--muted)">{{ $status['pendentes'] }}</div><div class="l">Pendentes</div></div>
        <div><div class="v" id="v-ambiguos" style="color:var(--warn-fg,#b45309)">{{ $status['ambiguos'] }}</div><div class="l">Ambíguos</div></div>
        <div><div class="v" id="v-total" style="color:var(--muted)">{{ $status['total'] }}</div><div class="l">Total de cartões</div></div>
      </div>
    </div>
    <div class="progress-track"><div id="bar"></div></div>
  </div>
  <div class="card card-pad">
    <h3 style="font-size:16px;margin-bottom:8px;">Cartões com marcação ambígua</h3>
    <ul class="ambig-list" id="ambig-list"></ul>
  </div>
</div>

<div class="card card-pad" style="margin-top:20px;">
  <h3 style="font-size:17px;margin-bottom:8px;">Cartões processados recentemente</h3>
  <ul class="activity" id="activity-list"></ul>
</div>

{{-- Modal de upload --}}
<div class="modal-backdrop" id="modal-upload" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;display:none;align-items:center;justify-content:center;padding:24px;">
  <div class="modal" style="background:var(--surface);border-radius:var(--radius-lg);box-shadow:0 24px 64px rgba(0,0,0,.22);width:100%;max-width:480px;">
    <div style="padding:20px 24px 16px;border-bottom:1px solid var(--border-soft);display:flex;align-items:center;justify-content:space-between;">
      <h2 style="font-size:17px;font-weight:700;">Enviar cartão-resposta</h2>
      <button type="button" onclick="fecharUpload()" style="width:32px;height:32px;border-radius:50%;background:none;border:0;cursor:pointer;font-size:20px;color:var(--muted);">&times;</button>
    </div>
    <form method="POST" action="{{ route('correcao.upload', $prova['id']) }}" enctype="multipart/form-data">
      @csrf
      <div style="padding:22px;display:flex;flex-direction:column;gap:14px;">
        <div class="field">
          <label>Imagem do cartão *</label>
          <input class="input" type="file" name="imagem" accept="image/*" required />
        </div>
        <div class="field">
          <label>Aluno (opcional — pode vincular depois)</label>
          <select class="select" name="aluno_id">
            <option value="">— Não identificado —</option>
            @foreach($alunos as $a)
              <option value="{{ $a['id'] }}">{{ $a['nome'] }}{{ isset($a['matricula']) ? ' — ' . $a['matricula'] : '' }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div style="padding:14px 22px;border-top:1px solid var(--border-soft);display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn-ghost" onclick="fecharUpload()">Cancelar</button>
        <button type="submit" class="btn btn-primary">Enviar para leitura</button>
      </div>
    </form>
  </div>
</div>

<div style="height:48px"></div>
@endsection

@push('scripts')
<script>
var STATUS_URL = '{{ route('correcao.status', $prova['id']) }}';
var CARTOES_URL = '{{ url('cartoes') }}';
var CSRF = '{{ csrf_token() }}';

function abrirUpload() { document.getElementById('modal-upload').style.display = 'flex'; }
function fecharUpload() { document.getElementById('modal-upload').style.display = 'none'; }

function tempoRelativo(iso) {
  if (!iso) return '';
  var diffMs = Date.now() - new Date(iso).getTime();
  var min = Math.floor(diffMs / 60000);
  if (min < 1) return 'agora mesmo';
  if (min < 60) return 'há ' + min + ' minuto' + (min > 1 ? 's' : '');
  var h = Math.floor(min / 60);
  return 'há ' + h + ' hora' + (h > 1 ? 's' : '');
}

function renderEstado(data) {
  var pct = data.total > 0 ? Math.round(data.lidos / data.total * 100) : 0;
  var donutEl = document.getElementById('donut');
  donutEl.dataset.donut = pct;
  donutEl.dataset.color = pct >= 100 ? '#168821' : '#1351b4';
  if (typeof drawDonut === 'function') drawDonut(donutEl);

  document.getElementById('v-lidos').textContent = data.lidos;
  document.getElementById('v-pendentes').textContent = data.pendentes;
  document.getElementById('v-ambiguos').textContent = data.ambiguos;
  document.getElementById('v-total').textContent = data.total;
  document.getElementById('bar').style.width = pct + '%';

  var btn = document.getElementById('btn-upload');
  if (data.concluida && data.total > 0) {
    var aviso = document.getElementById('aviso-concluida');
    if (!aviso) {
      aviso = document.createElement('span');
      aviso.id = 'aviso-concluida';
      aviso.className = 'badge badge-success';
      aviso.style.marginRight = '10px';
      aviso.textContent = 'Leitura concluída';
      btn.parentNode.insertBefore(aviso, btn);
    }
  }
}

function renderAmbiguidades(lista) {
  var ul = document.getElementById('ambig-list');
  ul.innerHTML = '';
  if (!lista.length) {
    ul.innerHTML = '<li style="border:0;color:var(--muted);">Nenhuma pendência de revisão.</li>';
    return;
  }
  lista.forEach(function (a) {
    var li = document.createElement('li');
    var detalhe = a.alternativas_detectadas.map(function (d) { return d.alternativa; }).join(' e ');
    var btns = a.alternativas_detectadas.map(function (d) {
      return '<button class="btn btn-secondary btn-sm" data-cartao="' + a.cartao_id + '" data-questao="' + a.numero_questao + '" data-alt="' + d.alternativa + '">' + d.alternativa + '</button>';
    }).join('');

    li.innerHTML = '<div><b>Cartão #' + a.cartao_id + '</b><div class="exam-meta">Questão ' + a.numero_questao + ' — marcações em ' + detalhe + '</div></div><div class="resolve-actions">' + btns + '</div>';
    ul.appendChild(li);
  });

  ul.querySelectorAll('[data-cartao]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      resolverAmbiguidade(btn.dataset.cartao, btn.dataset.questao, btn.dataset.alt);
    });
  });
}

function renderAtividade(recentes) {
  var ul = document.getElementById('activity-list');
  ul.innerHTML = '';
  if (!recentes.length) {
    ul.innerHTML = '<li style="border:0;color:var(--muted);">Nenhum cartão processado ainda.</li>';
    return;
  }
  recentes.forEach(function (r) {
    var li = document.createElement('li');
    var nome = r.aluno_nome || 'Aluno não identificado';
    var nota = r.nota_final !== null ? parseFloat(r.nota_final).toFixed(1).replace('.', ',') : '—';
    li.innerHTML = '<div class="act-ico" style="background:var(--success-light,#e3f5e1);color:var(--success,#168821);">✓</div><div><b>' + nome + '</b> — nota ' + nota + '<div class="act-time">' + tempoRelativo(r.updated_at) + '</div></div>';
    ul.appendChild(li);
  });
}

function resolverAmbiguidade(cartaoId, numeroQuestao, alternativa) {
  fetch(CARTOES_URL + '/' + cartaoId + '/resolver-ambiguidade', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ numero_questao: parseInt(numeroQuestao), alternativa: alternativa })
  }).then(function () { atualizar(); });
}

function atualizar() {
  fetch(STATUS_URL, { headers: { 'Accept': 'application/json' } })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      renderEstado(data);
      renderAmbiguidades(data.ambiguidades || []);
      renderAtividade(data.recentes || []);
    });
}

atualizar();
setInterval(atualizar, 5000);
</script>
@endpush
