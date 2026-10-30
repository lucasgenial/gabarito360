@extends('layouts.app')

{{--
  Arquivo: apps/web/resources/views/correcao/camera.blade.php
  Função: Modo só-câmera (aplicador): envia os cartões da prova do dia, sem ver notas,
          gabarito nem ambíguos (doc 03, †APLIC).
--}}

@section('title', 'Enviar cartões — ' . $prova['titulo'])

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Enviar cartões</span>
@endsection

@section('content')
<div style="margin-top:12px;">
  <div class="eyebrow">Aplicação</div>
  <h1 class="page-title">{{ $prova['disciplina'] }}</h1>
  <p class="page-sub">{{ $prova['titulo'] }} · {{ implode(' · ', array_column($prova['turmas'] ?? [], 'nome')) }}</p>
</div>

@include('correcao._camera')

<div style="height:48px"></div>
@endsection
