/* Dados selecionados são apresentados na confirmação compartilhada de turmas. */
(function () {
  'use strict';
  document.querySelectorAll('[data-turma-atribuicao]').forEach(form => {
    const base=form.dataset.turmaConfirmar;
    form.addEventListener('submit',()=>{
      const disciplina=form.elements.namedItem('disciplina_id').selectedOptions[0]?.textContent||'';
      const professor=form.elements.namedItem('professor_vinculo_id').selectedOptions[0]?.textContent||'';
      form.dataset.turmaConfirmar=`${disciplina} — ${professor}. ${base}`;
    },true);
  });
})();
