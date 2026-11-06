(function () {
  var f = document.getElementById('nav-folha'), b = document.querySelector('.nav-inferior-mais');
  if (!f || !b) return;
  function abrir(sim) { f.hidden = !sim; b.setAttribute('aria-expanded', sim ? 'true' : 'false'); document.body.style.overflow = sim ? 'hidden' : ''; }
  b.addEventListener('click', function () { abrir(f.hidden); });
  f.querySelectorAll('[data-fechar]').forEach(function (x) { x.addEventListener('click', function () { abrir(false); }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') abrir(false); });
})();
// Recolher/expandir o menu lateral; a preferência fica neste navegador
document.getElementById('menu-recolher').addEventListener('click', function () {
  var r = document.documentElement.classList.toggle('menu-recolhido');
  try { localStorage.setItem('g360-menu', r ? 'recolhido' : 'aberto'); } catch (e) {}
});
window.addEventListener('beforeprint', function () { document.querySelectorAll('details').forEach(function (d) { d.open = true; }); });
