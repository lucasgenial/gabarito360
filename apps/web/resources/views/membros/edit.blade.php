@extends('layouts.app')

@section('title', 'Editar Membro')

@push('styles')
<style>
  .member-layout { display:grid; grid-template-columns:280px 1fr; gap:24px; margin-top:20px; align-items:start; }
  .identity-card { background:var(--surface); border:1px solid var(--border-soft); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); padding:24px 20px; position:sticky; top:88px; text-align:center; }
  .avatar-circle { width:120px; height:120px; margin:0 auto 14px; border-radius:50%; background:linear-gradient(135deg,var(--accent) 0%,#0c326f 100%); display:grid; place-items:center; font-size:38px; font-weight:800; color:#fff; }
  .identity-label { font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); font-weight:700; margin-bottom:4px; }
  .identity-value { font-size:14px; color:var(--fg); font-weight:600; word-break:break-word; }
  .identity-block + .identity-block { margin-top:12px; }
  .form-main { display:flex; flex-direction:column; gap:18px; }
  .section-card { background:var(--surface); border:1px solid var(--border-soft); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); }
  .section-head { padding:18px 22px 14px; border-bottom:1px solid var(--border-soft); }
  .section-head h2 { font-size:16px; font-weight:700; margin-bottom:4px; }
  .section-head p { font-size:13px; color:var(--muted); }
  .section-body { padding:20px 22px 22px; }
  .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:0 18px; }
  .full-width { grid-column:1/-1; }
  .action-bar { display:flex; justify-content:space-between; align-items:center; gap:12px; padding-top:8px; }
  @media (max-width:960px){ .member-layout{grid-template-columns:1fr;} .identity-card{position:static;} }
  @media (max-width:680px){ .form-grid-2{grid-template-columns:1fr;} .action-bar{flex-direction:column;} .action-bar .btn{width:100%;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('membros.index') }}" class="active">Equipe</a>
<a href="{{ route('provas.index') }}">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('breadcrumb')
<span>Início</span><span class="sep">/</span>
<a href="{{ route('membros.index') }}">Equipe</a><span class="sep">/</span>
<span>Editar Membro</span>
@endsection

@section('content')

@php
  $perfilLabels = [
    'admin_rede'  => 'Administrador da Rede',
    'dir_nucleo'  => 'Diretor de Núcleo',
    'dir_escolar' => 'Diretor Escolar',
    'coordenador' => 'Coordenador Pedagógico',
    'professor'   => 'Professor',
    'aluno'       => 'Aluno',
  ];
  $ini = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(array_filter(explode(' ', $membro['nome'] ?? '')), 0, 2)));
@endphp

<div style="margin-top:12px;">
  <div class="eyebrow">Administração</div>
  <h1 class="page-title">Editar Membro</h1>
  <p class="page-sub">Atualize as informações do membro da equipe.</p>
</div>

@if(session('erro'))
  <div style="background:var(--danger-light,#fde8e4);color:var(--danger,#e52207);border:1px solid var(--danger,#e52207);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('erro') }}
  </div>
@endif

<form method="POST" action="{{ route('membros.update', $membro['id']) }}" class="member-layout">
  @csrf
  @method('PUT')

  {{-- Identidade lateral --}}
  <aside class="identity-card">
    <div class="avatar-circle">{{ $ini }}</div>
    @if(!($membro['ativo'] ?? true))
      <span class="badge badge-danger" style="margin-bottom:10px;">Inativo</span>
    @else
      <span class="badge badge-success" style="margin-bottom:10px;">Ativo</span>
    @endif
    <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--border-soft);text-align:left;">
      <div class="identity-block">
        <div class="identity-label">Nome</div>
        <div class="identity-value">{{ $membro['nome'] }}</div>
      </div>
      <div class="identity-block">
        <div class="identity-label">Perfil atual</div>
        <div class="identity-value">{{ $perfilLabels[$membro['perfil']] ?? $membro['perfil'] }}</div>
      </div>
      <div class="identity-block">
        <div class="identity-label">E-mail</div>
        <div class="identity-value">{{ $membro['email'] }}</div>
      </div>
      @if($membro['ultimo_acesso'] ?? null)
        <div class="identity-block">
          <div class="identity-label">Último acesso</div>
          <div class="identity-value">{{ \Carbon\Carbon::parse($membro['ultimo_acesso'])->diffForHumans() }}</div>
        </div>
      @endif
    </div>
  </aside>

  <div class="form-main">
    {{-- Perfil e Acesso --}}
    <section class="section-card">
      <div class="section-head">
        <h2>Perfil e Acesso</h2>
        <p>Altere o perfil ou o status de acesso do membro.</p>
      </div>
      <div class="section-body">
        <div class="form-grid-2">
          <div class="field">
            <label for="perfil">Perfil</label>
            <select class="select" id="perfil" name="perfil" required>
              @foreach($perfis as $key => $cfg)
                <option value="{{ $key }}" {{ ($membro['perfil'] ?? '') === $key ? 'selected' : '' }}>{{ $cfg['label'] }}</option>
              @endforeach
            </select>
            @error('perfil')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
          </div>
          <div class="field">
            <label>Status</label>
            <div style="display:flex;gap:20px;padding-top:12px;">
              <label style="display:flex;align-items:center;gap:6px;font-size:14px;cursor:pointer;">
                <input type="radio" name="ativo" value="1" {{ ($membro['ativo'] ?? true) ? 'checked' : '' }} style="accent-color:var(--accent);" />
                Ativo
              </label>
              <label style="display:flex;align-items:center;gap:6px;font-size:14px;cursor:pointer;">
                <input type="radio" name="ativo" value="0" {{ !($membro['ativo'] ?? true) ? 'checked' : '' }} style="accent-color:var(--accent);" />
                Inativo
              </label>
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- Dados Pessoais --}}
    <section class="section-card">
      <div class="section-head">
        <h2>Dados Pessoais</h2>
        <p>Atualize nome e e-mail do membro.</p>
      </div>
      <div class="section-body">
        <div class="form-grid-2">
          <div class="field full-width">
            <label for="nome">Nome completo</label>
            <input class="input" type="text" id="nome" name="nome" value="{{ old('nome', $membro['nome'] ?? '') }}" required />
            @error('nome')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
          </div>
          <div class="field full-width">
            <label for="email">E-mail institucional</label>
            <div class="input-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
              <input class="input" type="email" id="email" name="email" value="{{ old('email', $membro['email'] ?? '') }}" required />
            </div>
            @error('email')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>
    </section>

    <div class="action-bar">
      <a href="{{ route('membros.index') }}" class="btn btn-ghost">← Voltar</a>
      <div style="display:flex;gap:10px;">
        <form method="POST" action="{{ route('membros.toggle', $membro['id']) }}" style="display:inline;">
          @csrf
          <button type="submit" class="btn btn-secondary">
            {{ ($membro['ativo'] ?? true) ? 'Desativar membro' : 'Ativar membro' }}
          </button>
        </form>
        <button type="submit" class="btn btn-primary">Salvar alterações</button>
      </div>
    </div>
  </div>
</form>

<div style="height:48px"></div>
@endsection
