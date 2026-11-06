/* Interações locais de turmas; dados e navegação permanecem separados. */
(function () {
  'use strict';
  function mostrarModal(id, aberto) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.toggle('open', aberto);
    modal.setAttribute('aria-hidden', String(!aberto));
    document.body.style.overflow = aberto ? 'hidden' : '';
  }
  document.querySelectorAll('[data-turma-abrir]').forEach(botao => {
    botao.addEventListener('click', () => mostrarModal(botao.dataset.turmaAbrir, true));
  });
  document.querySelectorAll('[data-turma-fechar]').forEach(botao => {
    botao.addEventListener('click', () => mostrarModal(botao.dataset.turmaFechar, false));
  });
  ['modal-editar', 'modal-criar', 'modal-importar'].forEach(id => {
    const modal = document.getElementById(id);
    modal?.addEventListener('click', e => { if (e.target === modal) mostrarModal(id, false); });
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') ['modal-editar', 'modal-criar', 'modal-importar'].forEach(id => mostrarModal(id, false));
  });
  document.querySelectorAll('[data-turma-confirmar]').forEach(form => {
    form.addEventListener('submit', e => { if (!window.confirm(form.dataset.turmaConfirmar)) e.preventDefault(); });
  });
  const filtro = document.querySelector('[data-turma-filtrar-alunos]');
  filtro?.addEventListener('input', () => {
    const termo = filtro.value.toLowerCase().trim();
    let visiveis = 0;
    document.querySelectorAll('#alunos-tbody tr[data-nome]').forEach(row => {
      const mostrar = !termo || row.dataset.nome.includes(termo);
      row.style.display = mostrar ? '' : 'none';
      if (mostrar) visiveis++;
    });
    const vazio = document.getElementById('no-results');
    if (vazio) vazio.style.display = visiveis === 0 && termo ? 'block' : 'none';
  });
  const professor = document.getElementById('professor-selecionado');
  const vinculo = document.getElementById('professor-vinculo');
  if (professor && vinculo) {
    const atualizar = () => { vinculo.value = professor.selectedOptions[0]?.dataset.vinculo || ''; };
    professor.addEventListener('change', atualizar);
    atualizar();
  }
})();
