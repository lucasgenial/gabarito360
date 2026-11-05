@extends('layouts.app')
@section('title','Atendimento do protocolo')
@include('pedidos._estilo')
@section('breadcrumb')<a href="{{ route('pedidos.index',[]) }}">Pedidos de titulares</a><span class="sep">/</span><span>Atendimento</span>@endsection
@section('content')
@php($p=$pedido)
@php($id=$p['referencia'])
<x-pagina titulo="Atendimento do protocolo" subtitulo="Conferência humana e registro administrativo. Nenhuma ação sobre dados escolares é executada aqui." />
<section class="card card-pad pedidos-bloco pedidos-info">
<h2>{{ $tipos[$p['tipo']] ?? 'Pedido' }} · {{ $estados[$p['situacao']] ?? 'Situação indisponível' }}</h2>
<p class="pedidos-ref">Protocolo {{ $id }}</p><p>{{ $p['cliente_nome'] }} · {{ $p['espaco_nome'] }}</p>
<h3>Identificação declarada</h3><p class="pedidos-texto">{{ $p['identificacao_declarada'] }}</p>
<h3>Relato</h3><p class="pedidos-texto">{{ $p['relato'] }}</p>
@if($p['resultado'])<h3>Resultado informado</h3><p>{{ str_replace('_',' ',$p['resultado']) }}</p>@endif
@if($p['resposta_referencia'])<h3>Referência da resposta administrativa</h3><p class="pedidos-texto">{{ $p['resposta_referencia'] }}</p>@endif
</section>
@if(config('services.pedidos.exportacoes'))
<section class="card card-pad pedidos-bloco"><h2>Sujeito e exportação</h2>
<p>Aluno vinculado: {{ $p['aluno_id'] ?? 'Ainda não vinculado' }}. A referência deve pertencer ao espaço deste protocolo.</p>
@if(in_array($p['situacao'],['recebida','em_verificacao','aguardando_informacao']))
<form class="pedidos-form" method="POST" action="{{ route('pedidos.alterar',['id'=>$id,'acao'=>'sujeito']) }}">
@include('pedidos._envio',['envio'=>$envios['sujeito']])<input type="hidden" name="versao" value="{{ $p['versao'] }}">
<label for="aluno-sujeito">Referência do aluno conferido</label><input class="input" id="aluno-sujeito" name="aluno_id" type="number" min="1" required>
<label for="evidencia-sujeito">Referência da evidência da vinculação</label><input class="input" id="evidencia-sujeito" name="evidencia_referencia" minlength="5" maxlength="500" required>
<p>Vincular inicia novo ciclo de conferências. A identificação declarada não é suficiente para autorizar acesso.</p>
@include('pedidos._confirmacao',['sufixo'=>'sujeito'])<button class="btn btn-outline">Vincular aluno conferido</button></form>
@endif
<a href="{{ route('pedidos.exportacoes',[]) }}">Abrir exportações autorizadas</a>
</section>
@endif
<section class="card card-pad pedidos-bloco"><h2>Conferências atuais</h2>
@forelse($p['verificacoes'] as $v)
<div class="pedidos-bloco"><h3>{{ $v['tipo']==='identidade'?'Identidade e representação':'Autoridade institucional' }} · {{ $v['vigente']?'Vigente':'Vencida' }}</h3>
<p>{{ str_replace('_',' ',$v['metodo']) }} · válida até {{ \Illuminate\Support\Carbon::parse($v['valida_ate'],'UTC')->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (Brasília)</p>
<p class="pedidos-texto">{{ $v['evidencia_referencia'] }}</p></div>
@empty<p>Nenhuma conferência registrada neste ciclo.</p>@endforelse
</section>
@if(!in_array($p['situacao'],['concluida','cancelada']))
<div class="pedidos-grade">
<section class="card card-pad pedidos-bloco"><h2>Triagem</h2>
<form class="pedidos-form" method="POST" action="{{ route('pedidos.alterar',['id'=>$id,'acao'=>'triagem']) }}">
@include('pedidos._envio',['envio'=>$envios['triagem']])<input type="hidden" name="versao" value="{{ $p['versao'] }}">
<div class="form-group"><label for="situacao">Próximo estado</label><select class="input" id="situacao" name="situacao">
@if(in_array($p['situacao'],['recebida','aguardando_informacao']))<option value="em_verificacao">Iniciar verificação</option>@endif
@if(in_array($p['situacao'],['em_verificacao','em_analise']))<option value="aguardando_informacao">Solicitar informações ou renovar conferências</option>@endif
<option value="cancelada">Cancelar protocolo</option></select></div>
<div class="form-group"><label for="resultado-cancelar">Motivo do cancelamento (somente ao cancelar)</label><select class="input" id="resultado-cancelar" name="resultado"><option value="">Não se aplica</option><option value="cancelado_pelo_solicitante">Solicitado pelo titular</option><option value="duplicado">Pedido duplicado</option><option value="inviavel_verificar">Não foi possível verificar</option></select></div>
<p>Solicitar novas informações inicia outro ciclo de conferência e preserva o histórico anterior.</p>
@include('pedidos._confirmacao',['sufixo'=>'triagem'])<button class="btn btn-outline">Registrar triagem</button></form></section>
@if($p['situacao']==='em_verificacao')
<section class="card card-pad pedidos-bloco"><h2>Registrar conferência manual</h2><p>Registre apenas uma conferência já realizada no escopo deste pedido. O sistema não autentica documentos automaticamente.</p>
<form class="pedidos-form" method="POST" action="{{ route('pedidos.alterar',['id'=>$id,'acao'=>'verificacoes']) }}">
@include('pedidos._envio',['envio'=>$envios['verificacoes']])<input type="hidden" name="versao" value="{{ $p['versao'] }}">
<div class="form-group"><label for="conferencia-tipo">Conferência</label><select class="input" id="conferencia-tipo" name="tipo"><option value="identidade">Identidade e representação</option><option value="autoridade">Autoridade institucional</option></select></div>
<div class="form-group"><label for="metodo">Método utilizado</label><select class="input" id="metodo" name="metodo"><optgroup label="Identidade"><option value="conferencia_documental">Conferência documental</option><option value="canal_autenticado">Canal autenticado</option></optgroup><optgroup label="Autoridade"><option value="confirmacao_institucional">Confirmação institucional</option><option value="instrumento_representacao">Instrumento de representação</option></optgroup></select></div>
<div class="form-group"><label for="evidencia">Referência da evidência</label><input class="input" id="evidencia" name="evidencia_referencia" required minlength="5" maxlength="500"><p>Indique onde a conferência foi documentada, sem copiar documentos ou dados desnecessários.</p></div>
<div class="form-group"><label for="validade">Válida até (horário de Brasília)</label><input class="input" id="validade" name="valida_ate" type="datetime-local" required></div>
@include('pedidos._confirmacao',['sufixo'=>'verificacao'])<button class="btn btn-primary">Registrar conferência</button></form></section>
@endif
@if(in_array($p['situacao'],['em_verificacao','em_analise']))
@php($vigentes=collect($p['verificacoes'])->where('vigente',true)->pluck('tipo')->unique())
<section class="card card-pad pedidos-bloco"><h2>{{ $p['situacao']==='em_analise'?'Concluir atendimento':'Iniciar análise' }}</h2>
@if($vigentes->contains('identidade') && $vigentes->contains('autoridade'))
<form class="pedidos-form" method="POST" action="{{ route('pedidos.alterar',['id'=>$id,'acao'=>'decisao']) }}">
@include('pedidos._envio',['envio'=>$envios['decisao']])<input type="hidden" name="versao" value="{{ $p['versao'] }}"><input type="hidden" name="situacao" value="{{ $p['situacao']==='em_analise'?'concluida':'em_analise' }}">
@if($p['situacao']==='em_analise')
<div class="form-group"><label for="resultado">Resultado administrativo</label><select class="input" id="resultado" name="resultado"><option value="atendido">Atendido</option><option value="parcialmente_atendido">Parcialmente atendido</option><option value="indeferido">Indeferido</option></select></div>
<div class="form-group"><label for="resposta">Referência da resposta já produzida</label><input class="input" id="resposta" name="resposta_referencia" required minlength="5" maxlength="500"></div>
<p>Concluir registra o resultado informado. Não envia resposta nem executa exportação, correção ou eliminação.</p>
@endif
@include('pedidos._confirmacao',['sufixo'=>'decisao'])<button class="btn btn-primary">{{ $p['situacao']==='em_analise'?'Registrar conclusão':'Iniciar análise' }}</button></form>
@else<p>São necessárias conferências vigentes de identidade e autoridade. Renove o ciclo pela triagem se necessário.</p>@endif
</section>
@endif
</div>
@endif
@endsection
