// Ensaio do transporte por aba em Chromium, com páginas fictícias e sem servidor externo.
import { chromium } from 'playwright-core';
import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';
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
    return route.fulfill({ contentType: 'text/html', body: `<!doctype html><meta name="g360-contexto" content="${id}"><script src="/script.js"></script><a id="interno" href="/pdf">PDF</a><a id="externo" href="https://example.test/">Externo</a><a id="trocar" href="/vinculos?trocar=1" data-sem-contexto>Trocar</a><form action="/salvar"><input name="valor" value="7"><button>Salvar</button></form>` });
  });
  const a = await context.newPage(), b = await context.newPage();
  await a.goto('http://g360.test/a'); await b.goto('http://g360.test/b');
  const idA = 'a'.repeat(48), idB = 'b'.repeat(48);
  assert.equal(await a.evaluate(() => sessionStorage.getItem('g360-contexto')), idA);
  assert.equal(await b.evaluate(() => sessionStorage.getItem('g360-contexto')), idB);
  assert.equal(new URL(await a.locator('#interno').getAttribute('href')).searchParams.get('_ctx'), idA);
  assert.equal(await a.locator('#externo').getAttribute('href'), 'https://example.test/');
  assert.equal(await a.locator('#trocar').getAttribute('href'), '/vinculos?trocar=1');
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
