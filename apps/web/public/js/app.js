/* ============================================================
   Gabarito360 — comportamentos compartilhados
   ============================================================ */

/* ---------- Editor de gabarito oficial (marcar/editar respostas) ---------- */
function initGabaritoEditor(root) {
  root = root || document;
  root.querySelectorAll('[data-gabarito]').forEach(function (grid) {
    grid.addEventListener('click', function (e) {
      var bubble = e.target.closest('.bubble');
      if (!bubble) return;
      var row = bubble.closest('.q-row');
      row.querySelectorAll('.bubble').forEach(function (b) { b.classList.remove('marked'); });
      bubble.classList.add('marked');
      row.setAttribute('data-answer', bubble.dataset.opt);
      updateGabaritoSummary(grid);
    });
  });
  document.querySelectorAll('[data-gabarito]').forEach(updateGabaritoSummary);
}
function updateGabaritoSummary(grid) {
  var rows = grid.querySelectorAll('.q-row');
  var done = 0;
  rows.forEach(function (r) { if (r.getAttribute('data-answer')) done++; });
  var out = document.querySelector('[data-gabarito-count]');
  if (out) out.textContent = done + ' / ' + rows.length;
  var bar = document.querySelector('[data-gabarito-bar]');
  if (bar) bar.style.width = (done / rows.length * 100) + '%';
}

/* ---------- Busca + filtro de alunos ---------- */
function initStudentFilter() {
  var search = document.getElementById('student-search');
  var turma = document.getElementById('filter-turma');
  var status = document.getElementById('filter-status');
  var rows = Array.prototype.slice.call(document.querySelectorAll('[data-student]'));
  function apply() {
    var q = (search ? search.value : '').toLowerCase().trim();
    var t = turma ? turma.value : '';
    var s = status ? status.value : '';
    var shown = 0;
    rows.forEach(function (row) {
      var name = (row.dataset.name || '').toLowerCase();
      var matchQ = !q || name.indexOf(q) > -1;
      var matchT = !t || row.dataset.turma === t;
      var matchS = !s || row.dataset.status === s;
      var ok = matchQ && matchT && matchS;
      row.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });
    var counter = document.getElementById('student-count');
    if (counter) counter.textContent = shown;
  }
  [search, turma, status].forEach(function (el) {
    if (!el) return;
    el.addEventListener('input', apply);
    el.addEventListener('change', apply);
  });
  apply();
}

/* ---------- Busca + filtro de provas ---------- */
function initExamFilter() {
  var search = document.getElementById('exam-search');
  var disc = document.getElementById('filter-disciplina');
  var status = document.getElementById('filter-status');
  var rows = Array.prototype.slice.call(document.querySelectorAll('[data-exam]'));
  function apply() {
    var q = (search ? search.value : '').toLowerCase().trim();
    var d = disc ? disc.value : '';
    var s = status ? status.value : '';
    var shown = 0;
    rows.forEach(function (row) {
      var titulo = (row.dataset.titulo || '').toLowerCase();
      var matchQ = !q || titulo.indexOf(q) > -1;
      var matchD = !d || row.dataset.disc === d;
      var matchS = !s || row.dataset.status === s;
      var ok = matchQ && matchD && matchS;
      row.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });
    var counter = document.getElementById('exam-count');
    if (counter) counter.textContent = shown;
    var noResults = document.getElementById('no-results');
    if (noResults) noResults.style.display = shown ? 'none' : 'block';
  }
  [search, disc, status].forEach(function (el) {
    if (!el) return;
    el.addEventListener('input', apply);
    el.addEventListener('change', apply);
  });
  apply();
}

/* ---------- Busca + filtro de turmas ---------- */
function initTurmaFilter() {
  var search = document.getElementById('turma-search');
  var serie = document.getElementById('filter-serie');
  var status = document.getElementById('filter-status');
  var rows = Array.prototype.slice.call(document.querySelectorAll('[data-turma-row]'));
  function apply() {
    var q = (search ? search.value : '').toLowerCase().trim();
    var sr = serie ? serie.value : '';
    var st = status ? status.value : '';
    var shown = 0;
    rows.forEach(function (row) {
      var nome = (row.dataset.nome || '').toLowerCase();
      var matchQ = !q || nome.indexOf(q) > -1;
      var matchSerie = !sr || row.dataset.serie === sr;
      var matchStatus = !st || row.dataset.status === st;
      var ok = matchQ && matchSerie && matchStatus;
      row.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });
    var counter = document.getElementById('turma-count');
    if (counter) counter.textContent = shown;
    var noResults = document.getElementById('no-results');
    if (noResults) noResults.style.display = shown ? 'none' : 'block';
  }
  [search, serie, status].forEach(function (el) {
    if (!el) return;
    el.addEventListener('input', apply);
    el.addEventListener('change', apply);
  });
  apply();
}

/* ---------- Menu de Usuário ---------- */
function initUserMenu() {
  var trigger = document.getElementById('user-menu-trigger');
  var menu = document.getElementById('user-menu');
  if (!trigger || !menu) return;
  trigger.addEventListener('click', function (e) {
    e.stopPropagation();
    menu.classList.toggle('active');
  });
  document.addEventListener('click', function (e) {
    if (!menu.contains(e.target) && e.target !== trigger) {
      menu.classList.remove('active');
    }
  });
}

/* ---------- Alternância de tema (claro / escuro) ---------- */
function initThemeToggle() {
  var btn = document.getElementById('theme-toggle');
  if (!btn) return;

  var MOON_SVG = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  var SUN_SVG  = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    btn.innerHTML = theme === 'dark' ? SUN_SVG : MOON_SVG;
    btn.title = theme === 'dark' ? 'Mudar para tema claro' : 'Mudar para tema escuro';
    btn.setAttribute('aria-label', btn.title);
  }

  var saved = localStorage.getItem('g360-theme') || 'light';
  applyTheme(saved);

  btn.addEventListener('click', function () {
    var current = document.documentElement.getAttribute('data-theme') || 'light';
    var next = current === 'dark' ? 'light' : 'dark';
    localStorage.setItem('g360-theme', next);
    applyTheme(next);
  });
}

/* ---------- init global ---------- */
document.addEventListener('DOMContentLoaded', function () {
  initThemeToggle();
  initUserMenu();
  initGabaritoEditor();
  initStudentFilter();
});

/* ---------- Tabelas e filtros no celular (docs/21, etapa 5) ---------- */
// Listas: cada célula recebe o rótulo da coluna (mostrado no cartão do celular)
function rotularListas(root) {
  (root || document).querySelectorAll('table.lista-cel').forEach(function (t) {
    var cab = [];
    t.querySelectorAll('thead tr:first-child th').forEach(function (th) {
      for (var i = 0; i < (th.colSpan || 1); i++) cab.push(th.textContent.trim());
    });
    t.querySelectorAll('tbody tr').forEach(function (tr) {
      var col = 0;
      Array.prototype.forEach.call(tr.children, function (td) {
        if (!td.hasAttribute('data-rotulo')) td.setAttribute('data-rotulo', td.colSpan > 1 ? '' : (cab[col] || ''));
        col += td.colSpan || 1;
      });
    });
  });
}

// Quadros: dica "arraste para o lado" só quando o quadro é mais largo que a tela
function marcarQuadros() {
  document.querySelectorAll('.quadro-cel').forEach(function (q) {
    var dica = q.previousElementSibling;
    if (!dica || !dica.classList.contains('quadro-dica')) {
      dica = document.createElement('div');
      dica.className = 'quadro-dica no-print';
      dica.textContent = '↔ Arraste o quadro para o lado para ver todas as colunas';
      q.parentNode.insertBefore(dica, q);
    }
    var rola = q.scrollWidth > q.clientWidth + 2;
    q.classList.toggle('rola', rola);
    dica.classList.toggle('ativa', rola);
  });
}

// Filtros: no celular, busca à vista e selects em "Mais filtros" (aberto se algum estiver em uso)
function prepararFiltros() {
  document.querySelectorAll('form.filtros-cel').forEach(function (f) {
    if (f.querySelector('.filtros-toggle')) return;
    var selects = f.querySelectorAll(':scope > select');
    if (!selects.length) return;
    var emUso = Array.prototype.filter.call(selects, function (s) { return s.value; }).length;
    if (emUso) f.classList.add('aberto');
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'btn btn-ghost filtros-toggle';
    var rotulo = function () { b.textContent = (f.classList.contains('aberto') ? 'Menos filtros' : 'Mais filtros') + (emUso ? ' (' + emUso + ')' : ''); };
    rotulo();
    b.addEventListener('click', function () { f.classList.toggle('aberto'); rotulo(); });
    selects[0].parentNode.insertBefore(b, selects[0]);
  });
}

document.addEventListener('DOMContentLoaded', function () {
  rotularListas();
  marcarQuadros();
  prepararFiltros();
  window.addEventListener('resize', marcarQuadros);
  // Quadros dentro de <details> só têm largura quando abertos
  document.querySelectorAll('details').forEach(function (d) { d.addEventListener('toggle', marcarQuadros); });
});


// A seleção só persiste quando o formulário do aluno é salvo.
document.querySelectorAll('[data-seletor-avatar]').forEach(function (seletor) {
  var preview = seletor.querySelector('[data-avatar-preview]');
  var nome = document.getElementById(seletor.dataset.campoNome);
  function atualizarAvatar() {
    var selecionado = seletor.querySelector('input[name="avatar_id"]:checked');
    var id = Number(selecionado ? selecionado.value : 0);
    var imagem = Number.isInteger(id) && id >= 1 && id <= 36;
    preview.classList.toggle('avatar-aluno--imagem', imagem);
    preview.style.setProperty('--avatar-x', ((id - 1) % 6) * 20 + '%');
    preview.style.setProperty('--avatar-y', Math.floor((id - 1) / 6) * 20 + '%');
    var partes = (nome ? nome.value : '').trim().split(/\s+/u).filter(Boolean);
    preview.querySelector('.avatar-iniciais').textContent = partes.slice(0, 2).map(function (p) { return Array.from(p)[0].toLocaleUpperCase('pt-BR'); }).join('') || '?';
    seletor.querySelector('[data-avatar-status]').textContent = imagem ? 'Avatar ' + id + ' selecionado' : 'Iniciais do nome';
  }
  seletor.addEventListener('change', atualizarAvatar);
  if (nome) nome.addEventListener('input', atualizarAvatar);
  atualizarAvatar();
});
