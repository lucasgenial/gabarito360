@extends('layouts.app')
@section('title','Receber cadastro')
@section('content')
<x-pagina titulo="Receber cadastro"><x-slot:sub>Indique seu espaço à pessoa responsável pela origem.</x-slot:sub></x-pagina>
@include('copias._recebimento-guia')
<x-aviso>O código informa o nome e o tipo do espaço. Ele não autoriza a entrada do cadastro. Compartilhe-o somente com quem preparará a solicitação.</x-aviso>
<form method="POST" action="{{ route('copias.cadastro.emitir') }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="_ctx" value="{{ \App\Support\ContextoWeb::id() }}">
  <h2>1. Gerar o código para quem envia</h2><p>Selecione o espaço que receberá a cópia. Gerar o código ainda não autoriza a entrada de dados.</p><div class="field"><label for="espaco_id">Espaço que receberá o cadastro</label><select class="select" name="espaco_id" id="espaco_id" required @disabled(!$dados['espacos'])>
    @foreach($dados['espacos'] as $e)<option value="{{ $e['id'] }}" @selected($dados['espaco_id']===$e['id'])>{{ $e['nome'] }}</option>@endforeach
  </select></div>
  <button class="btn btn-primary" type="submit" @disabled(!$dados['espacos'])>Gerar código de destino</button>
</form>
@if($recebimento)
<section class="card card-pad" style="margin-top:20px;">
  <h2>{{ $recebimento['destino']['nome'] }}</h2>
  <p>Código válido até {{ \Carbon\Carbon::parse($recebimento['valida_ate'],'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} (horário de Brasília), para uma solicitação.</p>
  <div class="field"><label for="codigo">Código para compartilhar</label><textarea class="input" id="codigo" readonly rows="3" spellcheck="false">{{ $recebimento['codigo'] }}</textarea></div>
  <p>Copie e compartilhe o código. Nenhuma mensagem é enviada automaticamente.</p>
</section>
@endif
<form method="POST" action="{{ route('copias.cadastro.abrir') }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="_ctx" value="{{ \App\Support\ContextoWeb::id() }}">
  <h2>2. Conferir uma solicitação</h2><p>Depois de autorizar a saída, a origem fornecerá a referência. Confira o cadastro antes de autorizar sua entrada.</p>
  <div class="field"><label for="lote_id">Referência da solicitação</label><input class="input" id="lote_id" name="lote_id" required maxlength="36" autocomplete="off" spellcheck="false"></div>
  <button class="btn btn-secondary" type="submit">Conferir cadastro recebido</button>
</form>
@endsection
