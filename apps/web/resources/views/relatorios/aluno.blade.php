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
    <table class="tab lista-cel">
      <thead><tr><th class="esq">Prova</th><th>Nota</th><th>Acertos</th><th class="opc">Média turma</th><th>Posição</th><th class="no-print"></th></tr></thead>
      <tbody>
        @foreach($provas as $p)
          <tr>
            <td class="esq"><b>{{ $p['disciplina'] }}</b><div style="font-size:12px;color:var(--muted);">{{ $p['titulo'] }}{{ $p['lingua'] ? ' · ' . ($p['lingua'] === 'ESP' ? 'Espanhol' : 'Inglês') : '' }}</div></td>
            <td>
              @if($p['nota'] !== null)
                <span class="nota" style="color:{{ $p['nota'] >= $p['nota_maxima'] * 0.6 ? 'var(--success)' : 'var(--danger)' }};">{{ $fmt($p['nota']) }}</span>
              @else
                <span style="color:var(--muted);">não corrigida</span>
              @endif
            </td>
            <td>{{ $p['nota'] !== null ? $p['acertos'] . '/' . $p['total'] : '—' }}</td>
            <td class="opc">{{ $fmt($p['media_turma']) }}</td>
            <td>{{ $p['posicao'] ? $p['posicao'] . 'º de ' . $p['corrigidos'] : '—' }}</td>
            <td class="no-print">@if($p['nota'] !== null)<a class="btn btn-sm btn-ghost" href="{{ route('resultados.show', [$aluno['id'], $p['id']]) }}?from=rel-turma">Acertos e erros</a>@endif</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
<div style="height:48px" class="no-print"></div>
@endsection
