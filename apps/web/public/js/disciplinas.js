// Preserva somente campos não sensíveis do formulário ao conferir os pré-requisitos.
document.querySelector('.dc1-stack [aria-invalid="true"]')?.focus();
document.querySelector('[data-dc1-foco]')?.focus();

// A API decide se houve uso conjunto. Sem JavaScript, o diálogo permanece aberto na página.
(() => {
  const dialog = document.querySelector('[data-dc1-confirmacao-area]');
  if (!dialog || typeof dialog.showModal !== 'function') return;
  const origem = document.getElementById(dialog.dataset.dc1Origem);
  dialog.removeAttribute('open');
  dialog.showModal();
  dialog.querySelector('[data-dc1-cancelar-area]')?.focus();
  dialog.querySelector('[data-dc1-cancelar-area]')?.addEventListener('click', event => {
    event.preventDefault(); dialog.close();
  });
  dialog.addEventListener('close', () => origem?.focus());
})();
document.querySelectorAll('form[data-dc1-envio]').forEach(form => {
  form.addEventListener('submit', event => {
    if (form.dataset.enviando === 'true') { event.preventDefault(); return; }
    form.dataset.enviando = 'true';
    if (event.submitter) event.submitter.disabled = true;
  });
});
window.addEventListener('pageshow', () => document.querySelectorAll('form[data-dc1-envio]').forEach(form => {
  delete form.dataset.enviando;
  form.querySelectorAll('button[type="submit"]').forEach(button => { button.disabled = false; });
}));
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

// Somente as novas ações DC1 usam este diálogo. A API continua autorizando cada POST.
(() => {
  const dialog = document.getElementById('dc1-confirmacao');
  if (!dialog || typeof dialog.showModal !== 'function') return;
  const titulo = dialog.querySelector('#dc1-confirmacao-titulo');
  const texto = dialog.querySelector('#dc1-confirmacao-texto');
  const confirmar = dialog.querySelector('[data-dc1-confirmar-envio]');
  const cancelar = dialog.querySelector('[data-dc1-cancelar]');
  const aprovados = new WeakSet();
  let enviando = new WeakSet(), pendente = null, acionador = null, confirmado = false;
  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-dc1-confirmar]')) return;
    if (enviando.has(form)) { event.preventDefault(); return; }
    if (aprovados.has(form)) {
      aprovados.delete(form); enviando.add(form);
      if (event.submitter) { event.submitter.disabled = true; event.submitter.dataset.dc1Enviando = 'true'; }
      return;
    }
    event.preventDefault();
    if (dialog.open) return;
    pendente = form; acionador = event.submitter; confirmado = false;
    titulo.textContent = form.dataset.dc1Titulo;
    texto.textContent = form.dataset.dc1Confirmar;
    confirmar.textContent = form.dataset.dc1Verbo;
    confirmar.disabled = false;
    dialog.showModal(); cancelar.focus();
  });
  confirmar.addEventListener('click', () => {
    if (!pendente || confirmar.disabled) return;
    const form = pendente, botao = acionador;
    confirmar.disabled = true; confirmado = true;
    aprovados.add(form); dialog.close();
    form.requestSubmit(botao);
  });
  cancelar.addEventListener('click', () => dialog.close());
  dialog.querySelector('[data-dc1-fechar]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', () => {
    if (!confirmado && acionador?.isConnected) acionador.focus();
    pendente = null; acionador = null; confirmado = false;
  });
  window.addEventListener('pageshow', () => {
    enviando = new WeakSet();
    document.querySelectorAll('[data-dc1-enviando]').forEach(botao => { botao.disabled = false; delete botao.dataset.dc1Enviando; });
  });
})();
