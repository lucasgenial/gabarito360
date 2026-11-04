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
        'vice_dir_escolar' => 'Vice-diretor escolar',
        'auxiliar_secretaria' => 'Auxiliar de secretaria',
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
    <x-avatar-usuario :nome="$usuario['nome']" :avatar-id="$usuario['avatar_id']??null" :tem-foto="$usuario['tem_foto']??false" :versao="$usuario['imagem_versao']??1" :proprio="true" tamanho="xl" />
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
    <section class="section-card">
      <div class="section-head"><div><h2>Foto e avatar</h2><p>Escolha como você aparece no sistema. A imagem não altera seus vínculos ou permissões.</p></div></div>
      <div class="section-body">
        <form method="POST" action="{{ route('perfil.imagem') }}" enctype="multipart/form-data">
          @csrf
          <fieldset class="field"><legend>Imagem de perfil</legend>
            <label><input type="radio" name="tipo" value="avatar" @checked(old('tipo',($usuario['tem_foto']??false)?'foto':'avatar')==='avatar')> Usar avatar ou iniciais</label>
            <label><input type="radio" name="tipo" value="foto" @checked(old('tipo',($usuario['tem_foto']??false)?'foto':'avatar')==='foto')> Enviar foto</label>
          </fieldset>
          <div data-imagem-avatar><x-seletor-avatar titulo="Avatar do usuário" :nome="$usuario['nome']" :avatar-id="$usuario['avatar_id']??null" preview-id="usuario-avatar-preview" /></div>
          <div class="field" data-imagem-foto><label for="foto-perfil">Nova foto</label><input class="input" id="foto-perfil" type="file" name="foto" accept="image/jpeg,image/png,image/webp"><p class="field-help">JPG, PNG ou WebP, até 2 MB, 12 megapixels e 4096 pixels por lado. A foto será ajustada em formato quadrado. Escolher avatar ou iniciais remove a foto anterior.</p></div>
          @if($errors->any())<x-aviso tipo="erro">{{ $errors->first() }}</x-aviso>@endif
          <button class="btn btn-primary" type="submit">Salvar imagem de perfil</button>
        </form>
      </div>
    </section>

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

@push('scripts')
<script>
(function(){var f=document.querySelector('form[action="{{ route('perfil.imagem') }}"]');if(!f)return;
 var foto=f.querySelector('[name="foto"]'),a=f.querySelector('[data-imagem-avatar]'),p=f.querySelector('[data-imagem-foto]');
 function modo(){var imagem=f.querySelector('[name="tipo"]:checked').value==='foto';a.hidden=imagem;p.hidden=!imagem;foto.disabled=!imagem;foto.required=imagem;f.querySelectorAll('[name="avatar_id"]').forEach(function(i){i.disabled=imagem;});}
 f.querySelectorAll('[name="tipo"]').forEach(function(i){i.addEventListener('change',modo);});modo();
})();
</script>
@endpush
