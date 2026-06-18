@extends('layouts.app')

@section('title', 'Meu Perfil')

@push('styles')
<style>
  .profile-layout { display:grid;grid-template-columns:280px 1fr;gap:24px;margin-top:24px;align-items:start; }
  .profile-card { background:var(--surface);border:1px solid var(--border-soft);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);padding:28px 24px;text-align:center; }
  .avatar-lg { width:80px;height:80px;border-radius:50%;background:var(--accent-light);color:var(--accent-dark,#0c326f);display:grid;place-items:center;font-weight:800;font-size:28px;font-family:var(--font-mono);margin:0 auto 16px;border:3px solid var(--accent); }
  .profile-name { font-size:20px;font-weight:700;color:var(--fg); }
  .profile-role { font-size:13px;color:var(--muted);margin-top:4px; }
  .profile-institution { margin-top:16px;padding-top:16px;border-top:1px solid var(--border-soft);font-size:13px;color:var(--muted);display:flex;flex-direction:column;gap:6px;text-align:left; }
  .profile-institution span { display:flex;gap:8px;align-items:center; }
  .profile-institution strong { color:var(--fg-2);font-size:13px; }
  .profile-main { display:flex;flex-direction:column;gap:24px; }
  .section-card { background:var(--surface);border:1px solid var(--border-soft);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden; }
  .section-head { padding:20px 24px 16px;border-bottom:1px solid var(--border-soft);display:flex;align-items:center;justify-content:space-between; }
  .section-head h2 { font-size:16px;font-weight:700; }
  .section-head p { font-size:13px;color:var(--muted);margin-top:2px; }
  .section-body { padding:24px; }
  .form-grid-2 { display:grid;grid-template-columns:1fr 1fr;gap:0 20px; }
  @media (max-width:900px){ .profile-layout{grid-template-columns:1fr;} .form-grid-2{grid-template-columns:1fr;} }
</style>
@endpush

@section('nav')
<a href="{{ route('painel') }}">Painel</a>
<a href="{{ route('provas.index') }}">Provas</a>
<a href="{{ route('turmas.index') }}">Turmas</a>
<a href="{{ route('escolas.index') }}">Escolas</a>
@endsection

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<span>Meu Perfil</span>
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
  $iniciais = strtoupper(implode('', array_map(fn($p) => $p[0], array_slice(explode(' ', $usuario['nome']), 0, 2))));
@endphp

<div class="row-between" style="margin-top:12px;margin-bottom:0;">
  <div>
    <h1 class="page-title">Meu Perfil</h1>
    <p class="page-sub">Gerencie seus dados pessoais e sua senha de acesso</p>
  </div>
</div>

@if(session('sucesso'))
  <div style="background:var(--success-light,#e3f5e1);color:var(--success,#168821);border:1px solid var(--success,#168821);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('sucesso') }}
  </div>
@endif
@if(session('erro'))
  <div style="background:var(--danger-light,#fde8e4);color:var(--danger,#e52207);border:1px solid var(--danger,#e52207);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('erro') }}
  </div>
@endif

<div class="profile-layout">
  <div class="profile-card">
    <div class="avatar-lg">{{ $iniciais }}</div>
    <div class="profile-name">{{ $usuario['nome'] }}</div>
    <div class="profile-role">{{ $perfilLabels[$usuario['perfil']] ?? $usuario['perfil'] }}</div>
    <div class="profile-institution">
      @if($usuario['escola_nome'])
        <span>🏫 <strong>{{ $usuario['escola_nome'] }}</strong></span>
      @endif
      <span>📧 <strong>{{ $usuario['email'] }}</strong></span>
      <span>📅 <strong>Desde {{ \Carbon\Carbon::parse($usuario['created_at'])->format('m/Y') }}</strong></span>
    </div>
  </div>

  <div class="profile-main">
    <div class="section-card">
      <div class="section-head">
        <div>
          <h2>Dados Pessoais</h2>
          <p>Informações exibidas em provas, relatórios e no cabeçalho do sistema</p>
        </div>
      </div>
      <div class="section-body">
        <form method="POST" action="{{ route('perfil.update') }}">
          @csrf
          @method('PUT')
          <div class="form-grid-2">
            <div class="field">
              <label for="nome">Nome completo *</label>
              <input id="nome" name="nome" type="text" class="input" required value="{{ $usuario['nome'] }}" />
            </div>
            <div class="field">
              <label for="cpf">CPF</label>
              <input id="cpf" type="text" class="input" value="{{ substr($usuario['cpf'],0,3) }}.{{ substr($usuario['cpf'],3,3) }}.{{ substr($usuario['cpf'],6,3) }}-{{ substr($usuario['cpf'],9,2) }}" disabled />
            </div>
            <div class="field">
              <label for="email">E-mail institucional</label>
              <input id="email" type="email" class="input" value="{{ $usuario['email'] }}" disabled />
            </div>
            <div class="field">
              <label for="perfil">Perfil / cargo</label>
              <input id="perfil" type="text" class="input" value="{{ $perfilLabels[$usuario['perfil']] ?? $usuario['perfil'] }}" disabled />
            </div>
          </div>
          <div style="margin-top:16px;">
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
          </div>
        </form>
      </div>
    </div>

    <div class="section-card">
      <div class="section-head">
        <div>
          <h2>Alterar Senha</h2>
          <p>Use uma senha forte com letras, números e símbolos</p>
        </div>
      </div>
      <div class="section-body">
        <form method="POST" action="{{ route('perfil.senha') }}" style="max-width:440px;">
          @csrf
          @method('PUT')
          <div class="field">
            <label for="senha-atual">Senha atual *</label>
            <input id="senha-atual" name="senha_atual" type="password" class="input" required placeholder="Digite sua senha atual" />
          </div>
          <div class="field">
            <label for="nova-senha">Nova senha *</label>
            <input id="nova-senha" name="password" type="password" class="input" required minlength="8" placeholder="Mínimo 8 caracteres" />
          </div>
          <div class="field">
            <label for="confirmar-senha">Confirmar nova senha *</label>
            <input id="confirmar-senha" name="password_confirmation" type="password" class="input" required minlength="8" placeholder="Repita a nova senha" />
          </div>
          <button type="submit" class="btn btn-primary">Atualizar senha</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div style="height:48px;"></div>
@endsection
