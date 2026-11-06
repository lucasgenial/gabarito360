/* O diálogo mantém um rascunho; somente Aplicar modifica o recorte do painel. */
(function () {
  'use strict';
  const modal = document.querySelector('[data-painel-modal]');
  const form = document.querySelector('[data-painel-filtros]');
  const abrir = document.querySelector('[data-painel-abrir]');
  if (!modal || !form || !abrir) return;
  function restaurar() {
    form.reset();
    ['painel_disciplina','painel_professor','painel_inicio','painel_fim'].forEach(n=>{form.elements.namedItem(n).value=form.elements.namedItem('painel_anterior['+n+']')?.value||'';});
    validarPeriodo();
  }
  abrir.addEventListener('click', () => { restaurar(); modal.showModal(); });
  modal.querySelectorAll('[data-painel-cancelar]').forEach(b => b.addEventListener('click', () => modal.close()));
  modal.addEventListener('close', () => { restaurar(); abrir.focus(); });
  modal.querySelector('[data-painel-limpar]')?.addEventListener('click', () => {
    ['painel_disciplina','painel_professor','painel_inicio','painel_fim'].forEach(n => { form.elements.namedItem(n).value = ''; });
    validarPeriodo();form.querySelector('select')?.focus();
  });
  const inicio = form.elements.namedItem('painel_inicio'), fim = form.elements.namedItem('painel_fim');
  function validarPeriodo() { fim.min = inicio.value; }
  inicio.addEventListener('change', validarPeriodo);
  abrir.addEventListener('click', validarPeriodo);
  if(Number(modal.dataset.painelErros)>0){validarPeriodo();modal.showModal();modal.querySelector('[aria-invalid="true"]')?.focus();}
})();
