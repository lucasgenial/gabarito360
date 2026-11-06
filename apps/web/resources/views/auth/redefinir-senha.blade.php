@extends('layouts.auth')

@section('title', 'Redefinir Senha')

@push('styles')
<style>
  .auth-wrap { min-height: calc(100vh / var(--escala)); display: grid; grid-template-columns: 1.1fr 1fr; }
  .auth-aside {
    background: var(--g360-access-gradient);
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
  @media (max-width: 860px) { .auth-wrap { grid-template-columns: 1fr; } .auth-aside { display: none; } }
</style>
@endpush

@section('content')
<div class="auth-wrap">
  <aside class="auth-aside">
    <a class="brand" href="{{ route('home') }}"><x-marca theme="dark" /></a>
    <div class="aside-body">
      <h1>Crie uma nova senha segura.</h1>
      <p>Use pelo menos 8 caracteres. Combine letras, números e símbolos para maior segurança.</p>
    </div>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      <a class="brand auth-marca-mobile" href="{{ route('home') }}"><x-marca /></a>
      <div style="margin-bottom:24px;">
        <h2 style="font-size:26px;margin-top:4px;">Redefinir senha</h2>
        <p style="color:var(--muted);margin-top:8px;font-size:14px;">
          Informe seu e-mail, cole o código recebido e escolha a nova senha.
        </p>
      </div>

      @if(session('erro'))
        <div class="alert-erro">{{ session('erro') }}</div>
      @endif

      <form method="POST" action="{{ route('redefinir-senha.store') }}" novalidate>
        @csrf <x-revisao-acesso />
        <div class="field"><label for="redefinir-email">E-mail</label><input class="input" type="email" id="redefinir-email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="redefinir-email-erro">@error('email')<span id="redefinir-email-erro" role="alert" class="field-error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="redefinir-codigo">Código de recuperação</label><input class="input" type="text" id="redefinir-codigo" name="token" required minlength="64" maxlength="64" autocomplete="one-time-code" autocapitalize="none" spellcheck="false" aria-describedby="redefinir-codigo-erro redefinir-codigo-ajuda" aria-invalid="{{ $errors->has('token') ? 'true' : 'false' }}"><p class="field-help" id="redefinir-codigo-ajuda">Copie os 64 caracteres da mensagem. O código vale por 60 minutos.</p>@error('token')<span id="redefinir-codigo-erro" role="alert" class="field-error">{{ $message }}</span>@enderror</div>

        <div class="field">
          <label for="password">Nova senha</label>
          <div class="input-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <input class="input" type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required minlength="8"  aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-erro">
          </div>
          @error('password')<div id="password-erro" role="alert" style="color:var(--danger);font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
        </div>

        <div class="field">
          <label for="password_confirmation">Confirmar nova senha</label>
          <div class="input-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <input class="input" type="password" id="password_confirmation" name="password_confirmation" placeholder="Repita a nova senha" required minlength="8" />
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px;">
          Redefinir senha
        </button>
      </form>

      <div style="text-align:center;margin-top:20px;">
        <a href="{{ route('esqueci-senha') }}" class="btn btn-ghost">Solicitar novo código</a>
        <a href="{{ route('login') }}" style="font-size:14px;color:var(--accent);">
          ← Voltar para o login
        </a>
      </div>
    </div>
  </main>
</div>
@endsection
