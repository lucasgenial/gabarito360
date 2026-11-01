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
  <p>As ferramentas de gestão estarão disponíveis conforme forem liberadas para seu acesso.</p>
  <a class="btn btn-outline" href="{{ route('vinculos.index', ['trocar' => 1]) }}" data-sem-contexto>Trocar acesso</a>
</section>
@endsection
