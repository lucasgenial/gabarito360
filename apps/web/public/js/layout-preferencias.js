// Preferências visuais: não alteram sessão, vínculo ou permissões.
try {
  const preferencia=localStorage.getItem('g360-menu');
  document.documentElement.classList.toggle('menu-recolhido',preferencia==='recolhido'||(!preferencia&&!matchMedia('(min-width:1280px)').matches));
} catch (_) { document.documentElement.classList.toggle('menu-recolhido',!matchMedia('(min-width:1280px)').matches); }
try { document.documentElement.dataset.theme=localStorage.getItem('g360-theme')==='dark'?'dark':'light'; } catch (_) {}
