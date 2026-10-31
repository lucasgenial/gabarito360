@extends('layouts.app')

{{--
  Arquivo: apps/web/resources/views/correcao/index.blade.php
  Função: "<x-icone nome="camera" /> Corrigir" — escolha da prova a corrigir (publicada/em correção/corrigida),
          com atalhos para a câmera e para os cartões de cada turma (doc 19, MP-037).
--}}

@section('title', 'Corrigir cartões')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Corrigir</span>
@endsection

@section('content')
<div style="margin-top:12px;">
  <h1 class="page-title">Qual prova você vai corrigir?</h1>
  <p class="page-sub">Toque em <b><x-icone nome="camera" /> Corrigir</b> e fotografe os cartões. O aluno é identificado pelo QR impresso.</p>
</div>

<div class="card card-pad" style="margin-top:16px;">
  @include('dashboard._provas-ciclo', ['provas' => $provas])
</div>

@if(!empty($turmas))
  <h2 style="font-size:17px;margin-top:22px;">Acompanhar por turma</h2>
  <p class="page-sub" style="font-size:13px;">Quem já foi corrigido em cada prova e quem falta.</p>
  <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;">
    @foreach($turmas as $t)
      <a class="btn btn-secondary" href="{{ route('correcao.turma', $t['id']) }}"><x-icone nome="grafico" /> {{ $t['nome'] }}</a>
    @endforeach
  </div>
@endif

<p style="font-size:13px;color:var(--muted);margin-top:14px;">
  Não encontrou a prova? Ela precisa ter o gabarito publicado e estar vinculada a uma turma sua.
</p>
<div style="height:48px"></div>
@endsection
