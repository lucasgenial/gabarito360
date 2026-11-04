@extends('layouts.app')
@section('title','Área do conhecimento')
@section('breadcrumb')<a href="{{ route('disciplinas.index',$retorno) }}">Disciplinas</a><span class="sep">/</span><a href="{{ route('areas.index',$retorno) }}">Áreas do conhecimento</a><span class="sep">/</span><span>Consultar</span>@endsection
@section('content')
@php($a=$dados['area'])
<x-pagina :titulo="$a['nome']"><x-slot:sub>Área do conhecimento desta escola.</x-slot:sub><a class="btn btn-ghost" href="{{ route('areas.index',$retorno) }}">Voltar às áreas</a>@if($dados['gerir'])<a class="btn btn-primary" href="{{ route('areas.edit',['id'=>$a['id']]+$retorno) }}">Editar área</a>@endif</x-pagina>
@include('disciplinas._base')
<section class="card card-pad dc1-stack" aria-label="Dados da área"><dl class="dc1-meta"><div><dt>Sigla</dt><dd>{{ $a['sigla']?:'Não informada' }}</dd></div><div><dt>Situação</dt><dd>{{ $a['ativo']?'Ativa':'Inativa' }}</dd></div></dl><p class="dc1-texto">Associe esta área ao criar ou editar disciplinas. Uma disciplina pode participar de várias áreas; isso não altera o acesso a turmas ou provas.</p><a class="btn btn-secondary" href="{{ route('disciplinas.index',$retorno) }}">Consultar disciplinas</a></section>
@endsection
