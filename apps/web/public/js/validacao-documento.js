(() => {
  'use strict';
  const form = document.getElementById('documento-validacao');
  if (!form) return;
  const status = document.getElementById('documento-status');
  const textos = {
    nao_encontrado: 'Emissão não encontrada. Confira o código. Documentos antigos sem selo não podem ser validados.',
    emissao_encontrada: 'Registro de emissão encontrado. A integridade do PDF ainda não foi conferida. Selecione o arquivo para comparar.',
    arquivo_confirmado: 'Arquivo íntegro confirmado. Este PDF corresponde exatamente ao arquivo emitido pelo Gabarito360.',
    arquivo_divergente: 'Arquivo divergente. Este PDF não corresponde ao arquivo emitido com este código. Solicite o documento original ao emissor.',
  };
  function mostrar(texto, estado) { status.hidden = false; status.dataset.estado = estado; status.className = 'g-aviso ' + (estado === 'arquivo_confirmado' ? 'sucesso' : ['erro', 'arquivo_divergente'].includes(estado) ? 'erro' : 'info'); status.textContent = texto; }
  for (const input of form.querySelectorAll('input')) input.addEventListener('input', () => { if (!form.querySelector('button').disabled) status.hidden = true; });
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('button');
    if (button.disabled) return;
    const codigo = form.elements.codigo.value.trim().toUpperCase();
    const file = document.getElementById('arquivo-documento').files[0];
    button.disabled = true; form.setAttribute('aria-busy', 'true');
    const inputs = [...form.querySelectorAll('input:not([type="hidden"])')];
    inputs.forEach(input => { input.disabled = true; });
    mostrar('Conferindo documento…', 'carregando');
    try {
      const dados = { codigo };
      if (file) {
        if (!file.size || file.size > 50 * 1024 * 1024) throw new Error('Escolha um PDF com até 50 MB.');
        if (!window.crypto?.subtle) throw new Error('Este navegador não permite conferir o arquivo. Acesse por HTTPS em um navegador atualizado.');
        const bytes = await file.arrayBuffer();
        if (new TextDecoder().decode(bytes.slice(0, 5)) !== '%PDF-') throw new Error('O arquivo selecionado não é um PDF.');
        dados.sha256 = Array.from(new Uint8Array(await crypto.subtle.digest('SHA-256', bytes)), b => b.toString(16).padStart(2, '0')).join('');
      }
      const response = await fetch(form.action, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form.elements._token.value }, body: JSON.stringify(dados), cache: 'no-store', credentials: 'same-origin' });
      if (!response.ok) throw new Error(response.status === 429 ? 'Muitas consultas em pouco tempo. Aguarde um minuto e tente novamente.' : response.status === 422 ? 'Confira o código informado no rodapé do documento.' : 'Validação temporariamente indisponível. Tente novamente.');
      const { data } = await response.json();
      if (!textos[data?.estado]) throw new Error('Não foi possível conferir a resposta. Tente novamente.');
      let texto = textos[data.estado];
      if (data.emitido_em) texto += ` Emissão: ${data.emitido_em}. Modelo: ${data.modelo === 'professor' ? 'professor' : 'aluno'}. ${data.instituicao || ''}`;
      mostrar(texto, data.estado);
    } catch (error) { mostrar(error instanceof TypeError ? 'Sem conexão com a validação. Tente novamente.' : error.message, 'erro'); }
    finally { button.disabled = false; inputs.forEach(input => { input.disabled = false; }); form.removeAttribute('aria-busy'); }
  });
})();
