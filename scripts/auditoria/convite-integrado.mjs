// Ensaio local descartável: fixtures fictícias do ConviteInterfaceTest, API e web reais.
import { chromium } from 'playwright-core';
import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';
const c = JSON.parse(readFileSync(process.env.V2C_FIXTURE, 'utf8'));
const base = process.env.WEB_URL || 'http://127.0.0.1:8192';
const browser = await chromium.launch({ executablePath: process.env.CHROME_PATH, headless: true });
try {
  async function contexto() {
    const ctx = await browser.newContext(); ctx.setDefaultTimeout(60000);
    await ctx.route('**/*', r => new URL(r.request().url()).origin === base ? r.continue() : r.abort());
    return ctx;
  }
  async function login(p, email) {
    await p.locator('#email').fill(email); await p.locator('#password').fill('ficticio');
    await p.getByRole('button', {name:'Entrar no painel'}).click();
  }
  async function escolher(p, id) {
    await p.locator('form').filter({has:p.locator(`input[name="vinculo_id"][value="${id}"]`)}).getByRole('button').click();
    await p.waitForURL('**/painel?**');
  }
  const conta = await contexto(), p = await conta.newPage();
  await p.goto(`${base}/vinculos/convite#convite=${c.convite_id}&token=${c.token}`);
  await p.getByRole('link',{name:'Entrar na minha conta'}).waitFor();
  assert.equal(new URL(p.url()).hash,'');
  assert.ok(await p.evaluate(()=>!!sessionStorage.getItem('g360-convite-vinculo')));
  await p.getByRole('link',{name:'Entrar na minha conta'}).click(); await login(p,'2@example.test');
  await p.locator('#convite-detalhes').waitFor({state:'visible'});
  assert.equal(await p.evaluate(()=>sessionStorage.getItem('g360-convite-vinculo')),null);
  assert.ok(!(await p.content()).includes(c.token));
  if (process.env.PREVIEW_DIR) await p.screenshot({path:process.env.PREVIEW_DIR+'/convite-aceite.png',fullPage:true});
  await p.getByRole('button',{name:'Aceitar vínculo'}).click(); await p.waitForURL('**/vinculos?trocar=1');
  await escolher(p,c.vinculo_id); const primeiro = new URL(p.url()).searchParams.get('_ctx');
  const segunda = await conta.newPage(); await segunda.goto(base+'/vinculos?trocar=1'); await escolher(segunda,3);
  assert.notEqual(new URL(segunda.url()).searchParams.get('_ctx'),primeiro);
  await p.reload(); assert.equal(new URL(p.url()).searchParams.get('_ctx'),primeiro);
  assert.ok((await p.locator('.vinculo-topo').innerText()).includes('Coordenador'));
  assert.ok((await segunda.locator('.vinculo-topo').innerText()).includes('Professor'));
  const gestao = await contexto(), g = await gestao.newPage(); await g.goto(base+'/login'); await login(g,'1@example.test');
  await g.waitForURL('**/vinculos'); await escolher(g,1);
  const ctx = new URL(g.url()).searchParams.get('_ctx');
  await g.goto(base+'/membros?_ctx='+ctx); assert.ok((await g.textContent('body')).includes('Vínculo #'+c.vinculo_id));
  await g.goto(`${base}/membros/2/editar?_ctx=${ctx}&vinculo_id=${c.vinculo_id}`);
  await g.locator('#ativo').selectOption('0'); await g.getByRole('button',{name:'Salvar acesso'}).click();
  await g.getByText('Acesso deste vínculo atualizado.').waitFor();
  await p.reload(); await p.waitForURL('**/vinculos');
  await segunda.reload(); assert.ok((await segunda.locator('.vinculo-topo').innerText()).includes('Professor'));
  await g.goto(base+'/membros/convidar-vinculo?_ctx='+ctx);
  await g.locator('#email').fill('2@example.test'); await g.locator('#espaco').selectOption('5'); await g.locator('#perfil').selectOption('aplicador');
  if (process.env.PREVIEW_DIR) await g.screenshot({path:process.env.PREVIEW_DIR+'/convite-envio.png',fullPage:true});
  await g.getByRole('button',{name:'Enviar convite'}).click();
  await g.getByText('Se a conta puder receber este vínculo, o convite será enviado.').waitFor();
  console.log('OK: fragmento → login → consulta → aceite → duas abas → suspensão isolada → envio de novo convite, com API/web reais.');
} finally { await browser.close(); }
