@extends('layouts.auth')

@section('title', 'Acesso')

@push('styles')
<style>
  .auth-wrap { min-height: 100vh; display: grid; grid-template-columns: 1.1fr 1fr; }
  .auth-aside {
    background: linear-gradient(160deg, #1351b4 0%, #0c326f 100%);
    color: #fff; padding: 56px; display: flex; flex-direction: column;
  }
  .auth-aside .brand .logo { background: #fff; color: var(--accent); }
  .auth-aside .brand { color: #fff; font-size: 22px; }
  .aside-body { margin-top: auto; }
  .aside-body h1 { font-size: 38px; line-height: 1.1; max-width: 13ch; }
  .aside-body p { opacity: 0.85; font-size: 16px; margin-top: 16px; max-width: 42ch; }
  .aside-stats { display: flex; gap: 36px; margin-top: 40px; }
  .aside-stats .v { font-family: var(--font-mono); font-size: 30px; font-weight: 700; }
  .aside-stats .l { font-size: 13px; opacity: 0.8; margin-top: 2px; }
  .auth-main { display: grid; place-items: center; padding: 40px 24px; }
  .auth-card { width: 100%; max-width: 408px; }
  .tabs { display: flex; gap: 4px; background: var(--surface-2); padding: 4px; border-radius: var(--radius-md); margin-bottom: 28px; }
  .tabs button { flex: 1; padding: 10px; border: 0; background: transparent; font-family: var(--font-body); font-size: 15px; font-weight: 600; color: var(--muted); border-radius: 6px; cursor: pointer; }
  .tabs button.active { background: #fff; color: var(--accent); box-shadow: var(--shadow-sm); }
  .pane { display: none; } .pane.active { display: block; }
  .checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--fg-2); }
  .alert-erro { background: var(--danger-light); color: var(--danger); border: 1px solid var(--danger); border-radius: var(--radius-md); padding: 12px 16px; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
  @media (max-width: 860px) { .auth-wrap { grid-template-columns: 1fr; } .auth-aside { display: none; } }
</style>
@endpush

@section('content')
<div class="auth-wrap">
  <aside class="auth-aside">
    <a class="brand"><span class="logo">G360</span> Gabarito360</a>
    <div class="aside-body">
      <h1>Correção de provas em segundos, não em fins de semana.</h1>
      <p>Plataforma da rede pública para criar provas, capturar cartões-resposta pelo celular e acompanhar o desempenho das turmas.</p>
      <div class="aside-stats">
        <div><div class="v">12s</div><div class="l">por cartão lido</div></div>
        <div><div class="v">98,6%</div><div class="l">precisão do OMR</div></div>
        <div><div class="v">340</div><div class="l">escolas na rede</div></div>
      </div>
    </div>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      <div style="margin-bottom:24px;">
        <div class="eyebrow">Secretaria de Educação</div>
        <h2 style="font-size:26px;margin-top:4px;">Acesso ao sistema</h2>
      </div>

      @if(session('erro'))
      <div class="alert-erro">{{ session('erro') }}</div>
      @endif

      <div class="tabs" role="tablist">
        <button class="active" data-tab="login" role="tab">Entrar</button>
        <button data-tab="signup" role="tab">Cadastrar</button>
      </div>

      {{-- Login --}}
      <form class="pane active" id="pane-login" method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf
        <div class="field">
          <label for="email">E-mail institucional</label>
          <div class="input-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
            <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nome@edu.gov.br" required />
          </div>
          @error('email')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label for="password">Senha</label>
          <div class="input-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <input class="input" type="password" id="password" name="password" placeholder="••••••••" required />
          </div>
          @error('password')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div class="row-between" style="margin:4px 0 20px;">
          <label class="checkbox-row"><input type="checkbox" name="lembrar" checked /> Manter conectado</label>
          <a href="{{ route('esqueci-senha') }}" style="font-size:14px;">Esqueci a senha</a>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Entrar no painel</button>
        <p style="text-align:center;color:var(--muted);font-size:13px;margin-top:20px;">Acesso protegido por autenticação gov.br</p>
      </form>

      {{-- Cadastro --}}
      <form class="pane" id="pane-signup" method="POST" action="{{ route('cadastro.store') }}" novalidate>
        @csrf
        <div class="field">
          <label for="nome">Nome completo</label>
          <input class="input" type="text" id="nome" name="nome" placeholder="Ex.: Maria Aparecida Santos" />
        </div>
        <div class="field">
          <label for="cpf">CPF</label>
          <input class="input num" type="text" id="cpf" name="cpf" placeholder="000.000.000-00" maxlength="14" />
        </div>
        <div class="field">
          <label for="email2">E-mail institucional</label>
          <input class="input" type="email" id="email2" name="email" placeholder="nome@edu.gov.br" />
        </div>
        <label class="checkbox-row" style="margin:4px 0 20px;"><input type="checkbox" name="termos" /> Li e aceito os termos de uso e a LGPD</label>
        <button type="submit" class="btn btn-primary btn-block">Criar conta</button>
      </form>
    </div>
  </main>
</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('.tabs button').forEach(function(b) {
    b.addEventListener('click', function() {
      document.querySelectorAll('.tabs button').forEach(function(x) { x.classList.remove('active'); });
      document.querySelectorAll('.pane').forEach(function(p) { p.classList.remove('active'); });
      b.classList.add('active');
      document.getElementById('pane-' + b.dataset.tab).classList.add('active');
    });
  });
  document.getElementById('cpf').addEventListener('input', function(e) {
    var v = e.target.value.replace(/\D/g,'').slice(0,11);
    v = v.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2');
    e.target.value = v;
  });
</script>
@endpush
