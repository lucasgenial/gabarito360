/* Credenciais de convites são coladas no formulário, nunca lidas de uma URL. */
(function () {
  'use strict';
  // Remover somente o segredo legado desta aba. Não reaproveitá-lo para executar ações.
  try { sessionStorage.removeItem('g360-convite-vinculo'); } catch (e) {}
  if (location.hash) history.replaceState(null, '', location.pathname + location.search);
})();
