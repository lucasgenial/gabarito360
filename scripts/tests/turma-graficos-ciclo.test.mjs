import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';

const bundle = readFileSync(new URL('../../apps/web/public/js/turma-graficos.js', import.meta.url), 'utf8');

function el(extra = {}) {
  return Object.assign({
    dataset: {}, isConnected: true, clientWidth: 600, clientHeight: 300, textContent: '', classList: {add() {}, remove() {}},
    addEventListener() {}, removeEventListener() {}, setAttribute() {}, getAttribute: () => null, append() {}, replaceChildren() {},
    querySelector: () => null, querySelectorAll: () => [], closest: () => null,
  }, extra);
}

function montar() {
  const tokens = {'--fg': '#111', '--muted': '#555', '--border': '#ccc', '--border-soft': '#eee', '--success': '#0a0', '--warn': '#a80'};
  const graficos = [el({dataset: {grafico: 'situacoes'}}), el({dataset: {grafico: 'distribuicao'}})];
  const dados = {situacoes: {total: 4, dentro: 2, atencao: 1, sem_base: 1}, analise: {distribuicao_acertos: {total: 4, faixas: [{rotulo: '0-50', total: 2}, {rotulo: '50-100', total: 2}]}}};
  const area = el({querySelectorAll: s => (s.includes('[data-grafico]') ? graficos : []), querySelector: () => null});
  const raiz = el({dataset: {painelDados: JSON.stringify(dados)}, closest: s => (s === '[data-turma-painel]' ? area : null)});
  const quadros = [], ouvintesJanela = {}, contagem = {init: 0, setOption: 0, resize: 0, dispose: 0};
  let ro, mo;
  const document = {
    documentElement: el(), readyState: 'complete',
    querySelector: s => (s === '[data-painel-graficos]' ? raiz : null), querySelectorAll: () => [],
    addEventListener() {}, createElement: () => ({getContext: () => ({measureText: t => ({width: String(t).length * 6}), font: ''})}),
  };
  const window = {
    echarts: {init: () => { contagem.init++; return {setOption() { contagem.setOption++; }, resize() { contagem.resize++; }, dispose() { contagem.dispose++; }}; }},
    addEventListener: (t, fn) => { (ouvintesJanela[t] ||= []).push(fn); },
  };
  const contexto = {
    window, document, console, JSON, Map, Set, WeakMap, Math, Number, String, Array, Object, Intl, Promise, Error,
    getComputedStyle: () => ({getPropertyValue: n => tokens[n] || ''}),
    requestAnimationFrame: fn => { quadros.push(fn); return quadros.length; }, cancelAnimationFrame: () => { quadros.length = 0; },
    ResizeObserver: class { constructor(fn) { ro = fn; } observe() {} disconnect() { this.desligado = true; } },
    MutationObserver: class { constructor(fn) { mo = fn; } observe() {} disconnect() {} },
    CustomEvent: class {}, setTimeout, clearTimeout, matchMedia: () => ({matches: false, addEventListener() {}}),
  };
  contexto.self = contexto.globalThis = contexto;
  window.getComputedStyle = contexto.getComputedStyle; window.requestAnimationFrame = contexto.requestAnimationFrame;
  vm.createContext(contexto);
  vm.runInContext(bundle, contexto);
  const rodarQuadros = () => { const l = quadros.splice(0); l.forEach(f => f()); };
  return {graficos, tokens, contagem, rodarQuadros, quadros, ro: e => ro(e), mo: () => mo([]), ouvintesJanela};
}

test('vários avisos de tamanho viram uma renderização por gráfico no mesmo quadro', () => {
  const p = montar();
  p.ro(p.graficos.map(target => ({target}))); p.ro(p.graficos.map(target => ({target})));
  assert.equal(p.contagem.setOption, 0, 'nada é desenhado de forma síncrona');
  assert.equal(p.quadros.length, 1, 'um único quadro agendado');
  p.rodarQuadros();
  assert.equal(p.contagem.init, 2); assert.equal(p.contagem.setOption, 2);
  p.ro(p.graficos.map(target => ({target}))); p.rodarQuadros();
  assert.equal(p.contagem.setOption, 2, 'mesmo tamanho não redesenha');
  p.graficos[0].clientWidth = 380; p.ro([{target: p.graficos[0]}]); p.rodarQuadros();
  assert.equal(p.contagem.setOption, 3, 'mudança real de tamanho redesenha só o gráfico afetado');
});

test('mudança de classe ou estilo do documento só redesenha quando os tokens do tema mudam', () => {
  const p = montar();
  p.ro(p.graficos.map(target => ({target}))); p.rodarQuadros();
  const antes = p.contagem.setOption;
  p.mo(); p.rodarQuadros();
  assert.equal(p.contagem.setOption, antes, 'abrir um modal não redesenha');
  p.tokens['--fg'] = '#eee'; p.mo(); p.rodarQuadros();
  assert.equal(p.contagem.setOption, antes + 2, 'troca de tema redesenha todos');
});

test('bfcache preserva os gráficos; saída definitiva descarta e cancela o quadro pendente', () => {
  const p = montar();
  p.ro(p.graficos.map(target => ({target}))); p.rodarQuadros();
  p.ouvintesJanela.pagehide.forEach(f => f({persisted: true}));
  assert.equal(p.contagem.dispose, 0);
  p.graficos[1].clientWidth = 300; p.ro([{target: p.graficos[1]}]);
  p.ouvintesJanela.pagehide.forEach(f => f({persisted: false}));
  assert.equal(p.contagem.dispose, 2);
  const antes = p.contagem.setOption; p.rodarQuadros();
  assert.equal(p.contagem.setOption, antes, 'nenhum callback tardio depois do descarte');
});
