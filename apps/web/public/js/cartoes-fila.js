/* Cada foto tem uma chave estável: repetir a requisição não cria outro cartão. */
window.criarFilaCartoes = function (opcoes) {
  var ativos = new Map(), rodando = false;
  function guardar() {
    try { sessionStorage.setItem(opcoes.chaveSessao, JSON.stringify(Array.from(ativos.keys()))); } catch (_) {}
  }
  function texto(li, mensagem) { li.textContent = (li.dataset.arquivo ? li.dataset.arquivo + ' — ' : '') + mensagem; }
  function esc(t){return String(t).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
  function botao(li, titulo, acao) {
    var b = document.createElement('button'); b.type = 'button'; b.className = 'btn btn-sm btn-secondary';
    b.textContent = titulo; b.style.margin = '6px';
    b.addEventListener('click', function () { b.disabled = true; acao(); }); li.appendChild(b);
  }
  async function pedir(url, init) {
    var controlador = new AbortController(), timer = setTimeout(function () { controlador.abort(); }, 30000);
    try {
      var r = await G360Requisicao(url, Object.assign({credentials: 'same-origin', signal: controlador.signal,
        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': opcoes.csrf}}, init));
      var dados;
      try { dados = await r.json(); } catch (_) {
        var invalida = new Error(opcoes.erroHttp(r.redirected ? 401 : (r.ok ? 502 : r.status)));
        invalida.status = r.redirected ? 401 : (r.ok ? 502 : r.status); throw invalida;
      }
      if (!r.ok) { var erro = new Error(dados.erro || dados.message || opcoes.erroHttp(r.status)); erro.status = r.status; throw erro; }
      if (!dados || !['na_fila','processando','concluido','erro','recusado','substituido','aguardando_confirmacao'].includes(dados.processamento)) {
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
      li.innerHTML = (li.dataset.arquivo?'<b>'+esc(li.dataset.arquivo)+'</b><br>':'')+opcoes.resultado(d.resultado || {});
      if (opcoes.atualizar) opcoes.atualizar();
    } else if (d.processamento === 'na_fila' || d.processamento === 'processando') {
      texto(li, d.processamento === 'na_fila' ? 'Na fila de leitura. Você já pode fotografar o próximo cartão.' : 'Lendo cartão…');
      acompanhar(chave, li);
    } else {
      // Erro recuperável também precisa sobreviver à recarga, sem reenviar a foto.
      if (d.processamento === 'erro' && d.pode_repetir) acompanhar(chave, li);
      else { ativos.delete(chave); guardar(); }
      texto(li, d.mensagem || 'Não foi possível concluir esta leitura. Fotografe novamente.');
      if(d.processamento==='aguardando_confirmacao'&&d.resultado?._recursos?.tentativa){
        var link=document.createElement('button');link.type='button';link.className='btn btn-secondary';link.textContent='Conferir nova leitura';
        link.addEventListener('click',()=>G360Navegacao.abrir(d.resultado._recursos.tentativa));li.appendChild(link);
      }
    }
    if (d.pode_repetir) botao(li, 'Tentar leitura novamente', async function () {
      try { mostrar(chave, li, await pedir(opcoes.repetirUrl, {body:JSON.stringify({chave:chave})})); }
      catch (e) { texto(li, 'Não foi possível confirmar o reprocessamento. Acompanhando o envio…'); acompanhar(chave, li); }
    });
    if(opcoes.resumo)opcoes.resumo();
  }
  async function ciclo() {
    if (rodando || document.hidden || !ativos.size) return;
    rodando = true;
    var fila = Array.from(ativos.entries());
    async function proximo() {
      while (fila.length) {
        var item = fila.shift(), chave = item[0], li = item[1];
        try { mostrar(chave, li, await pedir(opcoes.envioUrl, {body:JSON.stringify({chave:chave})})); }
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
    var li = opcoes.linha('Verificando foto…'), foto;
    li.dataset.arquivo=file.name||'Foto';li.dataset.processamento='validando';
    try{foto=await opcoes.reduzir(file);}catch(e){texto(li,'Não foi possível preparar esta foto. Escolha outra imagem.');li.dataset.processamento='recusado';if(opcoes.resumo)opcoes.resumo();return {aceita:false,mensagem:'Escolha outra foto para continuar.'};}
    async function upload() {
      texto(li, 'Verificando qualidade e enviando foto…');
      var fd = new FormData(); fd.append('imagem', foto); fd.append('envio_id', chave);
      try {
        var d = await pedir(opcoes.uploadUrl, {method: 'POST', body: fd});
        foto = null; mostrar(chave, li, d);
        if (d.processamento === 'concluido') return {aceita:true,mensagem:'Leitura concluída. Confira o resultado na lista de envios.'};
        if (['recusado', 'substituido'].includes(d.processamento)) return {aceita:false,mensagem:d.mensagem||'Envio encerrado. Confira o motivo na lista de envios.'};
        if(d.processamento==='aguardando_confirmacao')return {aceita:true,mensagem:'Nova leitura aguardando conferência. A correção atual foi mantida.'};
        return {aceita:true,mensagem:d.processamento === 'erro' ? 'Foto recebida. Use “Tentar leitura novamente” na lista de envios.' : 'Qualidade inicial conferida. Foto recebida; leitura na fila.'};
      } catch (e) {
        li.dataset.processamento='recusado';texto(li, (e.status===422?'Foto recusada para leitura: ':'Envio não confirmado: ') + e.message);
        if (![401, 403, 413, 419, 422].includes(e.status)) botao(li, 'Tentar envio novamente', upload);
        if(e.status===422&&opcoes.escolherOutra)botao(li,'Escolher outra foto',function(){opcoes.escolherOutra();});
        if(opcoes.resumo)opcoes.resumo();return {aceita:false,mensagem:e.message};
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
