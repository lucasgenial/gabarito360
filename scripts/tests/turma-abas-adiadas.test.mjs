import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';

const abasJs = readFileSync(new URL('../../apps/web/public/js/turma-abas.js', import.meta.url), 'utf8');
const cargaJs = readFileSync(new URL('../../apps/web/public/js/carregamento.js', import.meta.url), 'utf8');

function el(extra = {}) {
  const attrs = {};
  return Object.assign({
    hidden: false, dataset: {}, listeners: {}, children: [], textContent: '', innerHTML: '',
    setAttribute(k, v) { attrs[k] = String(v); }, getAttribute(k) { return attrs[k] ?? null; }, hasAttribute(k) { return k in attrs; },
    addEventListener(t, fn) { (this.listeners[t] ||= []).push(fn); }, dispatchEvent() {},
    focus() {}, getBoundingClientRect: () => ({left: 0, right: 10}), contains: () => false,
    replaceChildren() { this.children = []; this.textContent = ''; }, append(...n) { this.children.push(...n); },
    querySelector: () => null, querySelectorAll: () => [],
  }, extra);
}

function montar(respostas) {
  const pedidos = [], eventos = [];
  const paineis = {}, abas = [];
  const raizes = {};
  for (const chave of ['visao-geral', 'acesso', 'disciplinas']) {
    const aba = el({id: 'aba-' + chave}); aba.dataset.turmaAba = chave; abas.push(aba);
    const painel = el({id: chave}); painel.dataset.turmaPainel = chave; paineis[chave] = painel;
    if (chave !== 'visao-geral') {
      const partes = {'[data-carga-conteudo]': el({hidden: true}), '[data-carga-esqueleto]': el({hidden: true}), '[data-carga-feedback]': el()};
      const root = el({querySelector: s => partes[s] ?? null, partes});
      root.dataset.turmaSecaoUrl = JSON.stringify({referencia: 'ref-' + chave, pagina: '/app/turmas/secao'});
      raizes[chave] = root;
      painel.querySelector = s => (s === '[data-turma-secao-adiada]' ? root : null);
    }
  }
  const nav = el({querySelectorAll: () => abas}); nav.dataset.secaoInicial = 'visao-geral';
  const document = {
    readyState: 'complete', activeElement: null,
    querySelector: s => (s === '[data-turma-abas]' ? nav : null),
    querySelectorAll: s => (s === '[data-turma-painel]' ? Object.values(paineis) : []),
    createElement: () => el(), dispatchEvent: e => eventos.push(e),
  };
  const window = {listeners: {}, addEventListener(t, fn) { (this.listeners[t] ||= []).push(fn); }};
  const contexto = {
    window, document, location: {hash: '', pathname: '/app/turmas/detalhes', search: ''}, history: {state: null, replaceState() {}},
    requestAnimationFrame: fn => fn(), setTimeout, clearTimeout, AbortController, Map, Set, WeakSet, JSON, Promise, Error,
    CustomEvent: class { constructor(tipo, o) { this.type = tipo; this.detail = o?.detail; } },
  };
  window.G360Requisicao = async alvo => {
    pedidos.push(alvo.referencia);
    const r = respostas.shift();
    return {ok: r.ok, json: async () => r.corpo};
  };
  vm.createContext(contexto);
  vm.runInContext(cargaJs, contexto);
  Object.assign(contexto, {G360Requisicao: window.G360Requisicao, G360Carga: window.G360Carga});
  vm.runInContext(abasJs, contexto);
  const clicar = chave => abas.find(a => a.dataset.turmaAba === chave).listeners.click[0]({preventDefault() {}});
  return {pedidos, eventos, raizes, paineis, clicar, window};
}

const esperar = () => new Promise(r => setTimeout(r, 5));

test('a visão geral não busca abas adiadas; a primeira abertura busca uma vez', async () => {
  const p = montar([{ok: true, corpo: {html: '<p>Acesso</p>'}}]);
  assert.deepEqual(p.pedidos, []);
  p.clicar('acesso');
  await esperar();
  assert.deepEqual(p.pedidos, ['ref-acesso']);
  assert.equal(p.raizes.acesso.partes['[data-carga-conteudo]'].innerHTML, '<p>Acesso</p>');
  assert.equal(p.raizes.acesso.partes['[data-carga-conteudo]'].hidden, false);
  assert.equal(p.eventos.at(-1).type, 'g360:turma-secao');
  assert.equal(p.eventos.at(-1).detail.secao, 'acesso');
  p.clicar('visao-geral'); p.clicar('acesso'); p.window.listeners.resize[0]();
  await esperar();
  assert.deepEqual(p.pedidos, ['ref-acesso'], 'trocar de aba e redimensionar não repetem a busca');
  assert.equal(p.paineis.disciplinas.hidden, true);
});

test('falha mostra nova tentativa sem conteúdo vazio e recupera ao tentar de novo', async () => {
  const p = montar([{ok: false, corpo: {}}, {ok: true, corpo: {html: '<p>Disciplinas</p>'}}]);
  p.clicar('disciplinas');
  await esperar();
  const feedback = p.raizes.disciplinas.partes['[data-carga-feedback]'];
  assert.match(feedback.textContent, /Não foi possível carregar este bloco/);
  assert.equal(p.raizes.disciplinas.partes['[data-carga-conteudo]'].hidden, true);
  const botao = feedback.children.find(c => c.textContent === 'Tentar novamente');
  assert.ok(botao);
  p.clicar('disciplinas');
  await esperar();
  assert.equal(p.pedidos.length, 1, 'reabrir a aba não dispara busca paralela; a nova tentativa é explícita');
  botao.onclick();
  await esperar();
  assert.deepEqual(p.pedidos, ['ref-disciplinas', 'ref-disciplinas']);
  assert.equal(p.raizes.disciplinas.partes['[data-carga-conteudo]'].innerHTML, '<p>Disciplinas</p>');
});

test('resposta sem html é tratada como falha', async () => {
  const p = montar([{ok: true, corpo: {total: 1}}]);
  p.clicar('acesso');
  await esperar();
  assert.match(p.raizes.acesso.partes['[data-carga-feedback]'].textContent, /Não foi possível carregar/);
  assert.equal(p.eventos.length, 0);
});
