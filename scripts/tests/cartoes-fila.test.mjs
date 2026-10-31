import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';
import {webcrypto} from 'node:crypto';

const codigo = readFileSync(new URL('../../apps/web/public/js/cartoes-fila.js', import.meta.url), 'utf8');
const chave = 'f4951e0c-7470-42f8-8141-683c25514b7e';
const resposta = (processamento, pode_repetir = false) => ({processamento, pode_repetir, resultado: {}, mensagem: 'Mensagem fictícia'});

function abrir(memoria, responder) {
  const linhas = [], chamadas = [];
  let ciclo;
  const contexto = {window: {}, crypto: webcrypto, Uint8Array, Map, Promise, Array, JSON, FormData, AbortController,
    setTimeout, clearTimeout, setInterval: fn => { ciclo = fn; },
    sessionStorage: {getItem: k => memoria.get(k) ?? null, setItem: (k, v) => memoria.set(k, v)},
    document: {hidden: true, addEventListener() {}, createElement() {return {style: {}, addEventListener(_, fn) {this.clicar = fn;}};}},
    fetch: async (url, init) => {chamadas.push({url, init}); return {ok: true, json: async () => responder(url, init)};}};
  vm.runInNewContext(codigo, contexto);
  const fila = contexto.window.criarFilaCartoes({chaveSessao: 'envios', csrf: 'ficticio', uploadUrl: '/upload',
    envioUrl: '/envios/__ENVIO__', erroHttp: () => '', reduzir: async f => f, resultado: () => 'Leitura concluída',
    linha: () => {const li = {dataset: {}, botoes: [], appendChild(b) {this.botoes.push(b);}}; linhas.push(li); return li;}});
  return {fila, linhas, chamadas, consultar: async () => {contexto.document.hidden = false; await ciclo();}};
}

test('erro recuperável permanece após recarga e repete sem novo upload', async () => {
  const memoria = new Map();
  const primeira = abrir(memoria, () => resposta('erro', true));
  await primeira.fila.enviar(new Blob(['foto fictícia'], {type: 'image/jpeg'}));
  assert.equal(JSON.parse(memoria.get('envios')).length, 1);
  let estado = 'erro';
  const segunda = abrir(memoria, url => {
    if (url.endsWith('/repetir')) estado = 'na_fila';
    return resposta(estado, estado === 'erro');
  });
  assert.equal(segunda.linhas.length, 1);
  await segunda.consultar();
  const botao = segunda.linhas[0].botoes.at(-1);
  assert.equal(botao.textContent, 'Tentar leitura novamente');
  botao.clicar();
  await new Promise(setImmediate);
  assert.equal(segunda.linhas[0].dataset.processamento, 'na_fila');
  assert.ok(segunda.chamadas.every(c => c.url !== '/upload'));
  estado = 'concluido';
  await segunda.consultar();
  assert.deepEqual(JSON.parse(memoria.get('envios')), []);
});

for (const estado of ['recusado', 'substituido', 'concluido']) {
  test(`${estado} encerra o acompanhamento persistido`, async () => {
    const memoria = new Map([['envios', JSON.stringify([chave])]]);
    const pagina = abrir(memoria, () => resposta(estado));
    await pagina.consultar();
    assert.deepEqual(JSON.parse(memoria.get('envios')), []);
    assert.equal(pagina.linhas[0].dataset.processamento, estado);
  });
}
