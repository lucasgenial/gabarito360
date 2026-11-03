@extends('layouts.app')
@section('title', 'Clientes')
@section('breadcrumb')<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Clientes</span>@endsection
@section('content')
<x-pagina titulo="Clientes">
  <x-slot:sub>{{ $lista['total'] }} cadastro(s)</x-slot:sub>
  @if(\App\Support\Acesso::pode('cliente.gerir'))<a class="btn btn-primary" href="{{ route('clientes.create') }}">Novo cliente</a>@endif
</x-pagina>
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
<form method="GET" action="{{ route('clientes.index') }}" class="card card-pad" style="margin-top:20px;">
  <div class="form-row-2">
    <div class="field"><label for="busca">Nome do cliente</label><input class="input" id="busca" name="busca" maxlength="100" value="{{ $filtros['busca'] ?? '' }}"></div>
    <div class="field"><label for="tipo">Tipo</label><select class="select" name="tipo" id="tipo"><option value="">Todos</option>@foreach($tipos as $v=>$l)<option value="{{ $v }}" @selected(($filtros['tipo'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label for="modalidade">Modalidade</label><select class="select" name="modalidade" id="modalidade"><option value="">Todas</option>@foreach($modalidades as $v=>$l)<option value="{{ $v }}" @selected(($filtros['modalidade'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
  </div>
  <button class="btn btn-secondary" type="submit">Filtrar</button>
  <a class="btn btn-ghost" href="{{ route('clientes.index') }}">Limpar</a>
</form>
<div class="card card-pad" style="margin-top:20px;overflow-x:auto;">
<x-tabela titulo="Clientes" modo="lista"><table class="tabela-padrao table"><caption class="sr-only">Clientes</caption><thead><tr><th scope="col">Cliente</th><th scope="col">Tipo</th><th scope="col">Modalidade</th><th scope="col">Situação</th><th scope="col">Ações</th></tr></thead><tbody>
@forelse($lista['data'] as $c)
<tr><td class="tabela-principal" data-rotulo="Cliente"><div class="tabela-valor">{{ $c['nome'] }}</div></td><td data-rotulo="Tipo"><div class="tabela-valor">{{ $tipos[$c['tipo']] ?? $c['tipo'] }}</div></td><td data-rotulo="Modalidade"><div class="tabela-valor">{{ $modalidades[$c['modalidade']] ?? $c['modalidade'] }}</div></td><td data-rotulo="Situação"><div class="tabela-valor">{{ ucfirst($c['situacao']) }}</div></td><td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor"><a class="btn btn-secondary" href="{{ route('clientes.show',$c['id']) }}">Ver cadastro</a></div></td></tr>
@empty <tr><td colspan="5">Nenhum cliente encontrado.</td></tr> @endforelse
</tbody></table></x-tabela>
<nav aria-label="Paginação dos clientes" style="margin-top:16px;">
@if($lista['current_page'] > 1)<a class="btn btn-secondary" href="{{ route('clientes.index', array_merge($filtros,['page'=>$lista['current_page']-1])) }}">Anterior</a>@endif
<span>Página {{ $lista['current_page'] }} de {{ $lista['last_page'] }}</span>
@if($lista['current_page'] < $lista['last_page'])<a class="btn btn-secondary" href="{{ route('clientes.index', array_merge($filtros,['page'=>$lista['current_page']+1])) }}">Próxima</a>@endif
</nav>
</div>
@endsection
