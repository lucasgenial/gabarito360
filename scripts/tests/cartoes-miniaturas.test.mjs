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
    img, setAttribute() {}, addEventListener(_, fn) { this.clicar = fn; }, replaceWith() {},
    querySelector(sel) { return sel === 'img[data-miniatura-imagem]' ? img : partes[sel]; },
  };
}

function abrir({comObserver}) {
  const botoes = Array.from({length: 10}, (_, i) => botao(i + 1));
  const pedidos = [];
  let callback = null; const observados = new Set();
  const contexto = {window: {}, Map, Set, Promise, AbortController, setTimeout, clearTimeout};
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
