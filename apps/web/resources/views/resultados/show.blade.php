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
  $pedagogico = !empty($dados['pedagogico']);
  $totalLeituras = array_key_exists('total_leituras',$dados) ? (int)$dados['total_leituras'] : null;
  $mostrarHistorico = !empty($dados['historico_permitido']) && $totalLeituras !== null && $totalLeituras > 1;
  $urlPdfAluno = $urlPdf.'?modelo=aluno';
  $urlPdfProfessor = $urlPdf.'?modelo=professor';
  $urlImpressao = $pedagogico ? $urlPdfProfessor : $urlPdfAluno;
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
<link rel="stylesheet" href="{{ asset('css/leituras-resultados.css') }}?v={{ filemtime(public_path('css/leituras-resultados.css')) }}">
<style>@media print { @page { size: A4; margin: 12mm; } }</style>
@endpush

@section('content')
@include('relatorios._cabecalho', ['cabecalho' => $dados['cabecalho'] ?? [], 'titulo' => 'Resultado · ' . $dados['prova']['titulo'], 'subtitulo' => $dados['prova']['disciplina']])

<header class="res-identificacao">
  <h1 class="page-title">{{ $aluno['nome'] }}</h1>
  <p class="page-sub">{{ collect([$aluno['turma_nome'] ? 'Turma ' . $aluno['turma_nome'] : null, $aluno['matricula'] ? 'Matrícula ' . $aluno['matricula'] : null, $lingua])->filter()->implode(' · ') }}</p>
  @if($totalLeituras !== null)<p class="res-tentativas-total" id="resultado-tentativas-total">{{ $totalLeituras }} {{ $totalLeituras===1?'leitura registrada':'leituras registradas' }}</p>@endif
</header>
<div class="res-barra-acoes no-print" aria-label="Ações do resultado">
  @if(!empty($dados['cartao_foto_url']))<button type="button" class="btn btn-secondary res-acao" id="doc-abrir" aria-label="Cartão resposta" title="Cartão resposta"><x-icone nome="prova"/><span class="res-acao-texto">Cartão resposta</span><span class="res-legenda" aria-hidden="true">Cartão resposta</span></button>@endif
  <details class="res-menu-relatorio" id="res-menu-relatorio"><summary class="btn btn-secondary res-acao" aria-label="Relatório" title="Relatório"><x-icone nome="grafico"/><span class="res-acao-texto">Relatório</span><span class="res-legenda" aria-hidden="true">Relatório</span></summary>
    <div class="res-opcoes-relatorio">
      <form method="POST" action="{{ $urlImpressao }}" class="arquivo-form" target="_blank" rel="noopener">@csrf <x-revisao-acesso /><button type="submit" class="btn btn-ghost" title="Abrir o PDF emitido para impressão"><x-icone nome="imprimir"/>Imprimir relatório {{ $pedagogico ? 'do professor' : 'do aluno' }}</button></form>
      <form method="POST" action="{{ $urlPdfAluno }}&amp;baixar=1" class="arquivo-form">@csrf <x-revisao-acesso /><button type="submit" class="btn btn-ghost"><x-icone nome="prova"/>Baixar relatório do aluno</button></form>
      @if($pedagogico)<form method="POST" action="{{ $urlPdfProfessor }}&amp;baixar=1" class="arquivo-form">@csrf <x-revisao-acesso /><button type="submit" class="btn btn-ghost"><x-icone nome="prova"/>Baixar relatório do professor</button></form>@endif
      <button type="button" class="btn btn-ghost" id="btn-whats"><x-icone nome="compartilhar"/>Compartilhar relatório do aluno</button>
    </div>
  </details>
  @if(!empty($dados['correcao']['fechada']))<span class="badge badge-warn"><x-icone nome="cadeado"/>Correção fechada</span>@endif
</div>
@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if($errors->any() && !request('tentativa'))<x-aviso tipo="erro">{{ $errors->first() }}</x-aviso>@endif

{{-- Resumo --}}
@if(($dados['qualidade_foto']['aprovada']??null)===false)
  <x-aviso tipo="atencao">{{ $dados['qualidade_foto']['mensagem'] }} As respostas e a nota já registradas foram preservadas. Confira todas as questões ou envie outra foto para preparar uma nova tentativa.</x-aviso>
@endif
<div class="res-resumo">
  <div class="res-box {{ $aprov ? 'ok' : 'no' }}">
    <div class="v" style="font-size:34px;">{{ $fmt($nota['nota_final']) }}</div>
    <div class="l">Nota (de {{ $fmt($maxima) }}){{ $pedagogico && ($dados['media_turma'] ?? null) !== null ? ' · média da turma ' . $fmt($dados['media_turma']) : '' }}</div>
  </div>
  <div class="res-box ok"><div class="v">{{ $cont['correta'] ?? 0 }}</div><div class="l">Acertos</div></div>
  <div class="res-box no"><div class="v">{{ $cont['incorreta'] ?? 0 }}</div><div class="l">Erros</div></div>
  <div class="res-box bl"><div class="v">{{ $cont['branco'] ?? 0 }}</div><div class="l">Em branco</div></div>
  <div class="res-box an"><div class="v">{{ $cont['anulada'] ?? 0 }}</div><div class="l">Anuladas</div></div>
</div>

{{-- Questão por questão --}}
<div class="{{ !empty($dados['cartao_foto_url']) ? 'documento-estacao resultado-documentos' : '' }}">
<div class="card card-pad res-questoes" @if(empty($dados['cartao_foto_url'])) style="margin-top:16px;" @endif>
  <div class="row-between">
    <h3 style="font-size:17px;">Questão por questão</h3>
    <span class="no-print" style="font-size:12px;color:var(--muted);">Verde: acertou · vermelho: errou · amarelo: em branco · cinza: anulada</span>
  </div>
  @if($pedagogico)
    @include('resultados._pedagogico')
  @else
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
  @endif
</div>
@if(!empty($dados['cartao_foto_url']))
  @include('correcao._documento',['documentoUrl'=>$dados['cartao_foto_url'],'documentoContexto'=>$aluno['nome'].' · '.$prova['titulo'],'documentoOpcoes'=>['ajuste'=>'largura','controles'=>false,'cardsIguais'=>true,'rotulo'=>'Cartão resposta']])
@endif
</div>

@if($mostrarHistorico)@include('resultados._historico')@endif
<div style="height:48px" class="no-print"></div>
@endsection

@push('scripts')
@php($g360Config = ['pdf'=>\App\Support\NavegacaoWeb::destino('resultados.pdf',[$aluno['id'],$prova['id'],'modelo'=>'aluno']),'texto'=>$resumoTexto,'nomeArquivo'=>'resultado-'.\Illuminate\Support\Str::slug($aluno['nome'].' '.$prova['disciplina']).'.pdf'])
<script type="application/json" id="resultado-compartilhar-config">@json($g360Config)</script>
<script src="{{ asset('js/resultado-compartilhar.js') }}?v={{ filemtime(public_path('js/resultado-compartilhar.js')) }}"></script>
@endpush
