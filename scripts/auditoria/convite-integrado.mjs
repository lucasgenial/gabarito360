// Ensaio local descartável: fixtures fictícias do ConviteInterfaceTest, API e web reais.
import { chromium } from 'playwright-core';
import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';
const c = JSON.parse(readFileSync(process.env.V2C_FIXTURE, 'utf8'));
const base = process.env.WEB_URL || 'http://127.0.0.1:8192';
assert.ok(['127.0.0.1','localhost','[::1]'].includes(new URL(base).hostname),'Ensaio exclusivo de API/web locais com fixtures fictícias.');
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
    await p.waitForURL(u=>u.pathname==='/app/painel');
    assert.equal(new URL(p.url()).search,'');
  }
  const conta = await contexto(), p = await conta.newPage();
  await p.goto(`${base}/app/vinculos/convite`);
  await p.getByRole('link',{name:'Entrar na minha conta'}).waitFor();
  assert.equal(new URL(p.url()).hash,'');
  assert.equal(await p.evaluate(()=>sessionStorage.getItem('g360-convite-vinculo')),null);
  await p.getByRole('link',{name:'Entrar na minha conta'}).click(); await login(p,'2@example.test');
  await p.locator('#vinculo-codigo').waitFor();
  await p.locator('#vinculo-codigo').fill(`${c.convite_id}.${c.token}`);
  await p.getByRole('button',{name:'Conferir convite'}).click();
  await p.getByRole('button',{name:'Aceitar vínculo'}).waitFor();
  assert.equal(new URL(p.url()).search,''); assert.equal(new URL(p.url()).hash,'');
  assert.ok(!(await p.content()).includes(c.token));
  if (process.env.PREVIEW_DIR) await p.screenshot({path:process.env.PREVIEW_DIR+'/convite-aceite.png',fullPage:true});
  await p.getByRole('button',{name:'Aceitar vínculo'}).click(); await p.waitForURL('**/app/vinculos?trocar=1');
  await escolher(p,c.vinculo_id);
  assert.ok((await p.locator('.contexto-dados').textContent()).includes('Coordenador'));
  const primeiraRevisao=await p.locator('meta[name="g360-revisao"]').getAttribute('content');
  const segunda = await conta.newPage(); await segunda.goto(base+'/app/vinculos?trocar=1'); await escolher(segunda,3);
  assert.notEqual(await segunda.locator('meta[name="g360-revisao"]').getAttribute('content'),primeiraRevisao);
  // A seleção da segunda aba muda a sessão; a tela antiga não pode agir como o novo perfil.
  assert.equal(await p.evaluate(async()=>{const r=await fetch('/app/painel',{headers:{Accept:'application/json'}});return r.status;}),409);
  await p.reload();assert.equal(new URL(p.url()).pathname,'/app/painel');
  assert.ok((await p.locator('.contexto-dados').textContent()).includes('Professor'));
  assert.ok((await segunda.locator('.contexto-dados').textContent()).includes('Professor'));
  // Voltar ao vínculo que será suspenso para testar revogação real da autoridade ativa.
  await p.goto(base+'/app/vinculos?trocar=1');await escolher(p,c.vinculo_id);
  const gestao = await contexto(), g = await gestao.newPage(); await g.goto(base+'/login'); await login(g,'1@example.test');
  await g.waitForURL('**/app/vinculos'); await escolher(g,1);
  await g.goto(base+'/app/membros'); assert.ok((await g.textContent('body')).includes('Vínculo #'+c.vinculo_id));
  await g.goto(`${base}/app/membros/2/editar?vinculo_id=${c.vinculo_id}`);
  await g.locator('#ativo').selectOption('0'); await g.getByRole('button',{name:'Salvar acesso'}).click();
  await g.getByText('Acesso deste vínculo atualizado.').waitFor();
  await p.reload(); await p.waitForURL('**/app/vinculos');
  await segunda.reload();await segunda.waitForURL('**/app/vinculos');
  await escolher(segunda,3);
  await segunda.reload(); assert.ok((await segunda.locator('.contexto-dados').textContent()).includes('Professor'));
  await g.goto(base+'/app/membros/convidar-vinculo');
  await g.locator('#email').fill('2@example.test'); await g.locator('#espaco').selectOption('5'); await g.locator('#perfil').selectOption('aplicador');
  if (process.env.PREVIEW_DIR) await g.screenshot({path:process.env.PREVIEW_DIR+'/convite-envio.png',fullPage:true});
  await g.getByRole('button',{name:'Enviar convite'}).click();
  await g.getByText('Se a conta puder receber este vínculo, o convite será enviado.').waitFor();
  console.log('OK UR3: página fixa → login → código por POST → aceite → seleção global em duas abas → ação antiga409 → suspensão do vínculo ativo → seleção do vínculo remanescente → novo convite, com API/web locais reais.');
} finally { await browser.close(); }
