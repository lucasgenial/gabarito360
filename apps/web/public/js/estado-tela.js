/* Aplicar o tema salvo antes da primeira pintura, sem carregar o aplicativo. */
(function () {
  'use strict';
  try {
    document.documentElement.dataset.theme = localStorage.getItem('g360-theme') === 'dark' ? 'dark' : 'light';
  } catch (_) { /* O estado continua legível quando o armazenamento está bloqueado. */ }
  try {
    const entrada=sessionStorage.getItem('g360-entrada-vinculo');
    sessionStorage.removeItem('g360-entrada-vinculo');
    const revisao=document.querySelector('meta[name="g360-revisao"]')?.content;
    if(entrada && entrada===revisao && location.pathname==='/app/painel')document.documentElement.classList.add('entrada-vinculo');
  } catch (_) { /* Sem armazenamento, a estrutura normal continua disponível. */ }
})();
