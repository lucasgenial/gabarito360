@extends('layouts.app')

@section('title', 'Cadastrar Novo Membro')

@push('styles')
<style>
  .member-layout { display:grid; grid-template-columns:280px 1fr; gap:24px; margin-top:20px; align-items:start; }
  .identity-card { background:var(--surface); border:1px solid var(--border-soft); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); padding:24px 20px; position:sticky; top:88px; text-align:center; }
  .avatar-preview { width:120px; height:120px; margin:0 auto 14px; border-radius:50%; background:linear-gradient(180deg,#f6f8fb 0%,#edf2f9 100%); border:3px solid #fff; box-shadow:0 8px 24px rgba(28,39,51,.12); display:grid; place-items:center; color:var(--muted); font-size:38px; font-weight:800; }
  .avatar-preview.has-initials { color:#fff; }
  .identity-label { font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); font-weight:700; margin-bottom:4px; }
  .identity-value { font-size:14px; color:var(--fg); font-weight:600; word-break:break-word; min-height:20px; }
  .identity-block + .identity-block { margin-top:12px; }
  .form-main { display:flex; flex-direction:column; gap:18px; }
  .section-card { background:var(--surface); border:1px solid var(--border-soft); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); }
  .section-head { padding:18px 22px 14px; border-bottom:1px solid var(--border-soft); }
  .section-head h2 { font-size:16px; font-weight:700; margin-bottom:4px; }
  .section-head p { font-size:13px; color:var(--muted); }
  .section-body { padding:20px 22px 22px; }
  .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:0 18px; }
  .full-width { grid-column:1/-1; }
  .action-bar { display:flex; justify-content:flex-end; gap:12px; padding-top:8px; }
  @media (max-width:960px){ .member-layout{grid-template-columns:1fr;} .identity-card{position:static;} }
  @media (max-width:680px){ .form-grid-2{grid-template-columns:1fr;} .action-bar{flex-direction:column-reverse;} .action-bar .btn{width:100%;} }
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
<span>Novo Membro</span>
@endsection

@section('content')

<div style="margin-top:12px;">
  <div class="eyebrow">Administração</div>
  <h1 class="page-title">Cadastrar Novo Membro</h1>
  <p class="page-sub">Preencha os dados para adicionar um novo integrante à equipe.</p>
</div>

@if(session('erro'))
  <div style="background:var(--danger-light,#fde8e4);color:var(--danger,#e52207);border:1px solid var(--danger,#e52207);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-top:16px;">
    {{ session('erro') }}
  </div>
@endif

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
    {{-- Perfil e Acesso --}}
    <section class="section-card">
      <div class="section-head">
        <h2>Perfil e Acesso</h2>
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

    {{-- Dados Pessoais --}}
    <section class="section-card">
      <div class="section-head">
        <h2>Dados Pessoais</h2>
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
              <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nome@escola.gov.br" required />
            </div>
            @error('email')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
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

  var palettes = ['#1351b4','#168821','#7b2cbf','#b45f06','#c2185b','#006b5d'];

  function initials(name) {
    var parts = (name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '';
    if (parts.length === 1) return parts[0].slice(0,2).toUpperCase();
    return (parts[0][0] + parts[parts.length-1][0]).toUpperCase();
  }

  function colorFor(name) {
    var n = 0;
    for (var i = 0; i < (name || '').length; i++) n += name.charCodeAt(i);
    return palettes[n % palettes.length];
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
      avatarEl.style.background = 'linear-gradient(135deg,' + colorFor(nome) + ' 0%,#0c326f 100%)';
      avatarEl.innerHTML = ini;
    } else {
      avatarEl.classList.remove('has-initials');
      avatarEl.style.background = '';
      avatarEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="54" height="54"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
    }
  }

  nomeEl.addEventListener('input', update);
  perfilEl.addEventListener('change', update);
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
