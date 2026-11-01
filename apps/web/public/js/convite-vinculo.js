(async function () {
  'use strict';
  const chave = 'g360-convite-vinculo', painel = document.getElementById('convite-vinculo');
  const mensagem = document.getElementById('convite-mensagem');
  const fragmento = new URLSearchParams(location.hash.slice(1));
  // O segredo sai do histórico antes de consultar a API ou navegar para login.
  if (location.hash) history.replaceState(null, '', location.pathname + location.search);
  let convite = null;
  try {
    if (fragmento.has('token') || fragmento.has('convite')) {
      sessionStorage.removeItem(chave);
      const id = fragmento.get('convite'), token = fragmento.get('token');
      if (/^[1-9][0-9]*$/.test(id || '') && /^[A-Za-z0-9]{64}$/.test(token || '')) {
        convite = { convite: id, token, ate: Date.now() + 20 * 60 * 1000 };
        sessionStorage.setItem(chave, JSON.stringify(convite));
      }
    } else { convite = JSON.parse(sessionStorage.getItem(chave) || 'null'); }
    if (!convite || convite.ate <= Date.now()) { sessionStorage.removeItem(chave); mensagem.textContent = 'Abra novamente o link recebido por e-mail para conferir este convite.'; return; }
    if (painel.dataset.autenticado !== '1') { mensagem.textContent = 'Seu convite está pronto para conferência.'; document.getElementById('convite-entrar').hidden = false; return; }
    if (painel.dataset.trocarSenha === '1') { mensagem.textContent = 'Há uma troca de senha pendente.'; document.getElementById('convite-senha').hidden = false; return; }
    const resp = await fetch(painel.dataset.consultar, { method: 'POST', credentials: 'same-origin', headers: {
      'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }, body: JSON.stringify({ convite: convite.convite, token: convite.token }) });
    if (resp.status === 401) { mensagem.textContent = 'Sua sessão expirou. Entre novamente.'; document.getElementById('convite-entrar').hidden = false; return; }
    const resultado = await resp.json();
    if (!resp.ok) {
      if (resp.status < 500) sessionStorage.removeItem(chave);
      mensagem.textContent = resultado.message || 'Não foi possível conferir o convite. Abra novamente o link.'; return;
    }
    sessionStorage.removeItem(chave);
    document.querySelectorAll('[data-convite]').forEach(e => e.textContent = resultado.data[e.dataset.convite] || '—');
    document.getElementById('convite-referencia').value = resultado.referencia;
    document.getElementById('convite-detalhes').hidden = false;
    mensagem.textContent = 'Confira os dados antes de aceitar ou recusar.';
  } catch (e) { mensagem.textContent = 'Não foi possível conferir agora. Verifique a conexão e abra novamente o link.'; }
})();
