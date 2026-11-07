@extends('layouts.app')
@section('tela','tela-turma')
@section('title', $turma['nome'])
@section('breadcrumb')
<a href="{{ route('turmas.index') }}">Turmas</a><span class="sep">/</span><span>{{ $turma['nome'] }}</span>
@endsection
@section('content')
<x-pagina :titulo="$turma['nome']">
  <x-slot:imagem>@pode('turma.gerir')<button type="button" class="imagem-perfil-abrir" data-abrir-imagem-turma aria-label="Alterar imagem da turma"><x-avatar-turma :turma="$turma" tamanho="lg" /></button>@else<x-avatar-turma :turma="$turma" tamanho="lg" />@endpode</x-slot:imagem>
  <x-slot:sub>{{ $turma['escola_nome'] }} · {{ $turma['ano_letivo'] }} · {{ $turma['total_alunos'] }} aluno(s) ativo(s)</x-slot:sub>
  @if(\App\Support\Acesso::pode('aluno.ver'))<a class="btn btn-secondary" href="{{ route('alunos.index', ['turma_id' => $turma['id']]) }}"><x-icone nome="aluno" /> Gerenciar alunos</a>@endif
</x-pagina>
@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@php($secoes=['visao-geral'=>'Visão geral'] + (\App\Support\Acesso::pode('aluno.ver')?['alunos'=>'Alunos']:[]))
@include('turmas._abas')
<section id="visao-geral" data-turma-painel="visao-geral" class="tu1-painel" aria-label="Visão geral">
<section class="card card-pad">
  <h2>Dados da turma</h2>
  <p>Status: {{ $turma['ativo'] ? 'Em uso' : 'Arquivada' }}</p>
  @pode('turma.gerir')<a class="btn btn-secondary" href="{{ route('turmas.edit',$turma['id']) }}"><x-icone nome="editar" /> Editar turma</a>@endpode
  @if(\App\Support\Acesso::pode('turma.desativar'))
  <x-arquivamento tipo="turmas" :registro="$turma" />
  @endif
</section>
@include('turmas._modelos', ['paginaModelos'=>'turma'])
</section>
<section id="alunos" data-turma-painel="alunos" class="tu1-painel" aria-label="Alunos">
@include('turmas._alunos')
</section>
@pode('turma.gerir')@include('turmas._imagem')@endpode
@endsection
