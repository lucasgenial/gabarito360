/* Contexto imutável desta página. O token API fica no servidor. */
(function (root) {
  'use strict';
  function destino(valor, base, id) {
    const u = new URL(valor, base), origem = new URL(base);
    if (!['http:', 'https:'].includes(u.protocol) || u.origin !== origem.origin) return valor;
    if (['/login', '/logout', '/vinculos'].includes(u.pathname)) return valor;
    u.searchParams.set('_ctx', id);
    return u.href;
  }
  if (typeof module === 'object' && module.exports) { module.exports = { destino }; return; }
  const id = document.querySelector('meta[name="g360-contexto"]')?.content;
  if (!id) return;
  try { sessionStorage.setItem('g360-contexto', id); } catch (e) {}
  const url = valor => destino(valor, location.href, id);
  const fetchOriginal = root.fetch.bind(root);
  root.fetch = function (input, options) {
    const alvo = new URL(input instanceof Request ? input.url : input, location.href);
    if (alvo.origin !== location.origin) return fetchOriginal(input, options);
    const config = Object.assign({}, options);
    const headers = new Headers(config.headers || (input instanceof Request ? input.headers : undefined));
    headers.set('X-G360-Contexto', id); config.headers = headers;
    return fetchOriginal(input, config).then(r => {
      if ([401, 409].includes(r.status)) r.clone().json().then(data => {
        if ((data.selecionar_vinculo || data.login_necessario) && !document.getElementById('contexto-expirado')) {
          const aviso = document.createElement('div'); aviso.id = 'contexto-expirado'; aviso.setAttribute('role', 'alert'); aviso.className = 'card vinculo-card';
          const link = document.createElement('a'); link.href = data.login_necessario ? '/login' : '/vinculos?trocar=1'; link.textContent = data.login_necessario ? 'Sua sessão expirou. Entrar novamente' : 'Seu acesso mudou ou expirou. Escolher vínculo'; aviso.append(link); document.body.prepend(aviso);
        }
      }).catch(() => {});
      return r;
    });
  };
  function prepararForm(form) {
    if (new URL(form.action, location.href).origin !== location.origin || form.hasAttribute('data-sem-contexto')) return;
    let campo = form.querySelector('input[name="_ctx"]');
    if (!campo) { campo = document.createElement('input'); campo.type = 'hidden'; campo.name = '_ctx'; form.append(campo); }
    campo.value = id;
  }
  function preparar() {
    document.querySelectorAll('a[href]:not([data-sem-contexto])').forEach(a => {
      if (!a.getAttribute('href').startsWith('#')) { const novo = url(a.href); if (novo !== a.href) a.href = novo; }
    });
    document.querySelectorAll('form').forEach(prepararForm);
    document.querySelectorAll('img[src]').forEach(img => {
      if (/^\/(escolas|redes|alunos)\//.test(new URL(img.src, location.href).pathname)) { const novo = url(img.src); if (novo !== img.src) img.src = novo; }
    });
  }
  document.addEventListener('submit', e => prepararForm(e.target), true);
  const submitOriginal = HTMLFormElement.prototype.submit;
  HTMLFormElement.prototype.submit = function () { prepararForm(this); return submitOriginal.call(this); };
  document.addEventListener('DOMContentLoaded', () => {
    preparar();
    new MutationObserver(preparar).observe(document.body, { childList: true, subtree: true });
  });
  root.addEventListener('pageshow', e => { if (e.persisted) location.reload(); });
})(typeof window !== 'undefined' ? window : globalThis);
