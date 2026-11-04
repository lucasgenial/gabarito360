(() => {
  const form = document.querySelector('[data-prova-curriculo]');
  if (!form || !window.fetch || !window.AbortController) return;
  let sequencia = 0, requisicao;
  const salvar = form.querySelector('[data-curriculo-salvar]');
  const erroInicial = form.querySelector('[aria-invalid=true],.field-error');
  erroInicial?.focus();
  async function preparar(botao, acao) {
    const ordem = ++sequencia;
    requisicao?.abort(); const controle = new AbortController(); requisicao = controle;
    let esgotado = false;
    const limite = setTimeout(() => { esgotado = true; controle.abort(); }, 25000);
    const editor = form.querySelector('[data-curriculo-editor]');
    const foco = document.activeElement?.id;
    const dados = new FormData(form); dados.set('acao', acao);
    editor.setAttribute('aria-busy', 'true'); salvar.disabled = true;
    const status = editor.querySelector('[data-curriculo-status]');
    if (status) status.textContent = 'Conferindo disciplinas e turmas…';
    try {
      const resposta = await fetch(botao.formAction, { method: 'POST', body: dados, credentials: 'same-origin', signal: controle.signal, headers: {'X-Curriculo-Fragmento': '1', Accept: 'text/html'} });
      const texto = await resposta.text();
      if (ordem !== sequencia) return;
      const doc = new DOMParser().parseFromString(texto, 'text/html');
      const novo = doc.querySelector('[data-curriculo-editor]');
      if (!novo) throw new Error('Resposta indisponível');
      editor.replaceWith(novo); salvar.disabled = novo.dataset.pronto !== '1';
      const erroCampo = novo.querySelector('[aria-invalid=true],.field-error');
      const destino = erroCampo || (acao === 'rever' ? novo.querySelector('#pc-revisao') : null)
        || (acao === 'opcoes' && foco ? document.getElementById(foco) : null)
        || (acao.startsWith('remover:') ? novo.querySelector('button[value^="remover:"]') : null)
        || novo.querySelector('#pc-nova-disciplina') || novo.querySelector('button[value=opcoes]');
      destino?.focus({preventScroll:acao !== 'rever'});
      const novoStatus = novo.querySelector('[data-curriculo-status]');
      if (novoStatus && !novoStatus.textContent.trim()) novoStatus.textContent = acao === 'adicionar' ? 'Disciplina adicionada à seleção.' : acao.startsWith('remover:') ? 'Disciplina removida da seleção.' : 'Opções conferidas.';
    } catch (erro) {
      if (ordem !== sequencia || (erro.name === 'AbortError' && !esgotado)) return;
      editor.setAttribute('aria-busy', 'false');
      if (status) status.textContent = 'Não foi possível conferir as opções. Sua seleção foi mantida. Use Atualizar opções para tentar novamente.';
    } finally {
      clearTimeout(limite);
    }
  }
  form.addEventListener('submit', evento => {
    const botao = evento.submitter;
    if (!botao?.hasAttribute('data-curriculo-intencao')) return;
    evento.preventDefault(); preparar(botao, botao.value);
  });
  form.addEventListener('change', evento => {
    if (!evento.target.matches('[data-curriculo-turma]')) return;
    const botao = form.querySelector('[data-curriculo-intencao][value=opcoes]');
    if (botao) preparar(botao, 'opcoes');
  });
})();
