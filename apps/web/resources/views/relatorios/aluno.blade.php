@extends('layouts.app')

@section('tela', 'tela-rel-aluno')

{{--
  Arquivo: apps/web/resources/views/relatorios/aluno.blade.php
  Função: Boletim do aluno — todas as provas da turma com nota, acertos, média da turma e
          posição; gráfico aluno × turma. Imprimível e com envio do resumo por WhatsApp.
--}}

@php
  $fmt = fn ($n) => $n === null ? '—' : number_format((float) $n, 1, ',', '.');
  $linhasWhats = collect($provas)->map(fn ($p) => '• ' . $p['disciplina'] . ': ' . ($p['nota'] !== null ? $fmt($p['nota']) . ' (' . $p['acertos'] . '/' . $p['total'] . ')' : 'não corrigida'))->implode("\n");
  $textoWhats  = "Boletim — {$aluno['nome']} · Turma {$turma['nome']}\n" . ($turma['escola_nome'] ?? '') . "\n\n{$linhasWhats}\n\nMédia: " . $fmt($kpis['media']) . ' · média da turma: ' . $fmt($kpis['media_turma']);
@endphp

@section('title', 'Boletim — ' . $aluno['nome'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<a href="{{ route('relatorios.turma', $turma['id']) }}">Turma {{ $turma['nome'] }}</a><span class="sep">/</span>
<span>Boletim</span>
@endsection

@section('content')
@include('relatorios._cabecalho', ['cabecalho' => $cabecalho ?? [], 'titulo' => 'Boletim de avaliações', 'subtitulo' => 'Turma ' . $turma['nome']])
<div class="row-between" style="margin-top:12px;flex-wrap:wrap;">
  <div>
    <h1 class="page-title" style="margin:4px 0 2px;">{{ $aluno['nome'] }}</h1>
    <p class="page-sub">{{ collect(['Turma ' . $turma['nome'], $aluno['matricula'] ? 'Matrícula ' . $aluno['matricula'] : null, $aluno['nome_responsavel'] ? 'Responsável: ' . $aluno['nome_responsavel'] : null])->filter()->implode(' · ') }}</p>
  </div>
  <div class="no-print" style="display:flex;gap:8px;flex-wrap:wrap;">
    <button type="button" class="btn btn-secondary" onclick="window.print()"><x-icone nome="imprimir" /> Imprimir / PDF</button>
    <a class="btn btn-primary" style="background:#1fa855;border-color:#1fa855;" target="_blank" rel="noopener"
       href="https://wa.me/?text={{ rawurlencode($textoWhats) }}"><x-icone nome="compartilhar" /> WhatsApp</a>
    <a class="btn btn-ghost" href="{{ route('alunos.show', $aluno['id']) }}">Cadastro</a>
  </div>
</div>

<div class="kpis">
  <div class="card"><div class="v" style="color:var(--accent);">{{ $fmt($kpis['media']) }}</div><div class="l">Média do aluno</div></div>
  <div class="card"><div class="v">{{ $fmt($kpis['media_turma']) }}</div><div class="l">Média da turma</div></div>
  <div class="card"><div class="v">{{ $kpis['feitas'] }}<span style="font-size:14px;color:var(--muted);">/{{ $kpis['provas'] }}</span></div><div class="l">Provas corrigidas</div></div>
  <div class="card"><div class="v">{{ $kpis['acertos'] }}<span style="font-size:14px;color:var(--muted);">/{{ $kpis['questoes'] }}</span></div><div class="l">Acertos</div></div>
</div>

{{-- Aluno × média da turma por prova --}}
@php
  $n = count($provas);
  $em10 = fn ($v, $p) => $v === null ? null : round($v * 10 / max($p['nota_maxima'], 0.1), 1);
  $listaP = collect($provas);
@endphp
@if($n)
<div class="card card-pad" style="margin-top:16px;">
  <div class="row-between" style="flex-wrap:wrap;">
    <h3 style="font-size:17px;">Aluno × turma</h3>
    <span style="font-size:12px;color:var(--muted);">Notas na escala de 0 a 10</span>
  </div>
  <x-grafico descricao="Nota do aluno e média da turma por prova" :altura="260"
    :dados="['categorias' => $listaP->map(fn ($p) => \Illuminate\Support\Str::limit($p['disciplina'], 18))->values(), 'formato' => 'nota', 'maximo' => 10, 'rotulos' => $n <= 6,
             'series' => [['nome' => 'Aluno', 'valores' => $listaP->map(fn ($p) => $em10($p['nota'], $p))->values()],
                          ['nome' => 'Média da turma', 'valores' => $listaP->map(fn ($p) => $em10($p['media_turma'], $p))->values()]]]" />
</div>
@endif

<div class="card card-pad" style="margin-top:16px;">
  <h3 style="font-size:17px;margin-bottom:6px;">Provas</h3>
  <div style="overflow-x:auto;">
    <x-tabela :integrada="true" titulo="Provas do aluno" modo="lista"><table class="tabela-padrao tab "><caption class="sr-only">Provas do aluno</caption>
      <thead><tr><th scope="col" class="esq">Prova</th><th scope="col">Nota</th><th scope="col">Acertos</th><th scope="col" class="opc">Média turma</th><th scope="col">Posição</th><th scope="col" class="tabela-coluna-acoes no-print">Ações</th></tr></thead>
      <tbody>
        @foreach($provas as $p)
          <tr>
            <td class="tabela-principal esq" data-rotulo="Prova"><div class="tabela-valor"><b>{{ $p['disciplina'] }}</b><div style="font-size:12px;color:var(--muted);">{{ $p['titulo'] }}{{ $p['lingua'] ? ' · ' . ($p['lingua'] === 'ESP' ? 'Espanhol' : 'Inglês') : '' }}</div></div></td>
            <td data-rotulo="Nota"><div class="tabela-valor">
              @if($p['nota'] !== null)
                <span class="nota" style="color:{{ $p['nota'] >= $p['nota_maxima'] * 0.6 ? 'var(--success)' : 'var(--danger)' }};">{{ $fmt($p['nota']) }}</span>
              @else
                <span style="color:var(--muted);">não corrigida</span>
              @endif
            </div></td>
            <td data-rotulo="Acertos"><div class="tabela-valor">{{ $p['nota'] !== null ? $p['acertos'] . '/' . $p['total'] : '—' }}</div></td>
            <td class="opc" data-rotulo="Média turma"><div class="tabela-valor">{{ $fmt($p['media_turma']) }}</div></td>
            <td data-rotulo="Posição"><div class="tabela-valor">{{ $p['posicao'] ? $p['posicao'] . 'º de ' . $p['corrigidos'] : '—' }}</div></td>
            <td class="tabela-acoes no-print" data-rotulo="Ações"><div class="tabela-valor">@if($p['nota'] !== null)<a class="btn btn-sm btn-ghost" href="{{ route('resultados.show', [$aluno['id'], $p['id']]) }}?from=rel-turma">Acertos e erros</a>@endif</div></td>
          </tr>
        @endforeach
      </tbody>
    </table></x-tabela>
  </div>
</div>
<div style="height:48px" class="no-print"></div>
@endsection
