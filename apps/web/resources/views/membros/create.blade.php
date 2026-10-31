@extends('layouts.app')

@section('tela', 'tela-membro')

@section('title', 'Cadastrar membro')

@section('breadcrumb')
<span>Início</span><span class="sep">/</span>
<a href="{{ route('membros.index') }}">Equipe</a><span class="sep">/</span>
<span>Novo Membro</span>
@endsection

@section('content')

<div style="margin-top:12px;">
  <h1 class="page-title">Cadastrar membro</h1>
  <p class="page-sub">Preencha os dados para adicionar um novo integrante à equipe.</p>
  {{-- Passos do cadastro (o 4º abre depois de salvar) --}}
  <ol style="display:flex;gap:6px 14px;flex-wrap:wrap;list-style:none;padding:0;margin:12px 0 0;font-size:13px;">
    <li><b style="color:var(--accent);">1</b> Perfil e acesso</li>
    <li><b style="color:var(--accent);">2</b> Dados pessoais</li>
    <li><b style="color:var(--accent);">3</b> Dados profissionais</li>
    <li style="color:var(--muted);"><b>4</b> Permissões (depois de salvar)</li>
  </ol>
</div>

@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif

<form method="POST" action="{{ route('membros.store') }}" class="member-layout" id="member-form">
  @csrf

  {{-- Preview lateral --}}
  <aside class="identity-card">
    <div class="avatar-preview" id="avatar-preview">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="54" height="54"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
    <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--border-soft);text-align:left;">
      <div class="identity-block">
        <div class="identity-label">Nome</div>
        <div class="identity-value" id="prev-nome">—</div>
      </div>
      <div class="identity-block">
        <div class="identity-label">Perfil</div>
        <div class="identity-value" id="prev-perfil">—</div>
      </div>
      <div class="identity-block">
        <div class="identity-label">E-mail</div>
        <div class="identity-value" id="prev-email">—</div>
      </div>
    </div>
  </aside>

  <div class="form-main">
    {{-- Perfil e acesso --}}
    <section class="section-card">
      <div class="section-head">
        <h2>Perfil e acesso</h2>
        <p>Defina o tipo de usuário e situação inicial.</p>
      </div>
      <div class="section-body">
        <div class="form-grid-2">
          <div class="field">
            <label for="perfil">Perfil <span style="color:var(--danger)">*</span></label>
            <select class="select" id="perfil" name="perfil" required>
              <option value="">— Selecione o perfil —</option>
              @foreach($perfis as $key => $cfg)
                <option value="{{ $key }}" {{ old('perfil') === $key ? 'selected' : '' }}>{{ $cfg['label'] }}</option>
              @endforeach
            </select>
            @error('perfil')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
          </div>
          <div class="field">
            <label>Status inicial</label>
            <div style="display:flex;gap:20px;padding-top:12px;">
              <label style="display:flex;align-items:center;gap:6px;font-size:14px;cursor:pointer;">
                <input type="radio" name="ativo" value="1" {{ old('ativo', '1') === '1' ? 'checked' : '' }} style="accent-color:var(--accent);" />
                Ativo
              </label>
              <label style="display:flex;align-items:center;gap:6px;font-size:14px;cursor:pointer;">
                <input type="radio" name="ativo" value="0" {{ old('ativo') === '0' ? 'checked' : '' }} style="accent-color:var(--accent);" />
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
        <p>Informações básicas de identificação e contato.</p>
      </div>
      <div class="section-body">
        <div class="form-grid-2">
          <div class="field full-width">
            <label for="nome">Nome completo <span style="color:var(--danger)">*</span></label>
            <input class="input" type="text" id="nome" name="nome" value="{{ old('nome') }}" placeholder="Ex.: Ana Paula Ferreira" required />
            @error('nome')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
          </div>
          <div class="field">
            <label for="cpf">CPF <span style="color:var(--danger)">*</span></label>
            <input class="input num" type="text" id="cpf" name="cpf" value="{{ old('cpf') }}" placeholder="000.000.000-00" maxlength="14" required />
            @error('cpf')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
          </div>
          <div class="field full-width">
            <label for="email">E-mail institucional <span style="color:var(--danger)">*</span></label>
            <div class="input-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
              <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nome@escola.com.br" required />
            </div>
            @error('email')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
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
            <label for="data_nascimento">Data de nascimento</label>
            <input class="input" type="date" id="data_nascimento" name="data_nascimento" value="{{ old('data_nascimento') }}" />
          </div>
          <div class="field">
            <label for="telefone">Telefone</label>
            <input class="input" type="text" id="telefone" name="telefone" value="{{ old('telefone') }}" placeholder="(00) 00000-0000" />
          </div>
          <div class="field">
            <label for="data_ingresso">Data de início</label>
            <input class="input" type="date" id="data_ingresso" name="data_ingresso" value="{{ old('data_ingresso') }}" />
          </div>
          @if(!empty($nucleos))
          <div class="field" id="campo-nucleo" style="display:none;">
            <label for="nucleo_id">Núcleo <span style="color:var(--danger)">*</span></label>
            <select class="select" id="nucleo_id" name="nucleo_id">
              <option value="">— Selecione o núcleo —</option>
              @foreach($nucleos as $n)
                <option value="{{ $n['id'] }}" {{ old('nucleo_id') == $n['id'] ? 'selected' : '' }}>{{ $n['nome'] }}</option>
              @endforeach
            </select>
          </div>
          @endif
          @if(!empty($turmas))
          <div class="field full-width" id="campo-turmas" style="display:none;">
            <label>Turmas do professor</label>
            <div id="lista-turmas" style="display:flex;gap:8px 16px;flex-wrap:wrap;padding-top:6px;">
              @foreach($turmas as $t)
                <label data-escola="{{ $t['escola_id'] }}" style="display:flex;gap:6px;align-items:center;font-size:14px;cursor:pointer;">
                  <input type="checkbox" name="turma_ids[]" value="{{ $t['id'] }}" {{ in_array($t['id'], (array) old('turma_ids', [])) ? 'checked' : '' }} style="width:18px;height:18px;"> {{ $t['nome'] }}
                </label>
              @endforeach
            </div>
            <div style="font-size:12px;color:var(--muted);margin-top:4px;">O professor vê as provas e corrige os cartões dessas turmas.</div>
          </div>
          @endif
          <div class="field" id="campo-escola">
            <label for="escola_id">Escola vinculada <span style="color:var(--danger)">*</span></label>
            <select class="select" id="escola_id" name="escola_id">
              <option value="">— Selecione a escola —</option>
              @foreach($escolas as $e)
                <option value="{{ $e['id'] }}" {{ old('escola_id') == $e['id'] ? 'selected' : '' }}>{{ $e['nome'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="field full-width">
            <label for="formacao_academica">Formação acadêmica</label>
            <input class="input" type="text" id="formacao_academica" name="formacao_academica" value="{{ old('formacao_academica') }}" placeholder="Ex.: Licenciatura em Matemática" />
          </div>
          <div class="field">
            <label for="especializacao">Especialização</label>
            <input class="input" type="text" id="especializacao" name="especializacao" value="{{ old('especializacao') }}" />
          </div>
          <div class="field">
            <label for="registro_profissional">Registro profissional</label>
            <input class="input" type="text" id="registro_profissional" name="registro_profissional" value="{{ old('registro_profissional') }}" />
          </div>
          <div class="field full-width">
            <label for="observacoes">Observações</label>
            <textarea class="input" id="observacoes" name="observacoes" rows="3">{{ old('observacoes') }}</textarea>
          </div>
        </div>
      </div>
    </section>

    <div class="action-bar">
      <a href="{{ route('membros.index') }}" class="btn btn-secondary">Cancelar</a>
      <button type="submit" class="btn btn-primary">Salvar Membro</button>
    </div>
  </div>
</form>

<div style="height:48px"></div>
@endsection

@push('scripts')
<script>
(function() {
  var nomeEl    = document.getElementById('nome');
  var perfilEl  = document.getElementById('perfil');
  var emailEl   = document.getElementById('email');
  var cpfEl     = document.getElementById('cpf');
  var avatarEl  = document.getElementById('avatar-preview');
  var prevNome  = document.getElementById('prev-nome');
  var prevPerf  = document.getElementById('prev-perfil');
  var prevEmail = document.getElementById('prev-email');

  var perfilLabels = {
    admin_rede:  'Administrador da Rede',
    dir_nucleo:  'Diretor de Núcleo',
    dir_escolar: 'Diretor Escolar',
    coordenador: 'Coordenador Pedagógico',
    professor:   'Professor',
    aluno:       'Aluno',
  };

  function initials(name) {
    var parts = (name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '';
    if (parts.length === 1) return parts[0].slice(0,2).toUpperCase();
    return (parts[0][0] + parts[parts.length-1][0]).toUpperCase();
  }

  function update() {
    var nome  = nomeEl.value.trim();
    var pf    = perfilEl.value;
    var email = emailEl.value.trim();
    var ini   = initials(nome);

    prevNome.textContent  = nome  || '—';
    prevPerf.textContent  = perfilLabels[pf] || '—';
    prevEmail.textContent = email || '—';

    if (ini) {
      avatarEl.classList.add('has-initials');
      avatarEl.style.background = 'var(--accent)';
      avatarEl.innerHTML = ini;
    } else {
      avatarEl.classList.remove('has-initials');
      avatarEl.style.background = '';
      avatarEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="54" height="54"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
    }
  }

  nomeEl.addEventListener('input', update);
  perfilEl.addEventListener('change', update);
  // Diretor de núcleo é ligado a um núcleo; os demais perfis, a uma escola
  function campoEscopo() {
    var nucleo = perfilEl.value === 'dir_nucleo' || perfilEl.value === 'admin_rede';
    var cn = document.getElementById('campo-nucleo'), ce = document.getElementById('campo-escola');
    if (cn) cn.style.display = perfilEl.value === 'dir_nucleo' ? '' : 'none';
    if (ce) ce.style.display = nucleo ? 'none' : '';
    // Turmas: só para professor, e só as da escola escolhida
    var ct = document.getElementById('campo-turmas'), esc = document.getElementById('escola_id');
    if (ct) {
      ct.style.display = perfilEl.value === 'professor' ? '' : 'none';
      document.querySelectorAll('#lista-turmas label').forEach(function (l) {
        var mostra = !esc.value || l.dataset.escola === esc.value;
        l.style.display = mostra ? '' : 'none';
        if (!mostra) l.querySelector('input').checked = false;
      });
    }
  }
  var escSel = document.getElementById('escola_id');
  if (escSel) escSel.addEventListener('change', campoEscopo);
  perfilEl.addEventListener('change', campoEscopo);
  campoEscopo();
  emailEl.addEventListener('input', update);

  cpfEl.addEventListener('input', function() {
    var v = cpfEl.value.replace(/\D/g,'').slice(0,11);
    v = v.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2');
    cpfEl.value = v;
  });

  update();
}());
</script>
@endpush
