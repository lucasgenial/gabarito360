/* Melhoria progressiva: o menu nativo continua utilizável sem JavaScript. */
(() => {
  document.querySelectorAll('[data-menu-publico]').forEach(menu => {
    const summary = menu.querySelector('summary');
    menu.addEventListener('click', event => {
      const link = event.target.closest('a[href]');
      if (!link) return;
      menu.open = false;
      const url = new URL(link.href, location.href);
      if (url.pathname === location.pathname && url.hash) {
        const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
        if (target) { target.tabIndex = -1; target.focus({preventScroll:true}); }
      }
    });
    menu.addEventListener('keydown', event => {
      if (event.key === 'Escape' && menu.open) { event.preventDefault(); menu.open = false; summary.focus(); }
    });
  });
})();
