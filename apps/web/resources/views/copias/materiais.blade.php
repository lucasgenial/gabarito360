@extends('layouts.app')
@section('title','Selecionar materiais')
@section('breadcrumb')<a href="{{ route('copias.index') }}">Cópias</a><span class="sep">/</span><span>Selecionar materiais</span>@endsection
@section('content')
@php($lista=$dados['lista'])
@php($ctx=\App\Support\ContextoWeb::id())
<x-pagina titulo="Selecionar materiais"><x-slot:sub>Escolha as provas que deseja conferir antes de preparar uma cópia.</x-slot:sub></x-pagina>
@if($erro)<x-aviso tipo="erro">{{ $erro }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro">Selecione de 1 a 20 provas válidas para conferir a prévia.</x-aviso>@endif
<form method="GET" action="{{ route('copias.materiais') }}" class="card card-pad" style="margin-top:20px;">

  <div class="field"><label for="origem">Espaço de origem</label><select class="select" name="espaco_id" id="origem" @disabled(!$dados['espacos'])>
    @forelse($dados['espacos'] as $e)<option value="{{ $e['id'] }}" @selected($dados['espaco_id']===$e['id'])>{{ $e['nome'] }}</option>
    @empty<option>Nenhum espaço disponível</option>@endforelse
  </select></div><button class="btn btn-secondary" type="submit">Ver materiais deste espaço</button>
</form>
<form method="POST" action="{{ route('copias.materiais.previa') }}" class="card card-pad" style="margin-top:20px;">
  @csrf<input type="hidden" name="espaco_id" value="{{ $dados['espaco_id'] }}">
  <p>A seleção usa apenas os itens desta página. Trocar a página ou o espaço inicia uma nova seleção.</p>
  <div style="overflow-x:auto;"><x-tabela :integrada="true" titulo="Selecionar provas" modo="selecao"><table class="tabela-padrao table"><caption style="text-align:left;">{{ $lista['total'] }} prova(s) disponíveis neste espaço</caption>
    <thead><tr><th scope="col">Selecionar</th><th scope="col">Prova</th><th scope="col">Disciplina</th><th scope="col">Série</th><th scope="col">Questões</th></tr></thead>
    <tbody>@forelse($lista['data'] as $p)<tr>
      <td class="tabela-selecionar" data-rotulo="Selecionar"><div class="tabela-valor"><input type="checkbox" id="prova-{{ $p['id'] }}" name="provas[]" value="{{ $p['id'] }}" aria-label="Selecionar {{ $p['titulo'] }}"></div></td>
      <td class="tabela-principal" data-rotulo="Prova"><div class="tabela-valor"><label for="prova-{{ $p['id'] }}">{{ $p['titulo'] }}</label></div></td><td data-rotulo="Disciplina"><div class="tabela-valor">{{ $p['disciplina'] }}</div></td><td data-rotulo="Série"><div class="tabela-valor">{{ $p['serie'] }}</div></td><td data-rotulo="Questões"><div class="tabela-valor">{{ $p['num_questoes'] }}</div></td>
    </tr>@empty<tr><td colspan="5">Nenhum material disponível neste espaço.</td></tr>@endforelse</tbody>
  </table></x-tabela></div>
  <button class="btn btn-primary" type="submit" @disabled(!$lista['data'])>Conferir prévia</button>
</form>
@if($lista['last_page']>1)<nav aria-label="Paginação dos materiais" style="margin-top:16px;">
  @if($lista['current_page']>1)<a class="btn btn-secondary" href="{{ route('copias.materiais',['espaco_id'=>$dados['espaco_id'],'page'=>$lista['current_page']-1]) }}">Anterior</a>@endif
  <span>Página {{ $lista['current_page'] }} de {{ $lista['last_page'] }}</span>
  @if($lista['current_page']<$lista['last_page'])<a class="btn btn-secondary" href="{{ route('copias.materiais',['espaco_id'=>$dados['espaco_id'],'page'=>$lista['current_page']+1]) }}">Próxima</a>@endif
</nav>@endif
@endsection
