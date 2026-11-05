/* Somente miniaturas: uma fila limitada e estado por imagem, sem reenviar cartões. */
window.criarMiniaturasCartoes = function (root, abrir) {
  const registros = new Map(), fila = [];
  let ativos = 0;
  function estado(r, valor) {
    r.botao.dataset.estado = valor;
    r.botao.setAttribute('aria-busy', String(valor === 'carregando'));
    // Mantém o foco durante uma nova carga; o clique só age nos estados pronta/erro.
    r.botao.disabled = false;
    r.botao.setAttribute('aria-disabled', String(valor === 'carregando'));
    r.img.hidden = valor !== 'pronta';
    r.botao.querySelector('[data-miniatura-carga]').hidden = valor !== 'carregando';
    r.botao.querySelector('[data-miniatura-erro]').hidden = valor !== 'erro';
    r.botao.setAttribute('aria-label', valor === 'erro'
      ? 'Foto indisponível. Tentar carregar a miniatura novamente'
      : valor === 'pronta' ? 'Abrir foto do cartão-resposta' : 'Carregando miniatura do cartão-resposta');
  }
  function proxima() {
    while (ativos < 4 && fila.length) {
      const r = fila.shift();
      if (registros.get(r.chave) !== r || !r.botao.isConnected) continue;
      ativos++;
      let finalizada = false;
      const terminar = ok => {
        if (finalizada) return;
        finalizada = true; clearTimeout(timer); r.img.onload = r.img.onerror = null; r.cancelar = null;
        ativos--;
        if (registros.get(r.chave) === r && r.botao.isConnected) estado(r, ok ? 'pronta' : 'erro');
        proxima();
      };
      const timer = setTimeout(() => { r.img.removeAttribute('src'); terminar(false); }, 25000);
      r.cancelar = () => { r.img.onload = r.img.onerror = null; r.img.removeAttribute('src'); terminar(false); };
      r.img.onload = () => {
        if (r.img.decode) r.img.decode().then(() => terminar(true), () => terminar(false));
        else terminar(r.img.naturalWidth > 0);
      };
      r.img.onerror = () => terminar(false);
      r.img.src = r.url;
    }
  }
  function atualizar() {
    const presentes = new Set();
    root.querySelectorAll('[data-miniatura-cartao]').forEach(botao => {
      const url = botao.dataset.miniaturaUrl;
      // A assinatura pode ser renovada no polling sem que a foto tenha mudado.
      // Somente a chave de cache ignora query; o carregamento usa a URL integral, sem modificá-la.
      const chave = botao.dataset.miniaturaCartao + '|' + botao.dataset.miniaturaVersao + '|' + url.split('?')[0];
      presentes.add(chave);
      const anterior = registros.get(chave);
      if (anterior) { anterior.url = url; if (anterior.botao !== botao) botao.replaceWith(anterior.botao); return; }
      const r = {chave, url, botao, img: botao.querySelector('img[data-miniatura-imagem]')};
      registros.set(chave, r); estado(r, 'carregando'); fila.push(r);
      botao.addEventListener('click', () => {
        if (botao.dataset.estado === 'erro') { estado(r, 'carregando'); fila.push(r); proxima(); }
        else if (botao.dataset.estado === 'pronta') abrir(Number(botao.dataset.miniaturaCartao));
      });
    });
    registros.forEach((r, chave) => { if (!presentes.has(chave)) { registros.delete(chave); r.cancelar?.(); } });
    proxima();
  }
  return {atualizar};
};
