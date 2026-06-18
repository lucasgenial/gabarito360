@extends('layouts.auth')

@section('title', 'Recuperar Senha')

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
  .auth-main { display: grid; place-items: center; padding: 40px 24px; }
  .auth-card { width: 100%; max-width: 408px; }
  .alert-erro { background: var(--danger-light); color: var(--danger); border: 1px solid var(--danger); border-radius: var(--radius-md); padding: 12px 16px; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
  .alert-sucesso { background: var(--success-light,#e3f5e1); color: var(--success,#168821); border: 1px solid var(--success,#168821); border-radius: var(--radius-md); padding: 12px 16px; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
  @media (max-width: 860px) { .auth-wrap { grid-template-columns: 1fr; } .auth-aside { display: none; } }
</style>
@endpush

@section('content')
<div class="auth-wrap">
  <aside class="auth-aside">
    <a class="brand"><span class="logo">G360</span> Gabarito360</a>
    <div class="aside-body">
      <h1>Recupere o acesso à sua conta.</h1>
      <p>Informe seu e-mail institucional e enviaremos um link para redefinir sua senha.</p>
    </div>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      <div style="margin-bottom:24px;">
        <div class="eyebrow">Secretaria de Educação</div>
        <h2 style="font-size:26px;margin-top:4px;">Recuperar senha</h2>
        <p style="color:var(--muted);margin-top:8px;font-size:14px;">
          Informe seu e-mail cadastrado para receber o link de recuperação.
        </p>
      </div>

      @if(session('erro'))
        <div class="alert-erro">{{ session('erro') }}</div>
      @endif

      @if(session('sucesso'))
        <div class="alert-sucesso">{{ session('sucesso') }}</div>
      @endif

      <form method="POST" action="{{ route('esqueci-senha.store') }}" novalidate>
        @csrf
        <div class="field">
          <label for="email">E-mail institucional</label>
          <div class="input-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
            <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nome@edu.gov.br" required autofocus />
          </div>
          @error('email')<div style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px;">
          Enviar link de recuperação
        </button>
      </form>

      <div style="text-align:center;margin-top:20px;">
        <a href="{{ route('login') }}" style="font-size:14px;color:var(--accent);">
          ← Voltar para o login
        </a>
      </div>
    </div>
  </main>
</div>
@endsection
