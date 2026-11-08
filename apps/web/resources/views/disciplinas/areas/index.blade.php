@extends('layouts.app')
@section('title','Áreas do conhecimento')
@section('breadcrumb')<a href="{{ route('disciplinas.index',$retorno) }}">Disciplinas</a><span class="sep">/</span><span>Áreas do conhecimento</span>@endsection
@section('content')
<x-pagina titulo="Áreas do conhecimento">
  <x-slot:sub>Organize as disciplinas da escola em áreas próprias. Uma disciplina pode participar de mais de uma área.</x-slot:sub>
  <a class="btn btn-ghost" href="{{ route('disciplinas.index',$retorno) }}">Voltar às disciplinas</a>
  @if($dados['gerir'])<a class="btn btn-primary" href="{{ route('areas.create',$retorno) }}">Adicionar área</a>@endif
</x-pagina>
@include('disciplinas._base')
<div class="dc1-stack">
<form class="card card-pad dc1-filtros" method="GET" action="{{ route('areas.index') }}">
  @include('disciplinas._retorno')
  <div class="field"><label for="area-busca">Buscar por nome ou sigla</label><input class="input" id="area-busca" type="search" name="busca" maxlength="100" value="{{ $f['busca']??'' }}"></div>
  <div class="field"><label for="area-situacao">Situação</label><select class="select" id="area-situacao" name="ativo"><option value="">Todas</option><option value="1" @selected(($f['ativo']??'')==='1')>Ativas</option><option value="0" @selected(($f['ativo']??'')==='0')>Inativas</option></select></div>
  <div class="dc1-acoes"><button class="btn btn-secondary" type="submit">Filtrar</button>@if(!empty($f['busca']) || isset($f['ativo']) && $f['ativo']!=='')<a class="btn btn-ghost" href="{{ route('areas.index',$retorno) }}">Limpar</a>@endif</div>
</form>
<section class="card">
<x-tabela :interna="true" :integrada="true" titulo="Áreas do conhecimento da escola"><table class="tabela-padrao"><caption class="sr-only">Áreas do conhecimento</caption><thead><tr><th scope="col">Área</th><th scope="col">Sigla</th><th scope="col">Situação</th><th class="tabela-coluna-acoes" scope="col">Ações</th></tr></thead><tbody>
@forelse($dados['areas'] as $a)
<tr><td class="tabela-principal" data-rotulo="Área"><div class="tabela-valor">{{ $a['nome'] }}</div></td><td data-rotulo="Sigla">{{ $a['sigla']?:'Não informada' }}</td><td data-rotulo="Situação"><span class="badge {{ $a['ativo']?'badge-success':'badge-info' }}">{{ $a['ativo']?'Ativa':'Inativa' }}</span></td><td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor"><a class="btn btn-secondary btn-sm" href="{{ route('areas.show',['id'=>$a['id']]+$retorno) }}">Consultar<span class="sr-only"> {{ $a['nome'] }}</span></a>@if($dados['gerir'])<a class="btn btn-ghost btn-sm" href="{{ route('areas.edit',['id'=>$a['id']]+$retorno) }}">Editar<span class="sr-only"> {{ $a['nome'] }}</span></a>@endif</div></td></tr>
@empty<tr><td colspan="4"><div class="dc1-vazio"><h2>{{ !empty($f['busca']) || isset($f['ativo']) && $f['ativo']!==''?'Nenhuma área encontrada neste filtro':'Nenhuma área cadastrada' }}</h2><p>{{ !empty($f['busca']) || isset($f['ativo']) && $f['ativo']!==''?'Revise o nome, a sigla ou a situação.':'As disciplinas podem ser cadastradas sem área e associadas depois.' }}</p></div></td></tr>@endforelse
</tbody></table></x-tabela>
<div class="card-pad">@include('disciplinas._paginas',['rota'=>'areas.index','parametros'=>$f+$retorno,'rotulo'=>'Páginas das áreas do conhecimento'])</div>
</section>
</div>
@endsection
