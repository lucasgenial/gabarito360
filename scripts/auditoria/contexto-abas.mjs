// Ensaio do transporte por aba em Chromium, com páginas fictícias e sem servidor externo.
import { chromium } from 'playwright-core';
import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';
import vm from 'node:vm';
const modulo={exports:{}};
vm.runInNewContext(readFileSync('apps/web/public/js/contexto-aba.js','utf8'),{module:modulo,URL});
const { destino } = modulo.exports;
const assinada = '/api/v1/cartoes/65/arquivo/original?acesso=ficticio%20teste&ator=4&espaco=13&expires=9999999999&vinculo=5&signature=assinatura-ficticia';
assert.equal(destino(assinada, 'http://g360.test/revisao', 'a'.repeat(48)), assinada, 'URL assinada deve permanecer byte a byte intacta');
for(const url of [assinada,'http://g360.test'+assinada,assinada+'&nome=a+b%2Fc&_ctx=ja-assinado']) {
  assert.equal(destino(url,'http://g360.test/revisao','outro-contexto'),url);
}
assert.equal(new URL(destino('/provas?pagina=2','http://g360.test/revisao','contexto')).searchParams.get('_ctx'),'contexto');
assert.equal(destino('https://externo.test/provas','http://g360.test/revisao','contexto'),'https://externo.test/provas');
const browser = await chromium.launch({ executablePath: process.env.CHROME_PATH, headless: true });
try {
  const context = await browser.newContext();
  const js = readFileSync('apps/web/public/js/contexto-aba.js', 'utf8');
  const recebidos = [];
  await context.route('**/*', async route => {
    const r = route.request(), u = new URL(r.url());
    if (u.hostname !== 'g360.test') return route.abort();
    if (u.pathname === '/script.js') return route.fulfill({ contentType: 'application/javascript', body: js });
    if (u.pathname === '/fetch') { recebidos.push(r.headers()); return route.fulfill({ json: { ok: true } }); }
    if (u.pathname === '/css/app.css') return route.fulfill({ contentType: 'text/css', body: readFileSync('apps/web/public/css/app.css', 'utf8') });
    if (u.pathname === '/preview' && process.env.PREVIEW_HTML) return route.fulfill({ contentType: 'text/html', body: readFileSync(process.env.PREVIEW_HTML, 'utf8') });
    if (u.pathname.startsWith('/js/')) return route.fulfill({ body: '' });
    const id = u.searchParams.get('_ctx') || u.pathname.slice(1).repeat(48);
    if(u.pathname.startsWith('/api/v1/cartoes/'))return route.fulfill({contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="blue"/></svg>'});
    return route.fulfill({ contentType: 'text/html', body: `<!doctype html><meta name="g360-contexto" content="${id}"><script src="/script.js"></script><a id="inteiro" href="${assinada}" target="_blank" rel="noopener">Ver cartão inteiro</a><a id="interno" href="/pdf">PDF</a><a id="externo" href="https://example.test/">Externo</a><a id="trocar" href="/vinculos?trocar=1" data-sem-contexto>Trocar</a><form action="/salvar"><input name="valor" value="7"><button>Salvar</button></form>` });
  });
  const a = await context.newPage(), b = await context.newPage();
  await a.goto('http://g360.test/a'); await b.goto('http://g360.test/b');
  const idA = 'a'.repeat(48), idB = 'b'.repeat(48);
  assert.equal(await a.evaluate(() => sessionStorage.getItem('g360-contexto')), idA);
  assert.equal(await b.evaluate(() => sessionStorage.getItem('g360-contexto')), idB);
  assert.equal(new URL(await a.locator('#interno').getAttribute('href')).searchParams.get('_ctx'), idA);
  assert.equal(await a.locator('#externo').getAttribute('href'), 'https://example.test/');
  assert.equal(await a.locator('#trocar').getAttribute('href'), '/vinculos?trocar=1');
  assert.equal(await a.locator('#inteiro').getAttribute('href'), assinada);
  const aberta=context.waitForEvent('page');await a.locator('#inteiro').click();const foto=await aberta;await foto.waitForLoadState();
  assert.equal(foto.url(),'http://g360.test'+assinada);await foto.close();
  await b.evaluate(url=>{const a=document.createElement('a');a.id='inteiro-dinamico';a.href=url;document.body.append(a);},assinada);
  // A inclusão de outro link garante que o observer rodou, inclusive após renderização dinâmica da revisão.
  await b.evaluate(()=>{const a=document.createElement('a');a.id='controle';a.href='/pdf?controle=1';document.body.append(a);});
  await b.waitForFunction(()=>document.getElementById('controle').href.includes('_ctx='));
  assert.equal(await b.locator('#inteiro-dinamico').getAttribute('href'),assinada);
  await a.evaluate(() => fetch('/fetch')); await b.evaluate(() => fetch('/fetch'));
  assert.deepEqual(recebidos.map(h => h['x-g360-contexto']), [idA, idB]);
  await a.locator('button').click(); await a.waitForURL('**/salvar?**');
  assert.equal(new URL(a.url()).searchParams.get('_ctx'), idA);
  await a.goto('http://g360.test/c');
  assert.equal(await b.evaluate(() => sessionStorage.getItem('g360-contexto')), idB);
  await b.evaluate(() => { const a = document.createElement('a'); a.id = 'dinamico'; a.href = '/pdf?aluno=2'; document.body.append(a); });
  await b.waitForFunction(() => document.getElementById('dinamico').href.includes('_ctx='));
  assert.equal(new URL(await b.locator('#dinamico').getAttribute('href')).searchParams.get('_ctx'), idB);
  await b.evaluate(() => document.querySelector('form').submit()); await b.waitForURL('**/salvar?**');
  assert.equal(new URL(b.url()).searchParams.get('_ctx'), idB);
  if (process.env.PREVIEW_HTML) {
    await a.setViewportSize({ width: 1280, height: 900 }); await a.goto('http://g360.test/preview');
    await a.screenshot({ path: process.env.PREVIEW_HTML + '.desktop.png', fullPage: true });
    await a.setViewportSize({ width: 390, height: 844 });
    assert.ok(await a.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await a.screenshot({ path: process.env.PREVIEW_HTML + '.mobile.png', fullPage: true });
  }
  console.log('OK: duas abas, troca independente, fetch, links, forms nativos/programáticos e destinos externos.');
} finally { await browser.close(); }
