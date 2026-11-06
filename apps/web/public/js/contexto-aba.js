/* URLs estáveis; a revisão compara a tela com a sessão, sem conceder acesso. */
(function (root) {
  'use strict';
  function destino(valor, base) {
    const u = new URL(valor, base), origem = new URL(base);
    if (!['http:', 'https:'].includes(u.protocol) || u.origin !== origem.origin) return valor;
    const publico = /^\/(?:api(?:\/|$)|login(?:\/|$)|logout(?:\/|$)|trocar-senha(?:\/|$)|fale-conosco(?:\/|$)|planos(?:\/|$)|checkout(?:\/|$)|convite(?:\/|$)|confirmar-email(?:\/|$)|redefinir-senha(?:\/|$)|esqueci-senha(?:\/|$)|validar-documento(?:\/|$)|health(?:\/|$)|up(?:\/|$)|js\/|css\/|fonts\/|brand\/|assets\/|favicon|apple-touch|maskable|site\.webmanifest)/;
    if (u.pathname !== '/' && !publico.test(u.pathname)) {
      u.pathname = u.pathname.replace(/^\/acessos\/[^/]+\//, '/app/');
      if (!/^\/app(?:\/|$)/.test(u.pathname)) u.pathname = '/app' + u.pathname;
      ['_ctx', 'acesso', '_revisao'].forEach(n => u.searchParams.delete(n));
    }
    return u.href;
  }
  if (typeof module === 'object' && module.exports) { module.exports = { destino }; return; }
  const revisao = document.querySelector('meta[name="g360-revisao"]')?.content;
  if (!revisao) return;
  root.g360ContextoDesmontar?.();
  try { sessionStorage.removeItem('g360-contexto'); sessionStorage.removeItem('g360-acesso'); } catch (_) {}
  const url = valor => destino(valor, location.href);
  let invalidado = false, canal;
  function aviso() {
    invalidado = true;
    if (document.getElementById('contexto-expirado')) return;
    const caixa = document.createElement('div');
    caixa.id = 'contexto-expirado'; caixa.setAttribute('role', 'alert'); caixa.tabIndex = -1; caixa.className = 'g-aviso atencao';
    const texto = document.createElement('p'); texto.textContent = 'Seu acesso ativo mudou. Esta ação não foi executada.'; caixa.append(texto);
    const ajuda = document.createElement('p'); ajuda.textContent = 'Confira o espaço e o perfil antes de continuar. O que você digitou nesta tela não foi reenviado.'; caixa.append(ajuda);
    const acoes = document.createElement('div'); acoes.className = 'contexto-aviso-acoes'; caixa.append(acoes);
    [['/app/painel', 'Abrir painel atual'], ['/app/vinculos', 'Selecionar outro acesso']].forEach(([href, nome]) => {
      const a = document.createElement('a'); a.href = href; a.className = 'btn btn-secondary'; a.textContent = nome; acoes.append(a);
    });
    (document.querySelector('main') || document.body).prepend(caixa);
    caixa.focus({ preventScroll: false });
    document.dispatchEvent(new CustomEvent('g360:acesso-alterado'));
  }
  function conflito() {
    return new Response(JSON.stringify({ message: 'Seu acesso ativo mudou. Esta ação não foi executada.', selecionar_vinculo: true }), { status: 409, headers: { 'Content-Type': 'application/json' } });
  }
  // Referências são transportadas somente no corpo. A API e suas permissões continuam no servidor.
  root.G360Requisicao = function (descritor, options = {}) {
    if (!descritor || typeof descritor !== 'object' || typeof descritor.url !== 'string' || typeof descritor.referencia !== 'string') return Promise.reject(new TypeError('Recurso indisponível.'));
    const alvo = new URL(descritor.url, location.href);
    if (alvo.origin !== location.origin || alvo.username || alvo.password || !alvo.pathname.startsWith('/app/') || alvo.search || alvo.hash) return Promise.reject(new TypeError('Destino inválido.'));
    const config = Object.assign({}, options), headers = new Headers(config.headers);
    let body;
    if (config.body instanceof FormData) body = new FormData();
    else if (config.body instanceof URLSearchParams) body = new URLSearchParams(config.body);
    else {
      try { body = typeof config.body === 'string' ? JSON.parse(config.body) : Object.assign({}, config.body || {}); }
      catch (_) { return Promise.reject(new TypeError('Dados inválidos.')); }
      if (!body || Array.isArray(body) || typeof body !== 'object') return Promise.reject(new TypeError('Dados inválidos.'));
    }
    if (config.body instanceof FormData) for (const [chave, valor] of config.body.entries()) body.append(chave, valor);
    // O método lógico pertence à referência registrada; o POST de transporte não aceita spoofing.
    if (body instanceof FormData || body instanceof URLSearchParams) body.delete('_method'); else delete body._method;
    const get = nome => body instanceof FormData || body instanceof URLSearchParams ? body.get(nome) : body[nome];
    const set = (nome, valor) => { if (body instanceof FormData || body instanceof URLSearchParams) body.set(nome, valor); else body[nome] = valor; };
    if (get('_recurso') && get('_recurso') !== descritor.referencia) return Promise.reject(new TypeError('Recurso divergente.'));
    set('_recurso', descritor.referencia);
    if (!get('_revisao')) set('_revisao', revisao);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
    if (!get('_token') && csrf) set('_token', csrf);
    if (!headers.has('X-CSRF-TOKEN') && csrf) headers.set('X-CSRF-TOKEN', csrf);
    if (body instanceof FormData) headers.delete('Content-Type');
    else if (!(body instanceof URLSearchParams)) { headers.set('Content-Type', 'application/json'); body = JSON.stringify(body); }
    config.body = body; config.headers = headers; config.method = 'POST'; config.credentials = 'same-origin'; config.cache = 'no-store'; config.redirect = 'error';
    return root.fetch(alvo.href, config);
  };
  const fetchOriginal = root.fetch.bind(root);
  let observador;
  root.g360ContextoDesmontar = () => { canal?.close(); observador?.disconnect(); root.fetch = fetchOriginal; HTMLFormElement.prototype.submit = submitOriginal; document.removeEventListener('submit', aoEnviar, true); root.removeEventListener('pagehide', aoOcultar); root.removeEventListener('pageshow', aoMostrar); blobs.forEach(b => URL.revokeObjectURL(b)); blobs.clear(); };
  root.fetch = function (input, options) {
    const alvo = new URL(input instanceof Request ? input.url : input, location.href);
    if (alvo.origin !== location.origin) return fetchOriginal(input, options);
    const novo = url(alvo.href), contextual = new URL(novo).pathname.startsWith('/app/');
    if (contextual && invalidado) { aviso(); return Promise.resolve(conflito()); }
    const config = Object.assign({}, options);
    const headers = new Headers(config.headers || (input instanceof Request ? input.headers : undefined));
    if (contextual) {
      if (!headers.has('X-G360-Revisao')) headers.set('X-G360-Revisao', revisao);
      config.cache = 'no-store';
    }
    config.headers = headers;
    const pedido = input instanceof Request ? new Request(novo, input) : novo;
    return fetchOriginal(pedido, config).then(r => {
      if (contextual && invalidado && r.ok) return conflito();
      if ([401, 409].includes(r.status)) r.clone().json().then(d => {
        if (d.selecionar_vinculo || d.acesso_alterado) aviso();
        if (d.login_necessario) {
          const a = document.createElement('a'); a.href = '/login'; a.textContent = 'Sua sessão expirou. Entrar novamente'; a.className = 'g-aviso atencao';
          if (!document.getElementById('sessao-expirada')) { a.id = 'sessao-expirada'; document.body.prepend(a); }
        }
      }).catch(() => {});
      return r;
    });
  };
  try {
    if (root.BroadcastChannel) {
      canal = new BroadcastChannel('g360-acesso-ativo');
      canal.onmessage = e => { if (e.data?.tipo === 'selecionado' && e.data.revisao !== revisao) aviso(); };
    }
  } catch (_) {}
  root.g360ContextoSelecionado = nova => {
    if (typeof nova === 'string' && nova !== revisao) {
      root.G360Navegacao?.limparEntradaDeAcesso();
      canal?.postMessage({ tipo: 'selecionado', revisao: nova });
    }
  };
  function prepararForm(form) {
    const destinoForm = new URL(form.action, location.href);
    if (destinoForm.origin !== location.origin) return;
    if (!form.hasAttribute('data-sem-contexto')) form.action = url(form.action);
    if ((form.method || 'get').toLowerCase() === 'get') return;
    // Nunca substituir uma revisão já fixada em formulário antigo.
    if (!form.querySelector('input[name="_revisao"]')) {
      const campo = document.createElement('input'); campo.type = 'hidden'; campo.name = '_revisao'; campo.value = revisao; form.append(campo);
    }
  }
  const imagens = new WeakMap(), blobs = new Set();
  root.g360ImagemContextual = async function (img, valor, opcoes = {}) {
    const descritor = typeof valor === 'string' ? JSON.parse(valor) : valor;
    if (!descritor || typeof descritor !== 'object') throw new Error('Imagem indisponível.');
    const alvo = new URL(descritor.url, location.href);
    if (alvo.origin !== location.origin || !['http:', 'https:'].includes(alvo.protocol)) throw new Error('Imagem indisponível.');
    const anterior = imagens.get(img); anterior?.abort.abort();
    const tarefa = { url: valor, abort: new AbortController() }; imagens.set(img, tarefa);
    const iniciais = img.hasAttribute('data-imagem-avatar') ? img.nextElementSibling : null;
    if (iniciais) {
      img.style.display = 'none'; iniciais.style.display = 'grid';
      img.addEventListener('load', async () => {
        try {
          if (img.decode) await img.decode();
          if (imagens.get(img) === tarefa && !invalidado && !tarefa.abort.signal.aborted) { img.style.display = 'block'; iniciais.style.display = 'none'; }
        } catch (_) { if (imagens.get(img) === tarefa) { img.style.display = 'none'; iniciais.style.display = 'grid'; } }
      }, { once: true });
      img.addEventListener('error', () => { if (imagens.get(img) === tarefa) { img.style.display = 'none'; iniciais.style.display = 'grid'; } }, { once: true });
    }
    opcoes.signal?.addEventListener('abort', () => tarefa.abort.abort(), { once: true });
    try {
      const r = await root.G360Requisicao(descritor, { credentials: 'same-origin', cache: 'no-store', signal: tarefa.abort.signal });
      if (!r.ok) throw new Error('Imagem indisponível.');
      const blob = await r.blob();
      if (!/^image\/(?:jpeg|png|webp|gif|avif)(?:;|$)/i.test(blob.type)) throw new Error('Imagem indisponível.');
      if (imagens.get(img) !== tarefa || tarefa.abort.signal.aborted || invalidado) return;
      const local = URL.createObjectURL(blob); blobs.add(local);
      const liberar = () => { URL.revokeObjectURL(local); blobs.delete(local); };
      img.addEventListener('load', liberar, { once: true }); img.addEventListener('error', liberar, { once: true });
      img.src = local;
    } catch (e) {
      if (imagens.get(img) === tarefa && !tarefa.abort.signal.aborted) img.dispatchEvent(new Event('error'));
      throw e;
    }
  };
  function preparar() {
    document.querySelectorAll('a[href]:not([data-sem-contexto])').forEach(a => {
      if (!a.getAttribute('href').startsWith('#')) { const novo = url(a.href); if (novo !== a.href) a.href = novo; }
    });
    document.querySelectorAll('form').forEach(prepararForm);
    document.querySelectorAll('img[data-imagem-contextual]').forEach(img => {
      const valor = img.dataset.imagemContextual;
      if (valor && imagens.get(img)?.url !== valor) root.g360ImagemContextual(img, valor).catch(() => {});
    });
  }
  function aoEnviar(e) {
    if (invalidado) { e.preventDefault(); e.stopImmediatePropagation(); aviso(); return; }
    prepararForm(e.target);
  }
  document.addEventListener('submit', aoEnviar, true);
  const submitOriginal = HTMLFormElement.prototype.submit;
  HTMLFormElement.prototype.submit = function () { if (invalidado) { aviso(); return; } prepararForm(this); return submitOriginal.call(this); };
  function iniciar() {
    preparar();
    observador = new MutationObserver(preparar); observador.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-imagem-contextual'] });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
  function aoOcultar() { blobs.forEach(b => URL.revokeObjectURL(b)); blobs.clear(); }
  function aoMostrar(e) { if (e.persisted) aviso(); }
  root.addEventListener('pagehide', aoOcultar);
  root.addEventListener('pageshow', aoMostrar);
})(typeof window !== 'undefined' ? window : globalThis);
