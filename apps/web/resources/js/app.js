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
