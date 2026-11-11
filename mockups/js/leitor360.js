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
  /* dimensionarLeitor() de correcao-revisao.js: o painel termina acima do rodapé de versão */
  function dimensionarPainel(painel) {
    if (!window.matchMedia('(min-width:1100px)').matches || !painel || !painel.offsetWidth) return;
    var caixa = painel.getBoundingClientRect(), escala = caixa.width / painel.offsetWidth;
    var rodape = document.querySelector('footer[aria-label="Versão do software"]');
    var limite = rodape ? Math.min(window.innerHeight, rodape.getBoundingClientRect().top) : window.innerHeight;
    painel.style.height = Math.max(260, (limite - caixa.top - 16) / escala) + 'px';
  }
  /* Ajuste à página e zoom de cada .rev-leitor visível (leitor-cartao.js: zoom guardado no próprio leitor) */
  function ajustarFolha() {
    document.querySelectorAll('.tela-mk.ativa .rev-documento-painel').forEach(dimensionarPainel);
    document.querySelectorAll('.rev-leitor').forEach(function (leitor) {
      var area = leitor.querySelector('.rev-leitor-area'), svg = leitor.querySelector('.rev-pdf-pagina > svg');
      if (!area || !svg || !area.clientWidth || !leitor.offsetParent) return;
      var zoom = Number(leitor.getAttribute('data-zoom') || 1);
      var w = area.clientWidth - 32, h = area.clientHeight - 32;
      var base = h > 100 ? Math.min(w, h * 210 / 297) : w;
      var largura = Math.max(120, Math.round(base * zoom));
      svg.style.width = largura + 'px'; svg.style.height = Math.round(largura * 297 / 210) + 'px';
      var out = leitor.querySelector('[data-zoom]'); if (out) out.textContent = zoom === 1 ? 'Ajustado' : Math.round(zoom * 100) + '%';
    });
  }
  var desktop = window.matchMedia('(min-width:1100px)');
  var dialogo = null, origemDialogo = null;
  function destinoLeitor() {
    var leitor = document.getElementById('rev-leitor');
    var destino = document.getElementById(desktop.matches || !dialogo.open || telaAtual !== 'leitor' ? 'rev-documento-desktop' : 'rev-documento-mobile');
    if (leitor.parentNode !== destino) destino.appendChild(leitor);
    requestAnimationFrame(ajustarFolha);
  }

  function tudo() {
    document.querySelectorAll('svg[data-folha]').forEach(function (s) { desenharFolha(s, s.getAttribute('data-folha') === 'principal'); });
    desenharRecorte(); atualizarPainel(); ajustarFolha();
  }

  /* =====================================================================
     Abertas, textos e mistas (FN-079 UC-11/12/13/14/16 e FN-080 UC-02 a 06)
     Folha e manuscritos simulados em SVG. A IA só lê; nada aqui calcula nota.
     ===================================================================== */
  var MANUS = '"Segoe Script","Bradley Hand","Comic Sans MS",cursive', TINTA = '#1b2a6b', LIMIAR = 0.6;
  var TIPO = { objetiva: ['◉', 'Objetiva'], texto: ['✎', 'Texto'], formula: ['∑', 'Fórmula'], desenho: ['△', 'Desenho'] };
  function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
  function pct(k) { return Math.round(k * 100) + '%'; }

  function novaFolhaMista(manual) {
    var obj = [['lido', 'B'], ['lido', 'C'], ['dupla', ['A', 'C']], ['lido', 'D'], ['fraca', 'E'], ['branco'], ['lido', 'A'], ['nao_lido']].map(function (o, i) {
      var r = { id: 'q' + (i + 1), n: i + 1, tipo: 'objetiva', e: o[0] };
      if (o[0] === 'dupla') r.d = o[1]; else if (o[1]) r.v = o[1];
      return r;
    });
    var abertas = [
      { id: 'q9', n: 9, tipo: 'aberta', esperado: 'texto', caixa: { x: 18, y: 126, w: 174, h: 38 }, enunciado: '9. Explique por que todo quadrado é um retângulo.', estado: 'transcrita',
        trechos: [{ t: 'texto', c: 'Porque o quadrado tem ', k: .96, l: 0 }, { t: 'texto', c: 'quatro ângulos retos', k: .44, l: 0 }, { t: 'texto', c: ', e todo', k: .91, l: 0 },
          { t: 'texto', c: ' quadrilátero com quatro ângulos retos é um ', k: .9, l: 1 }, { t: 'texto', c: 'retângulo', k: .38, l: 1 }, { t: 'texto', c: '.', k: .99, l: 1 }] },
      { id: 'q10', n: 10, tipo: 'aberta', esperado: 'formula', caixa: { x: 18, y: 168, w: 174, h: 34 }, enunciado: '10. Resolva x² + 5x + 6 = 0.', estado: 'transcrita',
        trechos: [{ t: 'texto', c: 'Por Bhaskara: ', k: .93, l: 0 }, { t: 'formula', c: '\\Delta = 5^2 - 4 \\cdot 1 \\cdot 6 = 1', m: 'Δ = 5² − 4·1·6 = 1', k: .9, l: 0 },
          { t: 'formula', c: 'x = \\frac{-5 \\pm 1}{2', m: 'x = (−5 ± 1) / 2', k: .35, l: 1 }, { t: 'texto', c: '  então x = −2 ou x = −3', k: .86, l: 1 }] },
      { id: 'q11', n: 11, tipo: 'aberta', esperado: 'desenho', caixa: { x: 18, y: 206, w: 84, h: 66 }, enunciado: '11. Desenhe um triângulo retângulo\ne indique a hipotenusa.', estado: 'transcrita',
        trechos: [{ t: 'desenho', k: .92 }, { t: 'texto', c: 'hipotenusa', k: .81, rotulo: true }], descricao: '' },
      { id: 'q12', n: 12, tipo: 'aberta', esperado: 'texto', caixa: { x: 108, y: 206, w: 84, h: 66 }, enunciado: '12. Cite duas propriedades do losango.', estado: 'transcrevendo', progresso: 60,
        trechos: [{ t: 'texto', c: 'Os quatro lados são ', k: 1, l: 0 }, { t: 'texto', c: 'iguais e as diagonais ', k: 1, l: 1 }, { t: 'texto', c: 'são perpendiculares.', k: 1, l: 2 }] }
    ];
    abertas.forEach(function (a) {
      a.trechos.forEach(function (t) { t.valor = t.c; });
      a.texto = a.trechos.filter(function (t) { return t.t === 'texto' && !t.rotulo; }).map(function (t) { return t.c; }).join('');
      if (manual) { a.manual = true; a.texto = ''; if (a.estado === 'transcrevendo') a.estado = 'transcrita'; }
    });
    return { aluno: 'Ana Beatriz Souza', turma: '9º A', regioes: obj.concat(abertas) };
  }
  var MODELOS = { mista: novaFolhaMista(false), manual: novaFolhaMista(true) };

  function pendObj(r) { return r.tipo === 'objetiva' && !r.corrigida && (r.e === 'dupla' || r.e === 'fraca' || r.e === 'nao_lido'); }
  function baixos(r) { return r.manual ? [] : (r.trechos || []).filter(function (t) { return t.k < LIMIAR && !t.ok; }); }
  function pendAberta(r) {
    if (r.tipo !== 'aberta' || r.conferida || r.estado === 'ilegivel' || r.estado === 'transcrevendo') return false;
    return r.manual ? r.esperado !== 'desenho' : baixos(r).length > 0;
  }
  function pend(r) { return pendObj(r) || pendAberta(r); }
  function porId(m, id) { for (var i = 0; i < m.regioes.length; i++) if (m.regioes[i].id === id) return m.regioes[i]; return null; }

  /* ---- Desenho da folha mista: cabeçalho, objetivas por bolha e caixas de resposta manuscrita ---- */
  function cabecalho(svg, titulo, aluno, paginaTxt) {
    el('rect', { x: 0, y: 0, width: 210, height: 297, fill: '#fff' }, svg);
    [[8, 8], [194, 8], [8, 281], [194, 281]].forEach(function (p) { el('rect', { x: p[0], y: p[1], width: 8, height: 8, fill: '#111' }, svg); });
    txt('Gabarito360 · Folha de respostas', { x: 22, y: 22, 'font-size': 6, 'font-weight': 700, fill: '#16233a' }, svg);
    txt(titulo, { x: 22, y: 30, 'font-size': 4.6, fill: '#333' }, svg);
    txt(aluno + (paginaTxt ? ' · ' + paginaTxt : ''), { x: 22, y: 37, 'font-size': 4.2, fill: '#555' }, svg);
    var qr = el('g', { transform: 'translate(160,16)' }, svg);
    for (var r = 0; r < 10; r++) for (var c = 0; c < 10; c++)
      if ((r * 5 + c * 7 + r * c) % 3 === 0 || (r < 3 && c < 3) || (r < 3 && c > 6) || (r > 6 && c < 3)) el('rect', { x: c * 3, y: r * 3, width: 3, height: 3, fill: '#111' }, qr);
  }
  function linhaObj(i) { var col = i < 4 ? 0 : 1, row = i % 4; return { x: 40 + col * 92, y: 70 + row * 12 }; }
  function caixaObj(i) { var p = linhaObj(i); return { x: p.x - 20, y: p.y - 6, w: 74, h: 12 }; }
  function marcar(svg, cx, opts, estilo, tracejado) {
    var a = { x: cx.x, y: cx.y, width: cx.w, height: cx.h, rx: 2, fill: 'none', style: estilo };
    if (tracejado) a['stroke-dasharray'] = tracejado;
    el('rect', a, opts.camada || svg);
  }

  function desenharMista(svg, m, opts) {
    while (svg.firstChild) svg.removeChild(svg.firstChild);
    cabecalho(svg, 'Avaliação 3 — Matemática 9º ano', 'Aluna: ' + m.aluno + ' · ' + m.turma);
    txt('Questões 1 a 8: marque uma alternativa.', { x: 22, y: 52, 'font-size': 4, fill: '#555' }, svg);
    var camada = el('g', {}, svg); opts.camada = camada;
    m.regioes.forEach(function (r, i) {
      if (r.tipo === 'objetiva') {
        var p = linhaObj(i);
        txt(String(r.n).padStart(2, '0'), { x: p.x - 16, y: p.y + 1.8, 'font-size': 4.6, fill: '#333', 'font-weight': 600 }, svg);
        LETRAS.forEach(function (L, a) {
          var marc = (r.e === 'lido' && r.v === L) || (r.e === 'dupla' && r.d.indexOf(L) >= 0), fr = (r.e === 'fraca' && r.v === L) || (r.e === 'nao_lido' && L === 'B');
          el('circle', { cx: p.x + a * 12, cy: p.y, r: 3.8, fill: marc ? '#1b2a6b' : fr ? '#9aa4c4' : '#fff', stroke: '#777', 'stroke-width': 0.5 }, svg);
          if (i < 2) txt(L, { x: p.x + a * 12 - 1.5, y: 61, 'font-size': 3.8, fill: '#777' }, svg);
        });
        if (opts.destaques) {
          var cx = caixaObj(i);
          if (r.id === opts.atual) marcar(svg, cx, opts, 'stroke:var(--accent);stroke-width:1.4');
          else if (pendObj(r)) marcar(svg, cx, opts, 'stroke:var(--atencao);stroke-width:1;fill:var(--atencao);fill-opacity:.14', r.e === 'fraca' ? '1.5 1.2' : null);
          else if (r.e === 'branco' && !r.corrigida) marcar(svg, cx, opts, 'stroke:#888;stroke-width:.6', '2 1.5');
        }
        return;
      }
      var c = r.caixa;
      el('rect', { x: c.x, y: c.y, width: c.w, height: c.h, rx: 1.5, fill: 'none', stroke: '#9aa3b2', 'stroke-width': 0.5 }, svg);
      r.enunciado.split('\n').forEach(function (l, j) { txt(l, { x: c.x + 3, y: c.y + 6 + j * 4.4, 'font-size': 3.6, fill: '#444' }, svg); });
      if (r.esperado === 'desenho') {
        var x = c.x, y = c.y;
        el('path', { d: 'M' + (x + 12) + ' ' + (y + 58) + ' L' + (x + 12) + ' ' + (y + 20) + ' L' + (x + 70) + ' ' + (y + 58) + ' Z', fill: 'none', stroke: TINTA, 'stroke-width': 0.9, 'stroke-linejoin': 'round' }, svg);
        el('path', { d: 'M' + (x + 12) + ' ' + (y + 53) + ' h5 v5', fill: 'none', stroke: TINTA, 'stroke-width': 0.6 }, svg);
        el('path', { d: 'M' + (x + 52) + ' ' + (y + 30) + ' L' + (x + 44) + ' ' + (y + 38), fill: 'none', stroke: TINTA, 'stroke-width': 0.5 }, svg);
        var rot = txt('hipotenusa', { x: x + 46, y: y + 28, 'font-size': 4.4, fill: TINTA, 'font-family': MANUS }, svg);
        rot.setAttribute('data-trecho', r.id + ':1');
      } else {
        var linhas = {};
        r.trechos.forEach(function (t, k) {
          var l = t.l || 0;
          if (!linhas[l]) linhas[l] = txt('', { x: c.x + 5, y: c.y + 17 + l * (r.esperado === 'formula' ? 11 : 10), 'font-size': 4.6, fill: TINTA, 'font-family': MANUS }, svg);
          var s = document.createElementNS(NS, 'tspan'); s.textContent = (t.m || t.c).replace(/^ +/, linhas[l].childNodes.length ? ' ' : '');
          s.setAttribute('data-trecho', r.id + ':' + k); linhas[l].appendChild(s);
        });
      }
      if (opts.destaques) {
        var cx2 = { x: c.x - 1, y: c.y - 1, w: c.w + 2, h: c.h + 2 };
        if (r.id === opts.atual) marcar(svg, cx2, opts, 'stroke:var(--accent);stroke-width:1.4');
        else if (pendAberta(r)) marcar(svg, cx2, opts, 'stroke:var(--atencao);stroke-width:1;fill:var(--atencao);fill-opacity:.08', '3 1.5');
      }
    });
    if (opts.trechos) destacarTrechos(svg, porId(m, opts.trechos), opts.trechoAtual);
  }
  /* Trechos de baixa confiança: retângulo decorativo atrás do texto (a lista ao lado é o equivalente acessível) */
  function destacarTrechos(svg, r, atualK) {
    if (!r || r.manual || !r.trechos) return;
    var camada = el('g', {}, null); svg.insertBefore(camada, svg.childNodes[1] || null);
    r.trechos.forEach(function (t, k) {
      if (!(t.k < LIMIAR) || t.ok) return;
      var no = svg.querySelector('[data-trecho="' + r.id + ':' + k + '"]'); if (!no || !no.getBBox) return;
      var b; try { b = no.getBBox(); } catch (e) { return; }
      if (!b.width) return;
      el('rect', { x: b.x - 0.8, y: b.y - 0.4, width: b.width + 1.6, height: b.height + 0.8, rx: 1,
        style: k === atualK ? 'fill:var(--atencao);fill-opacity:.25;stroke:var(--accent);stroke-width:.7' : 'fill:var(--atencao);fill-opacity:.18;stroke:var(--atencao);stroke-width:.4;stroke-dasharray:1.2 .8' }, camada);
    });
  }

  /* ---- LaTeX simples → MathML (sem biblioteca): \frac, \sqrt, ^, \cdot, \pm, \Delta… ---- */
  var MML = 'http://www.w3.org/1998/Math/MathML';
  function mk(n, t) { var e = document.createElementNS(MML, n); if (t != null) e.textContent = t; return e; }
  function latexParaMathML(s) {
    var i = 0;
    function erro() { throw new Error('notação'); }
    function grupo() { if (s[i] !== '{') erro(); i++; var r = seq('}'); if (s[i] !== '}') erro(); i++; return r; }
    function atomo() {
      var c = s[i];
      if (c === ' ') { i++; return null; }
      if (c === '{') return grupo();
      if (c === '\\') {
        var m = /^\\([a-zA-Z]+)/.exec(s.slice(i)); if (!m) erro(); i += m[0].length;
        if (m[1] === 'frac') { var f = mk('mfrac'); f.append(grupo(), grupo()); return f; }
        if (m[1] === 'sqrt') { var q = mk('msqrt'); q.append(grupo()); return q; }
        var sim = { cdot: '·', pm: '±', Delta: 'Δ', times: '×', div: '÷', le: '≤', ge: '≥', neq: '≠' }[m[1]]; if (!sim) erro();
        return mk(m[1] === 'Delta' ? 'mi' : 'mo', sim);
      }
      if (/[0-9]/.test(c)) { var n = /^[0-9]+([.,][0-9]+)?/.exec(s.slice(i))[0]; i += n.length; return mk('mn', n); }
      if (/[a-zA-Z]/.test(c)) { i++; return mk('mi', c); }
      if ('+-=()<>−'.indexOf(c) >= 0) { i++; return mk('mo', c === '-' ? '−' : c); }
      erro();
    }
    function seq(fim) {
      var row = mk('mrow');
      while (i < s.length && s[i] !== fim) {
        if (s[i] === '}') erro();
        var a = atomo(); if (!a) continue;
        if (s[i] === '^') { i++; var e = s[i] === '{' ? grupo() : atomo(); if (!e) erro(); var sup = mk('msup'); sup.append(a, e); a = sup; }
        row.appendChild(a);
      }
      return row;
    }
    var corpo = seq(null); if (i < s.length) erro();
    var math = mk('math'); math.setAttribute('display', 'block'); math.appendChild(corpo); return math;
  }
  function previaFormula(alvo, valor) {
    alvo.textContent = '';
    try { alvo.appendChild(latexParaMathML(valor)); alvo.removeAttribute('role'); alvo.className = 'lr-previa-formula'; }
    catch (e) { alvo.className = 'g-aviso atencao lr-previa-erro'; alvo.setAttribute('role', 'alert'); alvo.textContent = 'Não foi possível mostrar a prévia. Confira as chaves { } e os comandos da notação.'; }
  }

  /* ---- Estação (rev-estacao) das telas B, C, D, F e H2 ---- */
  var editado = false;
  function painelHtml(id, aluno) {
    return '<aside class="card rev-documento-painel" aria-labelledby="' + id + '-p"><header class="rev-modal-cabecalho"><div><h2 id="' + id + '-p">Folha completa</h2><p>' + esc(aluno) + '</p></div></header>'
      + '<div class="rev-documento-destino"><div class="rev-leitor"><div class="rev-leitor-ferramentas" aria-label="Controles da folha">'
      + '<button type="button" class="btn btn-ghost" data-menos aria-label="Diminuir folha">−</button><output data-zoom aria-label="Ampliação da folha">Ajustado</output>'
      + '<button type="button" class="btn btn-ghost" data-mais aria-label="Ampliar folha">+</button><button type="button" class="btn btn-ghost" data-ajustar>Ajustar à página</button></div>'
      + '<div class="rev-leitor-area" tabindex="0" role="region" aria-label="Folha completa"><div class="rev-pdf-pagina rev-imagem-restrita"><svg data-ajuste viewBox="0 0 210 297" role="img" aria-label="Folha com objetivas e respostas abertas destacadas. A lista de regiões é o equivalente acessível."></svg></div></div></div></div></aside>'
      + '<div class="card card-pad rev-cartao"><div class="lr-conteudo"></div></div>';
  }
  function modeloDe(cont) { return cont.hasAttribute('data-manual') ? MODELOS.manual : MODELOS.mista; }

  function renderEstacao(cont, foco) {
    var m = modeloDe(cont), id = 'est-' + cont.closest('.tela-mk').getAttribute('data-tela');
    if (!cont.firstChild) cont.innerHTML = painelHtml(id, m.aluno + ' · ' + m.turma);
    var r = porId(m, cont.getAttribute('data-atual') || cont.getAttribute('data-estacao-mista'));
    cont.setAttribute('data-atual', r.id);
    var trechoAtual = cont.hasAttribute('data-trecho') ? Number(cont.getAttribute('data-trecho')) : -1;
    var no = cont.querySelector('.lr-conteudo');
    var objP = m.regioes.filter(pendObj).length, abP = m.regioes.filter(pendAberta).length;
    var manual = cont.hasAttribute('data-manual');
    var h = '<div class="row-between rev-topo"><div><b>' + esc(m.aluno) + '</b><div class="exam-meta">' + esc(m.turma) + ' · identificada pelo QR</div></div>'
      + '<div class="rev-progresso" role="status">' + (manual ? (abP === 1 ? '1 resposta aberta para transcrever' : abP + ' respostas abertas para transcrever')
        : (objP === 1 ? '1 objetiva' : objP + ' objetivas') + ' para conferir · ' + (abP === 1 ? '1 resposta aberta' : abP + ' respostas abertas') + ' para conferir') + '</div></div>';
    h += '<h2 class="lr-grupo"><span aria-hidden="true">◉</span> Objetivas (8)</h2><ul class="lr-questoes" aria-label="Objetivas">';
    m.regioes.forEach(function (q) {
      if (q.tipo !== 'objetiva') return;
      var val = q.corrigida ? (q.v === 'BRANCO' ? '—' : q.v === 'ANULADA' ? 'N' : q.v) : q.e === 'lido' ? q.v : q.e === 'branco' ? '—' : '?';
      var est = q.corrigida ? 'corrigida' : pendObj(q) ? 'conferir' : q.e === 'branco' ? 'branco' : '';
      h += '<li><button type="button" class="lr-q' + (pendObj(q) ? ' lr-pend' : '') + (q.e === 'branco' && !q.corrigida ? ' lr-branco' : '') + (q.corrigida ? ' lr-corrigida' : '') + '" data-regiao="' + q.id + '"'
        + (q.id === r.id ? ' aria-current="true"' : '') + ' aria-label="Questão ' + q.n + ', objetiva: ' + val.replace('—', 'sem resposta') + '. ' + (est || 'lida') + '"><span>' + q.n + ' · ' + val + '</span><small>' + est + '</small></button></li>';
    });
    h += '</ul><h2 class="lr-grupo"><span aria-hidden="true">✎</span> Abertas (4)</h2><ul class="lr-regioes" aria-label="Respostas abertas">';
    m.regioes.forEach(function (q) {
      if (q.tipo !== 'aberta') return;
      var tp = TIPO[q.esperado], est = estadoAberta(q);
      h += '<li><button type="button" class="lr-regiao' + (pendAberta(q) ? ' lr-pend' : '') + (q.conferida ? ' lr-corrigida' : '') + '" data-regiao="' + q.id + '"' + (q.id === r.id ? ' aria-current="true"' : '')
        + '><span class="lr-tipo" aria-hidden="true">' + tp[0] + '</span><span class="lr-regiao-txt"><b>Questão ' + q.n + ' · ' + tp[1] + '</b><small>' + esc(est) + '</small></span></button></li>';
    });
    h += '</ul><div class="lr-legenda" aria-hidden="true"><span><i class="lr-leg lr-leg-pend"></i>Para conferir</span><span><i class="lr-leg lr-leg-branco"></i>Em branco</span><span><i class="lr-leg lr-leg-ok"></i>Conferida</span></div>';
    h += '<div class="rev-trabalho">' + (r.tipo === 'objetiva' ? trabalhoObjetiva(r) : trabalhoAberta(r, manual, trechoAtual)) + '</div>';
    h += '<div class="lr-salvar"><button type="button" class="btn btn-ghost" data-modal="descartar">Descartar folha</button><button type="button" class="btn btn-primary" data-acao="salvar">Salvar folha</button></div>';
    no.innerHTML = h;
    /* valores editáveis entram por propriedade (texto escapado, nunca HTML) */
    no.querySelectorAll('[data-campo]').forEach(function (c) {
      var k = c.getAttribute('data-campo');
      c.value = k === 'texto' ? r.texto : k === 'descricao' ? r.descricao : r.trechos[Number(k)].valor;
    });
    no.querySelectorAll('[data-previa]').forEach(function (p) { previaFormula(p, r.trechos[Number(p.getAttribute('data-previa'))].valor); });
    var folha = cont.querySelector('svg[data-ajuste]');
    desenharMista(folha, m, { destaques: true, atual: r.id, trechos: r.id, trechoAtual: trechoAtual });
    var rec = no.querySelector('svg[data-recorte]');
    if (rec) desenharMista(rec, m, { destaques: false, trechos: r.id, trechoAtual: trechoAtual });
    if (foco) { var f = no.querySelector(foco); if (f) f.focus({ preventScroll: false }); }
    requestAnimationFrame(ajustarFolha);
  }
  function estadoAberta(q) {
    if (q.estado === 'transcrevendo') return 'Transcrevendo… ' + q.progresso + '%';
    if (q.estado === 'ilegivel') return 'Ilegível · só o recorte';
    if (q.conferida) return q.esperado === 'desenho' ? 'Desenho guardado · conferido' : 'Conferida';
    if (q.esperado === 'desenho') return 'Desenho guardado';
    if (q.manual) return 'Transcrição indisponível · digitar';
    var n = baixos(q).length;
    return n ? (n === 1 ? '1 trecho para conferir' : n + ' trechos para conferir') : 'Sem trechos de baixa confiança';
  }
  function viewBoxDe(r) {
    if (r.tipo === 'objetiva') { var i = r.n - 1, c = caixaObj(i); return c.x + ' ' + c.y + ' ' + c.w + ' ' + c.h; }
    return (r.caixa.x - 1) + ' ' + (r.caixa.y - 1) + ' ' + (r.caixa.w + 2) + ' ' + (r.caixa.h + 2);
  }
  function recorte(r, rotulo) {
    return '<div id="rev-previa" class="rev-previa rev-imagem-restrita"><div class="rev-recorte lr-recorte" role="img" aria-label="' + esc(rotulo) + '"><svg data-recorte viewBox="' + viewBoxDe(r) + '" aria-hidden="true"></svg></div></div>';
  }
  var VER_FOLHA = '<div class="rev-visualizar"><button type="button" class="btn btn-ghost btn-sm" data-ver-folha>Ver folha inteira</button></div>';
  function trabalhoObjetiva(r) {
    var leit = r.corrigida ? 'Resposta atual: ' + (r.v === 'BRANCO' ? 'em branco' : r.v === 'ANULADA' ? 'anulada' : r.v) + ' (corrigida por você)'
      : r.e === 'dupla' ? 'Leitura do sistema: ' + r.d.join(' e ') + ' (marcação incerta)' : r.e === 'fraca' ? 'Leitura do sistema: ' + r.v + ' (marcação fraca)'
      : r.e === 'nao_lido' ? 'O sistema não identificou marcação' : r.e === 'branco' ? 'Leitura do sistema: em branco' : 'Resposta atual: ' + r.v;
    var h = '<h2 class="lr-trab-titulo" tabindex="-1"><span aria-hidden="true">◉</span> Questão ' + r.n + ' · Objetiva' + (pendObj(r) ? ' · para conferir' : '') + '</h2>'
      + recorte(r, 'Linha da questão ' + r.n + ' na folha') + '<div class="rev-respostas">' + VER_FOLHA + '<p class="rev-leitura">' + leit + '</p><div class="rev-opcoes" style="--rev-alternativas:5">';
    LETRAS.forEach(function (L) { var at = (r.corrigida || r.e === 'lido') && r.v === L; h += '<button type="button" class="btn rev-resposta' + (at ? ' rev-resposta-atual' : '') + '" data-resp="' + L + '" aria-pressed="' + at + '">' + L + '</button>'; });
    h += '</div><div class="rev-opcoes rev-opcoes-extra"><button type="button" class="btn rev-resposta-extra" data-resp="BRANCO">Em branco</button><button type="button" class="btn rev-resposta-extra" data-resp="ANULADA">Anulada</button></div>'
      + '<div class="rev-acoes"><button type="button" class="btn btn-secondary btn-sm" data-acao="prox-pend">Próxima pendência</button></div>'
      + '<p class="rev-teclas">Teclado: A a E respondem, 0 deixa em branco, X anula e N vai à próxima pendência.</p></div>';
    return h;
  }
  function trabalhoAberta(r, manual, trechoAtual) {
    var tp = TIPO[r.esperado];
    var h = '<h2 class="lr-trab-titulo" tabindex="-1"><span aria-hidden="true">' + tp[0] + '</span> Questão ' + r.n + ' · ' + tp[1] + (pendAberta(r) ? ' · para conferir' : '') + '</h2>'
      + recorte(r, 'Recorte da resposta manuscrita da questão ' + r.n) + '<div class="rev-respostas">' + VER_FOLHA;
    if (r.estado === 'transcrevendo') {
      return h + '<p class="rev-leitura" role="status">Transcrevendo… ' + r.progresso + '%. As objetivas e as outras respostas já podem ser conferidas.</p>'
        + '<div class="lr-trilho lr-trilho-claro" role="progressbar" aria-label="Transcrição da questão ' + r.n + '" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' + r.progresso + '"><div style="width:' + r.progresso + '%"></div></div>'
        + '<div class="rev-acoes"><button type="button" class="btn btn-secondary btn-sm" data-acao="prox-pend">Próxima pendência</button></div></div>';
    }
    if (r.estado === 'ilegivel') {
      return h + '<div class="g-aviso atencao" role="status">Marcada como ilegível. Só o recorte será guardado, sem texto.</div>'
        + '<div class="lr-linha-acoes"><button type="button" class="btn btn-secondary" data-acao="desfazer-ilegivel">Desfazer ilegível</button><button type="button" class="btn btn-secondary btn-sm" data-acao="prox-pend">Próxima pendência</button></div></div>';
    }
    h += manual ? '<div class="g-aviso atencao" role="status">Este aparelho não transcreve. Digite o que o aluno escreveu, olhando o recorte.</div>'
      : '<p class="rev-leitura">Transcrição da IA · modelo 0.3.1 · a IA só lê; não corrige.</p>';
    if (r.conferida) h += '<div class="g-aviso sucesso" role="status">Transcrição conferida. Você ainda pode editar.</div>';
    if (r.esperado === 'texto') {
      h += '<div class="field"><label for="campo-' + r.id + '">' + (manual ? 'Transcrição manual' : 'Texto transcrito') + '</label><textarea class="input" rows="4" id="campo-' + r.id + '" data-campo="texto"></textarea></div>';
    } else if (r.esperado === 'formula') {
      r.trechos.forEach(function (t, k) {
        var baixo = t.k < LIMIAR && !t.ok && !manual;
        if (t.t === 'formula') {
          h += '<div class="field lr-trecho-campo' + (baixo ? ' lr-baixo' : '') + '"><label for="campo-' + r.id + '-' + k + '">Fórmula ' + (k) + ' (LaTeX)' + (baixo ? ' <span class="badge badge-warn">! Confiança ' + pct(t.k) + '</span>' : '') + '</label>'
            + '<input class="input lr-latex" id="campo-' + r.id + '-' + k + '" data-campo="' + k + '" spellcheck="false" autocomplete="off"><div class="field-help">Prévia:</div><div data-previa="' + k + '" aria-live="polite"></div></div>';
        } else {
          h += '<div class="field lr-trecho-campo"><label for="campo-' + r.id + '-' + k + '">Texto</label><input class="input" id="campo-' + r.id + '-' + k + '" data-campo="' + k + '"></div>';
        }
      });
    } else if (r.esperado === 'desenho') {
      h += '<p class="lr-desenho-estado"><span class="badge badge-success"><span aria-hidden="true">△</span> Desenho preservado</span> O recorte é a fonte do desenho. A descrição é opcional e nunca substitui o recorte.</p>'
        + '<div class="field"><label for="campo-' + r.id + '-1">Texto escrito no desenho</label><input class="input" id="campo-' + r.id + '-1" data-campo="1"></div>'
        + '<div class="field"><label for="campo-' + r.id + '-d">Descrição (opcional)</label><textarea class="input" rows="2" id="campo-' + r.id + '-d" data-campo="descricao" placeholder="Ex.: triângulo retângulo com a hipotenusa indicada"></textarea></div>';
    }
    var bx = baixos(r);
    if (bx.length && r.esperado === 'texto') {
      h += '<h3 class="lr-sub">Trechos para conferir (' + bx.length + ')</h3><ul class="lr-trechos">';
      r.trechos.forEach(function (t, k) {
        if (!(t.k < LIMIAR) || t.ok) return;
        h += '<li' + (k === trechoAtual ? ' aria-current="true"' : '') + '><span class="badge badge-warn"><span aria-hidden="true">!</span> Confiança ' + pct(t.k) + '</span> <q>' + esc(t.c.trim()) + '</q>'
          + '<span class="lr-trecho-acoes"><button type="button" class="btn btn-sm btn-ghost" data-ir-trecho="' + k + '">Ir ao trecho</button><button type="button" class="btn btn-sm btn-secondary" data-ok-trecho="' + k + '">Está certo</button></span></li>';
      });
      h += '</ul>';
    }
    h += '<div class="lr-linha-acoes">' + (bx.length && r.esperado === 'texto' ? '<button type="button" class="btn btn-secondary" data-acao="prox-trecho">Próximo trecho para conferir</button>' : '')
      + (r.esperado !== 'desenho' ? '<button type="button" class="btn btn-ghost" data-acao="ilegivel">Marcar como ilegível</button>' : '') + '</div>'
      + '<div class="rev-acoes"><button type="button" class="btn btn-secondary btn-sm" data-acao="prox-pend">Próxima pendência</button><button type="button" class="btn btn-primary btn-sm" data-acao="confirmar">' + (r.esperado === 'desenho' ? 'Confirmar desenho' : 'Confirmar texto') + '</button></div>'
      + '<p id="lr-anuncio-' + r.id + '" class="rev-salvamento" role="status" aria-live="polite"></p></div>';
    return h;
  }
  function proxPendMista(m, desde) {
    var ids = m.regioes.map(function (q) { return q.id; }), i0 = ids.indexOf(desde);
    for (var k = 1; k <= ids.length; k++) { var q = m.regioes[(i0 + k) % ids.length]; if (pend(q)) return q.id; }
    return null;
  }
  function selecionarTrecho(cont, r, k) {
    cont.setAttribute('data-trecho', k); renderEstacao(cont);
    var ta = cont.querySelector('[data-campo="texto"]'), alvo = r.trechos[k].c.trim(), pos = ta ? ta.value.indexOf(alvo) : -1;
    if (ta) { ta.focus(); if (pos >= 0) ta.setSelectionRange(pos, pos + alvo.length); }
    var an = cont.querySelector('[id^="lr-anuncio-"]');
    if (an) an.textContent = pos >= 0 ? 'Trecho selecionado: ' + alvo + '.' : 'O trecho foi editado e não está mais no texto.';
  }
  function acaoEstacao(cont, alvo) {
    var m = modeloDe(cont), r = porId(m, cont.getAttribute('data-atual'));
    if (alvo.hasAttribute('data-regiao')) { cont.setAttribute('data-atual', alvo.getAttribute('data-regiao')); cont.removeAttribute('data-trecho'); renderEstacao(cont, '.lr-trab-titulo'); return; }
    if (alvo.hasAttribute('data-resp')) {
      r.corrigida = true; r.v = alvo.getAttribute('data-resp');
      var p = proxPendMista(m, r.id); if (p) cont.setAttribute('data-atual', p);
      renderEstacao(cont, '.lr-trab-titulo'); return;
    }
    if (alvo.hasAttribute('data-ir-trecho')) { selecionarTrecho(cont, r, Number(alvo.getAttribute('data-ir-trecho'))); return; }
    if (alvo.hasAttribute('data-ok-trecho')) { r.trechos[Number(alvo.getAttribute('data-ok-trecho'))].ok = true; cont.removeAttribute('data-trecho'); renderEstacao(cont, '.lr-trab-titulo'); return; }
    if (alvo.hasAttribute('data-ver-folha')) { abrirFolhaInteira(cont, alvo); return; }
    var a = alvo.getAttribute('data-acao');
    if (a === 'prox-pend') { var q = proxPendMista(m, r.id); if (q) { cont.setAttribute('data-atual', q); cont.removeAttribute('data-trecho'); renderEstacao(cont, '.lr-trab-titulo'); } return; }
    if (a === 'prox-trecho') {
      var bx = r.trechos.map(function (t, k) { return t.k < LIMIAR && !t.ok ? k : -1; }).filter(function (k) { return k >= 0; });
      var atualK = cont.hasAttribute('data-trecho') ? Number(cont.getAttribute('data-trecho')) : -1;
      var prox = bx.filter(function (k) { return k > atualK; })[0]; if (prox == null) prox = bx[0];
      if (prox != null) selecionarTrecho(cont, r, prox); return;
    }
    if (a === 'ilegivel') { r.estadoAnterior = r.estado; r.estado = 'ilegivel'; editado = false; renderEstacao(cont, '.lr-trab-titulo'); return; }
    if (a === 'desfazer-ilegivel') { r.estado = r.estadoAnterior || 'transcrita'; renderEstacao(cont, '.lr-trab-titulo'); return; }
    if (a === 'confirmar') {
      r.conferida = true; editado = false; (r.trechos || []).forEach(function (t) { t.ok = true; });
      var n = proxPendMista(m, r.id); if (n) cont.setAttribute('data-atual', n); cont.removeAttribute('data-trecho');
      renderEstacao(cont, '.lr-trab-titulo');
      var st = cont.querySelector('.rev-progresso'); if (st) st.textContent = 'Questão ' + r.n + ' conferida. ' + st.textContent;
      return;
    }
    if (a === 'salvar') abrirModal(m.regioes.some(pend) ? 'pendencias' : 'salva');
  }
  function campoEstacao(cont, c) {
    var r = porId(modeloDe(cont), cont.getAttribute('data-atual')), k = c.getAttribute('data-campo');
    editado = true;
    if (k === 'texto') r.texto = c.value; else if (k === 'descricao') r.descricao = c.value;
    else { r.trechos[Number(k)].valor = c.value; var p = cont.querySelector('[data-previa="' + k + '"]'); if (p) previaFormula(p, c.value); }
  }

  /* ---- Folha inteira no celular: dialog real (rev-cartao-modal) com uma cópia da folha ---- */
  function abrirFolhaInteira(cont, origem) {
    var painel = cont.querySelector('.rev-documento-painel');
    document.getElementById('rev-cartao-titulo').textContent = painel.querySelector('h2').textContent;
    document.getElementById('rev-cartao-contexto').textContent = painel.querySelector('header p').textContent;
    var mob = document.getElementById('rev-documento-mobile');
    mob.querySelectorAll('.lr-copia').forEach(function (n) { n.remove(); });
    var copia = painel.querySelector('.rev-leitor').cloneNode(true);
    copia.classList.add('lr-copia'); copia.removeAttribute('id'); copia.querySelectorAll('[id]').forEach(function (n) { n.removeAttribute('id'); });
    mob.appendChild(copia);
    origemDialogo = origem; document.documentElement.style.overflow = 'hidden';
    dialogo.showModal(); requestAnimationFrame(ajustarFolha);
    document.getElementById('rev-cartao-fechar').focus({ preventScroll: true });
  }

  /* ---- E. Produção textual longa ---- */
  var REDACAO = {
    aluno: 'Carla Mendes', turma: '9º B', pagina: 2, atual: 2,
    paragrafos: [
      { pag: 2, texto: 'A escola pública é o lugar onde muitos jovens encontram a primeira chance de aprender com qualidade. Por isso, ela precisa ser valorizada por todos.', baixos: [], conferido: true },
      { pag: 2, texto: 'Em primeiro lugar, a escola reúne pessoas diferentes e ensina a conviver. Quando o aluno aprende a ouvir o colega, ele aprende também a respeitar.', baixos: [], conferido: true },
      { pag: 2, texto: 'Além disso, a desigualdade ainda afasta muitos estudantes. Falta transporte, falta merenda e, às vezes, falta professor para todas as turmas.', baixos: ['desigualdade', 'merenda'], conferido: false },
      { pag: 3, texto: 'Outro ponto é a tecnologia. Com internet na escola, a inclusão digital deixa de ser promessa e vira parte da aula de todos os dias.', baixos: ['inclusão'], conferido: false },
      { pag: 3, texto: 'Portanto, investir na escola pública é investir no futuro do país. Cada aluno que termina os estudos melhora a vida da sua comunidade.', baixos: [], conferido: false }
    ]
  };
  function quebrar(texto, n) {
    var linhas = [], atual = '';
    texto.split(' ').forEach(function (p) { if ((atual + ' ' + p).trim().length > n) { linhas.push(atual.trim()); atual = p; } else atual += ' ' + p; });
    if (atual.trim()) linhas.push(atual.trim()); return linhas;
  }
  function linhasRedacao() {
    var por = {}, res = [];
    REDACAO.paragrafos.forEach(function (p, i) {
      por[p.pag] = por[p.pag] || 0;
      var ls = quebrar(p.texto, 52), ini = por[p.pag] + 1;
      ls.forEach(function (l, j) { res.push({ pag: p.pag, n: por[p.pag] + j + 1, txt: (j === 0 ? '      ' : '') + l, par: i }); });
      por[p.pag] += ls.length; p.de = ini; p.ate = por[p.pag];
    });
    return res;
  }
  function desenharRedacao(svg, pag, recorteDe) {
    while (svg.firstChild) svg.removeChild(svg.firstChild);
    cabecalho(svg, 'Avaliação 4 — Língua Portuguesa 9º ano', 'Aluna: ' + REDACAO.aluno + ' · ' + REDACAO.turma, 'Página ' + pag + ' de 3');
    txt('Redação (continuação)', { x: 22, y: 46, 'font-size': 4.2, fill: '#444' }, svg);
    for (var y = 58; y < 272; y += 9) el('line', { x1: 18, x2: 192, y1: y + 1.6, y2: y + 1.6, stroke: '#c9d6ee', 'stroke-width': 0.4 }, svg);
    linhasRedacao().forEach(function (l) {
      if (l.pag !== pag) return;
      var y = 58 + (l.n - 1) * 9;
      var t = txt(l.txt, { x: 20, y: y, 'font-size': 4.1, fill: TINTA, 'font-family': MANUS }, svg);
      t.setAttribute('data-par', l.par);
      txt(String(l.n), { x: 11, y: y, 'font-size': 2.8, fill: '#999' }, svg);
    });
    var p = REDACAO.paragrafos[REDACAO.atual];
    if (!recorteDe && p.pag === pag) el('rect', { x: 16, y: 58 + (p.de - 1) * 9 - 6, width: 178, height: (p.ate - p.de + 1) * 9 + 2.5, rx: 1.5, fill: 'none', style: 'stroke:var(--accent);stroke-width:1.2' }, svg);
    REDACAO.paragrafos.forEach(function (q, i) {
      if (q.pag !== pag || recorteDe != null && i !== recorteDe) return;
      q.baixos.forEach(function (b) {
        svg.querySelectorAll('[data-par="' + i + '"]').forEach(function (t) {
          var pos = t.textContent.indexOf(b); if (pos < 0 || !t.getExtentOfChar) return;
          try {
            var a = t.getExtentOfChar(pos), z = t.getExtentOfChar(pos + b.length - 1);
            el('rect', { x: a.x - 0.5, y: a.y - 0.3, width: z.x + z.width - a.x + 1, height: a.height + 0.6, rx: 0.8, style: 'fill:var(--atencao);fill-opacity:.2;stroke:var(--atencao);stroke-width:.4;stroke-dasharray:1.2 .8' }, svg);
          } catch (e) { /* sem layout de texto: sem destaque */ }
        });
      });
    });
  }
  function renderRedacao(foco) {
    var no = document.getElementById('redacao-conteudo'); if (!no) return;
    linhasRedacao();
    var feitos = REDACAO.paragrafos.filter(function (p) { return p.conferido; }).length;
    var palavras = REDACAO.paragrafos.reduce(function (s, p) { return s + (p.texto.trim() ? p.texto.trim().split(/\s+/).length : 0); }, 0);
    var linhas = linhasRedacao().length, p = REDACAO.paragrafos[REDACAO.atual];
    var h = '<div class="row-between rev-topo"><div><b>' + esc(REDACAO.aluno) + '</b><div class="exam-meta">' + esc(REDACAO.turma) + ' · questão 6 · produção textual · páginas 2 e 3</div></div>'
      + '<div class="rev-progresso" role="status">' + feitos + ' de 5 parágrafos conferidos</div></div>'
      + '<dl class="lr-contagem"><div><dt>Palavras</dt><dd id="redacao-palavras">' + palavras + '</dd></div><div><dt>Linhas</dt><dd>' + linhas + '</dd></div><div><dt>Páginas</dt><dd>2 (2 e 3)</dd></div></dl>'
      + '<div class="rev-trabalho"><h2 class="lr-trab-titulo" tabindex="-1"><span aria-hidden="true">✎</span> Parágrafo ' + (REDACAO.atual + 1) + ' · página ' + p.pag + ', linhas ' + p.de + '–' + p.ate + '</h2>'
      + '<div class="rev-previa rev-imagem-restrita"><div class="rev-recorte lr-recorte" role="img" aria-label="Recorte do parágrafo ' + (REDACAO.atual + 1) + ' na página ' + p.pag + '"><svg data-recorte-redacao viewBox="14 ' + (58 + (p.de - 1) * 9 - 7) + ' 182 ' + ((p.ate - p.de + 1) * 9 + 4) + '" aria-hidden="true"></svg></div></div>'
      + '<div class="rev-visualizar"><button type="button" class="btn btn-ghost btn-sm" data-ver-folha-redacao>Ver página inteira</button></div></div>'
      + '<ol class="lr-paragrafos">';
    REDACAO.paragrafos.forEach(function (q, i) {
      if (i > 0 && q.pag !== REDACAO.paragrafos[i - 1].pag) h += '<li class="lr-quebra-pagina" aria-label="Página ' + q.pag + ' começa aqui"><span aria-hidden="true">— Página ' + q.pag + ' começa aqui —</span></li>';
      var pend = !q.conferido && q.baixos.length, est = q.conferido ? '<span class="badge badge-success"><span aria-hidden="true">✓</span> Conferido</span>'
        : pend ? '<span class="badge badge-warn"><span aria-hidden="true">!</span> ' + q.baixos.length + (q.baixos.length === 1 ? ' trecho' : ' trechos') + ' para conferir</span>' : '<span class="badge badge-muted">Não conferido</span>';
      h += '<li class="lr-paragrafo' + (i === REDACAO.atual ? ' lr-paragrafo-atual' : '') + '"><div class="lr-paragrafo-cabeca"><label for="par-' + i + '"><b>Parágrafo ' + (i + 1) + '</b> · página ' + q.pag + ', linhas ' + q.de + '–' + q.ate + '</label>' + est + '</div>'
        + '<textarea class="input" rows="3" id="par-' + i + '" data-par="' + i + '"></textarea>'
        + (q.baixos.length && !q.conferido ? '<p class="field-help">Baixa confiança: ' + q.baixos.map(function (b) { return '<q>' + esc(b) + '</q>'; }).join(', ') + '</p>' : '')
        + '<div class="lr-linha-acoes"><button type="button" class="btn btn-sm btn-ghost" data-ver-par="' + i + '">Ver recorte</button><button type="button" class="btn btn-sm btn-secondary" data-conferir-par="' + i + '"' + (q.conferido ? ' aria-pressed="true"' : '') + '>' + (q.conferido ? 'Conferido' : 'Confirmar parágrafo') + '</button></div></li>';
    });
    h += '</ol><p id="redacao-status" class="rev-salvamento" role="status" aria-live="polite"></p>'
      + '<div class="lr-salvar"><button type="button" class="btn btn-secondary" data-redacao="parcial">Salvar parcial</button><button type="button" class="btn btn-primary" data-redacao="confirmar">Confirmar texto</button></div>';
    no.innerHTML = h;
    no.querySelectorAll('[data-par]').forEach(function (t) { t.value = REDACAO.paragrafos[Number(t.getAttribute('data-par'))].texto; });
    desenharRedacao(document.getElementById('redacao-folha'), REDACAO.pagina);
    desenharRedacao(no.querySelector('[data-recorte-redacao]'), p.pag, REDACAO.atual);
    document.querySelectorAll('[data-pagina-redacao]').forEach(function (b) {
      var on = Number(b.getAttribute('data-pagina-redacao')) === REDACAO.pagina;
      b.setAttribute('aria-pressed', String(on)); b.className = 'btn btn-sm ' + (on ? 'btn-primary' : 'btn-secondary');
    });
    if (foco) { var f = no.querySelector(foco); if (f) f.focus(); }
    requestAnimationFrame(ajustarFolha);
  }

  /* ---- G. Várias páginas: a página é o gabarito ---- */
  var PAGINAS = { aluno: 'Eduardo Prado', turma: '9º B', atual: 3,
    lista: [{ n: 1, estado: 'lida', info: '4 objetivas · sem pendências', foto: 'IMG_2207' }, { n: 2, estado: 'faltando', info: 'Ainda não chegou' }, { n: 3, estado: 'pendente', info: 'Redação · 1 parágrafo para conferir', foto: 'IMG_2205' }] };
  var EST_PAG = { lida: ['✓', 'Lida', 'badge-success'], pendente: ['!', 'Lida com pendência', 'badge-warn'], faltando: ['—', 'Faltando', 'badge-muted'] };
  function desenharPagina(svg, n) {
    while (svg.firstChild) svg.removeChild(svg.firstChild);
    var p = PAGINAS.lista[n - 1];
    if (p.estado === 'faltando') {
      el('rect', { x: 0, y: 0, width: 210, height: 297, fill: '#eef2f8' }, svg);
      el('rect', { x: 20, y: 20, width: 170, height: 257, rx: 4, fill: 'none', stroke: '#9aa3b2', 'stroke-width': 0.8, 'stroke-dasharray': '4 3' }, svg);
      txt('Página ' + n + ' ainda não foi lida', { x: 105, y: 146, 'font-size': 8, fill: '#56637a', 'text-anchor': 'middle' }, svg);
      txt('Fotografe a página ' + n + ' deste aluno.', { x: 105, y: 158, 'font-size': 5, fill: '#56637a', 'text-anchor': 'middle' }, svg);
      return;
    }
    cabecalho(svg, 'Avaliação 4 — Língua Portuguesa 9º ano', 'Aluno: ' + PAGINAS.aluno + ' · ' + PAGINAS.turma, 'Página ' + n + ' de 3 (QR)');
    if (n === 1) {
      for (var q = 0; q < 4; q++) {
        var y = 56 + q * 52;
        txt('Questão ' + (q + 1), { x: 22, y: y, 'font-size': 5, 'font-weight': 700, fill: '#333' }, svg);
        for (var l = 0; l < 4; l++) el('rect', { x: 22, y: y + 4 + l * 6, width: 150 - l * 18, height: 2.4, fill: '#d5dbe5' }, svg);
        LETRAS.forEach(function (L, a) {
          var cy = y + 34;
          el('circle', { cx: 32 + a * 30, cy: cy, r: 4, fill: [1, 3, 0, 2][q] === a ? '#1b2a6b' : '#fff', stroke: '#777', 'stroke-width': 0.5 }, svg);
          txt(L + ')', { x: 38 + a * 30, y: cy + 1.6, 'font-size': 4.2, fill: '#555' }, svg);
        });
      }
    } else {
      txt('Questão 6 · Redação: "A escola pública e o futuro do país" (continuação)', { x: 22, y: 46, 'font-size': 4, fill: '#444' }, svg);
      for (var yy = 58; yy < 272; yy += 9) el('line', { x1: 18, x2: 192, y1: yy + 1.6, y2: yy + 1.6, stroke: '#c9d6ee', 'stroke-width': 0.4 }, svg);
      ['      Outro ponto é a tecnologia. Com internet na escola, a', 'inclusão digital deixa de ser promessa e vira parte da', 'aula de todos os dias.', '      Portanto, investir na escola pública é investir no', 'futuro do país. Cada aluno que termina os estudos', 'melhora a vida da sua comunidade.'].forEach(function (s, i) {
        txt(s, { x: 20, y: 58 + i * 9, 'font-size': 4.1, fill: TINTA, 'font-family': MANUS }, svg);
      });
      el('rect', { x: 16, y: 52, width: 178, height: 29, rx: 1.5, fill: 'none', style: 'stroke:var(--atencao);stroke-width:1;stroke-dasharray:3 1.5' }, svg);
    }
  }
  function renderPaginas(foco) {
    var no = document.getElementById('paginas-conteudo'); if (!no) return;
    var p = PAGINAS.lista[PAGINAS.atual - 1], falta = PAGINAS.lista.filter(function (x) { return x.estado === 'faltando'; });
    var h = '<div class="row-between rev-topo"><div><b>' + esc(PAGINAS.aluno) + '</b><div class="exam-meta">' + esc(PAGINAS.turma) + ' · identificado pelo QR de cada página</div></div>'
      + '<div class="rev-progresso" role="status">Página ' + PAGINAS.atual + ' de 3' + (falta.length ? ' · Falta a página ' + falta.map(function (x) { return x.n; }).join(' e ') : ' · Completo') + '</div></div>'
      + '<div class="lr-pag-nav" role="group" aria-label="Navegar entre as páginas do aluno"><button type="button" class="btn btn-secondary" data-pag="-1"' + (PAGINAS.atual === 1 ? ' disabled' : '') + '>‹ Página anterior</button>'
      + '<output aria-live="polite">Página ' + PAGINAS.atual + ' de 3</output><button type="button" class="btn btn-secondary" data-pag="1"' + (PAGINAS.atual === 3 ? ' disabled' : '') + '>Próxima página ›</button></div>'
      + '<div class="rev-visualizar"><button type="button" class="btn btn-ghost btn-sm" data-ver-folha-paginas>Ver página inteira</button></div>'
      + '<div class="g-aviso info" role="status">As páginas chegaram fora de ordem: página 3 (IMG_2205) e depois página 1 (IMG_2207). O QR decide a página; a ordem não importa.</div>'
      + (falta.length ? '<div class="g-aviso atencao" role="status">Falta a página 2. Fotografe a página 2 ou salve o aluno sem ela. <button type="button" class="btn btn-sm btn-secondary" data-ir="camera">Ler a página 2</button></div>' : '')
      + '<h3 class="lr-sub">Páginas do aluno</h3><ol class="lr-paginas">';
    PAGINAS.lista.forEach(function (x) {
      var e = EST_PAG[x.estado];
      h += '<li' + (x.n === PAGINAS.atual ? ' aria-current="true"' : '') + '><span class="lr-pag-icone" aria-hidden="true">' + e[0] + '</span><span class="lr-pag-txt"><b>Página ' + x.n + '</b> <span class="badge ' + e[2] + '">' + e[1] + '</span><small>' + esc(x.info) + (x.foto ? ' · ' + x.foto : '') + '</small></span>'
        + (x.estado === 'faltando' ? '<button type="button" class="btn btn-sm btn-secondary" data-ir="camera">Ler agora</button>' : '<button type="button" class="btn btn-sm btn-ghost" data-abrir-pag="' + x.n + '">Abrir</button>') + '</li>';
    });
    h += '</ol><h3 class="lr-sub">Avisos desta sessão</h3><ul class="lr-envios"><li><b>Página 1</b> lida de novo (IMG_2210). <span class="badge badge-warn">Aguardando decisão</span> <button type="button" class="btn btn-sm btn-secondary" data-modal="pagina-repetida">Resolver</button></li></ul>'
      + (p.estado === 'pendente' ? '<h3 class="lr-sub">Regiões desta página</h3><ul class="lr-regioes"><li><button type="button" class="lr-regiao lr-pend" data-ir="redacao"><span class="lr-tipo" aria-hidden="true">✎</span><span class="lr-regiao-txt"><b>Questão 6 · Texto (redação)</b><small>1 parágrafo para conferir</small></span></button></li></ul>' : '')
      + '<div class="lr-salvar"><button type="button" class="btn btn-ghost" data-modal="descartar">Descartar página</button><button type="button" class="btn btn-primary" data-modal="' + (falta.length ? 'falta-pagina' : 'salva') + '">Salvar aluno</button></div>';
    no.innerHTML = h;
    var s = document.getElementById('paginas-folha'); desenharPagina(s, PAGINAS.atual);
    var at = document.querySelector('[data-pag-atual]'); if (at) at.textContent = PAGINAS.atual;
    if (foco) { var f = no.querySelector(foco); if (f) f.focus(); }
    requestAnimationFrame(ajustarFolha);
  }

  function renderTelaNova(tela) {
    var sec = document.querySelector('.tela-mk[data-tela="' + tela + '"]'); if (!sec) return;
    var cont = sec.querySelector('[data-estacao-mista]'); if (cont) renderEstacao(cont);
    if (tela === 'redacao') renderRedacao();
    if (tela === 'paginas') renderPaginas();
  }
  function abrirDocumentoCopia(svgId, origem, titulo, contexto) {
    var mob = document.getElementById('rev-documento-mobile');
    mob.querySelectorAll('.lr-copia').forEach(function (n) { n.remove(); });
    var copia = document.getElementById(svgId).closest('.rev-leitor').cloneNode(true);
    copia.classList.add('lr-copia'); copia.querySelectorAll('[id]').forEach(function (n) { n.removeAttribute('id'); });
    mob.appendChild(copia);
    document.getElementById('rev-cartao-titulo').textContent = titulo; document.getElementById('rev-cartao-contexto').textContent = contexto;
    origemDialogo = origem; document.documentElement.style.overflow = 'hidden';
    dialogo.showModal(); requestAnimationFrame(ajustarFolha);
    document.getElementById('rev-cartao-fechar').focus({ preventScroll: true });
  }
  function ligarTelasNovas() {
    document.addEventListener('click', function (ev) {
      var cont = ev.target.closest('[data-estacao-mista]');
      if (cont) {
        var a = ev.target.closest('[data-regiao],[data-resp],[data-ir-trecho],[data-ok-trecho],[data-ver-folha],[data-acao]');
        if (a && cont.contains(a)) { acaoEstacao(cont, a); return; }
      }
      var b = ev.target.closest('[data-pagina-redacao]'); if (b) { REDACAO.pagina = Number(b.getAttribute('data-pagina-redacao')); renderRedacao(); return; }
      b = ev.target.closest('[data-ver-par]'); if (b) { REDACAO.atual = Number(b.getAttribute('data-ver-par')); REDACAO.pagina = REDACAO.paragrafos[REDACAO.atual].pag; renderRedacao('.lr-trab-titulo'); return; }
      b = ev.target.closest('[data-conferir-par]');
      if (b) {
        var i = Number(b.getAttribute('data-conferir-par')), q = REDACAO.paragrafos[i]; q.conferido = !q.conferido; editado = false;
        renderRedacao('[data-conferir-par="' + i + '"]');
        document.getElementById('redacao-status').textContent = 'Parágrafo ' + (i + 1) + (q.conferido ? ' conferido.' : ' voltou para conferir.');
        return;
      }
      b = ev.target.closest('[data-redacao]');
      if (b) {
        var faltam = REDACAO.paragrafos.filter(function (x) { return !x.conferido; }).length, st = document.getElementById('redacao-status');
        if (b.getAttribute('data-redacao') === 'parcial') { editado = false; st.textContent = 'Conferência parcial salva nesta sessão: ' + (5 - faltam) + ' de 5 parágrafos. Você pode continuar depois.'; }
        else if (faltam) st.textContent = 'Faltam ' + faltam + ' parágrafos para conferir. Use Salvar parcial para continuar depois.';
        else { editado = false; abrirModal('salva'); }
        return;
      }
      if (ev.target.closest('[data-ver-folha-redacao]')) { abrirDocumentoCopia('redacao-folha', ev.target.closest('button'), 'Página ' + REDACAO.pagina + ' da redação', REDACAO.aluno + ' · ' + REDACAO.turma); return; }
      if (ev.target.closest('[data-ver-folha-paginas]')) { abrirDocumentoCopia('paginas-folha', ev.target.closest('button'), 'Página ' + PAGINAS.atual + ' de 3', PAGINAS.aluno + ' · ' + PAGINAS.turma); return; }
      b = ev.target.closest('[data-pag]'); if (b) { PAGINAS.atual = Math.min(3, Math.max(1, PAGINAS.atual + Number(b.getAttribute('data-pag')))); renderPaginas('[data-pag="' + b.getAttribute('data-pag') + '"]'); return; }
      b = ev.target.closest('[data-abrir-pag]'); if (b) { PAGINAS.atual = Number(b.getAttribute('data-abrir-pag')); renderPaginas('.lr-pag-nav output'); return; }
      if (ev.target.closest('[data-sair-edicao]')) abrirModal(editado ? 'descartar-edicao' : 'sair');
    });
    document.addEventListener('input', function (ev) {
      var c = ev.target.closest('[data-campo]'), cont = c && c.closest('[data-estacao-mista]');
      if (cont) { campoEstacao(cont, c); return; }
      var t = ev.target.closest('[data-par]');
      if (t) {
        REDACAO.paragrafos[Number(t.getAttribute('data-par'))].texto = t.value; editado = true;
        var pal = REDACAO.paragrafos.reduce(function (s, p) { return s + (p.texto.trim() ? p.texto.trim().split(/\s+/).length : 0); }, 0);
        var out = document.getElementById('redacao-palavras'); if (out) out.textContent = pal;
      }
    });
  }
  /* Atalhos A–E da objetiva nas estações mistas */
  function teclaMista(ev) {
    var sec = document.querySelector('.tela-mk.ativa [data-estacao-mista]'); if (!sec) return false;
    var r = porId(modeloDe(sec), sec.getAttribute('data-atual')); var k = ev.key.toUpperCase();
    if (k === 'N') { acaoEstacao(sec, { hasAttribute: function () { return false; }, getAttribute: function () { return 'prox-pend'; } }); return true; }
    if (r.tipo !== 'objetiva') return false;
    var cod = LETRAS.indexOf(k) >= 0 ? k : k === '0' ? 'BRANCO' : k === 'X' ? 'ANULADA' : null; if (!cod) return false;
    var b = sec.querySelector('[data-resp="' + cod + '"]'); if (b) acaoEstacao(sec, b); return true;
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
    'descartar-edicao': ['Descartar a edição?', '<p>Você mudou a transcrição e ainda não confirmou. Se sair agora, a edição será descartada.</p><p class="page-sub">A transcrição original da IA continua guardada.</p>', [['Descartar', 'btn-ghost', 'entrada'], ['Voltar', 'btn-primary']]],
    'pagina-repetida': ['Esta página já foi lida', '<p>A <strong>página 1</strong> de <strong>Eduardo Prado</strong> já foi lida (IMG_2207). A nova foto é IMG_2210.</p><p class="page-sub">A página anterior só é trocada se você escolher Substituir.</p>', [['Manter a anterior', 'btn-secondary'], ['Substituir', 'btn-primary']]],
    'falta-pagina': ['Falta a página 2', '<p><strong>Eduardo Prado</strong> tem as páginas 1 e 3. Falta a página 2.</p><p class="page-sub">Se salvar agora, as páginas lidas vão para o envio e o aluno aparece no resumo como "Faltam páginas".</p>', [['Ler a página 2', 'btn-secondary', 'camera'], ['Salvar sem a página 2', 'btn-primary', '#salva']]],
    'cancelar-ia': ['Cancelar o download da IA?', '<p>O download para e a parte já baixada é apagada. As objetivas continuam sendo lidas neste aparelho.</p><p class="page-sub">As respostas abertas ficam "Aguardando a IA" até você baixar de novo.</p>', [['Continuar o download', 'btn-secondary'], ['Cancelar o download', 'btn-primary']]],
    'fechar-correcao': ['Fechar a correção?', '<p>Depois disso só a coordenação ou a direção poderá alterar, excluir, incluir ou reabrir.</p>', [['Cancelar', 'btn-secondary'], ['Fechar correção', 'btn-primary']]]
  };
  var ultimoFoco = null;
  function abrirModal(id) {
    var m = MODAIS[id]; if (!m) return;
    var fundo = document.getElementById('mk-modal');
    if (!fundo.classList.contains('open')) ultimoFoco = document.activeElement;
    document.getElementById('mk-modal-titulo').textContent = m[0];
    var nomeTela = { paginas: 'Eduardo Prado', redacao: 'Carla Mendes' }[telaAtual];
    document.getElementById('mk-modal-corpo').innerHTML = nomeTela ? m[1].replace('Ana Beatriz Souza', nomeTela) : m[1];
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
    if (tela === 'transcricao') tela = 'aberta';
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
    editado = false;
    renderTelaNova(tela);
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
    window.addEventListener('scroll', function () { if (document.querySelector('.tela-mk.ativa .rev-documento-painel')) ajustarFolha(); }, { passive: true });
    window.addEventListener('resize', function () { atualizarQuadro(); ajustarFolha(); if (modoCamera) desenharCamera(); });

    document.addEventListener('click', function (ev) {
      var ir = ev.target.closest('[data-ir]'); if (ir) { ev.preventDefault(); fecharModal(); irPara(ir.getAttribute('data-ir')); return; }
      var mo = ev.target.closest('[data-modal]'); if (mo) { abrirModal(mo.getAttribute('data-modal')); return; }
      if (ev.target.closest('[data-fechar]') || ev.target.id === 'mk-modal') fecharModal();
      if (ev.target.closest('a[href="#"]')) ev.preventDefault();
    });

    /* Leitor da folha: zoom (leitor-cartao.js) e dialog no celular (correcao/revisao) */
    document.addEventListener('click', function (ev) {
      var b = ev.target.closest('.rev-leitor [data-mais], .rev-leitor [data-menos], .rev-leitor [data-ajustar]'); if (!b) return;
      var leitor = b.closest('.rev-leitor'), z = Number(leitor.getAttribute('data-zoom') || 1);
      z = b.hasAttribute('data-mais') ? Math.min(3, z * 1.25) : b.hasAttribute('data-menos') ? Math.max(0.5, z * 0.8) : 1;
      leitor.setAttribute('data-zoom', String(z)); ajustarFolha();
    });
    ligarTelasNovas();
    document.getElementById('rev-ver-cartao').addEventListener('click', function () {
      origemDialogo = this; document.documentElement.style.overflow = 'hidden';
      dialogo.showModal(); destinoLeitor(); document.getElementById('rev-cartao-fechar').focus({ preventScroll: true });
    });
    document.getElementById('rev-cartao-fechar').addEventListener('click', function () { dialogo.close(); });
    dialogo.addEventListener('keydown', function (e) { if (e.key === 'Tab') prenderFoco(e, dialogo); });
    dialogo.addEventListener('close', function () {
      document.documentElement.style.overflow = ''; destinoLeitor();
      document.querySelectorAll('#rev-documento-mobile .lr-copia').forEach(function (n) { n.remove(); });
      document.getElementById('rev-cartao-titulo').textContent = 'Folha completa'; document.getElementById('rev-cartao-contexto').textContent = 'Ana Beatriz Souza · 9º A';
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
      if (document.querySelector('dialog[open]')) return;
      if (ev.target.closest && ev.target.closest('.rev-leitor, input, textarea, select')) return;
      if (telaAtual !== 'leitor') { if (!ev.ctrlKey && !ev.metaKey && !ev.altKey && teclaMista(ev)) ev.preventDefault(); return; }
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
