@extends('layouts.app')
@section('title', 'Secretaria escolar')
@section('breadcrumb')<span>Início</span><span class="sep">/</span><span>Secretaria escolar</span>@endsection
@section('content')
<x-pagina titulo="Secretaria escolar">
  <x-slot:sub>Consulte as turmas e mantenha os cadastros de alunos da escola selecionada.</x-slot:sub>
</x-pagina>
<section class="card card-pad">
  @if(\App\Support\Acesso::pode('turma.ver'))<a class="btn btn-primary" href="{{ route('turmas.index') }}">Ver turmas</a>@endif
  @if(\App\Support\Acesso::pode('aluno.ver'))<a class="btn btn-secondary" href="{{ route('alunos.index') }}">Ver alunos</a>@endif
</section>
@endsection
