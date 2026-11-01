@extends('layouts.app')

@section('tela', 'tela-perfil')

@section('title', 'Meu perfil')

@section('breadcrumb')
<a href="{{ route('painel') }}">Início</a><span class="sep">/</span>
<span>Meu perfil</span>
@endsection

@section('content')

@php
  $perfilLabels = [
        'secretario_escolar' => 'Secretário Escolar',
    'desenvolvedor' => 'Desenvolvedor',
    'suporte' => 'Suporte',
    'financeiro' => 'Financeiro',
    'admin_rede'  => 'Administrador da Rede',
    'dir_nucleo'  => 'Diretor de Núcleo',
    'dir_escolar' => 'Diretor Escolar',
    'coordenador' => 'Coordenador Pedagógico',
    'professor'   => 'Professor',
    'aluno'       => 'Aluno',
  ];
  $iniciais = strtoupper(implode('', array_map(fn($p) => $p[0], array_slice(explode(' ', $usuario['nome']), 0, 2))));
@endphp

<x-pagina titulo="Meu perfil">
  <x-slot:sub>Gerencie seus dados pessoais e sua senha de acesso</x-slot:sub>
</x-pagina>

@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

<div class="profile-layout">
  <div class="profile-card">
    <div class="avatar-lg">{{ $iniciais }}</div>
    <div class="profile-name">{{ $usuario['nome'] }}</div>
    <div class="profile-role">{{ $perfilLabels[$usuario['perfil']] ?? $usuario['perfil'] }}</div>
    <div class="profile-institution">
      @if($usuario['escola_nome'])
        <span><x-icone nome="escola" /> <strong>{{ $usuario['escola_nome'] }}</strong></span>
      @endif
      <span><strong>{{ $usuario['email'] }}</strong></span>
      <span><strong>Desde {{ \Carbon\Carbon::parse($usuario['created_at'])->format('m/Y') }}</strong></span>
    </div>
  </div>

  <div class="profile-main">
    <div class="section-card">
      <div class="section-head">
        <div>
          <h2>Dados pessoais</h2>
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
          <h2>Alterar senha</h2>
          <p>Use uma senha forte com letras, números e símbolos</p>
        </div>
      </div>
      <div class="section-body">
        <form method="POST" action="{{ route('perfil.senha') }}">
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

        <form method="POST" action="{{ route('perfil.email') }}" style="margin-top:28px;">
          @csrf
          <h2 style="font-size:18px;margin-bottom:4px;">Trocar e-mail</h2>
          <p style="margin-bottom:14px;color:var(--muted);font-size:14px;">O e-mail atual segue valendo até você confirmar o novo pelo link enviado.</p>
          <div class="field">
            <label for="novo-email">Novo e-mail</label>
            <input id="novo-email" name="novo_email" type="email" class="input" required autocomplete="email" />
          </div>
          <div class="field">
            <label for="senha-email">Senha atual</label>
            <input id="senha-email" name="senha_atual" type="password" class="input" required autocomplete="current-password" />
          </div>
          <button type="submit" class="btn btn-secondary">Enviar confirmação</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div style="height:48px;"></div>
@endsection
