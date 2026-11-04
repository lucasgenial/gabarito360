// Preserva somente campos não sensíveis do formulário ao conferir os pré-requisitos.
document.querySelector('.dc1-stack [aria-invalid="true"]')?.focus();
document.querySelectorAll('[data-dc1-retorno]').forEach(link => {
  link.addEventListener('click', () => {
    const form = document.getElementById('dc1-atribuicao');
    if (!form) return;
    const url = new URL(link.href, location.origin);
    const campos = {inicio_em:'rascunho_inicio',fim_em:'rascunho_fim',papel:'rascunho_papel',vinculo_id:'rascunho_vinculo',chave:'rascunho_chave'};
    for (const [nome, destino] of Object.entries(campos)) {
      const valor = form.elements.namedItem(nome)?.value;
      if (valor) url.searchParams.set(destino, valor); else url.searchParams.delete(destino);
    }
    link.href = url.toString();
  });
});
