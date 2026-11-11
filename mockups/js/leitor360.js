/* Mockup Leitor360 (FN-079). Dados fictícios. Nada é enviado a lugar algum.
   Reproduz o comportamento das telas reais: app.js (tema), correcao-camera.js (guias no canvas),
   correcao-revisao.js e leitor-cartao.js (estação de revisão e leitor da folha). Sem confirm/alert/prompt (ADR-0025). */
(function () {
  'use strict';
  var params = new URLSearchParams(location.search);
  var noQuadro = params.get('quadro') === '1';
  var LETRAS = ['A', 'B', 'C', 'D', 'E'];
  var NS = 'http://www.w3.org/2000/svg';

  /* ---------- Leitura simulada de 20 questões (lido, branco, dupla, fraca, nao_lido) ---------- */
  var questoes = [
    { e: 'lido', v: 'A' }, { e: 'lido', v: 'C' }, { e: 'lido', v: 'B' }, { e: 'dupla', d: ['B', 'D'] },
    { e: 'lido', v: 'E' }, { e: 'branco' }, { e: 'fraca', v: 'C' }, { e: 'lido', v: 'A' },
    { e: 'lido', v: 'D' }, { e: 'lido', v: 'B' }, { e: 'nao_lido' }, { e: 'lido', v: 'C' },
    { e: 'lido', v: 'A' }, { e: 'branco' }, { e: 'lido', v: 'E' }, { e: 'lido', v: 'B' },
    { e: 'lido', v: 'D' }, { e: 'lido', v: 'C' }, { e: 'lido', v: 'A' }, { e: 'lido', v: 'B' }
  ];
  var ESTADO = { dupla: 'Marcação dupla', fraca: 'Marcação fraca', nao_lido: 'Não lida', branco: 'Em branco', lido: 'Lida' };
  var atual = 3, historico = [];

  function pendente(q) { return !q.corrigida && (q.e === 'dupla' || q.e === 'fraca' || q.e === 'nao_lido'); }
  function emBranco(q) { return !q.corrigida && q.e === 'branco'; }

  /* ---------- Folha em SVG (geometria de apps/api/omr/layout.py: marcadores de 8 mm a 12 mm das bordas) ---------- */
  function el(nome, at, pai) { var n = document.createElementNS(NS, nome); for (var k in at) n.setAttribute(k, at[k]); if (pai) pai.appendChild(n); return n; }
  function txt(s, at, pai) { var t = el('text', at, pai); t.textContent = s; return t; }
  function posicao(i, a) { var col = i < 10 ? 0 : 1, lin = i % 10; return { x: 36 + col * 92 + a * 13, y: 112 + lin * 15 }; }
  function marcada(q, L) { return (q.e === 'lido' && q.v === L) || (q.e === 'dupla' && q.d.indexOf(L) >= 0); }
  function fraca(q, L) { return (q.e === 'fraca' && q.v === L) || (q.e === 'nao_lido' && L === 'B'); }

  function desenharFolha(svg, comDestaques) {
    while (svg.firstChild) svg.removeChild(svg.firstChild);
    el('rect', { x: 0, y: 0, width: 210, height: 297, fill: '#fff' }, svg);
    [[8, 8], [194, 8], [8, 281], [194, 281]].forEach(function (p) { el('rect', { x: p[0], y: p[1], width: 8, height: 8, fill: '#111' }, svg); });
    txt('Gabarito360 · Cartão-resposta', { x: 22, y: 26, 'font-size': 7, 'font-weight': 700, fill: '#16233a' }, svg);
    txt('Avaliação 2 — Matemática 9º ano', { x: 22, y: 36, 'font-size': 5, fill: '#555' }, svg);
    txt('Aluno: Ana Beatriz Souza · 9º A', { x: 22, y: 46, 'font-size': 5, fill: '#555' }, svg);
    var qr = el('g', { transform: 'translate(160,18)' }, svg);
    for (var r = 0; r < 10; r++) for (var c = 0; c < 10; c++)
      if ((r * 7 + c * 3 + r * c) % 3 === 0 || (r < 3 && c < 3) || (r < 3 && c > 6) || (r > 6 && c < 3)) el('rect', { x: c * 3, y: r * 3, width: 3, height: 3, fill: '#111' }, qr);
    txt('Marque assim: ●', { x: 22, y: 92, 'font-size': 5, fill: '#555' }, svg);
    for (var col = 0; col < 2; col++) LETRAS.forEach(function (L, a) { txt(L, { x: 36 + col * 92 + a * 13 - 1.8, y: 103, 'font-size': 4.5, fill: '#777' }, svg); });
    questoes.forEach(function (q, i) {
      var p0 = posicao(i, 0);
      txt(String(i + 1).padStart(2, '0'), { x: p0.x - 18, y: p0.y + 1.8, 'font-size': 5, fill: '#333', 'font-weight': 600 }, svg);
      LETRAS.forEach(function (L, a) {
        var p = posicao(i, a);
        el('circle', { cx: p.x, cy: p.y, r: 4.2, fill: marcada(q, L) ? '#1b2a6b' : fraca(q, L) ? '#9aa4c4' : '#fff', stroke: '#777', 'stroke-width': 0.5 }, svg);
      });
      if (!comDestaques) return;
      var caixa = { x: p0.x - 21, y: p0.y - 6.5, width: 86, height: 13, rx: 2, fill: 'none' };
      if (i === atual) { caixa.style = 'stroke:var(--accent);stroke-width:1.6'; el('rect', caixa, svg); }
      else if (pendente(q)) { caixa.style = 'stroke:var(--atencao);stroke-width:1;fill:var(--atencao);fill-opacity:.14'; if (q.e === 'fraca') caixa['stroke-dasharray'] = '1.5 1.2'; el('rect', caixa, svg); }
      else if (emBranco(q)) { caixa.style = 'stroke:#888;stroke-width:.6'; caixa['stroke-dasharray'] = '2 1.5'; el('rect', caixa, svg); }
      else if (q.corrigida) { caixa.style = 'stroke:var(--success);stroke-width:.8'; el('rect', caixa, svg); }
    });
    txt('Não escreva fora das bolhas.', { x: 22, y: 274, 'font-size': 4.5, fill: '#999' }, svg);
  }

  function desenharRecorte() {
    var s = document.getElementById('lr-recorte'); if (!s) return;
    while (s.firstChild) s.removeChild(s.firstChild);
    var q = questoes[atual];
    el('rect', { x: 0, y: 0, width: 120, height: 14, fill: '#fff' }, s);
    txt(String(atual + 1).padStart(2, '0'), { x: 2, y: 9, 'font-size': 6, 'font-weight': 700, fill: '#333' }, s);
    LETRAS.forEach(function (L, a) {
      el('circle', { cx: 24 + a * 20, cy: 7, r: 5, fill: marcada(q, L) ? '#1b2a6b' : fraca(q, L) ? '#9aa4c4' : '#fff', stroke: '#777', 'stroke-width': 0.6 }, s);
      txt(L, { x: 31 + a * 20, y: 9, 'font-size': 4.5, fill: '#888' }, s);
    });
    document.getElementById('lr-recorte-rotulo').setAttribute('aria-label', 'Linha da questão ' + (atual + 1) + ' na folha');
  }

  /* ---------- Painel de trabalho (HTML no padrão de correcao-revisao.js) ---------- */
  function leitura(q) {
    if (q.corrigida) return 'Resposta atual: ' + (q.v === 'BRANCO' ? 'em branco' : q.v === 'ANULADA' ? 'anulada' : q.v) + ' (corrigida por você)';
    if (q.e === 'dupla') return 'Leitura do sistema: ' + q.d.join(' e ') + ' (marcação incerta)';
    if (q.e === 'fraca') return 'Leitura do sistema: ' + q.v + ' (marcação fraca)';
    if (q.e === 'nao_lido') return 'O sistema não identificou marcação';
    if (q.e === 'branco') return 'Leitura do sistema: em branco';
    return 'Resposta atual: ' + q.v;
  }
  function valor(q) { return q.corrigida ? (q.v === 'BRANCO' ? '—' : q.v === 'ANULADA' ? 'N' : q.v) : q.e === 'lido' ? q.v : q.e === 'branco' ? '—' : '?'; }

  function botao(texto, classe, cod, pressionado) {
    var b = document.createElement('button');
    b.type = 'button'; b.className = 'btn ' + classe; b.textContent = texto;
    b.setAttribute('aria-pressed', String(!!pressionado));
    b.addEventListener('click', function () { corrigir(cod); });
    return b;
  }

  function atualizarPainel() {
    var lista = document.getElementById('lr-questoes'); if (!lista) return;
    lista.textContent = '';
    questoes.forEach(function (q, i) {
      var li = document.createElement('li'), b = document.createElement('button'), estado = q.corrigida ? 'Corrigida' : ESTADO[q.e];
      b.type = 'button'; b.className = 'lr-q';
      var marca = document.createElement('span'); marca.textContent = (i + 1) + ' · ' + valor(q);
      var sub = document.createElement('small'); sub.textContent = pendente(q) ? 'conferir' : q.corrigida ? 'corrigida' : q.e === 'branco' ? 'branco' : '';
      b.append(marca, sub);
      b.setAttribute('aria-label', 'Questão ' + (i + 1) + ': ' + valor(q).replace('—', 'sem resposta') + '. ' + estado);
      if (pendente(q)) b.classList.add('lr-pend');
      if (emBranco(q)) b.classList.add('lr-branco');
      if (q.corrigida) b.classList.add('lr-corrigida');
      if (i === atual) b.setAttribute('aria-current', 'true');
      b.addEventListener('click', function () { atual = i; tudo(); });
      li.appendChild(b); lista.appendChild(li);
    });
    var q = questoes[atual];
    document.getElementById('lr-trab-titulo').textContent = 'Questão ' + (atual + 1) + (pendente(q) ? ' · para conferir' : q.corrigida ? ' · corrigida' : '');
    document.getElementById('lr-leitura').textContent = leitura(q);
    var op = document.getElementById('lr-opcoes'), ex = document.getElementById('lr-opcoes-extra');
    op.textContent = ''; ex.textContent = '';
    LETRAS.forEach(function (L) {
      var atualResp = (q.corrigida || q.e === 'lido') && q.v === L;
      var b = botao(L, 'rev-resposta' + (atualResp ? ' rev-resposta-atual' : ''), L, atualResp);
      if (atualResp) b.setAttribute('aria-label', L + ', resposta atual');
      op.appendChild(b);
    });
    ex.appendChild(botao('Em branco', 'rev-resposta-extra', 'BRANCO', q.corrigida && q.v === 'BRANCO'));
    ex.appendChild(botao('Anulada', 'rev-resposta-extra', 'ANULADA', q.corrigida && q.v === 'ANULADA'));
    if (q.e === 'fraca' && !q.corrigida) ex.appendChild(botao('Manter leitura (' + q.v + ')', 'rev-resposta-extra', q.v, false));
    var np = questoes.filter(pendente).length, nb = questoes.filter(emBranco).length;
    document.getElementById('lr-progresso').textContent = (np === 1 ? '1 questão para conferir' : np + ' questões para conferir') + ' · ' + nb + ' em branco';
    document.getElementById('lr-voltar').disabled = !historico.length;
    document.getElementById('prox-pend').disabled = proximaPendencia() < 0;
  }

  function corrigir(cod) {
    var q = questoes[atual]; historico.push(atual);
    q.corrigida = true; q.v = cod;
    var aviso = document.getElementById('rev-salvamento'); aviso.textContent = 'Questão ' + (atual + 1) + ' corrigida.';
    var prox = proximaPendencia(); if (prox >= 0) atual = prox;
    tudo();
  }
  function proximaPendencia() {
    for (var k = 1; k <= questoes.length; k++) { var i = (atual + k) % questoes.length; if (pendente(questoes[i])) return i; }
    return -1;
  }

  /* ---------- Leitor da folha (rev-leitor de leitor-cartao.js): zoom e destino painel/dialog ---------- */
  var zoom = 1;
  /* dimensionarLeitor() de correcao-revisao.js: o painel termina acima do rodapé de versão */
  function dimensionarPainel() {
    var painel = document.getElementById('rev-documento-painel');
    if (!window.matchMedia('(min-width:1100px)').matches || !painel || !painel.offsetWidth) return;
    var caixa = painel.getBoundingClientRect(), escala = caixa.width / painel.offsetWidth;
    var rodape = document.querySelector('footer[aria-label="Versão do software"]');
    var limite = rodape ? Math.min(window.innerHeight, rodape.getBoundingClientRect().top) : window.innerHeight;
    painel.style.height = Math.max(260, (limite - caixa.top - 16) / escala) + 'px';
  }
  function ajustarFolha() {
    dimensionarPainel();
    var area = document.querySelector('#rev-leitor .rev-leitor-area'), svg = document.getElementById('folha-principal');
    if (!area || !svg || !area.clientWidth) return;
    var w = area.clientWidth - 32, h = area.clientHeight - 32;
    var base = h > 100 ? Math.min(w, h * 210 / 297) : w;
    var largura = Math.max(120, Math.round(base * zoom));
    svg.style.width = largura + 'px'; svg.style.height = Math.round(largura * 297 / 210) + 'px';
    document.querySelector('#rev-leitor [data-zoom]').textContent = zoom === 1 ? 'Ajustado' : Math.round(zoom * 100) + '%';
  }
  var desktop = window.matchMedia('(min-width:1100px)');
  var dialogo = null, origemDialogo = null;
  function destinoLeitor() {
    var leitor = document.getElementById('rev-leitor');
    var destino = document.getElementById(desktop.matches || !dialogo.open ? 'rev-documento-desktop' : 'rev-documento-mobile');
    if (leitor.parentNode !== destino) destino.appendChild(leitor);
    requestAnimationFrame(ajustarFolha);
  }

  function tudo() {
    document.querySelectorAll('svg[data-folha]').forEach(function (s) { desenharFolha(s, s.getAttribute('data-folha') === 'principal'); });
    desenharRecorte(); atualizarPainel(); ajustarFolha();
  }

  /* ---------- Visor .cam-tela: imagem simulada + guias iguais a correcao-camera.js ---------- */
  var PAG_W = 210, PAG_H = 297, MARCA = 8, CENTROS = [[12, 12], [198, 12], [12, 285], [198, 285]];
  var imagemFolha = null, modoCamera = null;
  function prepararImagemFolha(pronto) {
    var svg = document.createElementNS(NS, 'svg'); svg.setAttribute('xmlns', NS); svg.setAttribute('viewBox', '0 0 210 297');
    svg.setAttribute('width', '840'); svg.setAttribute('height', '1188');
    desenharFolha(svg, false);
    var img = new Image();
    img.onload = function () { imagemFolha = img; pronto(); };
    img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(new XMLSerializer().serializeToString(svg));
  }
  function desenharCamera() {
    var cv = document.getElementById('cam-canvas'); if (!modoCamera || !cv.clientWidth) return;
    var dpr = window.devicePixelRatio || 1, W = cv.clientWidth * dpr, H = cv.clientHeight * dpr;
    cv.width = W; cv.height = H;
    var ctx = cv.getContext('2d');
    var fundo = ctx.createRadialGradient(W / 2, H * 0.4, 10, W / 2, H / 2, Math.max(W, H) * 0.7);
    fundo.addColorStop(0, '#4a4f58'); fundo.addColorStop(1, '#16181d');
    ctx.fillStyle = fundo; ctx.fillRect(0, 0, W, H);
    var areaTop = H * 0.09, areaH = H * 0.74, fw = Math.min(W * 0.92, areaH * PAG_W / PAG_H), fh = fw * PAG_H / PAG_W;
    var f = { x: (W - fw) / 2, y: areaTop + (areaH - fh) / 2, w: fw, h: fh, mm: fw / PAG_W };
    var completa = modoCamera === 'webcam';
    if (imagemFolha) {
      ctx.save();
      ctx.translate(f.x + f.w / 2, f.y + f.h / 2);
      ctx.rotate(-0.006);
      ctx.shadowColor = 'rgba(0,0,0,.5)'; ctx.shadowBlur = 24 * dpr;
      ctx.drawImage(imagemFolha, -f.w / 2, -f.h / 2, f.w, f.h);
      ctx.restore();
      if (!completa) {
        /* Dedo sobre o canto inferior direito: o 4º quadrado não aparece */
        var cx = f.x + 198 * f.mm, cy = f.y + 290 * f.mm;
        ctx.save(); ctx.fillStyle = '#c99a7c'; ctx.shadowColor = 'rgba(0,0,0,.45)'; ctx.shadowBlur = 12 * dpr;
        ctx.beginPath(); ctx.ellipse(cx + 4 * f.mm, cy + 6 * f.mm, 13 * f.mm, 24 * f.mm, -0.5, 0, Math.PI * 2); ctx.fill(); ctx.restore();
      }
    }
    var cantosOk = completa ? [true, true, true, true] : [true, true, true, false], lado = MARCA * f.mm * 1.9;
    ctx.save();
    ctx.lineWidth = Math.max(2, f.mm * 0.6); ctx.setLineDash([f.mm * 3, f.mm * 2]);
    ctx.strokeStyle = 'rgba(255,255,255,.55)'; ctx.strokeRect(f.x, f.y, f.w, f.h); ctx.setLineDash([]);
    CENTROS.forEach(function (c, i) {
      var x = f.x + c[0] * f.mm, y = f.y + c[1] * f.mm, ok = cantosOk[i];
      ctx.strokeStyle = ok ? '#2ecc71' : '#ffcc00'; ctx.fillStyle = ok ? 'rgba(46,204,113,.25)' : 'rgba(255,204,0,.12)';
      ctx.lineWidth = Math.max(3, f.mm * 0.9);
      ctx.fillRect(x - lado / 2, y - lado / 2, lado, lado); ctx.strokeRect(x - lado / 2, y - lado / 2, lado, lado);
    });
    if (completa) {
      var r = f.mm * 14; ctx.strokeStyle = '#2ecc71'; ctx.lineWidth = f.mm * 2;
      ctx.beginPath(); ctx.arc(W / 2, f.y + f.h / 2, r, -Math.PI / 2, -Math.PI / 2 + Math.PI * 2 * 0.75); ctx.stroke();
    }
    ctx.restore();
  }
  function abrirCamera(modo) {
    modoCamera = modo;
    var tela = document.getElementById('cam-tela'), dica = document.getElementById('cam-dica'), ultimo = document.getElementById('cam-ultimo');
    tela.hidden = !modo;
    document.body.classList.toggle('lr-camera-aberta', !!modo);
    if (!modo) return;
    ultimo.textContent = '';
    if (modo === 'camera') {
      dica.textContent = 'Falta o canto inferior direito. Afaste um pouco o celular.';
      ultimo.innerHTML = '<b>Bruno Lima</b> · 9º A — folha salva. <span class="badge badge-success">Sem pendências</span>';
    } else {
      dica.textContent = 'Perfeito! Segure firme…';
      ultimo.innerHTML = '<b>Última folha: Carlos Eduardo Nunes</b> · 9º A — <span class="badge badge-success">Sem pendências</span> <span class="badge badge-muted">1 em branco</span>'
        + '<div class="page-sub" style="margin-top:4px;">A captura automática fica pausada enquanto há folha aberta no leitor.</div>'
        + '<div class="lr-cam-acoes"><button type="button" class="btn btn-sm btn-secondary" data-ir="leitor">Abrir no leitor</button><button type="button" class="btn btn-sm btn-primary" data-modal="salva">Salvar e continuar</button></div>';
    }
    if (imagemFolha) desenharCamera(); else prepararImagemFolha(desenharCamera);
    document.getElementById('cam-disparo').focus({ preventScroll: true });
  }

  /* ---------- Modais (componente .modal-backdrop do app; nunca confirm/alert) ---------- */
  var MODAIS = {
    salva: ['Folha salva', '<p>A folha de <strong>Ana Beatriz Souza</strong> foi salva. 13 folhas nesta sessão.</p><p class="page-sub">O envio ao servidor continua em segundo plano.</p>', [['Encerrar', 'btn-secondary', 'resumo'], ['Continuar', 'btn-primary', 'camera']]],
    pendencias: ['Salvar com pendências?', '<p>Ainda há <strong>2 questões para conferir</strong>.</p><p class="page-sub">Se salvar agora, elas vão para a revisão da correção. <span class="mk-pendente">D-11</span></p>', [['Voltar e conferir', 'btn-secondary'], ['Salvar com pendências', 'btn-primary', '#salva']]],
    sair: ['Sair do leitor?', '<p>4 folhas ainda não foram enviadas. Se sair agora, elas serão descartadas.</p>', [['Sair mesmo assim', 'btn-ghost', 'entrada'], ['Continuar no leitor', 'btn-primary']]],
    'outra-prova': ['Esta folha é de outra prova', '<p>O QR indica <strong>Avaliação 2 — Ciências 9º ano</strong>, da mesma escola.</p><p class="page-sub">Você pode ler esta folha naquela prova ou descartá-la.</p>', [['Descartar folha', 'btn-ghost'], ['Ler em Ciências', 'btn-primary']]],
    'ja-lida': ['Este aluno já tem leitura', '<p><strong>Bruno Lima</strong> já tem uma folha lida nesta prova.</p><p class="page-sub">A nova folha vira uma tentativa. A coordenação escolhe qual vale (FN-047).</p>', [['Descartar folha', 'btn-ghost'], ['Salvar como nova tentativa', 'btn-primary']]],
    'pdf-paginas': ['Este PDF tem 42 páginas', '<p>Ler as 42 páginas neste aparelho leva alguns minutos. Você pode pausar e retomar.</p>', [['Escolher outro arquivo', 'btn-ghost'], ['Ler as 42 páginas', 'btn-primary', 'lote']]],
    'cancelar-lote': ['Cancelar o lote?', '<p>36 páginas já foram lidas e salvas na sessão. As outras 84 não serão lidas.</p>', [['Continuar o lote', 'btn-secondary'], ['Cancelar o lote', 'btn-primary', 'resumo']]],
    'salvar-lote': ['Salvar 28 folhas sem pendências?', '<p>As 28 folhas sem pendências serão salvas e enviadas. As 6 para conferir continuam no lote.</p>', [['Voltar', 'btn-secondary'], ['Salvar 28 folhas', 'btn-primary']]],
    descartar: ['Descartar a folha?', '<p>A imagem e as correções desta folha saem da sessão. Nada foi enviado ainda.</p>', [['Voltar', 'btn-secondary'], ['Descartar folha', 'btn-primary']]],
    fechada: ['Correção fechada', '<p>A correção desta prova foi fechada por <strong>Paulo Andrade</strong> às 10:15. As folhas não salvas não podem ser enviadas.</p><p class="page-sub">Peça à coordenação para reabrir a correção.</p>', [['Entendi', 'btn-primary', 'resumo']]],
    'fechar-correcao': ['Fechar a correção?', '<p>Depois disso só a coordenação ou a direção poderá alterar, excluir, incluir ou reabrir.</p>', [['Cancelar', 'btn-secondary'], ['Fechar correção', 'btn-primary']]]
  };
  var ultimoFoco = null;
  function abrirModal(id) {
    var m = MODAIS[id]; if (!m) return;
    var fundo = document.getElementById('mk-modal');
    if (!fundo.classList.contains('open')) ultimoFoco = document.activeElement;
    document.getElementById('mk-modal-titulo').textContent = m[0];
    document.getElementById('mk-modal-corpo').innerHTML = m[1];
    var pe = document.getElementById('mk-modal-pe'); pe.textContent = '';
    m[2].forEach(function (b) {
      var x = document.createElement('button'); x.type = 'button'; x.className = 'btn ' + b[1]; x.textContent = b[0];
      x.addEventListener('click', function () {
        if (b[2] && b[2].charAt(0) === '#') { abrirModal(b[2].slice(1)); return; }
        fecharModal(); if (b[2]) irPara(b[2]);
      });
      pe.appendChild(x);
    });
    fundo.classList.add('open'); fundo.setAttribute('aria-hidden', 'false');
    var principal = pe.querySelector('.btn-primary'); if (principal) principal.focus();
  }
  function fecharModal() {
    var fundo = document.getElementById('mk-modal');
    if (!fundo.classList.contains('open')) return;
    fundo.classList.remove('open'); fundo.setAttribute('aria-hidden', 'true');
    if (ultimoFoco && ultimoFoco.isConnected) ultimoFoco.focus();
  }
  function prenderFoco(ev, caixa) {
    var itens = Array.prototype.filter.call(caixa.querySelectorAll('button:not([disabled]),[href],input,select,textarea,[tabindex="0"]'), function (n) { return n.getClientRects().length; });
    if (!itens.length) return;
    var primeiro = itens[0], ultimo = itens[itens.length - 1];
    if (ev.shiftKey && document.activeElement === primeiro) { ev.preventDefault(); ultimo.focus(); }
    else if (!ev.shiftKey && document.activeElement === ultimo) { ev.preventDefault(); primeiro.focus(); }
  }

  /* ---------- Tema (igual a app.js: data-theme no <html> e ícone no botão #theme-toggle) ---------- */
  var LUA = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  var SOL = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
  function aplicarTema(tema) {
    document.documentElement.setAttribute('data-theme', tema);
    var btn = document.getElementById('theme-toggle');
    btn.innerHTML = tema === 'dark' ? SOL : LUA;
    btn.title = tema === 'dark' ? 'Mudar para tema claro' : 'Mudar para tema escuro';
    btn.setAttribute('aria-label', btn.title);
    var mk = document.getElementById('mk-tema'); if (mk) mk.setAttribute('aria-pressed', String(tema === 'dark'));
    atualizarQuadro();
    if (modoCamera) desenharCamera();
  }
  function temaAtual() { return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'; }

  /* ---------- Navegação entre telas ---------- */
  var telaAtual = 'leitor';
  function irPara(tela) {
    if (!document.querySelector('.tela-mk[data-tela="' + tela + '"]')) tela = 'leitor';
    telaAtual = tela;
    var corpo = 'tela-correcao';
    document.querySelectorAll('.tela-mk').forEach(function (s) {
      var ativa = s.getAttribute('data-tela') === tela;
      s.classList.toggle('ativa', ativa);
      if (ativa) corpo = s.getAttribute('data-corpo') || corpo;
    });
    document.body.classList.remove('tela-correcao', 'tela-revisao');
    document.body.classList.add(corpo);
    var sel = document.getElementById('mk-tela'); if (sel) sel.value = tela;
    if (dialogo && dialogo.open) dialogo.close();
    abrirCamera(tela === 'camera' || tela === 'webcam' ? tela : null);
    if (tela === 'camera' || tela === 'webcam') document.querySelector('.tela-mk[data-tela="entrada"]').classList.add('ativa');
    if (!noQuadro) { var u = new URL(location.href); u.searchParams.set('tela', tela); history.replaceState(null, '', u); }
    atualizarQuadro();
    window.scrollTo(0, 0);
    requestAnimationFrame(ajustarFolha);
  }

  /* ---------- Seletor de aparelho: iframe na largura exata, escalado para caber ---------- */
  function aparelho() { var s = document.getElementById('mk-aparelho'); return s ? s.value : '0'; }
  function atualizarQuadro() {
    if (noQuadro) return;
    var quadro = document.getElementById('mk-quadro'), app = document.getElementById('mk-app'), saida = document.getElementById('mk-escala');
    var valorAp = aparelho(), comMoldura = valorAp !== '0';
    quadro.classList.toggle('mk-oculto', !comMoldura); app.classList.toggle('mk-oculto', comMoldura);
    if (!comMoldura) { saida.textContent = ''; return; }
    var dim = valorAp.split('x').map(Number), w = dim[0], h = dim[1];
    var iframe = document.getElementById('mk-iframe');
    var src = 'leitor360.html?quadro=1&tela=' + telaAtual + '&tema=' + temaAtual();
    if (iframe.getAttribute('data-src') !== src) { iframe.setAttribute('data-src', src); iframe.src = src; }
    var disponivel = Math.max(200, quadro.clientWidth - 34);
    var escala = Math.min(1, disponivel / w);
    iframe.style.width = w + 'px'; iframe.style.height = h + 'px';
    iframe.style.transform = escala < 1 ? 'scale(' + escala.toFixed(4) + ')' : '';
    quadro.style.height = Math.ceil(h * escala + 34) + 'px';
    /* Só o iframe é reduzido para caber nesta janela. A área logada não aplica zoom (.tw2-app fixa --escala:1). */
    saida.textContent = w + ' × ' + h + ' px · visualização ' + (escala < 1 ? 'reduzida a ' + Math.round(escala * 100) + '%' : 'em 100%');
  }

  /* ---------- Lateral (layout-preferencias.js e layout-interacoes.js) ----------
     Sem escolha gravada: compacta (80 px) abaixo de 1280 px e expandida (256 px) a partir de 1280 px.
     O mockup guarda a escolha só na memória da página, para as larguras do seletor ficarem previsíveis. */
  var escolhaMenu = null;
  function aplicarMenu() {
    var raiz = document.documentElement;
    raiz.classList.toggle('menu-recolhido', escolhaMenu ? escolhaMenu === 'recolhido' : !window.matchMedia('(min-width:1280px)').matches);
    var botao = document.getElementById('menu-recolher'), aberto = !raiz.classList.contains('menu-recolhido');
    botao.setAttribute('aria-expanded', String(aberto));
    botao.setAttribute('aria-label', aberto ? 'Recolher menu principal' : 'Expandir menu principal');
    botao.title = botao.getAttribute('aria-label');
  }

  document.addEventListener('DOMContentLoaded', function () {
    dialogo = document.getElementById('rev-cartao-modal');
    aplicarMenu();
    document.getElementById('menu-recolher').addEventListener('click', function () {
      escolhaMenu = document.documentElement.classList.contains('menu-recolhido') ? 'aberto' : 'recolhido';
      aplicarMenu(); requestAnimationFrame(function () { window.dispatchEvent(new Event('resize')); });
    });
    window.matchMedia('(min-width:1280px)').addEventListener('change', aplicarMenu);
    /* Folha "Mais" do celular (layout-interacoes.js) */
    var folha = document.getElementById('nav-folha'), mais = document.querySelector('.nav-inferior-mais');
    mais.addEventListener('click', function () { folha.showModal(); mais.setAttribute('aria-expanded', 'true'); folha.querySelector('[data-fechar]').focus(); });
    folha.querySelector('[data-fechar]').addEventListener('click', function () { folha.close(); });
    folha.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { folha.close(); }); });
    folha.addEventListener('close', function () { mais.setAttribute('aria-expanded', 'false'); if (!window.matchMedia('(min-width:768px)').matches) mais.focus(); });
    folha.addEventListener('click', function (e) {
      if (e.target !== folha) return;
      var r = folha.getBoundingClientRect();
      if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) folha.close();
    });
    window.matchMedia('(min-width:768px)').addEventListener('change', function (m) { if (m.matches && folha.open) folha.close(); });
    if (noQuadro) { document.getElementById('mk-barra').remove(); document.getElementById('mk-quadro').remove(); }
    aplicarTema(params.get('tema') === 'dark' ? 'dark' : 'light');
    tudo();
    var ap = params.get('aparelho'); if (ap && document.getElementById('mk-aparelho')) document.getElementById('mk-aparelho').value = ap;
    irPara(params.get('tela') || 'leitor');

    var sel = document.getElementById('mk-tela');
    if (sel) {
      sel.addEventListener('change', function () { irPara(sel.value); });
      document.getElementById('mk-aparelho').addEventListener('change', function () {
        var u = new URL(location.href); u.searchParams.set('aparelho', aparelho()); history.replaceState(null, '', u); atualizarQuadro();
      });
      document.getElementById('mk-tema').addEventListener('click', function () { aplicarTema(temaAtual() === 'dark' ? 'light' : 'dark'); });
    }
    document.getElementById('theme-toggle').addEventListener('click', function () { aplicarTema(temaAtual() === 'dark' ? 'light' : 'dark'); });
    window.addEventListener('scroll', function () { if (telaAtual === 'leitor') ajustarFolha(); }, { passive: true });
    window.addEventListener('resize', function () { atualizarQuadro(); ajustarFolha(); if (modoCamera) desenharCamera(); });

    document.addEventListener('click', function (ev) {
      var ir = ev.target.closest('[data-ir]'); if (ir) { ev.preventDefault(); fecharModal(); irPara(ir.getAttribute('data-ir')); return; }
      var mo = ev.target.closest('[data-modal]'); if (mo) { abrirModal(mo.getAttribute('data-modal')); return; }
      if (ev.target.closest('[data-fechar]') || ev.target.id === 'mk-modal') fecharModal();
      if (ev.target.closest('a[href="#"]')) ev.preventDefault();
    });

    /* Leitor da folha: zoom (leitor-cartao.js) e dialog no celular (correcao/revisao) */
    var leitor = document.getElementById('rev-leitor');
    leitor.querySelector('[data-mais]').addEventListener('click', function () { zoom = Math.min(3, zoom * 1.25); ajustarFolha(); });
    leitor.querySelector('[data-menos]').addEventListener('click', function () { zoom = Math.max(0.5, zoom * 0.8); ajustarFolha(); });
    leitor.querySelector('[data-ajustar]').addEventListener('click', function () { zoom = 1; ajustarFolha(); });
    document.getElementById('rev-ver-cartao').addEventListener('click', function () {
      origemDialogo = this; document.documentElement.style.overflow = 'hidden';
      dialogo.showModal(); destinoLeitor(); document.getElementById('rev-cartao-fechar').focus({ preventScroll: true });
    });
    document.getElementById('rev-cartao-fechar').addEventListener('click', function () { dialogo.close(); });
    dialogo.addEventListener('keydown', function (e) { if (e.key === 'Tab') prenderFoco(e, dialogo); });
    dialogo.addEventListener('close', function () {
      document.documentElement.style.overflow = ''; destinoLeitor();
      if (origemDialogo && origemDialogo.isConnected) origemDialogo.focus({ preventScroll: true });
    });
    desktop.addEventListener('change', function () { if (desktop.matches && dialogo.open) dialogo.close(); destinoLeitor(); });

    var salvar = document.getElementById('salvar-folha');
    salvar.addEventListener('click', function () { abrirModal(questoes.filter(pendente).length ? 'pendencias' : 'salva'); });
    document.getElementById('prox-pend').addEventListener('click', function () { var p = proximaPendencia(); if (p >= 0) { atual = p; tudo(); } });
    document.getElementById('lr-voltar').addEventListener('click', function () {
      if (!historico.length) return;
      atual = historico.pop(); var q = questoes[atual]; delete q.corrigida;
      if (q.e !== 'lido' && q.e !== 'fraca') delete q.v;
      if (q.e === 'fraca') q.v = 'C';
      tudo();
    });
    document.getElementById('cam-luz').addEventListener('click', function () { this.setAttribute('aria-pressed', String(this.getAttribute('aria-pressed') !== 'true')); });

    document.addEventListener('keydown', function (ev) {
      var fundo = document.getElementById('mk-modal');
      if (fundo.classList.contains('open')) {
        if (ev.key === 'Escape') { ev.preventDefault(); fecharModal(); }
        else if (ev.key === 'Tab') prenderFoco(ev, fundo.querySelector('.modal'));
        return;
      }
      if (telaAtual !== 'leitor' || document.querySelector('dialog[open]')) return;
      if (ev.target.closest && ev.target.closest('.rev-leitor, input, textarea, select')) return;
      if (ev.ctrlKey && ev.key === 'Enter') { ev.preventDefault(); salvar.click(); return; }
      if (ev.ctrlKey || ev.metaKey || ev.altKey) return;
      var k = ev.key.toUpperCase();
      if (LETRAS.indexOf(k) >= 0) corrigir(k);
      else if (k === '0') corrigir('BRANCO');
      else if (k === 'X') corrigir('ANULADA');
      else if (k === 'N') document.getElementById('prox-pend').click();
    });
  });
})();
