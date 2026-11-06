(function () {
  'use strict';
  const form = document.querySelector('[data-turma-atribuicao]');
  if (!form) return;
  const disciplina = form.querySelector('[name="componente_id"]');
  const professor = form.querySelector('[name="vinculo_id"]');
  function atualizar() {
    let disponiveis = 0;
    for (const option of professor.options) {
      if (!option.value) continue;
      const mostrar = option.dataset.componente === disciplina.value;
      option.hidden = !mostrar; option.disabled = !mostrar;
      if (mostrar) disponiveis++;
      if (!mostrar && option.selected) professor.value = '';
    }
    form.querySelector('[data-atribuicao-versao]').value = disciplina.selectedOptions[0]?.dataset.versao || '';
    form.querySelector('[data-atribuicao-vazio]').hidden = !disciplina.value || disponiveis > 0;
    professor.disabled = !disciplina.value || !disponiveis;
    form.querySelector('[type="submit"]').disabled = !disciplina.value || !disponiveis;
  }
  disciplina.addEventListener('change', atualizar);
  atualizar();
})();
