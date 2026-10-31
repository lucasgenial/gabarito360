@extends('layouts.auth')

@section('title', 'Acesso')

@push('styles')
<style>
  .auth-wrap { min-height: calc(100vh / var(--escala)); display: grid; grid-template-columns: 1.1fr 1fr; }
  .auth-aside {
    background: linear-gradient(160deg, #1d4f9e 0%, #153b78 100%);
    color: #fff; padding: 56px; display: flex; flex-direction: column;
  }
  .auth-aside .brand .logo { background: #fff; color: var(--accent); }
  .auth-aside .brand { color: #fff; font-size: 22px; }
  .aside-body { margin-top: auto; }
  .aside-body h1 { font-size: 38px; line-height: 1.1; max-width: 13ch; }
  .aside-body p { opacity: 0.85; font-size: 16px; margin-top: 16px; max-width: 42ch; }
  .auth-main { display: grid; place-items: center; padding: 40px 24px; }
  .auth-card { width: 100%; max-width: 408px; }
  .checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--fg-2); }
  .alert-erro { background: var(--danger-light); color: var(--danger); border: 1px solid var(--danger); border-radius: var(--radius-md); padding: 12px 16px; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
  @media (max-width: 860px) { .auth-wrap { grid-template-columns: 1fr; } .auth-aside { display: none; } }
</style>
@endpush

@section('content')
<div class="auth-wrap">
  <aside class="auth-aside">
    <a class="brand" href="{{ route('home') }}"><span class="logo">G360</span> Gabarito360</a>
    <div class="aside-body">
      <h1>Da prova impressa à nota da turma, pelo celular.</h1>
      <p>Crie provas, capture cartões-resposta pelo celular e acompanhe o desempenho das turmas.</p>
    </div>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      <div style="margin-bottom:24px;">
        <h2 style="font-size:26px;margin-top:4px;">Acesso ao sistema</h2>
      </div>

      @if(session('erro'))
      <div class="alert-erro">{{ session('erro') }}</div>
      @endif

      @if(session('sucesso'))
      <div style="background:var(--success-light,#e3f5e1);color:var(--success,#168821);border:1px solid var(--success,#168821);border-radius:var(--radius-md);padding:12px 16px;font-size:14px;font-weight:600;margin-bottom:20px;">
        {{ session('sucesso') }}
      </div>
      @endif

      {{-- Login --}}
      <form id="pane-login" method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf
        <div class="field">
          <label for="email">E-mail</label>
          <div class="input-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
            <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nome@escola.com.br" required />
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
      </form>
      <a href="{{ route('home') }}" class="btn btn-ghost btn-block login-voltar">Voltar ao início</a>
    </div>
  </main>
</div>
@endsection

