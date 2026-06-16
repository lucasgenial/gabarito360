@extends('layouts.app')

@section('title', 'Painel Administrativo')

@section('nav')
<a href="{{ route('painel') }}" class="active">Painel</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('provas.index') }}">Provas</a>
@endsection

@section('context-badge')
<span class="badge badge-info badge-dot">Rede Municipal</span>
@endsection

@section('breadcrumb')
<span>Início</span><span class="sep">/</span><span>Painel</span>
@endsection

@section('content')
<div class="row-between" style="margin-top:12px;">
  <div>
    <div class="eyebrow">Visão da rede</div>
    <h1 class="page-title">Painel administrativo</h1>
    <p class="page-sub">{{ $nome }} · Administrador · Rede Municipal</p>
  </div>
</div>
<div style="margin-top:32px;padding:32px;background:var(--surface-2);border-radius:var(--radius-lg);text-align:center;color:var(--muted);">
  <p style="font-size:18px;font-weight:600;">Dashboard em construção</p>
  <p style="margin-top:8px;font-size:14px;">Os indicadores serão carregados nos próximos MPs.</p>
</div>
@endsection
