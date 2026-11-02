@extends('layouts.app')
@section('title', 'Área interna')
@section('breadcrumb')
<span>Início</span><span class="sep">/</span><span>Área interna</span>
@endsection
@section('content')
<x-pagina titulo="Área interna do Gabarito360">
  <x-slot:sub>Olá, {{ session('auth_nome') }}. Você está atuando como {{ \App\Support\Acesso::rotulo() }} nesta aba.</x-slot:sub>
</x-pagina>
<section class="card card-pad">
  @if(\App\Support\Acesso::pode('cliente.ver'))<p><a class="btn btn-primary" href="{{ route('clientes.index') }}">Gerenciar clientes</a></p>@endif
  @if(\App\Support\Acesso::pode('acesso_interno.gerir'))
  <p>Gerencie as atribuições de Suporte e Financeiro da equipe.</p>
  <a class="btn btn-primary" href="{{ route('internos.index') }}">Gerenciar acessos internos</a>
  @else
  <p>Consulte e atualize os dados comerciais dos clientes.</p>
  @endif
  <a class="btn btn-outline" href="{{ route('vinculos.index', ['trocar' => 1]) }}" data-sem-contexto>Trocar acesso</a>
</section>
@endsection
