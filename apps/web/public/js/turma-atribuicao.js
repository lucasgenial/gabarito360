/* Dados selecionados são apresentados na confirmação compartilhada de turmas. */
(function () {
  'use strict';
  const bases = new WeakMap();
  // Captura no documento: roda antes da confirmação de turmas.js e alcança formulários carregados depois (F4-B).
  document.addEventListener('submit', e => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-turma-atribuicao]')) return;
    if (!bases.has(form)) bases.set(form, form.dataset.turmaConfirmar);
    const disciplina=form.elements.namedItem('disciplina_id').selectedOptions[0]?.textContent||'';
    const professor=form.elements.namedItem('professor_vinculo_id').selectedOptions[0]?.textContent||'';
    form.dataset.turmaConfirmar=`${disciplina} — ${professor}. ${bases.get(form)}`;
  }, true);
})();
