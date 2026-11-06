(function () {
  'use strict';
  const form = document.getElementById('cadastro-aluno');
  if (!form) return;
  function atualizarPreview() {
    const nome = form.querySelector('[name="nome"]').value;
    const turma = form.querySelector('[name="turma_id"]').selectedOptions[0];
    const iniciais = nome.trim().split(/\s+/u).filter(Boolean).slice(0,2).map(p => Array.from(p)[0].toLocaleUpperCase('pt-BR')).join('') || '?';
    const area = form.querySelector('[data-imagem-aluno]');
    area.dataset.iniciais = iniciais;
    const letras = area.querySelector('.avatar-iniciais');
    if (letras) letras.textContent = iniciais;
    document.getElementById('preview-nome').textContent = nome || 'Novo aluno';
    document.getElementById('preview-turma').textContent = turma?.dataset.label || 'Série / Turma';
  }
  form.querySelector('[name="nome"]').addEventListener('input', atualizarPreview);
  form.querySelector('[name="turma_id"]').addEventListener('change', atualizarPreview);
  atualizarPreview();
})();
