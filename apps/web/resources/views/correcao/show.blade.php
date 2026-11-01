@extends('layouts.app')

@section('tela', 'tela-correcao')

@section('title', 'Acompanhar correção — ' . $prova['titulo'])

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
  <button class="btn btn-ghost" id="btn-upload" onclick="abrirUpload()">Envio manual</button>
</div>

@php
  $corr      = $status['correcao'] ?? ['fechada' => false];
  $fechada   = !empty($corr['fechada']);
  $podeMexer = \App\Support\Acesso::podeAlterarCorrecao($fechada);
@endphp

{{-- Correção aberta/fechada --}}
<div class="card card-pad no-print" style="margin-top:16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;{{ $fechada ? 'border-color:var(--warn-fg);background:var(--warn-light);' : '' }}">
  @if($fechada)
    <div style="flex:1;min-width:220px;font-size:14px;">
      <x-icone nome="cadeado" /> <b>Correção fechada</b>{{ !empty($corr['fechada_por']) ? ' por ' . $corr['fechada_por'] : '' }}{{ !empty($corr['fechada_em']) ? ' em ' . \Carbon\Carbon::parse($corr['fechada_em'])->timezone('America/Bahia')->format('d/m/Y \à\s H:i') : '' }}.
      <div style="font-size:12px;color:var(--muted);">{{ $podeMexer ? 'Você (supervisão) ainda pode excluir e incluir correções.' : 'Para enviar, excluir ou alterar cartões, peça à coordenação para reabrir.' }}</div>
    </div>
    @pode('correcao.reabrir')
      <form method="POST" action="{{ route('correcao.reabrir', $prova['id']) }}" onsubmit="return confirm('Reabrir a correção? Professores e corretores voltam a poder enviar, excluir e alterar.')">
        @csrf<button type="submit" class="btn btn-secondary"><x-icone nome="cadeado" /> Reabrir correção</button>
      </form>
    @endpode
  @else
    <div style="flex:1;min-width:220px;font-size:14px;"><b>Correção aberta</b><div style="font-size:12px;color:var(--muted);">Ao terminar, feche a correção para evitar alterações.</div></div>
    @pode('correcao.fechar')
      <form method="POST" action="{{ route('correcao.fechar', $prova['id']) }}" onsubmit="return confirm('Fechar a correção? Depois disso só a coordenação/direção poderá alterar, excluir, incluir ou reabrir.')">
        @csrf<button type="submit" class="btn btn-secondary"><x-icone nome="cadeado" /> Fechar correção</button>
      </form>
    @endpode
  @endif
</div>

@if($podeMexer || \App\Support\Acesso::perfil() === 'aplicador')
  @pode('cartao.enviar')
    @include('correcao._camera')
  @endpode
@endif

@if(session('sucesso'))
  <div style="background:var(--success-light);color:var(--success);border:1px solid var(--success);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('sucesso') }}
  </div>
@endif
@if(session('erro'))
  <div style="background:var(--danger-light);color:var(--danger);border:1px solid var(--danger);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('erro') }}
  </div>
@endif

<div class="progress-head">
  <div class="card card-pad">
    <div class="stat-row">
      <div class="correcao-pct"><b id="v-pct">0%</b><span>concluído</span></div>
      <div class="stat-grid">
        <div><div class="v" id="v-lidos" style="color:var(--accent);">{{ $status['lidos'] }}</div><div class="l">Cartões lidos</div></div>
        <div><div class="v" id="v-pendentes" style="color:var(--muted)">{{ $status['pendentes'] }}</div><div class="l">Pendentes</div></div>
        <div><div class="v" id="v-ambiguos" style="color:var(--atencao)">{{ $status['ambiguos'] }}</div><div class="l">Ambíguos</div></div>
        <div><div class="v" id="v-total" style="color:var(--muted)">{{ $status['total'] }}</div><div class="l">Total de cartões</div></div>
      </div>
    </div>
    <div class="progress-track"><div id="bar"></div></div>
  </div>
  <div class="card card-pad">
    <h3 style="font-size:16px;margin-bottom:8px;">Leituras para revisar</h3>
    <p id="ambig-resumo" style="margin-bottom:12px;"></p>
    @if($podeMexer)<a class="btn btn-primary" id="btn-revisar" href="{{ route('correcao.revisao', $prova['id']) }}" style="display:none;">Revisar leituras</a>@endif
  </div>
</div>

{{-- Alunos corrigidos: foto do cartão, nota, acertos/erros e exclusão para fotografar de novo --}}
<div class="card card-pad" style="margin-top:20px;" id="corrigidos">
  <div class="row-between" style="flex-wrap:wrap;margin-bottom:12px;">
    <h3 style="font-size:17px;">Alunos corrigidos <span class="badge badge-muted" id="corr-qtd">0</span></h3>
    <input class="input" id="corr-busca" type="search" placeholder="Buscar aluno…" style="max-width:240px;">
  </div>
  <ul class="corr-list" id="corr-lista"><li style="color:var(--muted);font-size:13px;">Carregando…</li></ul>
</div>

<div class="g360-modal" id="foto-modal" onclick="if (event.target === this) fecharModal('foto-modal')">
  <div class="caixa">
    <div class="row-between" style="margin-bottom:10px;"><b id="foto-titulo"></b><button type="button" class="btn btn-sm btn-ghost" onclick="fecharModal('foto-modal')">✕</button></div>
    <img id="foto-img" class="foto-img" alt="Foto do cartão-resposta" onerror="if (this.getAttribute('src')) { this.style.display = 'none'; document.getElementById('foto-erro').style.display = 'block'; }">
    <p id="foto-erro" style="display:none;">Não foi possível abrir a foto. Feche e tente de novo.</p>
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
      <input type="hidden" name="envio_id" value="{{ \Illuminate\Support\Str::uuid() }}">
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
var STATUS_URL = '{{ route('correcao.status', $prova['id'], false) }}';
var CARTOES_URL = '/cartoes';
var LISTA_URL = '{{ route('correcao.cartoes', $prova['id'], false) }}';
var PROVA_ID = {{ (int) $prova['id'] }};
var PODE_EXCLUIR = {{ $podeMexer ? 'true' : 'false' }};
var NOTA_MAX = {{ (float) ($prova['nota_maxima'] ?? 10) }};

// ---------- Alunos corrigidos ----------
var corrDados = [], corrAssinatura = '';
function escH(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
function fmtNota(n) { return Number(n).toFixed(1).replace('.', ','); }
function carregarCorrigidos() {
  fetch(LISTA_URL, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      var lista = d.cartoes || [], assin = JSON.stringify(lista);
      if (assin === corrAssinatura) return; // nada mudou: não redesenha (evita piscar as fotos)
      corrAssinatura = assin; corrDados = lista; desenharCorrigidos();
    }).catch(function () {});
}
function desenharCorrigidos() {
  var termo = (document.getElementById('corr-busca').value || '').toLowerCase();
  var itens = corrDados.filter(function (c) {
    return !termo || (c.aluno_nome || 'aluno não identificado').toLowerCase().indexOf(termo) >= 0 || String(c.matricula || '').indexOf(termo) >= 0;
  });
  document.getElementById('corr-qtd').textContent = corrDados.length;
  var ul = document.getElementById('corr-lista');
  if (!itens.length) {
    ul.innerHTML = '<li style="color:var(--muted);font-size:13px;">' + (corrDados.length ? 'Nenhum aluno encontrado.' : 'Nenhum cartão corrigido ainda.') + '</li>';
    return;
  }
  ul.innerHTML = itens.map(function (c) {
    var nome = c.aluno_nome ? escH(c.aluno_nome) : '<span style="color:var(--danger)">Aluno não identificado</span>';
    var sub = [c.turma, c.matricula, c.lingua ? (c.lingua === 'ESP' ? 'Espanhol' : 'Inglês') : null].filter(Boolean).map(escH).join(' · ');
    var st = c.status === 'lido' ? '<span class="badge badge-success">lido</span>'
      : c.status === 'ambiguo' ? '<span class="badge badge-warn">' + c.ambiguas + ' p/ revisar</span>'
      : '<span class="badge badge-muted">lendo</span>';
    var nota = (c.nota_final !== null && c.nota_final !== undefined)
      ? '<div class="corr-nota" style="color:' + (Number(c.nota_final) >= NOTA_MAX * 0.6 ? 'var(--success)' : 'var(--danger)') + '">' + fmtNota(c.nota_final) + '</div>'
        + '<div style="font-size:11px;color:var(--muted);text-align:right;">' + c.acertos + '/' + c.total_questoes + '</div>'
      : '';
    var foto = c.miniatura_url
      ? '<img class="corr-thumb" data-src="' + escH(c.miniatura_url) + '" alt="Foto do cartão" onclick="verFoto(' + c.id + ')">'
      : '<div class="corr-thumb" style="display:grid;place-items:center;font-size:11px;color:var(--muted);">sem miniatura</div>';
    var acoes = c.tem_imagem ? '<button type="button" class="btn btn-sm btn-ghost" onclick="verFoto(' + c.id + ')">Ver foto</button>' : '';
    if (c.aluno_id && c.nota_final !== null && c.nota_final !== undefined) {
      acoes += '<a class="btn btn-sm btn-secondary" href="/resultados/' + c.aluno_id + '/' + PROVA_ID + '?from=correcao">Acertos e erros</a>';
    }
    if (!c.aluno_id && typeof seletorAluno === 'function' && typeof ALUNOS !== 'undefined' && ALUNOS.length) acoes += seletorAluno(c.id);
    if (PODE_EXCLUIR && c.status === 'lido') acoes += '<a class="btn btn-sm btn-ghost" href="/provas/' + PROVA_ID + '/revisao?cartao=' + c.id + '">Revisar leitura</a>';
    if (PODE_EXCLUIR) acoes += '<button type="button" class="btn btn-sm btn-ghost" style="color:var(--danger);" onclick="excluirCorrecao(' + c.id + ')">Excluir</button>';
    return '<li class="corr-item">' + foto + '<div class="corr-info"><b>' + nome + '</b><div style="color:var(--muted);">' + sub + '</div>'
      + '<div style="margin-top:4px;">' + st + '</div><div class="corr-acoes">' + acoes + '</div></div><div>' + nota + '</div></li>';
  }).join('');
  filaMiniaturas = Array.prototype.slice.call(ul.querySelectorAll('img[data-src]'));
  proximaMiniatura();
}
// Poucas por vez, para uma turma inteira não ocupar todos os processos da API
var filaMiniaturas = [], miniaturasAtivas = 0;
function proximaMiniatura() {
  while (miniaturasAtivas < 4 && filaMiniaturas.length) {
    var img = filaMiniaturas.shift();
    miniaturasAtivas++;
    img.onload = img.onerror = function () { miniaturasAtivas--; proximaMiniatura(); };
    img.src = img.getAttribute('data-src');
  }
}
function abrirModal(id) { document.getElementById(id).style.display = 'flex'; }
function fecharModal(id) { document.getElementById(id).style.display = 'none'; if (id === 'foto-modal') document.getElementById('foto-img').src = ''; }
function verFoto(id) {
  var c = corrDados.filter(function (x) { return x.id === id; })[0] || {};
  document.getElementById('foto-titulo').textContent = (c.aluno_nome || 'Aluno não identificado') + (c.turma ? ' · ' + c.turma : '');
  document.getElementById('foto-erro').style.display = 'none';
  document.getElementById('foto-img').style.display = '';
  document.getElementById('foto-img').src = c.foto_url || (CARTOES_URL + '/' + id + '/imagem');
  abrirModal('foto-modal');
}
function excluirCorrecao(id) {
  var c = corrDados.filter(function (x) { return x.id === id; })[0] || {};
  if (!confirm('Excluir a correção de ' + (c.aluno_nome || 'este cartão') + '?\nA foto e a nota serão apagadas e você poderá fotografar o cartão de novo.')) return;
  fetch(CARTOES_URL + '/' + id, { method: 'DELETE', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
    .then(function (res) {
      if (!res.ok) { alert(res.j.erro || 'Não foi possível excluir.'); return; }
      corrAssinatura = ''; carregarCorrigidos(); atualizar();
    });
}
document.getElementById('corr-busca').addEventListener('input', desenharCorrigidos);
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { fecharModal('foto-modal'); } });
carregarCorrigidos();
setInterval(function () { if (!document.hidden) carregarCorrigidos(); }, 20000);
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
  document.getElementById('v-pct').textContent = pct + '%';

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
  var alunos = {};
  lista.forEach(function (a) { alunos[a.cartao_id] = true; });
  var botao = document.getElementById('btn-revisar');
  document.getElementById('ambig-resumo').textContent = lista.length
    ? lista.length + ' questões de ' + Object.keys(alunos).length + ' cartão(ões) aguardam sua decisão.'
    : 'Nenhuma pendência de revisão.';
  if (botao) {
    botao.textContent = 'Revisar leituras (' + lista.length + ')';
    botao.style.display = lista.length ? '' : 'none';
  }
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
    li.innerHTML = '<div class="act-ico" style="background:var(--success-light);color:var(--success);">✓</div><div><b>' + nome + '</b> — nota ' + nota + '<div class="act-time">' + tempoRelativo(r.updated_at) + '</div></div>';
    ul.appendChild(li);
  });
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
setInterval(function () { if (!document.hidden) atualizar(); }, 10000);

</script>
@endpush
