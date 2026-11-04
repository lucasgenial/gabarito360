@extends('layouts.app')
@section('title','Disciplinas da escola')
@section('breadcrumb')<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Disciplinas</span>@endsection
@section('content')
<x-pagina :titulo="\App\Support\Acesso::perfil()==='professor'?'Disciplinas indicadas para mim':'Disciplinas da escola'">
  <x-slot:sub>{{ \App\Support\Acesso::perfil()==='professor'?'Consulte suas indicações. As atribuições por turma aparecem em Turmas.':'Organize o catálogo da escola e os professores de cada disciplina.' }}</x-slot:sub>
  @include('disciplinas._voltar')
  <a class="btn btn-secondary" href="{{ route('areas.index',$retorno) }}">Áreas do conhecimento</a>
  @if($dados['gerir'])<a class="btn btn-primary" href="{{ route('disciplinas.create',$retorno) }}">Adicionar disciplina</a>@endif
</x-pagina>
@include('disciplinas._base')
<div class="dc1-stack">
<form class="card card-pad dc1-filtros" method="GET" action="{{ route('disciplinas.index') }}">
  @include('disciplinas._retorno')
  <div class="field"><label for="busca-disciplina">Buscar disciplina</label><input class="input" id="busca-disciplina" name="busca" maxlength="100" type="search" value="{{ $f['busca']??'' }}" placeholder="Nome ou sigla"></div>
  <div class="field"><label for="ativo-disciplina">Situação</label><select class="select" id="ativo-disciplina" name="ativo"><option value="">Todas</option><option value="1" @selected(($f['ativo']??'')==='1')>Ativas</option><option value="0" @selected(($f['ativo']??'')==='0')>Inativas</option></select></div>
  <div class="dc1-acoes"><button class="btn btn-primary" type="submit">Buscar</button>@if(!empty($f['busca'])||isset($f['ativo']))<a class="btn btn-ghost" href="{{ route('disciplinas.index',$retorno) }}">Limpar</a>@endif</div>
</form>
<div class="card"><x-tabela titulo="Disciplinas da escola"><table class="tabela-padrao"><caption class="sr-only">Disciplinas da escola</caption><thead><tr><th scope="col">Disciplina</th><th scope="col">Sigla</th><th scope="col">Origem</th><th scope="col">Situação</th><th scope="col">Ações</th></tr></thead><tbody>
@forelse($dados['disciplinas'] as $d)
<tr><td class="tabela-principal" data-rotulo="Disciplina"><div class="tabela-valor">{{ $d['nome'] }}<span class="dc1-periodo">Áreas: @forelse($d['areas']??[] as $area){{ $area['nome'] }}{{ $area['ativo']?'':' (inativa)' }}@unless($loop->last); @endunless @empty Não associada @endforelse</span></div></td><td data-rotulo="Sigla">{{ $d['sigla']?:'—' }}</td><td data-rotulo="Origem">{{ $d['referencia']?'Referência nacional':'Própria da escola' }}</td><td data-rotulo="Situação"><span class="badge {{ $d['ativo']?'badge-success':'badge-info' }}">{{ $d['ativo']?'Ativa':'Inativa' }}</span></td><td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor"><div class="tabela-acoes"><a class="btn btn-secondary btn-sm" href="{{ route('disciplinas.show',['id'=>$d['id']]+$retorno) }}">Consultar<span class="sr-only"> {{ $d['nome'] }}</span></a>@if($dados['gerir'])<a class="btn btn-ghost btn-sm" href="{{ route('disciplinas.edit',['id'=>$d['id']]+$retorno) }}">Editar<span class="sr-only"> {{ $d['nome'] }}</span></a>@endif</div></div></td></tr>
@empty<tr><td colspan="5"><div class="dc1-vazio"><h2>{{ !empty($f['busca'])||isset($f['ativo'])?'Nenhuma disciplina encontrada':'Nenhuma disciplina por aqui' }}</h2><p>{{ $dados['gerir']?'Adicione uma referência nacional ou crie uma disciplina própria da escola.':'As indicações de disciplinas são organizadas pela coordenação.' }}</p></div></td></tr>@endforelse
</tbody></table></x-tabela></div>
@include('disciplinas._paginas',['rota'=>'disciplinas.index','parametros'=>$f+$retorno])
</div>
@endsection
