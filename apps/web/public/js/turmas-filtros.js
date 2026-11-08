/* O recorte so muda ao enviar o formulario; cancelar descarta o rascunho. */
(() => {
  'use strict';
  const dialogo = document.getElementById('filtros-turmas');
  if (!dialogo) return;
  const formulario = dialogo.querySelector('form');
  let acionador;
  document.querySelectorAll('[data-turmas-filtros-abrir]').forEach(botao => {
    botao.addEventListener('click', () => {
      formulario.reset();
      acionador = botao;
      dialogo.showModal();
      formulario.querySelector('input[type="search"]')?.focus();
    });
  });
  dialogo.querySelectorAll('[data-turmas-filtros-fechar]').forEach(botao => {
    botao.addEventListener('click', () => dialogo.close());
  });
  dialogo.querySelector('[data-turmas-filtros-limpar]')?.addEventListener('click', () => {
    for (const campo of formulario.querySelectorAll('input[name="busca"],input[name="ano_letivo"],select[name="escola_id"]')) campo.value = '';
    formulario.querySelector('input[type="search"]')?.focus();
  });
  dialogo.addEventListener('click', evento => {
    if (evento.target !== dialogo) return;
    const limite = dialogo.getBoundingClientRect();
    if (evento.clientX < limite.left || evento.clientX > limite.right || evento.clientY < limite.top || evento.clientY > limite.bottom) dialogo.close();
  });
  // Escape fecha mesmo quando o campo search tem texto (o navegador pode apenas limpa-lo).
  dialogo.addEventListener('keydown', evento => {
    if (evento.key !== 'Escape') return;
    evento.preventDefault();
    dialogo.close();
  });
  dialogo.addEventListener('close', () => {
    formulario.reset();
    acionador?.focus();
  });
})();
