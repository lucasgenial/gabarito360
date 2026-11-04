@extends('layouts.app')
@section('title','Área do conhecimento')
@section('breadcrumb')<a href="{{ route('disciplinas.index',$retorno) }}">Disciplinas</a><span class="sep">/</span><a href="{{ route('areas.index',$retorno) }}">Áreas do conhecimento</a><span class="sep">/</span><span>Consultar</span>@endsection
@section('content')
@php($a=$dados['area'])
<x-pagina :titulo="$a['nome']"><x-slot:sub>Sigla: {{ $a['sigla']?:'Não informada' }} · <span class="badge {{ $a['ativo']?'badge-success':'badge-info' }}">{{ $a['ativo']?'Ativa':'Inativa' }}</span></x-slot:sub><a class="btn btn-ghost" href="{{ route('areas.index',$retorno) }}">Voltar às áreas</a>@if($dados['gerir'])<a class="btn btn-primary" href="{{ route('areas.edit',['id'=>$a['id']]+$retorno) }}">Editar área</a>@endif</x-pagina>
@include('disciplinas._base')
<div class="dc1-stack">
<section class="card"><x-tabela titulo="Disciplinas desta área" :completa="true"><table class="table tabela-padrao"><caption class="sr-only">Disciplinas desta área disponíveis neste perfil</caption><thead><tr><th scope="col" data-ordenar="texto">Disciplina</th><th scope="col" data-ordenar="texto">Sigla</th><th scope="col" data-ordenar="texto">Situação</th><th scope="col">Ações</th></tr></thead><tbody>
@forelse($a['disciplinas']??[] as $d)<tr><td class="tabela-principal" data-rotulo="Disciplina"><div class="tabela-valor">{{ $d['nome'] }}</div></td><td data-rotulo="Sigla">{{ $d['sigla']?:'Não informada' }}</td><td data-rotulo="Situação">{{ $d['ativo']?'Ativa':'Inativa' }}</td><td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor"><a class="btn btn-secondary btn-sm" href="{{ route('disciplinas.show',['id'=>$d['id']]+$retorno) }}">Consultar<span class="sr-only"> {{ $d['nome'] }}</span></a></div></td></tr>@empty<tr><td colspan="4"><div class="dc1-vazio"><h2>Nenhuma disciplina disponível nesta área</h2><p>{{ $dados['gerir']?'Edite a área para associar disciplinas.':'Consulte a coordenação sobre as disciplinas disponíveis no seu vínculo.' }}</p></div></td></tr>@endforelse
</tbody></table></x-tabela></section>
</div>
@endsection
