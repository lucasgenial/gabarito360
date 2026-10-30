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

function escH(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }

function recorteHtml(q) {
  if (!q.endireitada_url) {
    return '<img class="rev-foto" src="' + escH(q.foto_url) + '" alt="Foto do cartão">';
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
    + recorteHtml(q)
    + '<p class="rev-leitura">' + leitura(q) + '</p>'
    + '<div class="rev-opcoes">' + botoes + '</div>'
    + '<div class="rev-opcoes rev-opcoes-extra">'
    + '<button type="button" class="btn btn-ghost" onclick="escolher(\'BRANCO\')">Em branco</button>'
    + '<button type="button" class="btn btn-ghost" onclick="escolher(\'ANULADA\')">Anulada</button></div>'
    + '<div class="rev-acoes">'
    + (q.foto_url ? '<a class="btn btn-ghost btn-sm" href="' + escH(q.foto_url) + '" target="_blank" rel="noopener">Ver cartão inteiro</a>' : '')
    + '<button type="button" class="btn btn-ghost btn-sm" onclick="voltar()"' + (historico.length ? '' : ' disabled') + '>Voltar</button>'
    + '<button type="button" class="btn btn-ghost btn-sm" onclick="pular()"' + (FILA.length > 1 ? '' : ' disabled') + '>Pular</button></div>'
    + '<p class="rev-teclas">Teclado: A a ' + ALTERNATIVAS[ALTERNATIVAS.length - 1] + ' respondem, 0 deixa em branco e X anula.</p>';
  if (FILA[1] && FILA[1].endireitada_url) new Image().src = FILA[1].endireitada_url;
}

function escolher(alternativa) {
  if (salvando || !FILA.length) return;
  salvando = true;
  var q = FILA[0];
  var acao = statusCartao[q.cartao_id] === 'lido' ? 'revisar' : 'resolver-ambiguidade';
  fetch(CARTOES_URL + '/' + q.cartao_id + '/' + acao, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ numero_questao: q.numero_questao, alternativa: alternativa })
  }).then(function (r) {
    return r.json().catch(function () { return {}; }).then(function (d) {
      salvando = false;
      if (!r.ok) { alert(d.message || d.erro || 'Não foi possível salvar a resposta.'); return; }
      if (d.cartao_status) statusCartao[q.cartao_id] = d.cartao_status;
      q.alternativa = ROTULOS[alternativa] ? null : alternativa;
      q.ambigua = false;
      historico.push(FILA.shift());
      desenhar();
    });
  }).catch(function () { salvando = false; alert('Sem conexão. A resposta não foi salva.'); });
}

function voltar() {
  if (salvando || !historico.length) return;
  FILA.unshift(historico.pop());
  desenhar();
}

function pular() {
  if (salvando || FILA.length < 2) return;
  FILA.push(FILA.shift());
  desenhar();
}

document.addEventListener('keydown', function (e) {
  if (e.ctrlKey || e.metaKey || e.altKey) return;
  var tecla = e.key.toUpperCase();
  if (ALTERNATIVAS.indexOf(tecla) >= 0) escolher(tecla);
  else if (tecla === '0') escolher('BRANCO');
  else if (tecla === 'X') escolher('ANULADA');
});

desenhar();
</script>
@endpush
