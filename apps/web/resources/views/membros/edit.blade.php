@extends('layouts.app')

@section('tela', 'tela-membro')

@section('title', 'Editar membro')

@section('breadcrumb')
<span>Início</span><span class="sep">/</span>
<a href="{{ route('membros.index') }}">Equipe</a><span class="sep">/</span>
<span>Editar membro</span>
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
  <h1 class="page-title">Editar membro</h1>
  <p class="page-sub">Atualize as informações do membro da equipe.</p>
  <a href="{{ route('membros.permissoes', $membro['id']) }}" class="btn btn-secondary btn-sm" style="margin-top:8px;">Permissões deste membro</a>
</div>

@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

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
    {{-- Perfil e acesso --}}
    <section class="section-card">
      <div class="section-head">
        <h2>Perfil e acesso</h2>
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

    {{-- Dados pessoais --}}
    <section class="section-card">
      <div class="section-head">
        <h2>Dados pessoais</h2>
        <p>Atualize nome, e-mail e dados cadastrais do membro.</p>
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
          <div class="field">
            <label for="cpf-disp">CPF</label>
            <input class="input num" type="text" id="cpf-disp" value="{{ $membro['cpf'] ?? '' }}" disabled />
          </div>
          <div class="field">
            <label for="data_nascimento">Data de nascimento</label>
            <input class="input" type="date" id="data_nascimento" name="data_nascimento" value="{{ old('data_nascimento', $membro['data_nascimento'] ?? '') }}" />
          </div>
          <div class="field">
            <label for="telefone">Telefone</label>
            <input class="input" type="text" id="telefone" name="telefone" value="{{ old('telefone', $membro['telefone'] ?? '') }}" />
          </div>
        </div>
      </div>
    </section>

    {{-- Dados profissionais --}}
    <section class="section-card">
      <div class="section-head">
        <h2>Dados profissionais</h2>
        <p>Formação, vínculo institucional e datas relevantes.</p>
      </div>
      <div class="section-body">
        <div class="form-grid-2">
          <div class="field">
            <label for="data_ingresso">Data de início</label>
            <input class="input" type="date" id="data_ingresso" name="data_ingresso" value="{{ old('data_ingresso', $membro['data_ingresso'] ?? '') }}" />
          </div>
          <div class="field">
            <label for="escola_id">Escola vinculada</label>
            <select class="select" id="escola_id" name="escola_id">
              <option value="">— Nenhuma (nível rede/núcleo) —</option>
              @foreach($escolas as $e)
                <option value="{{ $e['id'] }}" {{ old('escola_id', $membro['escola_id'] ?? '') == $e['id'] ? 'selected' : '' }}>{{ $e['nome'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="field full-width">
            <label for="formacao_academica">Formação acadêmica</label>
            <input class="input" type="text" id="formacao_academica" name="formacao_academica" value="{{ old('formacao_academica', $membro['formacao_academica'] ?? '') }}" />
          </div>
          <div class="field">
            <label for="especializacao">Especialização</label>
            <input class="input" type="text" id="especializacao" name="especializacao" value="{{ old('especializacao', $membro['especializacao'] ?? '') }}" />
          </div>
          <div class="field">
            <label for="registro_profissional">Registro profissional</label>
            <input class="input" type="text" id="registro_profissional" name="registro_profissional" value="{{ old('registro_profissional', $membro['registro_profissional'] ?? '') }}" />
          </div>
          <div class="field full-width">
            <label for="observacoes">Observações</label>
            <textarea class="input" id="observacoes" name="observacoes" rows="3">{{ old('observacoes', $membro['observacoes'] ?? '') }}</textarea>
          </div>
        </div>
      </div>
    </section>

    {{-- Acesso ao sistema --}}
    <section class="section-card">
      <div class="section-head">
        <h2>Acesso ao Sistema</h2>
        <p>Redefina a senha ou exija troca no próximo login.</p>
      </div>
      <div class="section-body">
        <div class="form-grid-2">
          <div class="field">
            <label for="nova_senha">Nova senha</label>
            <input class="input" type="password" id="nova_senha" name="nova_senha" minlength="8" placeholder="Deixe em branco para manter a atual" />
            @error('nova_senha')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
          </div>
          <div class="field">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding-top:30px;">
              <input type="checkbox" name="forcar_troca_senha" value="1" {{ old('forcar_troca_senha', $membro['forcar_troca_senha'] ?? false) ? 'checked' : '' }} style="accent-color:var(--accent);" />
              Forçar troca de senha no próximo login
            </label>
          </div>
        </div>
      </div>
    </section>

    <div class="action-bar">
      <a href="{{ route('membros.index') }}" class="btn btn-ghost">← Voltar</a>
      <button type="submit" class="btn btn-primary">Salvar alterações</button>
    </div>
  </div>
</form>

<form method="POST" action="{{ route('membros.convite', $membro['id']) }}" style="margin-top:12px;">
  @csrf
  <button type="submit" class="btn btn-secondary">Reenviar convite por e-mail</button>
</form>

<form method="POST" action="{{ route('membros.toggle', $membro['id']) }}" style="margin-top:12px;">
  @csrf
  <button type="submit" class="btn btn-secondary">
    {{ ($membro['ativo'] ?? true) ? 'Desativar membro' : 'Ativar membro' }}
  </button>
</form>

<div style="height:48px"></div>
@endsection
