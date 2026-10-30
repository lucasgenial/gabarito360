@extends('layouts.app')

{{--
  Arquivo: apps/web/resources/views/correcao/index.blade.php
  Função: "📷 Corrigir" — escolha da prova a corrigir (publicada/em correção/corrigida),
          com atalhos para a câmera e para os cartões de cada turma (doc 19, MP-037).
--}}

@section('title', 'Corrigir cartões')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span><span>Corrigir</span>
@endsection

@section('content')
<div style="margin-top:12px;">
  <div class="eyebrow">Correção pelo celular</div>
  <h1 class="page-title">Qual prova você vai corrigir?</h1>
  <p class="page-sub">Toque em <b>📷 Corrigir</b> e fotografe os cartões. O aluno é identificado pelo QR impresso.</p>
</div>

<div class="card card-pad" style="margin-top:16px;">
  @include('dashboard._provas-ciclo', ['provas' => $provas])
</div>

<p style="font-size:13px;color:var(--muted);margin-top:14px;">
  Não encontrou a prova? Ela precisa ter o gabarito publicado e estar vinculada a uma turma sua.
</p>
<div style="height:48px"></div>
@endsection
