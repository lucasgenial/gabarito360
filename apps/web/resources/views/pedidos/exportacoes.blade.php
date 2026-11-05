@extends('layouts.app')
@section('title','Exportações de titulares')
@include('pedidos._estilo')
@section('content')
<x-pagina titulo="Exportações de titulares" subtitulo="Acesso delimitado por protocolo, pessoa e autorização vigente." />
<section class="card card-pad pedidos-bloco">
<h2>{{ $gestor?'Consultar protocolo para autorizar':'Consultar arquivo gerado' }}</h2>
<form class="pedidos-form" method="GET" action="{{ route('pedidos.exportacoes') }}">

<label for="referencia">{{ $gestor?'Referência do protocolo':'Referência do arquivo' }}</label><input class="input" id="referencia" name="referencia" value="{{ $ref }}" required maxlength="36">
<button class="btn btn-outline">Consultar referência</button></form>
</section>
@if($previa && $gestor)
<section class="card card-pad pedidos-bloco"><h2>Autorizar exportação</h2>
<p class="pedidos-ref">Protocolo {{ $ref }}</p><p>Aluno de referência {{ $previa['aluno_id'] }} · responsável interno {{ $previa['destinatario_acesso_id'] }}</p>
<p>Confirme a referência do aluno e a autoridade documentada antes de autorizar. Somente os campos selecionados poderão ser exportados.</p>
<p>Limite: {{ \Illuminate\Support\Carbon::parse($previa['limite_validade'])->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (Brasília).</p>
<form class="pedidos-form" method="POST" action="{{ route('pedidos.exportacoes.enviar',['acao'=>'conceder']) }}">
@include('pedidos._envio',['envio'=>$envios['conceder']])<input type="hidden" name="referencia" value="{{ $ref }}">
@foreach(['versao','aluno_id','sujeito_versao','destinatario_acesso_id'] as $campo)<input type="hidden" name="{{ $campo }}" value="{{ $previa[$campo] }}">@endforeach
<fieldset><legend>Campos autorizados</legend><label><input type="checkbox" name="campos[]" value="nome"> Nome</label> <label><input type="checkbox" name="campos[]" value="matricula"> Matrícula</label></fieldset>
<label for="validade-exportacao">Válida até (Brasília)</label><input class="input" id="validade-exportacao" type="datetime-local" name="valida_ate" required>
<label for="evidencia-exportacao">Referência da autorização institucional</label><input class="input" id="evidencia-exportacao" name="evidencia_referencia" minlength="5" maxlength="500" required>
@include('pedidos._confirmacao',['sufixo'=>'autorizar-exportacao'])<button class="btn btn-primary">Autorizar campos selecionados</button></form></section>
@elseif($previa)
<section class="card card-pad pedidos-bloco"><h2>Arquivo disponível</h2><p class="pedidos-ref">{{ $previa['referencia'] }}</p><p>Campos: {{ implode(', ',$previa['campos']) }}</p>
<p>Expira em {{ \Illuminate\Support\Carbon::parse($previa['expira_em'])->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (Brasília).</p>
<a class="btn btn-primary" href="{{ route('pedidos.exportacoes.arquivo',['id'=>$previa['referencia']]) }}">Baixar arquivo JSON</a><p>O acesso será conferido novamente ao baixar.</p></section>
@endif
<section class="card card-pad pedidos-bloco"><h2>{{ $gestor?'Revogar autorização':'Gerar arquivo autorizado' }}</h2>
<p>{{ $gestor?'Use a referência e a versão da autorização que deseja revogar. O histórico será preservado.':'Informe a autorização fornecida pelo administrador da rede ou proprietário do espaço. Nenhum campo adicional será incluído.' }}</p>
@php($acao=$gestor?'revogar':'gerar')
<form class="pedidos-form" method="POST" action="{{ route('pedidos.exportacoes.enviar',['acao'=>$acao]) }}">
@include('pedidos._envio',['envio'=>$envios[$acao]])
<label for="autorizacao">Referência da autorização</label><input class="input" id="autorizacao" name="referencia" required maxlength="36">
<label for="versao-autorizacao">Versão da autorização</label><input class="input" id="versao-autorizacao" name="versao" type="number" min="1" value="1" required>
@if($gestor)@include('pedidos._confirmacao',['sufixo'=>'revogar-exportacao'])
@else<label><input type="checkbox" name="confirmado" value="1" required> Confirmo a geração no escopo da autorização indicada.</label>@endif
<button class="btn btn-outline">{{ $gestor?'Revogar autorização':'Gerar arquivo' }}</button></form></section>
@endsection
