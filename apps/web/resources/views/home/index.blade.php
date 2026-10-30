<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<meta name="description" content="Gabarito360 lê cartões-resposta pelo celular com 98,6% de precisão e entrega relatórios instantâneos. Para professor, escola ou rede municipal.">
<title>Gabarito360 — Corrija provas em minutos, não em dias</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800;900&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/gov.css') }}?v={{ @filemtime(public_path('css/gov.css')) }}"/>
<style>
/* ─── Reset / base ─────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }
:root {
  --blue:  #1351b4;
  --blue2: #0c326f;
  --teal:  #2670e8;
  --green: #168821;
  --gray1: #f8f8f8;
  --gray2: #e0e0e0;
  --gray3: #8e8e8e;
  --gray4: #333;
  --white: #ffffff;
  --radius: 10px;
  --shadow: 0 2px 16px rgba(0,0,0,.08);
}
body { margin:0; font-family:'Raleway',sans-serif; color:var(--gray4); background:var(--white); }
a { color:inherit; text-decoration:none; }
img { display:block; max-width:100%; }

/* ─── Nav ───────────────────────────────────────────── */
.lp-nav {
  position:sticky; top:0; z-index:100;
  background:rgba(255,255,255,.95);
  backdrop-filter:blur(8px);
  border-bottom:1px solid var(--gray2);
  padding:0 1.5rem;
}
.lp-nav-inner {
  max-width:1080px; margin:0 auto;
  display:flex; align-items:center; gap:2rem;
  height:60px;
}
.lp-brand {
  font-size:1.2rem; font-weight:900; color:var(--blue);
  display:flex; align-items:center; gap:.5rem;
}
.lp-brand .badge-g360 {
  background:var(--blue); color:#fff; font-size:.7rem;
  padding:.2rem .45rem; border-radius:4px; font-weight:800;
  letter-spacing:.02em;
}
.lp-links { display:flex; gap:1.5rem; align-items:center; flex:1; }
.lp-links a { font-size:.875rem; font-weight:600; color:var(--gray3); transition:color .15s; }
.lp-links a:hover { color:var(--blue); }
.nav-ctas { display:flex; gap:.75rem; margin-left:auto; align-items:center; }
.btn-nav-ghost { font-size:.875rem; font-weight:700; color:var(--blue); padding:.4rem .9rem; border-radius:6px; border:1.5px solid var(--blue); transition:background .15s,color .15s; }
.btn-nav-ghost:hover { background:var(--blue); color:#fff; }
.btn-nav-solid { font-size:.875rem; font-weight:700; color:#fff; background:var(--blue); padding:.4rem 1.1rem; border-radius:6px; transition:background .15s; }
.btn-nav-solid:hover { background:var(--blue2); }

/* ─── Container ─────────────────────────────────────── */
.lp-section { padding:5rem 1.5rem; }
.lp-section.bg-gray { background:var(--gray1); }
.lp-section.bg-blue { background:var(--blue); color:#fff; }
.lp-inner { max-width:1080px; margin:0 auto; }

/* ─── Hero ──────────────────────────────────────────── */
.hero {
  min-height:calc(100vh - 60px);
  display:flex; align-items:center;
  background:linear-gradient(135deg, #0c326f 0%, #1351b4 60%, #2670e8 100%);
  color:#fff; padding:4rem 1.5rem;
}
.hero-inner {
  max-width:1080px; margin:0 auto;
  display:grid; grid-template-columns:1fr 1fr; gap:4rem; align-items:center;
}
.hero-eyebrow {
  display:inline-flex; align-items:center; gap:.5rem;
  background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2);
  padding:.35rem .85rem; border-radius:99px; font-size:.8rem; font-weight:700;
  letter-spacing:.04em; text-transform:uppercase; margin-bottom:1.5rem;
}
.hero h1 {
  font-size:clamp(2rem, 4vw, 3rem); font-weight:900; line-height:1.15;
  margin:0 0 1.25rem; letter-spacing:-.02em;
}
.hero h1 em { font-style:normal; color:#6eb0ff; }
.hero-sub {
  font-size:1.1rem; color:rgba(255,255,255,.82); line-height:1.65;
  margin:0 0 2.5rem;
}
.hero-ctas { display:flex; gap:1rem; flex-wrap:wrap; }
.btn-hero-primary {
  background:#fff; color:var(--blue); font-weight:800; font-size:1rem;
  padding:.875rem 1.75rem; border-radius:8px; transition:transform .15s, box-shadow .15s;
  box-shadow:0 4px 20px rgba(0,0,0,.18);
}
.btn-hero-primary:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(0,0,0,.24); }
.btn-hero-ghost {
  border:2px solid rgba(255,255,255,.5); color:#fff; font-weight:700; font-size:1rem;
  padding:.875rem 1.75rem; border-radius:8px; transition:border-color .15s, background .15s;
}
.btn-hero-ghost:hover { border-color:#fff; background:rgba(255,255,255,.08); }
.hero-visual {
  background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.15);
  border-radius:16px; padding:2rem; display:flex; flex-direction:column; gap:1rem;
}
.hero-stat {
  display:flex; align-items:center; gap:1rem;
  background:rgba(255,255,255,.1); border-radius:10px; padding:1rem 1.25rem;
}
.hero-stat-icon { font-size:1.75rem; }
.hero-stat-val { font-size:1.5rem; font-weight:900; line-height:1; }
.hero-stat-label { font-size:.8rem; color:rgba(255,255,255,.72); margin-top:.15rem; }
.hero-stat-badge {
  margin-left:auto; background:rgba(22,136,33,.25); border:1px solid rgba(22,136,33,.5);
  color:#5de86d; font-size:.7rem; font-weight:800; padding:.2rem .6rem; border-radius:99px;
}

/* ─── Seção: como funciona ──────────────────────────── */
.steps-grid {
  display:grid; grid-template-columns:repeat(3,1fr); gap:2rem; margin-top:3rem;
  position:relative;
}
.steps-grid::before {
  content:''; position:absolute; top:2.5rem; left:calc(16.66% + 1rem); right:calc(16.66% + 1rem);
  height:2px; background:var(--gray2); z-index:0;
}
.step { position:relative; z-index:1; text-align:center; }
.step-num {
  width:52px; height:52px; border-radius:50%;
  background:var(--blue); color:#fff; font-size:1.1rem; font-weight:900;
  display:inline-flex; align-items:center; justify-content:center;
  margin:0 auto 1rem; box-shadow:0 0 0 5px var(--white), 0 0 0 7px var(--gray2);
}
.step h3 { font-size:1.05rem; font-weight:800; margin:0 0 .5rem; }
.step p { font-size:.9rem; color:var(--gray3); line-height:1.6; margin:0; }

/* ─── Seção: números ────────────────────────────────── */
.numbers-grid {
  display:grid; grid-template-columns:repeat(3,1fr); gap:1.5rem; margin-top:3rem;
}
.number-card {
  background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.14);
  border-radius:12px; padding:2rem; text-align:center;
}
.number-val { font-size:2.75rem; font-weight:900; line-height:1; }
.number-label { font-size:.9rem; color:rgba(255,255,255,.72); margin-top:.5rem; }

/* ─── Seção: features ────────────────────────────────── */
.features-grid {
  display:grid; grid-template-columns:repeat(3,1fr); gap:1.5rem; margin-top:3rem;
}
.feature-card {
  background:var(--white); border:1px solid var(--gray2);
  border-radius:12px; padding:1.75rem;
  transition:box-shadow .2s, border-color .2s;
}
.feature-card:hover { box-shadow:var(--shadow); border-color:var(--blue); }
.feature-icon { font-size:2rem; margin-bottom:1rem; }
.feature-card h3 { font-size:1rem; font-weight:800; margin:0 0 .5rem; color:var(--blue); }
.feature-card p { font-size:.875rem; color:var(--gray3); line-height:1.6; margin:0; }

/* ─── Seção: para quem ──────────────────────────────── */
.personas-grid {
  display:grid; grid-template-columns:repeat(4,1fr); gap:1.25rem; margin-top:3rem;
}
.persona-card {
  border:1px solid var(--gray2); border-radius:12px; padding:1.5rem;
  position:relative; overflow:hidden; transition:box-shadow .2s;
}
.persona-card:hover { box-shadow:var(--shadow); }
.persona-card::before {
  content:''; position:absolute; top:0; left:0; right:0; height:4px;
  background:var(--blue);
}
.persona-emoji { font-size:2rem; margin-bottom:.75rem; }
.persona-card h3 { font-size:.95rem; font-weight:800; margin:0 0 .5rem; }
.persona-card ul { padding:0 0 0 1rem; margin:0; font-size:.82rem; color:var(--gray3); line-height:1.8; }

/* ─── Seção: planos ──────────────────────────────────── */
.plans-section h2 { text-align:center; }
.plans-grid {
  display:grid; grid-template-columns:repeat(3,1fr); gap:1.5rem; margin-top:2.5rem;
}
.plan-card {
  background:var(--white); border:1.5px solid var(--gray2);
  border-radius:12px; padding:2rem; display:flex; flex-direction:column;
  transition:box-shadow .2s;
}
.plan-card:hover { box-shadow:var(--shadow); }
.plan-card.popular {
  border-color:var(--blue); position:relative;
  box-shadow:0 0 0 3px rgba(19,81,180,.15);
}
.popular-badge {
  position:absolute; top:-1px; left:50%; transform:translateX(-50%);
  background:var(--blue); color:#fff; font-size:.72rem; font-weight:800;
  padding:.25rem .9rem; border-radius:0 0 8px 8px; white-space:nowrap;
  letter-spacing:.04em; text-transform:uppercase;
}
.plan-name { font-size:.9rem; font-weight:700; color:var(--gray3); text-transform:uppercase; letter-spacing:.04em; margin-bottom:.5rem; }
.plan-price { font-size:2.25rem; font-weight:900; color:var(--blue); line-height:1; }
.plan-price small { font-size:.875rem; font-weight:400; color:var(--gray3); }
.plan-desc { font-size:.8rem; color:var(--gray3); margin:.3rem 0 1.25rem; }
.plan-features { list-style:none; padding:0; margin:0 0 1.5rem; flex:1; }
.plan-features li { font-size:.85rem; color:var(--gray4); padding:.3rem 0; border-bottom:1px solid var(--gray1); }
.plan-features li::before { content:'✓ '; color:var(--green); font-weight:700; }
.plan-cta { margin-top:auto; }
.plan-cta a { display:block; text-align:center; background:var(--blue); color:#fff; font-weight:700; padding:.875rem; border-radius:7px; transition:background .15s; }
.plan-cta a:hover { background:var(--blue2); }
.plan-card.comercial { background:var(--gray1); }
.plan-card.comercial .plan-price { font-size:1.4rem; color:var(--gray4); }
.plan-card.comercial .plan-cta a { background:transparent; color:var(--blue); border:2px solid var(--blue); }
.plan-card.comercial .plan-cta a:hover { background:var(--blue); color:#fff; }
.plans-note { text-align:center; margin-top:1.25rem; font-size:.85rem; color:var(--gray3); }

/* ─── CTA final ─────────────────────────────────────── */
.final-cta {
  background:linear-gradient(135deg, #0c326f, #1351b4);
  color:#fff; text-align:center; padding:5rem 1.5rem;
}
.final-cta h2 { font-size:clamp(1.6rem,3vw,2.4rem); font-weight:900; margin:0 0 1rem; }
.final-cta p { font-size:1.05rem; color:rgba(255,255,255,.8); margin:0 auto 2.5rem; max-width:540px; }
.btn-final { display:inline-block; background:#fff; color:var(--blue); font-weight:800; font-size:1.05rem; padding:1rem 2.5rem; border-radius:9px; box-shadow:0 4px 20px rgba(0,0,0,.2); transition:transform .15s,box-shadow .15s; }
.btn-final:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(0,0,0,.28); }
.btn-final-ghost { display:inline-block; border:2px solid rgba(255,255,255,.45); color:#fff; font-weight:700; font-size:1rem; padding:.875rem 2rem; border-radius:9px; margin-left:1rem; transition:border-color .15s; }
.btn-final-ghost:hover { border-color:#fff; }

/* ─── Footer ─────────────────────────────────────────── */
.lp-footer {
  padding:2.5rem 1.5rem; border-top:1px solid var(--gray2);
  font-size:.82rem; color:var(--gray3);
}
.lp-footer-inner {
  max-width:1080px; margin:0 auto;
  display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;
}
.footer-links { display:flex; gap:1.5rem; }
.footer-links a { color:var(--gray3); transition:color .15s; }
.footer-links a:hover { color:var(--blue); }

/* ─── Section titles ─────────────────────────────────── */
.section-tag {
  display:inline-block; font-size:.75rem; font-weight:800; text-transform:uppercase;
  letter-spacing:.08em; color:var(--blue); background:rgba(19,81,180,.08);
  padding:.25rem .75rem; border-radius:99px; margin-bottom:.75rem;
}
.section-tag.light { color:rgba(255,255,255,.8); background:rgba(255,255,255,.12); }
.section-title { font-size:clamp(1.5rem,2.5vw,2rem); font-weight:900; margin:0 0 .75rem; letter-spacing:-.01em; }
.section-sub { font-size:1rem; color:var(--gray3); margin:0; max-width:580px; }

/* ─── Responsive ─────────────────────────────────────── */
@media(max-width:900px) {
  .hero-inner { grid-template-columns:1fr; }
  .hero-visual { display:none; }
  .steps-grid, .features-grid { grid-template-columns:1fr; }
  .steps-grid::before { display:none; }
  .personas-grid { grid-template-columns:1fr 1fr; }
  .plans-grid { grid-template-columns:1fr; max-width:420px; margin-left:auto; margin-right:auto; }
  .numbers-grid { grid-template-columns:1fr; }
  .lp-links { display:none; }
}
@media(max-width:500px) {
  .personas-grid { grid-template-columns:1fr; }
  .nav-ctas .btn-nav-ghost { display:none; }
}
</style>
</head>
<body>

{{-- ═══ NAV ═══ --}}
<nav class="lp-nav" aria-label="Navegação principal">
  <div class="lp-nav-inner">
    <a href="{{ route('home') }}" class="lp-brand">
      <span class="badge-g360">G360</span> Gabarito360
    </a>
    <div class="lp-links">
      <a href="#como-funciona">Como funciona</a>
      <a href="#funcionalidades">Funcionalidades</a>
      <a href="{{ route('planos.index') }}">Planos</a>
      <a href="{{ route('leads.index') }}">Fale Conosco</a>
    </div>
    <div class="nav-ctas">
      <a href="{{ route('login') }}" class="btn-nav-ghost">Entrar</a>
      <a href="{{ route('planos.index') }}" class="btn-nav-solid">Assinar grátis</a>
    </div>
  </div>
</nav>

{{-- ═══ HERO ═══ --}}
<section class="hero" aria-labelledby="hero-title">
  <div class="hero-inner">
    <div class="hero-copy">
      <div class="hero-eyebrow">
        ⚡ Leitura de cartão-resposta por celular
      </div>
      <h1 id="hero-title">
        Corrija provas inteiras<br>
        em <em>minutos</em>,<br>
        não em dias.
      </h1>
      <p class="hero-sub">
        Gabarito360 lê cartões-resposta pela câmera do celular,
        calcula as notas automaticamente e entrega relatórios pedagógicos
        na hora. Para o professor solo, para a escola ou para toda a rede municipal.
      </p>
      <div class="hero-ctas">
        <a href="{{ route('planos.index') }}" class="btn-hero-primary">
          Começar grátis — 14 dias →
        </a>
        <a href="#como-funciona" class="btn-hero-ghost">
          Ver como funciona
        </a>
      </div>
    </div>
    <div class="hero-visual" aria-hidden="true">
      <div class="hero-stat">
        <div class="hero-stat-icon">⚡</div>
        <div>
          <div class="hero-stat-val">12s</div>
          <div class="hero-stat-label">por cartão lido</div>
        </div>
        <span class="hero-stat-badge">OMR</span>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-icon">🎯</div>
        <div>
          <div class="hero-stat-val">98,6%</div>
          <div class="hero-stat-label">de precisão na leitura</div>
        </div>
        <span class="hero-stat-badge">AI</span>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-icon">📊</div>
        <div>
          <div class="hero-stat-val">0</div>
          <div class="hero-stat-label">planilhas manuais</div>
        </div>
        <span class="hero-stat-badge">AUTO</span>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-icon">🏫</div>
        <div>
          <div class="hero-stat-val">+340</div>
          <div class="hero-stat-label">escolas usando</div>
        </div>
        <span class="hero-stat-badge">LIVE</span>
      </div>
    </div>
  </div>
</section>

{{-- ═══ COMO FUNCIONA ═══ --}}
<section class="lp-section" id="como-funciona" aria-labelledby="como-funciona-title">
  <div class="lp-inner">
    <div class="section-tag">Como funciona</div>
    <h2 class="section-title" id="como-funciona-title">Três passos. Zero scanner.</h2>
    <p class="section-sub">Sem equipamento especial, sem planilha manual. O celular que está no seu bolso já é tudo que você precisa.</p>

    <div class="steps-grid">
      <div class="step">
        <div class="step-num">1</div>
        <h3>Configure a prova</h3>
        <p>Crie o gabarito oficial no sistema, defina questões e publique. O cartão-resposta é gerado em PDF pronto para impressão na impressora da escola.</p>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <h3>Aplique normalmente</h3>
        <p>Alunos respondem no cartão impresso como sempre. Nada muda na rotina de aplicação — só o processo de correção vai mudar.</p>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <h3>Escaneie e pronto</h3>
        <p>Abra o app, aponte a câmera para o cartão. Em 12 segundos a nota está calculada, o relatório está pronto e o aluno já pode consultar.</p>
      </div>
    </div>
  </div>
</section>

{{-- ═══ NÚMEROS ═══ --}}
<section class="lp-section bg-blue" aria-label="Estatísticas do produto">
  <div class="lp-inner">
    <div style="text-align:center">
      <div class="section-tag light">Resultados reais</div>
      <h2 class="section-title" style="color:#fff;margin-bottom:0">Números que falam por si</h2>
    </div>
    <div class="numbers-grid">
      <div class="number-card">
        <div class="number-val">12<small style="font-size:1.5rem">s</small></div>
        <div class="number-label">Tempo médio de leitura por cartão</div>
      </div>
      <div class="number-card">
        <div class="number-val">98,6<small style="font-size:1.5rem">%</small></div>
        <div class="number-label">Precisão de leitura do OMR</div>
      </div>
      <div class="number-card">
        <div class="number-val">0</div>
        <div class="number-label">Dados de cartão armazenados — só o Mercado Pago processa</div>
      </div>
    </div>
  </div>
</section>

{{-- ═══ FUNCIONALIDADES ═══ --}}
<section class="lp-section bg-gray" id="funcionalidades" aria-labelledby="features-title">
  <div class="lp-inner">
    <div class="section-tag">Funcionalidades</div>
    <h2 class="section-title" id="features-title">Tudo que você precisa para avaliar de verdade</h2>
    <p class="section-sub">Do cartão-resposta impresso ao relatório consolidado da rede — em um único sistema.</p>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">📷</div>
        <h3>OMR por câmera</h3>
        <p>Leitura óptica de cartões-resposta direto pela câmera do celular. Sem scanner, sem equipamento caro, sem calibração. Funciona na luz da sala de aula.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h3>Relatórios instantâneos</h3>
        <p>Nota individual, média da turma, comparativo entre turmas, ranking de desempenho por disciplina. Prontos no momento em que o último cartão é lido.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🧑‍💼</div>
        <h3>Dashboards por perfil</h3>
        <p>Cada usuário vê exatamente o que importa para ele. Professor vê suas turmas. Diretor vê a escola. Secretário vê todas as redes vinculadas.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🏫</div>
        <h3>Multi-escola e multi-rede</h3>
        <p>Um professor pode usar sozinho. Uma secretaria municipal pode gerenciar dezenas de escolas e redes na mesma plataforma, com dados segregados por escopo.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔒</div>
        <h3>Seguro por padrão</h3>
        <p>Nenhum dado de cartão de crédito armazenado. Dados de alunos isolados por rede. Autenticação com token por sessão. Acesso suspenso automaticamente em inadimplência.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">☁️</div>
        <h3>100% na nuvem, SaaS</h3>
        <p>Sem instalação, sem servidor próprio, sem atualização manual. Assine, acesse pelo navegador ou pelo celular e comece a corrigir no mesmo dia.</p>
      </div>
    </div>
  </div>
</section>

{{-- ═══ PARA QUEM É ═══ --}}
<section class="lp-section" aria-labelledby="personas-title">
  <div class="lp-inner">
    <div class="section-tag">Para quem é</div>
    <h2 class="section-title" id="personas-title">De um professor a uma secretaria inteira</h2>
    <p class="section-sub">O Gabarito360 cresce junto com você — começa pequeno e escala sem trocar de sistema.</p>

    <div class="personas-grid">
      <div class="persona-card">
        <div class="persona-emoji">🧑‍🏫</div>
        <h3>Professor individual</h3>
        <ul>
          <li>Corrija provas em 12s por cartão</li>
          <li>Relatório de cada aluno automaticamente</li>
          <li>Sem precisar de TI ou secretaria</li>
          <li>Plano a partir de R$ 9,99/mês</li>
        </ul>
      </div>
      <div class="persona-card">
        <div class="persona-emoji">🏫</div>
        <h3>Escola</h3>
        <ul>
          <li>Todos os professores em uma conta</li>
          <li>Diretores com visão consolidada</li>
          <li>Alertas de alunos em atenção</li>
          <li>Visitas pedagógicas agendadas</li>
        </ul>
      </div>
      <div class="persona-card">
        <div class="persona-emoji">🏙️</div>
        <h3>Rede municipal</h3>
        <ul>
          <li>Todas as escolas em um painel</li>
          <li>Comparativo entre núcleos e escolas</li>
          <li>Meta de desempenho configurável</li>
          <li>Alertas automáticos de escolas abaixo da meta</li>
        </ul>
      </div>
      <div class="persona-card">
        <div class="persona-emoji">🏛️</div>
        <h3>Secretaria estadual</h3>
        <ul>
          <li>Supervisão de múltiplas redes</li>
          <li>Relatórios consolidados por UF</li>
          <li>Gestão centralizada de usuários</li>
          <li>Proposta personalizada — Fale Conosco</li>
        </ul>
      </div>
    </div>
  </div>
</section>

{{-- ═══ PLANOS ═══ --}}
<section class="lp-section bg-gray plans-section" id="planos" aria-labelledby="plans-title">
  <div class="lp-inner">
    <div style="text-align:center;margin-bottom:1rem">
      <div class="section-tag">Planos e preços</div>
      <h2 class="section-title" id="plans-title">Simples. Sem surpresa.</h2>
      <p class="section-sub" style="margin:0 auto">14 dias grátis em todos os planos de autoatendimento. Cancele a qualquer momento.</p>
    </div>

    <div class="plans-grid">
      @foreach($planos as $plano)
        @php
          $popular = $plano['publico'] === 'individual' && $plano['periodicidade'] === 'anual';
        @endphp
        <div class="plan-card {{ $popular ? 'popular' : '' }}">
          @if($popular)
            <span class="popular-badge">Mais econômico</span>
          @endif
          <div class="plan-name">{{ $plano['publico'] === 'individual' ? 'Individual' : 'Institucional' }}</div>
          <div class="plan-price">
            @if($plano['preco_fixo'] !== null)
              R$ {{ number_format($plano['preco_fixo'], 2, ',', '.') }}
              <small>/{{ $plano['periodicidade'] === 'anual' ? 'ano' : 'mês' }}</small>
            @else
              R$ {{ number_format($plano['preco_por_licenca'], 2, ',', '.') }}
              <small>/licença/mês</small>
            @endif
          </div>
          <div class="plan-desc">
            @if($plano['periodicidade'] === 'anual')
              Equivale a R$ {{ number_format($plano['preco_fixo'] / 12, 2, ',', '.') }}/mês — economize 16%
            @elseif($plano['preco_por_licenca'] !== null)
              De {{ $plano['licencas_min'] }} a {{ $plano['licencas_max'] }} licenças (professores/usuários)
            @else
              Cobrado mensalmente, cancele quando quiser
            @endif
          </div>
          <ul class="plan-features">
            @if($plano['publico'] === 'individual')
              <li>1 escola · até 20 turmas</li>
              <li>OMR por câmera</li>
              <li>Relatórios completos</li>
              <li>Suporte por e-mail</li>
            @else
              <li>Até 5 escolas · 100 turmas</li>
              <li>OMR por câmera</li>
              <li>Relatórios e dashboards</li>
              <li>Gestão de equipe</li>
            @endif
          </ul>
          <div class="plan-cta">
            <a href="{{ session('auth_token') ? route('checkout.index', ['plano_id' => $plano['id']]) : route('login') }}">
              Começar grátis →
            </a>
          </div>
        </div>
      @endforeach

      {{-- Card Fale Conosco --}}
      <div class="plan-card comercial">
        <div class="plan-name">Rede / Secretaria</div>
        <div class="plan-price">Sob consulta</div>
        <div class="plan-desc">Para redes acima de 10 licenças e secretarias estaduais</div>
        <ul class="plan-features">
          <li>Escolas ilimitadas</li>
          <li>OMR por câmera</li>
          <li>Relatórios consolidados</li>
          <li>SLA e suporte dedicado</li>
        </ul>
        <div class="plan-cta">
          <a href="{{ route('leads.index') }}">Fale Conosco →</a>
        </div>
      </div>
    </div>

    <p class="plans-note">
      Tem dúvidas sobre qual plano escolher? <a href="{{ route('leads.index') }}" style="color:var(--blue);font-weight:700">Fale com a gente →</a>
    </p>
  </div>
</section>

{{-- ═══ CTA FINAL ═══ --}}
<section class="final-cta" aria-label="Chamada final para ação">
  <h2>Você ainda corrige prova na mão?</h2>
  <p>Junte-se a mais de 340 escolas que já correem provas em minutos. Comece grátis, sem cartão de crédito, e descubra o que é ter os relatórios prontos antes de sair da sala.</p>
  <a href="{{ route('planos.index') }}" class="btn-final">Começar agora — é grátis por 14 dias</a>
  <a href="{{ route('leads.index') }}" class="btn-final-ghost">Fale Conosco</a>
</section>

{{-- ═══ FOOTER ═══ --}}
<footer class="lp-footer">
  <div class="lp-footer-inner">
    <div>
      <strong style="color:var(--blue)">Gabarito360</strong> — Plataforma de avaliações educacionais
    </div>
    <div class="footer-links">
      <a href="{{ route('planos.index') }}">Planos</a>
      <a href="{{ route('leads.index') }}">Fale Conosco</a>
      <a href="{{ route('login') }}">Entrar</a>
      <a href="#">Privacidade</a>
    </div>
    <div>© {{ date('Y') }} Gabarito360. Todos os direitos reservados.</div>
  </div>
</footer>

<script>
// Smooth scroll para âncoras internas
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});
</script>
</body>
</html>
