import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';

const codigo = readFileSync(new URL('../../apps/web/public/js/cartoes-miniaturas.js', import.meta.url), 'utf8');

function botao(id) {
  const filho = () => ({hidden: false});
  const partes = {'[data-miniatura-carga]': filho(), '[data-miniatura-erro]': filho()};
  const img = {hidden: true, removeAttribute() {}, naturalWidth: 1};
  return {
    dataset: {miniaturaCartao: String(id), miniaturaVersao: '1', miniaturaUrl: '/cartoes/imagem/' + id}, isConnected: true,
    img, attrs: {}, setAttribute(k, v) { this.attrs[k] = v; }, addEventListener(_, fn) { this.clicar = fn; },
    replaceWith(outro) { this.isConnected = false; outro.isConnected = true; },
    querySelector(sel) { return sel === 'img[data-miniatura-imagem]' ? img : partes[sel]; },
  };
}

function abrir({comObserver}) {
  const botoes = Array.from({length: 10}, (_, i) => botao(i + 1));
  const pedidos = [];
  let callback = null; const observados = new Set();
  const contexto = {window: {}, Map, Set, WeakMap, Promise, AbortController, setTimeout, clearTimeout};
  contexto.window.g360ImagemContextual = (img, url) => { pedidos.push(url); setTimeout(() => img.onload?.(), 0); return Promise.resolve(); };
  if (comObserver) contexto.IntersectionObserver = class { constructor(fn) { callback = fn; } observe(el) { observados.add(el); } unobserve(el) { observados.delete(el); } };
  vm.runInNewContext(codigo, contexto);
  const root = {querySelectorAll: () => botoes};
  const mini = contexto.window.criarMiniaturasCartoes(root, () => {});
  mini.atualizar();
  const mostrar = (...ids) => callback(ids.map(id => ({isIntersecting: true, target: botoes[id - 1]})));
  return {mini, botoes, pedidos, observados, mostrar};
}

test('fora da área visível nenhuma miniatura é pedida', () => {
  const p = abrir({comObserver: true});
  assert.equal(p.pedidos.length, 0);
  assert.equal(p.observados.size, 10);
  assert.ok(p.botoes.every(b => b.dataset.estado === 'carregando'));
});

test('só as miniaturas que entram na área visível são pedidas, e uma vez', async () => {
  const p = abrir({comObserver: true});
  p.mostrar(1, 2);
  assert.deepEqual(p.pedidos, ['/cartoes/imagem/1', '/cartoes/imagem/2']);
  assert.equal(p.observados.size, 8);
  p.mini.atualizar();
  assert.equal(p.pedidos.length, 2);
  await new Promise(r => setTimeout(r, 5));
  assert.equal(p.botoes[0].dataset.estado, 'pronta');
  assert.equal(p.botoes[2].dataset.estado, 'carregando');
});

test('sem IntersectionObserver mantém a fila limitada a quatro cargas', async () => {
  const p = abrir({comObserver: false});
  assert.equal(p.pedidos.length, 4);
  await new Promise(r => setTimeout(r, 20));
  assert.equal(p.pedidos.length, 10);
});

test('polling recria os botões; o antigo volta, continua observado e carrega; erro, nova tentativa e filtro', async () => {
  let botoes = Array.from({length: 6}, (_, i) => botao(i + 1));
  const originais = botoes, pedidos = [], observados = new Set();
  let callback, falhar = true;
  const contexto = {window: {}, Map, Set, WeakMap, Promise, AbortController, setTimeout, clearTimeout};
  contexto.window.g360ImagemContextual = (img, url) => { pedidos.push(url); setTimeout(() => (falhar && url.endsWith('/1') ? img.onerror() : img.onload()), 0); return Promise.resolve(); };
  contexto.IntersectionObserver = class { constructor(fn) { callback = fn; } observe(el) { observados.add(el); } unobserve(el) { observados.delete(el); } };
  vm.runInNewContext(codigo, contexto);
  const mini = contexto.window.criarMiniaturasCartoes({querySelectorAll: () => botoes}, () => {});
  mini.atualizar();
  originais.forEach(b => { b.isConnected = false; });
  botoes = Array.from({length: 6}, (_, i) => botao(i + 1));
  mini.atualizar();
  assert.equal(observados.size, 6);
  assert.ok([...observados].every(b => originais.includes(b) && b.isConnected));
  callback([{isIntersecting: true, target: originais[0]}, {isIntersecting: true, target: originais[5]}]);
  assert.deepEqual(pedidos, ['/cartoes/imagem/1', '/cartoes/imagem/6']);
  await new Promise(r => setTimeout(r, 5));
  assert.equal(originais[0].dataset.estado, 'erro');
  assert.equal(originais[0].attrs['aria-busy'], 'false');
  assert.equal(originais[5].dataset.estado, 'pronta');
  falhar = false;
  originais[0].clicar(); originais[0].clicar();
  assert.equal(pedidos.length, 3, 'duplo clique não duplica o pedido');
  await new Promise(r => setTimeout(r, 5));
  assert.equal(originais[0].dataset.estado, 'pronta');
  botoes = [botao(1), botao(2)];
  mini.atualizar();
  assert.equal(observados.size, 1, 'itens filtrados deixam de ser observados');
});
