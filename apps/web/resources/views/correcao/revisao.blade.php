@extends('layouts.app')

@section('tela', 'tela-revisao')

@section('title', 'Revisar leituras — ' . $prova['titulo'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('correcao.show', $prova['id']) }}">{{ $prova['titulo'] }}</a><span class="sep">/</span>
<span>Revisar leituras</span>
@endsection

@section('content')

<x-pagina titulo="Revisar leituras">
  <x-slot:sub>{{ $prova['disciplina'] }}</x-slot:sub>
</x-pagina>

<div class="card card-pad rev-cartao">
  <div id="rev-conteudo"></div>
</div>

<dialog id="rev-cartao-modal" class="rev-cartao-modal" aria-labelledby="rev-cartao-titulo" aria-describedby="rev-cartao-contexto">
  <header class="rev-modal-cabecalho">
    <div><h2 id="rev-cartao-titulo">Cartão-resposta</h2><p id="rev-cartao-contexto"></p></div>
    <button type="button" class="btn btn-secondary" id="rev-cartao-fechar" autofocus aria-label="Fechar cartão">Fechar</button>
  </header>
  <div class="rev-modal-ferramentas"><button type="button" class="btn btn-ghost" id="rev-cartao-ampliar" aria-pressed="false" disabled>Ampliar</button></div>
  <div id="rev-cartao-area" class="rev-modal-area" role="region" aria-label="Imagem do cartão-resposta" tabindex="0">
    <p id="rev-cartao-carregando" class="rev-modal-estado" role="status" hidden>Carregando cartão…</p>
    <div id="rev-cartao-imagem" class="rev-modal-imagem rev-imagem-restrita" hidden></div>
    <div id="rev-cartao-erro" class="rev-modal-estado" hidden>
      <x-aviso tipo="erro">Não foi possível carregar o cartão. Tente novamente.
        <p>Se continuar, feche este cartão e atualize a revisão.</p>
        <button type="button" class="btn btn-secondary" id="rev-cartao-repetir">Tentar novamente</button>
      </x-aviso>
    </div>
  </div>
</dialog>

@endsection

@push('scripts')
<script>
var FILA = @json($itens);
var ALTERNATIVAS = 'ABCDE'.slice(0, {{ (int) ($prova['num_alternativas'] ?? 5) }}).split('');
var CARTOES_URL = '/cartoes';
var VOLTAR_URL = @json(route('correcao.show', $prova['id'], false));
var CSRF = '{{ csrf_token() }}';
var ROTULOS = { BRANCO: 'Em branco', ANULADA: 'Anulada' };

var historico = [], salvando = false, total = FILA.length;
var statusCartao = {};
FILA.forEach(function (q) { statusCartao[q.cartao_id] = q.status; });
var cartaoModal = document.getElementById('rev-cartao-modal');
var cartaoArea = document.getElementById('rev-cartao-area'), cartaoImagem = document.getElementById('rev-cartao-imagem');
var cartaoCarregando = document.getElementById('rev-cartao-carregando'), cartaoErro = document.getElementById('rev-cartao-erro');
var cartaoAmpliar = document.getElementById('rev-cartao-ampliar');
var cartaoUrl = null, cartaoFoto = null, cartaoOrigem = null, cartaoSequencia = 0, cartaoOverflow = '';
var revisaoDesktop = window.matchMedia('(min-width:1024px)');

// Dissuasão restrita à imagem; não oferece proteção contra captura de tela/devtools.
function restringirCopiaImagem(area) {
  ['contextmenu', 'dragstart', 'copy', 'cut'].forEach(function (tipo) {
    area.addEventListener(tipo, function (e) { e.preventDefault(); });
  });
}
restringirCopiaImagem(cartaoImagem);
['copy', 'cut'].forEach(function (tipo) {
  cartaoArea.addEventListener(tipo, function (e) {
    // Texto selecionado no cabeçalho/aviso continua copiável.
    if (!cartaoImagem.hidden && !window.getSelection()?.toString()) e.preventDefault();
  });
});
function atualizarPreviaRevisao() {
  var area = document.getElementById('rev-previa');
  if (area) area.innerHTML = revisaoDesktop.matches && FILA[0] ? recorteHtml(FILA[0]) : '';
}
revisaoDesktop.addEventListener('change', atualizarPreviaRevisao);

function ajustarCartao() {
  cartaoImagem.classList.remove('ampliado');
  if (cartaoFoto) cartaoFoto.style.removeProperty('width');
  cartaoArea.scrollTop = 0; cartaoArea.scrollLeft = 0;
  cartaoAmpliar.textContent = 'Ampliar'; cartaoAmpliar.setAttribute('aria-pressed', 'false');
}
function carregarCartao() {
  if (!cartaoModal.open || !cartaoUrl) return;
  if (cartaoErro.contains(document.activeElement)) cartaoArea.focus({preventScroll:true});
  var sequencia = ++cartaoSequencia;
  if (cartaoFoto) { cartaoFoto.onload = cartaoFoto.onerror = null; cartaoFoto.removeAttribute('src'); }
  cartaoFoto = new Image(); cartaoFoto.alt = 'Cartão-resposta completo'; cartaoFoto.draggable = false;
  cartaoImagem.replaceChildren(); ajustarCartao(); cartaoImagem.hidden = true;
  cartaoErro.hidden = true; cartaoCarregando.hidden = false; cartaoAmpliar.disabled = true;
  cartaoArea.setAttribute('aria-busy', 'true');
  cartaoFoto.onload = function () {
    if (!cartaoModal.open || sequencia !== cartaoSequencia) return;
    cartaoImagem.replaceChildren(cartaoFoto); cartaoImagem.hidden = false;
    cartaoCarregando.hidden = true; cartaoAmpliar.disabled = false; cartaoArea.setAttribute('aria-busy', 'false');
  };
  cartaoFoto.onerror = function () {
    if (!cartaoModal.open || sequencia !== cartaoSequencia) return;
    cartaoCarregando.hidden = true; cartaoErro.hidden = false; cartaoArea.setAttribute('aria-busy', 'false');
  };
  // Usar a URL assinada exatamente como foi emitida, inclusive ao tentar novamente.
  cartaoFoto.src = cartaoUrl;
}
function abrirCartaoInteiro() {
  if (salvando || cartaoModal.open || !FILA[0]?.foto_url) return;
  var q = FILA[0]; cartaoUrl = q.foto_url; cartaoOrigem = document.getElementById('rev-ver-cartao');
  document.getElementById('rev-cartao-contexto').textContent = [q.aluno_nome, q.turma].filter(Boolean).join(' · ') || 'Cartão sem aluno identificado';
  cartaoOverflow = document.documentElement.style.overflow; document.documentElement.style.overflow = 'hidden';
  cartaoModal.showModal(); carregarCartao(); document.getElementById('rev-cartao-fechar').focus({preventScroll:true});
}
document.getElementById('rev-cartao-fechar').addEventListener('click', function () { cartaoModal.close(); });
document.getElementById('rev-cartao-repetir').addEventListener('click', carregarCartao);
cartaoModal.addEventListener('keydown', function (e) {
  if (e.key !== 'Tab') return;
  var controles = Array.from(cartaoModal.querySelectorAll('button:not([disabled]), [tabindex="0"]')).filter(function (el) { return el.getClientRects().length > 0; });
  var primeiro = controles[0], ultimo = controles[controles.length - 1];
  if (e.shiftKey && document.activeElement === primeiro) { e.preventDefault(); ultimo.focus(); }
  else if (!e.shiftKey && document.activeElement === ultimo) { e.preventDefault(); primeiro.focus(); }
});
cartaoAmpliar.addEventListener('click', function () {
  if (!cartaoFoto || cartaoAmpliar.disabled) return;
  if (cartaoImagem.classList.contains('ampliado')) { ajustarCartao(); return; }
  cartaoImagem.classList.add('ampliado'); cartaoFoto.style.width = Math.max(cartaoFoto.naturalWidth, cartaoArea.clientWidth * 1.5) + 'px';
  cartaoAmpliar.textContent = 'Ajustar à tela'; cartaoAmpliar.setAttribute('aria-pressed', 'true');
});
cartaoModal.addEventListener('close', function () {
  ++cartaoSequencia;
  if (cartaoFoto) { cartaoFoto.onload = cartaoFoto.onerror = null; cartaoFoto.removeAttribute('src'); }
  cartaoImagem.replaceChildren(); cartaoFoto = null; cartaoUrl = null;
  document.documentElement.style.overflow = cartaoOverflow;
  if (cartaoOrigem?.isConnected) cartaoOrigem.focus({preventScroll:true});
});
function estadoSalvando(estado) {
  salvando = estado;
  var botao = document.getElementById('rev-ver-cartao'); if (botao) botao.disabled = estado;
}

function escH(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }

function recorteHtml(q) {
  if (!q.endireitada_url) {
    return q.foto_url ? '<img class="rev-foto" src="' + escH(q.foto_url) + '" alt="Foto do cartão" draggable="false">' : '';
  }
  var r = q.recorte;
  // Mostra só a linha da questão: a foto endireitada tem sempre a mesma geometria da folha
  var estilo = 'aspect-ratio:' + (r.largura * 210 / (r.altura * 297)).toFixed(3)
    + ';background-image:url(\'' + escH(q.endireitada_url) + '\')'
    + ';background-size:' + (100 / r.largura).toFixed(2) + '% auto'
    + ';background-position:' + (r.x / (1 - r.largura) * 100).toFixed(2) + '% ' + (r.y / (1 - r.altura) * 100).toFixed(2) + '%';
  return '<div class="rev-recorte" role="img" aria-label="Linha da questão ' + q.numero_questao + ' no cartão" style="' + estilo + '"></div>';
}

function leitura(q) {
  if (q.alternativa && !q.ambigua) return 'Resposta atual: ' + q.alternativa;
  return q.detectadas.length ? 'Leitura do sistema: ' + q.detectadas.join(' e ') + ' (marcação incerta)' : 'O sistema não identificou marcação';
}

function desenhar() {
  var el = document.getElementById('rev-conteudo');
  if (!FILA.length) {
    el.innerHTML = '<div class="rev-fim"><h2>Revisão concluída</h2><p>' + historico.length + ' de ' + total + ' respostas salvas. As notas dos cartões concluídos já foram calculadas.</p>'
      + (historico.length ? '<button type="button" class="btn btn-ghost" onclick="voltar()">Voltar à última</button> ' : '')
      + '<a class="btn btn-primary" href="' + VOLTAR_URL + '">Voltar à correção</a></div>';
    return;
  }
  var q = FILA[0];
  var botoes = ALTERNATIVAS.map(function (l) {
    var classe = q.detectadas.indexOf(l) >= 0 || q.alternativa === l ? 'btn-secondary' : 'btn-ghost';
    return '<button type="button" class="btn ' + classe + '" onclick="escolher(\'' + l + '\')">' + l + '</button>';
  }).join('');
  el.innerHTML = '<div class="row-between rev-topo"><div><b>' + escH(q.aluno_nome || 'Aluno não identificado') + '</b>'
    + '<div class="exam-meta">' + escH(q.turma || '') + '</div></div>'
    + '<div class="rev-progresso">Questão ' + q.numero_questao + ' · Faltam ' + FILA.length + '</div></div>'
    + '<div class="rev-trabalho' + (q.foto_url || q.endireitada_url ? '' : ' sem-imagem') + '"><div id="rev-previa" class="rev-previa rev-imagem-restrita"></div><div class="rev-respostas">'
    + (q.foto_url ? '<div class="rev-visualizar"><button type="button" class="btn btn-ghost btn-sm" id="rev-ver-cartao" onclick="abrirCartaoInteiro()">Ver cartão inteiro</button></div>' : '')
    + '<p class="rev-leitura">' + leitura(q) + '</p>'
    + '<div class="rev-opcoes">' + botoes + '</div>'
    + '<div class="rev-opcoes rev-opcoes-extra">'
    + '<button type="button" class="btn btn-ghost" onclick="escolher(\'BRANCO\')">Em branco</button>'
    + '<button type="button" class="btn btn-ghost" onclick="escolher(\'ANULADA\')">Anulada</button></div>'
    + '<div class="rev-acoes">'
    + '<button type="button" class="btn btn-ghost btn-sm" onclick="voltar()"' + (historico.length ? '' : ' disabled') + '>Voltar</button>'
    + '<button type="button" class="btn btn-ghost btn-sm" onclick="pular()"' + (FILA.length > 1 ? '' : ' disabled') + '>Pular</button></div>'
    + '<p class="rev-teclas">Teclado: A a ' + ALTERNATIVAS[ALTERNATIVAS.length - 1] + ' respondem, 0 deixa em branco e X anula.</p></div></div>';
  restringirCopiaImagem(document.getElementById('rev-previa'));
  atualizarPreviaRevisao();
  if (revisaoDesktop.matches && FILA[1] && FILA[1].endireitada_url) new Image().src = FILA[1].endireitada_url;
}

function escolher(alternativa) {
  if (salvando || cartaoModal.open || !FILA.length) return;
  estadoSalvando(true);
  var q = FILA[0];
  var acao = statusCartao[q.cartao_id] === 'lido' ? 'revisar' : 'resolver-ambiguidade';
  fetch(CARTOES_URL + '/' + q.cartao_id + '/' + acao, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ numero_questao: q.numero_questao, alternativa: alternativa })
  }).then(function (r) {
    return r.json().catch(function () { return {}; }).then(function (d) {
      estadoSalvando(false);
      if (!r.ok) { alert(d.message || d.erro || 'Não foi possível salvar a resposta.'); return; }
      if (d.cartao_status) statusCartao[q.cartao_id] = d.cartao_status;
      q.alternativa = ROTULOS[alternativa] ? null : alternativa;
      q.ambigua = false;
      historico.push(FILA.shift());
      desenhar();
    });
  }).catch(function () { estadoSalvando(false); alert('Sem conexão. A resposta não foi salva.'); });
}

function voltar() {
  if (salvando || cartaoModal.open || !historico.length) return;
  FILA.unshift(historico.pop());
  desenhar();
}

function pular() {
  if (salvando || cartaoModal.open || FILA.length < 2) return;
  FILA.push(FILA.shift());
  desenhar();
}

document.addEventListener('keydown', function (e) {
  if (document.querySelector('dialog[open]')) return;
  if (e.ctrlKey || e.metaKey || e.altKey) return;
  var tecla = e.key.toUpperCase();
  if (ALTERNATIVAS.indexOf(tecla) >= 0) escolher(tecla);
  else if (tecla === '0') escolher('BRANCO');
  else if (tecla === 'X') escolher('ANULADA');
});

desenhar();
</script>
@endpush
