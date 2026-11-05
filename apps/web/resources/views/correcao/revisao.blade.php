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

<div class="rev-estacao" id="rev-estacao">
  <div class="card card-pad rev-cartao"><div id="rev-conteudo"></div></div>
  <aside class="card rev-documento-painel" id="rev-documento-painel" aria-labelledby="rev-painel-titulo">
    <header class="rev-modal-cabecalho"><div><h2 id="rev-painel-titulo">Cartão-resposta completo</h2><p id="rev-painel-contexto"></p></div></header>
    <div id="rev-documento-desktop" class="rev-documento-destino"><div id="rev-leitor"></div></div>
  </aside>
</div>
<dialog id="rev-cartao-modal" class="rev-cartao-modal" aria-labelledby="rev-cartao-titulo" aria-describedby="rev-cartao-contexto">
  <header class="rev-modal-cabecalho">
    <div><h2 id="rev-cartao-titulo">Cartão-resposta</h2><p id="rev-cartao-contexto"></p></div>
    <button type="button" class="btn btn-secondary" id="rev-cartao-fechar" autofocus aria-label="Fechar cartão">Fechar</button>
  </header>
  <div id="rev-documento-mobile" class="rev-documento-destino"></div>
</dialog>

@endsection

@push('scripts')
<script src="{{ asset('js/leitor-cartao.js') }}?v={{ filemtime(public_path('js/leitor-cartao.js')) }}"></script>
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
var leitorRoot = document.getElementById('rev-leitor'), leitor = new LeitorCartao(leitorRoot);
var cartaoOrigem = null, cartaoOverflow = '';
var revisaoDesktop = window.matchMedia('(min-width:1100px)');
function dimensionarLeitor() {
  var painel=document.getElementById('rev-documento-painel');
  if(!revisaoDesktop.matches||!painel.offsetWidth)return;
  var caixa=painel.getBoundingClientRect(),escala=caixa.width/painel.offsetWidth;
  var rodape=document.querySelector('footer[aria-label="Versão do software"]');
  var limite=rodape?Math.min(window.innerHeight,rodape.getBoundingClientRect().top):window.innerHeight;
  painel.style.height=Math.max(260,(limite-caixa.top-16)/escala)+'px';
}
var ajusteLeitorPendente=false;
function agendarDimensaoLeitor(){if(ajusteLeitorPendente)return;ajusteLeitorPendente=true;requestAnimationFrame(function(){ajusteLeitorPendente=false;dimensionarLeitor();});}
window.addEventListener('resize',agendarDimensaoLeitor);
window.addEventListener('scroll',agendarDimensaoLeitor,{passive:true});
function restringirCopiaImagem(area) {
  ['contextmenu','dragstart','copy','cut'].forEach(function(tipo) { area.addEventListener(tipo,function(e) { e.preventDefault(); }); });
}
function sincronizarDocumento() {
  var q=FILA[0], disponivel=!!q?.foto_url;
  var contexto=q ? [q.aluno_nome,q.turma].filter(Boolean).join(' · ') || 'Cartão sem aluno identificado' : '';
  document.getElementById('rev-painel-contexto').textContent=contexto;
  document.getElementById('rev-cartao-contexto').textContent=contexto;
  document.getElementById('rev-estacao').classList.toggle('sem-documento',!disponivel);
  var destino=document.getElementById(revisaoDesktop.matches?'rev-documento-desktop':'rev-documento-mobile');
  if(leitorRoot.parentNode!==destino)destino.append(leitorRoot);
  dimensionarLeitor();
  if(revisaoDesktop.matches&&disponivel)leitor.carregar(q.foto_url);
  else if(!cartaoModal.open)leitor.limpar();
}
revisaoDesktop.addEventListener('change',function() {
  if(cartaoModal.open&&revisaoDesktop.matches)cartaoModal.close();
  sincronizarDocumento();
});
function abrirCartaoInteiro() {
  if(salvando||cartaoModal.open||!FILA[0]?.foto_url)return;
  cartaoOrigem=document.getElementById('rev-ver-cartao');
  cartaoOverflow=document.documentElement.style.overflow;document.documentElement.style.overflow='hidden';
  cartaoModal.showModal();sincronizarDocumento();leitor.carregar(FILA[0].foto_url);
  document.getElementById('rev-cartao-fechar').focus({preventScroll:true});
}
document.getElementById('rev-cartao-fechar').addEventListener('click',function(){cartaoModal.close();});
cartaoModal.addEventListener('keydown',function(e) {
  if(e.key!=='Tab')return;
  var controles=Array.from(cartaoModal.querySelectorAll('button:not([disabled]),[tabindex="0"]')).filter(function(el){return el.getClientRects().length>0;});
  var primeiro=controles[0],ultimo=controles[controles.length-1];
  if(e.shiftKey&&document.activeElement===primeiro){e.preventDefault();ultimo.focus();}
  else if(!e.shiftKey&&document.activeElement===ultimo){e.preventDefault();primeiro.focus();}
});
cartaoModal.addEventListener('close',function() {
  document.documentElement.style.overflow=cartaoOverflow;
  if(!revisaoDesktop.matches)leitor.limpar();
  if(cartaoOrigem?.isConnected)cartaoOrigem.focus({preventScroll:true});
});
function estadoSalvando(estado) {
  salvando = estado;
  document.querySelectorAll('.rev-opcoes button,.rev-acoes button').forEach(function(b) {
    if(estado){b.dataset.desabilitadoAnterior=String(b.disabled);b.disabled=true;}
    else b.disabled=b.dataset.desabilitadoAnterior==='true';
  });
  var aviso=document.getElementById('rev-salvamento');if(aviso)aviso.textContent=estado?'Salvando resposta…':'';
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
    sincronizarDocumento();return;
  }
  var q = FILA[0];
  var botoes = ALTERNATIVAS.map(function (l) {
    var atual = q.alternativa === l && !q.ambigua;
    return '<button type="button" class="btn rev-resposta' + (atual ? ' rev-resposta-atual' : '') + '"' + (atual ? ' aria-label="' + l + ', resposta atual"' : '') + ' onclick="escolher(\'' + l + '\')">' + l + '</button>';
  }).join('');
  el.innerHTML = '<div class="row-between rev-topo"><div><b>' + escH(q.aluno_nome || 'Aluno não identificado') + '</b>'
    + '<div class="exam-meta">' + escH(q.turma || '') + '</div></div>'
    + '<div class="rev-progresso">Questão ' + q.numero_questao + ' · Faltam ' + FILA.length + '</div></div>'
    + '<div class="rev-trabalho"><div id="rev-previa" class="rev-previa rev-imagem-restrita">' + recorteHtml(q) + '</div><div class="rev-respostas">'
    + (q.foto_url ? '<div class="rev-visualizar"><button type="button" class="btn btn-ghost btn-sm" id="rev-ver-cartao" onclick="abrirCartaoInteiro()">Visualizar gabarito</button></div>' : '')
    + '<p class="rev-leitura">' + leitura(q) + '</p>'
    + '<div class="rev-opcoes">' + botoes + '</div>'
    + '<div class="rev-opcoes rev-opcoes-extra">'
    + '<button type="button" class="btn rev-resposta-extra" onclick="escolher(\'BRANCO\')">Em branco</button>'
    + '<button type="button" class="btn rev-resposta-extra" onclick="escolher(\'ANULADA\')">Anulada</button></div>'
    + '<p id="rev-salvamento" class="rev-salvamento" role="status" aria-live="polite"></p>'
    + '<div class="rev-acoes">'
    + '<button type="button" class="btn btn-ghost btn-sm" onclick="voltar()"' + (historico.length ? '' : ' disabled') + '>Voltar</button>'
    + '<button type="button" class="btn btn-ghost btn-sm" onclick="pular()"' + (FILA.length > 1 ? '' : ' disabled') + '>Pular</button></div>'
    + '<p class="rev-teclas">Teclado: A a ' + ALTERNATIVAS[ALTERNATIVAS.length - 1] + ' respondem, 0 deixa em branco e X anula.</p></div></div>';
  restringirCopiaImagem(document.getElementById('rev-previa'));
  sincronizarDocumento();
  if (FILA[1] && FILA[1].endireitada_url) new Image().src = FILA[1].endireitada_url;
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
  if (document.querySelector('dialog[open]') || e.target.closest?.('.rev-leitor')) return;
  if (e.ctrlKey || e.metaKey || e.altKey) return;
  var tecla = e.key.toUpperCase();
  if (ALTERNATIVAS.indexOf(tecla) >= 0) escolher(tecla);
  else if (tecla === '0') escolher('BRANCO');
  else if (tecla === 'X') escolher('ANULADA');
});

desenhar();
</script>
@endpush
