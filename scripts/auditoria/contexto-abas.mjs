// Transporte por aba com URL pública de negócio, sem handle de autenticação.
import {chromium} from 'playwright-core';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
import vm from 'node:vm';
const modulo={exports:{}};
vm.runInNewContext(readFileSync('apps/web/public/js/contexto-aba.js','utf8'),{module:modulo,URL});
const {destino}=modulo.exports;
const assinada='/api/v1/cartoes/65/arquivo/original?acesso=teste&ator=4&signature=assinatura-ficticia';
for(const u of [assinada,'http://g360.test'+assinada,assinada+'&nome=a+b%2Fc'])assert.equal(destino(u,'http://g360.test/revisao','vinculo-1'),u);
const canonica=new URL(destino('/provas?pagina=2&_ctx=handle-antigo','http://g360.test/revisao','vinculo-1'));
assert.equal(canonica.pathname,'/acessos/vinculo-1/provas');assert.equal(canonica.search,'?pagina=2');
assert.equal(destino('https://externo.test/provas','http://g360.test/revisao','vinculo-1'),'https://externo.test/provas');
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try{
 const context=await browser.newContext();const js=readFileSync('apps/web/public/js/contexto-aba.js','utf8');
 const recebidos=[],formularios=[];
 await context.route('**/*',async route=>{
  const r=route.request(),u=new URL(r.url());if(u.hostname!=='g360.test')return route.abort();
  if(u.pathname==='/script.js')return route.fulfill({contentType:'application/javascript',body:js});
  if(u.pathname.endsWith('/fetch')){recebidos.push({url:u.pathname,metodo:r.method(),headers:r.headers(),body:r.postData()});return route.fulfill({json:{ok:true}});}
  if(u.pathname.endsWith('/salvar'))formularios.push({url:u.href,metodo:r.method(),body:r.postData()});
  if(u.pathname==='/css/app.css')return route.fulfill({contentType:'text/css',body:readFileSync('apps/web/public/css/app.css','utf8')});
  if(u.pathname==='/preview'&&process.env.PREVIEW_HTML)return route.fulfill({contentType:'text/html',body:readFileSync(process.env.PREVIEW_HTML,'utf8')});
  if(u.pathname.startsWith('/api/v1/cartoes/'))return route.fulfill({contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="blue"/></svg>'});
  const id=u.pathname.match(/^\/acessos\/(vinculo-[1-9][0-9]*)\//)?.[1]||({'/a':'vinculo-1','/b':'vinculo-2','/c':'vinculo-3'}[u.pathname])||'vinculo-1';
  return route.fulfill({contentType:'text/html',body:`<!doctype html><meta name="g360-acesso" content="${id}"><script>window.__fetchConferidos=[];const original=window.fetch.bind(window);window.fetch=function(input,opcoes){const pedido=new Request(input,opcoes);window.__fetchConferidos.push(pedido.clone().text().then(body=>({metodo:pedido.method,body})));return original(pedido);};</script><script src="/script.js"></script><a id="inteiro" href="${assinada}" target="_blank" rel="noopener">Ver cartão inteiro</a><a id="interno" href="/pdf">PDF</a><a id="externo" href="https://example.test/">Externo</a><a id="trocar" href="/vinculos?trocar=1" data-sem-contexto>Trocar</a><form id="nativo" action="/salvar"><input name="valor" value="7"><button>Salvar</button></form><form id="programatico" method="POST" action="/salvar"><input type="hidden" name="_token" value="csrf-ficticio"><input name="valor" value="9"></form>`});
 });
 const a=await context.newPage(),b=await context.newPage();await a.goto('http://g360.test/a');await b.goto('http://g360.test/b');
 assert.equal(await a.evaluate(()=>sessionStorage.getItem('g360-acesso')),'vinculo-1');assert.equal(await b.evaluate(()=>sessionStorage.getItem('g360-acesso')),'vinculo-2');
 assert.equal(new URL(await a.locator('#interno').getAttribute('href')).pathname,'/acessos/vinculo-1/pdf');
 assert.equal(await a.locator('#externo').getAttribute('href'),'https://example.test/');assert.equal(await a.locator('#trocar').getAttribute('href'),'/vinculos?trocar=1');
 assert.equal(await a.locator('#inteiro').getAttribute('href'),assinada);
 const aberta=context.waitForEvent('page');await a.locator('#inteiro').click();const foto=await aberta;await foto.waitForLoadState();assert.equal(foto.url(),'http://g360.test'+assinada);await foto.close();
 await b.evaluate(()=>{const x=document.createElement('a');x.id='dinamico';x.href='/pdf?aluno=2';document.body.append(x);});
 await b.waitForFunction(()=>document.getElementById('dinamico').href.includes('/acessos/vinculo-2/'));
 await a.evaluate(()=>fetch('/fetch'));await b.evaluate(()=>fetch(new Request('/fetch',{method:'POST',body:'valor=9',headers:{'Content-Type':'application/x-www-form-urlencoded'}})));
 assert.deepEqual(recebidos.map(r=>r.headers['x-g360-acesso']),['vinculo-1','vinculo-2']);
 assert.ok(recebidos.every(r=>!r.headers['x-g360-contexto']&&!r.url.includes('_ctx')));assert.equal(recebidos[1].metodo,'POST');const final=await b.evaluate(()=>Promise.all(window.__fetchConferidos));assert.deepEqual(final[0],{metodo:'POST',body:'valor=9'});
 await a.locator('#nativo button').click();await a.waitForURL('**/acessos/vinculo-1/salvar?valor=7');
 assert.ok(!a.url().includes('_ctx'));await a.goto('http://g360.test/c');
 assert.equal(await b.evaluate(()=>sessionStorage.getItem('g360-acesso')),'vinculo-2');
 await b.evaluate(()=>document.querySelector('#programatico').submit());await b.waitForURL('**/acessos/vinculo-2/salvar');
 assert.equal(formularios[1].metodo,'POST');assert.equal(formularios[1].body,'_token=csrf-ficticio&valor=9');
 assert.ok(formularios.every(f=>!f.url.includes('_ctx')&&!(f.body||'').includes('_ctx')));
 if(process.env.PREVIEW_HTML){await a.setViewportSize({width:1280,height:900});await a.goto('http://g360.test/preview');await a.screenshot({path:process.env.PREVIEW_HTML+'.desktop.png',fullPage:true});await a.setViewportSize({width:390,height:844});assert.ok(await a.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));await a.screenshot({path:process.env.PREVIEW_HTML+'.mobile.png',fullPage:true});}
 console.log('OK: abas independentes; URLs limpas; fetch Request preservado; GET/POST nativos/programáticos; CSRF no corpo e assinaturas intactas.');
}finally{await browser.close();}
