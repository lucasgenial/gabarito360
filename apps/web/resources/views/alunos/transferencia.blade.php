@extends('layouts.app')
@section('title','Transferir aluno entre turmas')
@section('breadcrumb')<a href="{{ route('turmas.show',['id'=>$aluno['turma_id'],'secao'=>'alunos']) }}">{{ $aluno['turma_nome'] ?? 'Turma' }} · Alunos</a><span class="sep">/</span><span>Transferir aluno</span>@endsection
@section('content')
<x-pagina titulo="Transferir aluno entre turmas"><x-slot:sub>{{ $aluno['nome'] }} · Origem: {{ $aluno['turma_nome'] }}</x-slot:sub></x-pagina>
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-aviso>@endif
@include('alunos._transferencia-form')
@push('scripts')<script src="@ativo('js/turma-transferencia.js')" defer></script>@endpush
@endsection

@push('scripts')<script src="@ativo('js/formulario-erros.js')" defer></script>@endpush

@push('styles')<link rel="stylesheet" href="@ativo('css/formulario-escolar.css')">@endpush

@push('styles')<link rel="stylesheet" href="@ativo('css/turma-detalhe.css')">@endpush
