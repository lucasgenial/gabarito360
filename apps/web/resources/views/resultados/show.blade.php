@extends('layouts.app')

@section('tela', 'tela-resultado')

{{--
  Arquivo: apps/web/resources/views/resultados/show.blade.php
  Função: Resultado do aluno numa prova — nota, resumo e acertos/erros questão por questão.
          Página pensada para imprimir, baixar em PDF e enviar por WhatsApp ao aluno ou
          aos responsáveis (mockup resultado.html).
--}}

@section('title', 'Resultado — ' . $dados['aluno']['nome'])

@php
  $aluno  = $dados['aluno'];
  $prova  = $dados['prova'];
  $nota   = $dados['nota'];
  $folha  = $dados['folha'];
  $maxima = (float) ($prova['nota_maxima'] ?? 10);
  $aprov  = (float) $nota['nota_final'] >= $maxima * 0.6;
  $cont   = collect($folha)->countBy('situacao');
  $lingua = !empty($dados['lingua']) ? ($dados['lingua'] === 'ESP' ? 'Espanhol' : 'Inglês') : null;
  $fmt    = fn ($n) => number_format((float) $n, 1, ',', '.');
  $urlPdf = route('resultados.pdf', [$aluno['id'], $prova['id']], false);
  $resumoTexto = "Resultado — {$prova['titulo']} ({$prova['disciplina']})\n{$aluno['nome']}"
    . ($aluno['turma_nome'] ? " · {$aluno['turma_nome']}" : '')
    . "\nNota: " . $fmt($nota['nota_final']) . " · {$nota['acertos']} acertos de {$nota['total_questoes']} questões";
@endphp

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
@if($from === 'correcao')
  <a href="{{ route('correcao.show', $prova['id']) }}">Correção — {{ $prova['disciplina'] }}</a><span class="sep">/</span>
@elseif($from === 'prova')
  <a href="{{ route('relatorios.prova', $prova['id']) }}">Relatório da prova</a><span class="sep">/</span>
@elseif($from === 'rel-turma' && $aluno['turma_id'])
  <a href="{{ route('relatorios.turmaProva', [$aluno['turma_id'], $prova['id']]) }}">Relatório da turma</a><span class="sep">/</span>
@elseif($aluno['turma_id'])
  <a href="{{ route('turmas.show', $aluno['turma_id']) }}">{{ $aluno['turma_nome'] }}</a><span class="sep">/</span>
@endif
<span>{{ $aluno['nome'] }}</span>
@endsection

@push('styles')
<style>@media print { @page { size: A4; margin: 12mm; } }</style>
@endpush

@section('content')
@include('relatorios._cabecalho', ['cabecalho' => $dados['cabecalho'] ?? [], 'titulo' => 'Resultado · ' . $dados['prova']['titulo'], 'subtitulo' => $dados['prova']['disciplina']])

<div class="row-between" style="margin-top:12px;flex-wrap:wrap;">
  <div>
    <div class="eyebrow no-print">{{ $prova['disciplina'] }} · {{ $prova['titulo'] }}</div>
    <h1 class="page-title" style="margin:4px 0 2px;">{{ $aluno['nome'] }}</h1>
    <p class="page-sub">
      {{ collect([$aluno['turma_nome'] ? 'Turma ' . $aluno['turma_nome'] : null, $aluno['matricula'] ? 'Matrícula ' . $aluno['matricula'] : null, $lingua])->filter()->implode(' · ') }}
    </p>
  </div>
  <div class="res-acoes no-print">
    @if($from === 'correcao')
      <a class="btn btn-ghost" href="{{ route('correcao.show', $prova['id']) }}">← Voltar à correção</a>
    @endif
    <button type="button" class="btn btn-secondary" onclick="window.print()"><x-icone nome="imprimir" /> Imprimir</button>
    <a class="btn btn-secondary" href="{{ $urlPdf }}?baixar=1">⬇ PDF</a>
    <button type="button" class="btn btn-primary" id="btn-whats" style="background:#1fa855;border-color:#1fa855;"><x-icone nome="compartilhar" /> WhatsApp</button>
    @php $podeMexer = \App\Support\Acesso::podeAlterarCorrecao(!empty($dados['correcao']['fechada'])); @endphp
    @if($dados['cartao_id'] && $podeMexer)
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal-revisar').style.display='flex'">Revisar leitura</button>
      <button type="button" class="btn btn-ghost" style="color:var(--danger);" id="btn-excluir-correcao"><x-icone nome="lixeira" /> Excluir correção</button>
    @elseif(!empty($dados['correcao']['fechada']))
      <span class="badge badge-warn" title="Peça à coordenação para reabrir"><x-icone nome="cadeado" /> correção fechada</span>
    @endif
  </div>
</div>

{{-- Resumo --}}
<div class="res-resumo">
  <div class="res-box {{ $aprov ? 'ok' : 'no' }}">
    <div class="v" style="font-size:34px;">{{ $fmt($nota['nota_final']) }}</div>
    <div class="l">Nota (de {{ $fmt($maxima) }}){{ $dados['media_turma'] !== null ? ' · média da turma ' . $fmt($dados['media_turma']) : '' }}</div>
  </div>
  <div class="res-box ok"><div class="v">{{ $cont['correta'] ?? 0 }}</div><div class="l">Acertos</div></div>
  <div class="res-box no"><div class="v">{{ $cont['incorreta'] ?? 0 }}</div><div class="l">Erros</div></div>
  <div class="res-box bl"><div class="v">{{ $cont['branco'] ?? 0 }}</div><div class="l">Em branco</div></div>
  <div class="res-box an"><div class="v">{{ $cont['anulada'] ?? 0 }}</div><div class="l">Anuladas</div></div>
</div>

{{-- Questão por questão --}}
<div class="card card-pad" style="margin-top:16px;">
  <div class="row-between">
    <h3 style="font-size:17px;">Questão por questão</h3>
    <span class="no-print" style="font-size:12px;color:var(--muted);">Verde: acertou · vermelho: errou · amarelo: em branco · cinza: anulada</span>
  </div>
  {{-- Colunas de cima para baixo, como no cartão-resposta (no celular, lista corrida) --}}
  <div class="res-folha" style="--linhas:{{ (int) ceil(count($folha) / 3) }};">
    @foreach($folha as $q)
      <div class="res-q {{ $q['situacao'] }}">
        <b>{{ str_pad($q['numero_questao'], 2, '0', STR_PAD_LEFT) }}</b>
        <span>
          @switch($q['situacao'])
            @case('correta') acertou ({{ $q['marcada'] }}) @break
            @case('incorreta') marcou <b>{{ $q['marcada'] }}</b> · certa <b>{{ $q['gabarito'] }}</b> @break
            @case('branco') {{ !empty($q['ambigua']) ? 'marcação dupla/fraca' : 'em branco' }} · certa <b>{{ $q['gabarito'] }}</b> @break
            @default anulada
          @endswitch
        </span>
      </div>
    @endforeach
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
            @foreach($folha as $q)
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

<div style="height:48px" class="no-print"></div>
@endsection

@push('scripts')
<script>
// Excluir a correção do aluno (apaga foto, respostas e nota) para fotografar de novo
(function () {
  var b = document.getElementById('btn-excluir-correcao');
  if (!b) return;
  b.addEventListener('click', function () {
    if (!confirm('Excluir a correção de {{ addslashes($aluno['nome']) }} nesta prova?\nA foto, as respostas e a nota serão apagadas e você poderá fotografar o cartão de novo.')) return;
    fetch('/cartoes/{{ (int) $dados['cartao_id'] }}', { method: 'DELETE', credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (!res.ok) { alert(res.j.erro || 'Não foi possível excluir.'); return; }
        window.location.href = @json(route('correcao.show', $prova['id'], false));
      });
  });
})();

// Envia o PDF pelo compartilhamento do celular (WhatsApp aparece na lista); sem suporte a
// arquivos, baixa o PDF e abre o WhatsApp com o resumo para anexar.
(function () {
  var btn = document.getElementById('btn-whats');
  var PDF_URL = @json($urlPdf);
  var TEXTO = @json($resumoTexto);
  btn.addEventListener('click', function () {
    var rotulo = btn.textContent;
    btn.disabled = true; btn.textContent = 'Gerando PDF…';
    fetch(PDF_URL, { credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw new Error('falha ao gerar o PDF'); return r.blob(); })
      .then(function (blob) {
        var nome = 'resultado-{{ \Illuminate\Support\Str::slug($aluno['nome'] . ' ' . $prova['disciplina']) }}.pdf';
        var arq = new File([blob], nome, { type: 'application/pdf' });
        if (navigator.canShare && navigator.canShare({ files: [arq] })) {
          return navigator.share({ files: [arq], title: 'Resultado', text: TEXTO }).catch(function () {});
        }
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob); a.download = nome; document.body.appendChild(a); a.click(); a.remove();
        window.open('https://wa.me/?text=' + encodeURIComponent(TEXTO + '\n(PDF em anexo)'), '_blank');
      })
      .catch(function (e) { alert('Não foi possível enviar: ' + e.message); })
      .finally(function () { btn.disabled = false; btn.textContent = rotulo; });
  });
})();
</script>
@endpush
