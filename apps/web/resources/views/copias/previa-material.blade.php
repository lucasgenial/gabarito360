@extends('layouts.app')
@section('title','Prévia dos materiais')
@section('breadcrumb')<a href="{{ route('copias.index') }}">Cópias</a><span class="sep">/</span><span>Prévia dos materiais</span>@endsection
@section('content')
<x-pagina titulo="Prévia dos materiais"><x-slot:sub>Confira os dados incluídos na seleção.</x-slot:sub></x-pagina>
<x-aviso>Esta consulta não autoriza nem inicia uma cópia. Os textos precisam de revisão antes das decisões de origem e destino.</x-aviso>
@foreach($dados['materiais'] as $item)
@php($p=$item['material']['prova'])
<section class="card card-pad" style="margin-top:20px;">
  <h2>{{ $p['titulo'] }}</h2>
  <dl><dt>Disciplina e série</dt><dd>{{ $p['disciplina'] }} · {{ $p['serie'] }}</dd>
    <dt>Formato</dt><dd>{{ $p['num_questoes'] }} questões · {{ $p['num_alternativas'] }} alternativas</dd>
    <dt>Nota máxima</dt><dd>{{ $p['nota_maxima'] }}</dd>
    <dt>Pontuação</dt><dd>{{ $p['tipo_pontuacao']==='personalizado'?'Personalizada':'Igual por questão' }}</dd>
    <dt>Anular se todas marcadas</dt><dd>{{ $p['anular_se_todas']?'Sim':'Não' }}</dd>
    <dt>Opção de língua</dt><dd>{{ $p['opcao_lingua']?'Sim':'Não' }}</dd></dl>
  <details><summary>Conferir gabarito</summary><div style="overflow-x:auto;">
    <table class="table"><thead><tr><th scope="col">Questão</th><th scope="col">Alternativa</th><th scope="col">Espanhol</th><th scope="col">Peso</th><th scope="col">Anulada</th></tr></thead>
      <tbody>@foreach($item['material']['questoes'] as $q)<tr><td>{{ $q['numero_questao'] }}</td><td>{{ $q['alternativa'] }}</td><td>{{ $q['alternativa_espanhol'] ?? '—' }}</td><td>{{ $q['peso'] }}</td><td>{{ $q['anulada']?'Sim':'Não' }}</td></tr>@endforeach</tbody>
    </table></div></details>
</section>
@endforeach
<a class="btn btn-secondary" style="margin-top:20px;" href="{{ route('copias.materiais',['espaco_id'=>$dados['espaco_id'],'_ctx'=>\App\Support\ContextoWeb::id()]) }}">Voltar à seleção</a>
@endsection
