/* Cada foto tem uma chave estável: repetir a requisição não cria outro cartão. */
window.criarFilaCartoes = function (opcoes) {
  var ativos = new Map(), rodando = false;
  function guardar() {
    try { sessionStorage.setItem(opcoes.chaveSessao, JSON.stringify(Array.from(ativos.keys()))); } catch (_) {}
  }
  function texto(li, mensagem) { li.textContent = mensagem; }
  function botao(li, titulo, acao) {
    var b = document.createElement('button'); b.type = 'button'; b.className = 'btn btn-sm btn-secondary';
    b.textContent = titulo; b.style.margin = '6px';
    b.addEventListener('click', function () { b.disabled = true; acao(); }); li.appendChild(b);
  }
  async function pedir(url, init) {
    var controlador = new AbortController(), timer = setTimeout(function () { controlador.abort(); }, 30000);
    try {
      var r = await fetch(url, Object.assign({credentials: 'same-origin', signal: controlador.signal,
        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': opcoes.csrf}}, init));
      var dados;
      try { dados = await r.json(); } catch (_) {
        var invalida = new Error(opcoes.erroHttp(r.redirected ? 401 : (r.ok ? 502 : r.status)));
        invalida.status = r.redirected ? 401 : (r.ok ? 502 : r.status); throw invalida;
      }
      if (!r.ok) { var erro = new Error(dados.erro || dados.message || opcoes.erroHttp(r.status)); erro.status = r.status; throw erro; }
      if (!dados || !['na_fila','processando','concluido','erro','recusado','substituido'].includes(dados.processamento)) {
        var formato = new Error('O servidor não confirmou o estado deste envio.'); formato.status = 502; throw formato;
      }
      return dados;
    } finally { clearTimeout(timer); }
  }
  function acompanhar(chave, li) { ativos.set(chave, li); guardar(); }
  function mostrar(chave, li, d) {
    li.dataset.processamento = d.processamento;
    if (d.processamento === 'concluido') {
      ativos.delete(chave); guardar();
      li.innerHTML = opcoes.resultado(d.resultado || {});
      if (opcoes.atualizar) opcoes.atualizar();
    } else if (d.processamento === 'na_fila' || d.processamento === 'processando') {
      texto(li, d.processamento === 'na_fila' ? 'Na fila de leitura. Você já pode fotografar o próximo cartão.' : 'Lendo cartão…');
      acompanhar(chave, li);
    } else {
      // Erro recuperável também precisa sobreviver à recarga, sem reenviar a foto.
      if (d.processamento === 'erro' && d.pode_repetir) acompanhar(chave, li);
      else { ativos.delete(chave); guardar(); }
      texto(li, d.mensagem || 'Não foi possível concluir esta leitura. Fotografe novamente.');
    }
    if (d.pode_repetir) botao(li, 'Tentar leitura novamente', async function () {
      try { mostrar(chave, li, await pedir(opcoes.envioUrl.replace('__ENVIO__', chave) + '/repetir', {method: 'POST'})); }
      catch (e) { texto(li, 'Não foi possível confirmar a nova tentativa. Acompanhando o envio…'); acompanhar(chave, li); }
    });
  }
  async function ciclo() {
    if (rodando || document.hidden || !ativos.size) return;
    rodando = true;
    var fila = Array.from(ativos.entries());
    async function proximo() {
      while (fila.length) {
        var item = fila.shift(), chave = item[0], li = item[1];
        try { mostrar(chave, li, await pedir(opcoes.envioUrl.replace('__ENVIO__', chave))); }
        catch (e) {
          texto(li, (e.status === 401 || e.status === 419) ? opcoes.erroHttp(e.status) : 'Aguardando conexão para acompanhar a leitura.');
          if ([401, 403, 404, 419].includes(e.status)) {
            ativos.delete(chave); guardar();
            texto(li, e.message);
          }
        }
      }
    }
    try { await Promise.all([proximo(), proximo(), proximo()]); } finally { rodando = false; }
  }
  async function enviar(file) {
    var bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 15) | 64; bytes[8] = (bytes[8] & 63) | 128;
    var hex = Array.from(bytes, function (b) { return b.toString(16).padStart(2, '0'); }).join('');
    var chave = hex.slice(0,8)+'-'+hex.slice(8,12)+'-'+hex.slice(12,16)+'-'+hex.slice(16,20)+'-'+hex.slice(20);
    var li = opcoes.linha('Enviando foto…'), foto = await opcoes.reduzir(file);
    async function upload() {
      texto(li, 'Enviando foto…');
      var fd = new FormData(); fd.append('imagem', foto); fd.append('envio_id', chave);
      try {
        var d = await pedir(opcoes.uploadUrl, {method: 'POST', body: fd});
        foto = null; mostrar(chave, li, d);
        if (d.processamento === 'concluido') return 'Leitura concluída. Confira o resultado na lista de envios.';
        if (['recusado', 'substituido'].includes(d.processamento)) return 'Envio encerrado. Confira o motivo na lista de envios.';
        return d.processamento === 'erro' ? 'Foto salva. Use “Tentar leitura novamente” na lista de envios.' : 'Foto salva. A leitura está na fila.';
      } catch (e) {
        texto(li, 'Envio não confirmado: ' + e.message);
        if (![401, 403, 413, 419, 422].includes(e.status)) botao(li, 'Tentar envio novamente', upload);
        return 'Envio não confirmado. Confira a lista de envios antes de fotografar novamente.';
      }
    }
    return upload(); // Termina no aceite, sem esperar o OMR.
  }
  try {
    var salvos = JSON.parse(sessionStorage.getItem(opcoes.chaveSessao) || '[]');
    if (Array.isArray(salvos)) salvos.filter(function (c) {return /^[0-9a-f-]{36}$/i.test(c);}).slice(0,200)
      .forEach(function (c) { acompanhar(c, opcoes.linha('Retomando acompanhamento da leitura…')); });
  } catch (_) {}
  if (typeof opcoes.envioInicial === 'string' && /^[0-9a-f-]{36}$/i.test(opcoes.envioInicial) && !ativos.has(opcoes.envioInicial)) {
    acompanhar(opcoes.envioInicial, opcoes.linha('Acompanhando a foto enviada…'));
  }
  setInterval(ciclo, 2500);
  document.addEventListener('visibilitychange', ciclo);
  ciclo();
  return {enviar: enviar};
};
