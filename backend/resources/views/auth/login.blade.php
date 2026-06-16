@extends('layouts.guest')

@section('title', 'Acesso ao sistema')

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
            <div class="row-between" style="margin-bottom:24px;">
                <div>
                    <div class="eyebrow">Secretaria de Educação</div>
                    <h2 style="font-size:26px;margin-top:4px;">Acesso ao sistema</h2>
                </div>
            </div>

            <div class="auth-tabs" role="tablist">
                <button type="button" class="active" data-tab="login" role="tab">Entrar</button>
                <button type="button" data-tab="signup" role="tab">Cadastrar</button>
            </div>

            {{-- Login --}}
            <form class="pane active" id="pane-login" method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                <div class="field">
                    <label for="email">E-mail institucional</label>
                    <div class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nome@edu.gov.br" autocomplete="username" autofocus />
                    </div>
                    <div class="err {{ $errors->has('email') ? 'show' : '' }}" id="err-email">{{ $errors->first('email') ?: 'Informe um e-mail válido.' }}</div>
                </div>
                <div class="field">
                    <label for="senha">Senha</label>
                    <div class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        <input class="input" type="password" id="senha" name="password" placeholder="••••••••" autocomplete="current-password" />
                    </div>
                    <div class="err" id="err-senha">A senha deve ter ao menos 6 caracteres.</div>
                </div>
                <div class="row-between" style="margin:4px 0 20px;">
                    <label class="checkbox-row"><input type="checkbox" name="remember" checked /> Manter conectado</label>
                    <a href="#" style="font-size:14px;">Esqueci a senha</a>
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="btn-login">Entrar no painel</button>
                <p style="text-align:center;color:var(--muted);font-size:13px;margin-top:20px;">Acesso protegido por autenticação gov.br</p>
            </form>

            {{-- Cadastro (acesso por convite/onboarding institucional) --}}
            <form class="pane" id="pane-signup" novalidate>
                <div class="field">
                    <label for="nome">Nome completo</label>
                    <input class="input" type="text" id="nome" placeholder="Ex.: Maria Aparecida Santos" />
                </div>
                <div class="field">
                    <label for="cpf">CPF</label>
                    <input class="input num" type="text" id="cpf" placeholder="000.000.000-00" maxlength="14" />
                </div>
                <div class="field">
                    <label for="papel">Perfil</label>
                    <select class="select" id="papel">
                        <option>Coordenação / Secretaria</option>
                        <option>Professor(a)</option>
                        <option>Diretor(a)</option>
                    </select>
                </div>
                <div class="field">
                    <label for="email2">E-mail institucional</label>
                    <input class="input" type="email" id="email2" placeholder="nome@edu.gov.br" />
                </div>
                <label class="checkbox-row" style="margin:4px 0 20px;"><input type="checkbox" id="termos" /> Li e aceito os termos de uso e a LGPD</label>
                <button type="button" class="btn btn-primary btn-block" id="btn-signup">Solicitar acesso</button>
                <div class="err" id="err-signup" style="text-align:center;margin-top:14px;">O acesso é liberado por convite institucional. Sua solicitação será enviada à coordenação.</div>
            </form>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.auth-tabs button').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('.auth-tabs button').forEach(function (x) { x.classList.remove('active'); });
            document.querySelectorAll('.pane').forEach(function (p) { p.classList.remove('active'); });
            b.classList.add('active');
            document.getElementById('pane-' + b.dataset.tab).classList.add('active');
        });
    });
    document.getElementById('pane-login').addEventListener('submit', function (e) {
        var email = document.getElementById('email');
        var senha = document.getElementById('senha');
        var ok = true;
        var ev = /\S+@\S+\.\S+/.test(email.value);
        document.getElementById('err-email').classList.toggle('show', !ev); if (!ev) ok = false;
        var sv = senha.value.length >= 6;
        document.getElementById('err-senha').classList.toggle('show', !sv); if (!sv) ok = false;
        if (!ok) e.preventDefault();
    });
    document.getElementById('btn-signup').addEventListener('click', function () {
        document.getElementById('err-signup').classList.add('show');
    });
    document.getElementById('cpf').addEventListener('input', function (e) {
        var v = e.target.value.replace(/\D/g, '').slice(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = v;
    });
</script>
@endpush
