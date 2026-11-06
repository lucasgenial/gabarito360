// UR3: um acesso por sessão; este ensaio usa apenas páginas/respostas fictícias interceptadas.
import {chromium} from 'playwright-core';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
import vm from 'node:vm';
const js=readFileSync('apps/web/public/js/contexto-aba.js','utf8');
const modulo={exports:{}};vm.runInNewContext(js,{module:modulo,URL});
const {destino}=modulo.exports,base='http://g360.test/app/revisao';
const canonica=new URL(destino('/provas?pagina=2&_ctx=handle-ficticio&acesso=vinculo-1&_revisao=epoca-ficticia',base));
assert.equal(canonica.pathname,'/app/provas');assert.equal(canonica.search,'?pagina=2');
assert.equal(destino('/app/painel',base),'http://g360.test/app/painel');
assert.equal(destino('https://externo.test/provas',base),'https://externo.test/provas');
assert.equal(destino('/api/v1/health',base),'http://g360.test/api/v1/health');
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try{
 const context=await browser.newContext();const recebidos=[],formularios=[],imagens=[];
 const revA='revisao-ficticia-tela-a',revB='revisao-ficticia-tela-b';let ativa=revA;
 const png=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j5noAAAAASUVORK5CYII=','base64');
 await context.route('**/*',async route=>{
  const r=route.request(),u=new URL(r.url());if(u.hostname!=='g360.test')return route.abort();
  const headers=r.headers(),body=r.postData(),rev=headers['x-g360-revisao'];
  if(u.pathname==='/script.js')return route.fulfill({contentType:'application/javascript',body:js});
  if(u.pathname==='/css/app.css')return route.fulfill({contentType:'text/css',body:readFileSync('apps/web/public/css/app.css','utf8')});
  if(u.pathname==='/preview'&&process.env.PREVIEW_HTML)return route.fulfill({contentType:'text/html',body:readFileSync(process.env.PREVIEW_HTML,'utf8')});
  if(u.pathname==='/app/cartoes/65/imagens/original'){
   imagens.push({url:u.href,rev});
   if(rev!==ativa)return route.fulfill({status:409,json:{acesso_alterado:true}});
   return route.fulfill({contentType:'image/png',body:png});
  }
  if(u.pathname==='/app/fetch'){
   recebidos.push({url:u.href,metodo:r.method(),headers,body});
   if(rev!==ativa)return route.fulfill({status:409,json:{acesso_alterado:true}});
   return route.fulfill({json:{ok:true}});
  }
  if(u.pathname==='/app/salvar'){
   formularios.push({url:u.href,metodo:r.method(),body});
   if(r.method()==='POST'){
    const dados=new URLSearchParams(body);if(dados.get('_revisao')!==ativa)return route.fulfill({status:409,body:'Acesso alterado'});
    if(dados.get('_token')!=='csrf-ficticio')return route.fulfill({status:419,body:'CSRF inválido'});
   }
   return route.fulfill({contentType:'text/html',body:'<!doctype html><p>Salvo no ensaio fictício</p>'});
  }
  return route.fulfill({contentType:'text/html',body:`<!doctype html><meta name="g360-revisao" content="${ativa}"><script>sessionStorage.setItem('g360-acesso','legado-ficticio');sessionStorage.setItem('g360-contexto','legado-ficticio');window.__fetchConferidos=[];const original=window.fetch.bind(window);window.__fetchOriginal=original;window.fetch=function(input,opcoes){const pedido=new Request(input,opcoes);window.__fetchConferidos.push(pedido.clone().text().then(body=>({metodo:pedido.method,body})));return original(pedido);};</script><script src="/script.js"></script><main><a id="interno" href="/pdf">PDF</a><a id="externo" href="https://example.test/">Externo</a><a id="trocar" href="/app/vinculos">Trocar</a><img id="cartao" data-imagem-contextual="/app/cartoes/65/imagens/original" alt="Cartão fictício"><form id="nativo" action="/salvar"><input name="valor" value="7"><button>Salvar</button></form><form id="programatico" method="POST" action="/salvar"><input type="hidden" name="_token" value="csrf-ficticio"><input type="hidden" name="_revisao" value="${ativa}"><input name="valor" value="9"><button>Salvar</button></form></main>`});
 });
 const a=await context.newPage(),b=await context.newPage();await a.goto('http://g360.test/app/a');await b.goto('http://g360.test/app/b');
 for(const p of [a,b]){
  assert.equal(await p.evaluate(()=>sessionStorage.getItem('g360-acesso')),null);
  assert.equal(await p.evaluate(()=>sessionStorage.getItem('g360-contexto')),null);
  await p.waitForFunction(()=>document.getElementById('cartao').src.startsWith('blob:')&&document.getElementById('cartao').naturalWidth===1);
 }
 assert.equal(new URL(await a.locator('#interno').getAttribute('href')).pathname,'/app/pdf');
 assert.equal(await a.locator('#externo').getAttribute('href'),'https://example.test/');
 assert.equal(new URL(await a.locator('#trocar').getAttribute('href'),base).pathname,'/app/vinculos');
 assert.ok(imagens.length>=2&&imagens.every(i=>i.rev===revA&&!new URL(i.url).search));
 // Um consumidor nativo sem header não recebe a imagem; helper fetch→Blob é necessário.
 assert.equal(await a.evaluate(async()=>{const r=await window.__fetchOriginal('/app/cartoes/65/imagens/original');return r.status;}),409);
 await b.evaluate(()=>{const x=document.createElement('a');x.id='dinamico';x.href='/pdf?aluno=2';document.body.append(x);});
 await b.waitForFunction(()=>document.getElementById('dinamico').href.includes('/app/pdf?aluno=2'));
 await a.evaluate(()=>fetch('/fetch'));
 await b.evaluate(()=>fetch(new Request('/fetch',{method:'POST',body:'valor=9',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':'csrf-ficticio'}})));
 assert.deepEqual(recebidos.map(r=>r.headers['x-g360-revisao']),[revA,revA]);
 // CDP pode omitir postData de um Request baseado em ReadableStream: conferir clone no fetch final.
 assert.equal(recebidos[1].metodo,'POST');assert.equal(recebidos[1].headers['x-csrf-token'],'csrf-ficticio');
 assert.deepEqual((await b.evaluate(()=>Promise.all(window.__fetchConferidos))).at(-1),{metodo:'POST',body:'valor=9'});
 await a.locator('#nativo button').click();await a.waitForURL('**/app/salvar?valor=7');
 await b.evaluate(()=>document.querySelector('#programatico').submit());await b.waitForURL('**/app/salvar');
 assert.equal(formularios[1].metodo,'POST');const campos=new URLSearchParams(formularios[1].body);
 assert.equal(campos.get('_token'),'csrf-ficticio');assert.equal(campos.get('_revisao'),revA);assert.equal(campos.get('valor'),'9');
 await a.goto('http://g360.test/app/a');await b.goto('http://g360.test/app/b');
 // Simula conclusão de seleção POST no servidor: novas páginas têm B; velhas mantêm A.
 ativa=revB;
 // O callback ainda está na tela A que recebeu a resposta de seleção B, antes de navegar.
 await a.evaluate(revisao=>window.g360ContextoSelecionado(revisao),revB);
 await b.locator('#contexto-expirado').waitFor();
 const c=await context.newPage();await c.goto('http://g360.test/app/c');
 const antes=recebidos.length,nativos= formularios.length;
 assert.equal(await b.evaluate(async()=>{const r=await fetch('/fetch',{method:'POST',body:'valor=antigo'});return r.status;}),409);
 await b.evaluate(()=>document.querySelector('#programatico').submit());
 assert.equal(recebidos.length,antes);assert.equal(formularios.length,nativos);assert.equal(b.url(),'http://g360.test/app/b');
 assert.equal(await b.locator('input[name="_revisao"]').inputValue(),revA);
 await c.evaluate(()=>fetch('/fetch'));assert.equal(recebidos.at(-1).headers['x-g360-revisao'],revB);
 for(const r of [...recebidos,...formularios,...imagens]){
  const u=new URL(r.url);assert.ok(!u.pathname.includes('/acessos/'));
  for(const nome of ['token','access_token','api_token','signature','_ctx','_revisao','acesso'])assert.ok(!u.searchParams.has(nome));
  if(r.headers){assert.ok(!r.headers.authorization&&!r.headers['x-g360-contexto']&&!r.headers['x-g360-acesso']);}
 }
 if(process.env.PREVIEW_HTML){await a.setViewportSize({width:1280,height:900});await a.goto('http://g360.test/preview');await a.screenshot({path:process.env.PREVIEW_HTML+'.desktop.png',fullPage:true});await a.setViewportSize({width:390,height:844});assert.ok(await a.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));await a.screenshot({path:process.env.PREVIEW_HTML+'.mobile.png',fullPage:true});}
 console.log('OK UR3: /app estável; uma época ativa; revisão no corpo/header; Request/CSRF preservados; foto Blob; abas antigas409 sem replay; nenhum token/contexto na URL.');
}finally{await browser.close();}
