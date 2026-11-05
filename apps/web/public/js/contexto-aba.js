/* O vínculo é identificado na rota; credenciais permanecem no servidor. */
(function (root) {
  'use strict';
  const valido = id => /^(vinculo|pessoal|interno)-[1-9][0-9]{0,17}$/.test(id || '');
  function destino(valor, base, id) {
    const u = new URL(valor, base), origem = new URL(base);
    if (!valido(id) || !['http:', 'https:'].includes(u.protocol) || u.origin !== origem.origin) return valor;
    // A API já associa a imagem ao ator/vínculo. Alterar a query invalida sua assinatura.
    if (u.searchParams.has('signature')) return valor;
    u.searchParams.delete('_ctx');
    const publico = /^\/(?:acessos\/|api\/|login(?:\/|$)|logout(?:\/|$)|vinculos(?:\/|$)|trocar-senha(?:\/|$)|fale-conosco(?:\/|$)|planos(?:\/|$)|checkout(?:\/|$)|convite(?:\/|$)|confirmar-email(?:\/|$)|redefinir-senha(?:\/|$)|esqueci-senha(?:\/|$)|validar-documento(?:\/|$)|health(?:\/|$)|up(?:\/|$))/;
    if (u.pathname !== '/' && !publico.test(u.pathname)) u.pathname = '/acessos/' + id + u.pathname;
    return u.href;
  }
  if (typeof module === 'object' && module.exports) { module.exports = { destino }; return; }
  const id = document.querySelector('meta[name="g360-acesso"]')?.content;
  if (!valido(id)) return;
  try { sessionStorage.removeItem('g360-contexto'); sessionStorage.setItem('g360-acesso', id); } catch (e) {}
  const url = valor => destino(valor, location.href, id);
  const fetchOriginal = root.fetch.bind(root);
  root.fetch = function (input, options) {
    const alvo = new URL(input instanceof Request ? input.url : input, location.href);
    if (alvo.origin !== location.origin) return fetchOriginal(input, options);
    const config = Object.assign({}, options);
    const headers = new Headers(config.headers || (input instanceof Request ? input.headers : undefined));
    const novo = url(alvo.href);
    if (new URL(novo).pathname.startsWith('/acessos/')) headers.set('X-G360-Acesso', id);
    config.headers = headers;
    const pedido = input instanceof Request ? new Request(novo, input) : novo;
    return fetchOriginal(pedido, config).then(r => {
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
    const novo = url(form.action); if (form.action !== novo) form.action = novo;
    form.querySelectorAll('input[name="_ctx"]').forEach(campo => campo.remove());
  }
  function preparar() {
    document.querySelectorAll('a[href]:not([data-sem-contexto])').forEach(a => {
      if (!a.getAttribute('href').startsWith('#')) { const novo = url(a.href); if (novo !== a.href) a.href = novo; }
    });
    document.querySelectorAll('form').forEach(prepararForm);
    document.querySelectorAll('img[src]').forEach(img => {
      if (/^\/(escolas|redes|alunos|usuarios)\/|^\/perfil\/foto/.test(new URL(img.src, location.href).pathname)) { const novo = url(img.src); if (novo !== img.src) img.src = novo; }
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
