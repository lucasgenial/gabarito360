import {chromium} from 'playwright-core';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
const framesFoco=Number(process.env.UR4_FRAMES_FOCO||0);
assert.ok(Number.isInteger(framesFoco)&&framesFoco>=0&&framesFoco<=30);
async function conferirFoco(p,alvo){
 const elemento=await alvo.elementHandle();assert.ok(elemento);
 await p.waitForFunction(e=>e.isConnected&&document.activeElement===e,elemento,{timeout:5000,polling:'raf'});
 assert.equal(await alvo.evaluate(e=>e===document.activeElement),true);
 await elemento.dispose();
}
const leitura='99999999-9999-4999-8999-999999999999';
const estados=new Map(),referencias=new Map();
function descritor(pagina=1,detalhe=false){const chave=`${pagina}:${detalhe}`;if(referencias.has(chave))return referencias.get(chave);const referencia=String(referencias.size+1).padStart(48,'a');const d={url:'/app/resultados/historico/consultar',pagina:'/app/resultados/detalhes',referencia,metodo:'GET'};referencias.set(chave,d);estados.set(referencia,{pagina,detalhe});return d;}
const atributo=d=>JSON.stringify(d).replace(/&/g,'&amp;').replace(/"/g,'&quot;');
const bootstrap=`let cliques=0;document.getElementById('cartao').addEventListener('click',()=>document.getElementById('cartao').textContent='Aberto '+(++cliques));iniciarHistoricoResultado(JSON.parse(document.getElementById('historico-config').textContent));`;
const html=`<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="g360-revisao" content="revisao-ficticia-ci"><meta name="csrf-token" content="csrf-ficticio-ci"><script src="/js/contexto-aba.js"></script><meta name="viewport" content="width=device-width,initial-scale=1"><body><h1>Resultado preservado</h1><button id="cartao">Cartão resposta</button><p id="resultado-tentativas-total">Carregando leituras…</p><section id="historico-tentativas" aria-busy="true"><h2 id="historico-titulo" tabindex="-1">Histórico de tentativas</h2><div data-carga-esqueleto>Carregando</div><div data-carga-conteudo hidden></div><p data-carga-feedback role="status"></p></section><script src="/js/carregamento.js"></script><script src="/js/resultado-historico.js"></script><script type="application/json" id="historico-config">${JSON.stringify(descritor())}</script><script src="/fixture/historico-bootstrap.js"></script></body></html>`;
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try{
 const c=await browser.newContext();let principais=0,modo='ok',atrasar,ocultarAcionador=false;await c.route('**/*',async r=>{const u=new URL(r.request().url());if(u.hostname!=='g360.test')return r.abort();
 if(u.pathname==='/app/resultados/detalhes'){principais++;return r.fulfill({contentType:'text/html',body:html});}
 if(u.pathname==='/fixture/historico-bootstrap.js')return r.fulfill({contentType:'text/javascript',body:bootstrap});
 if(u.pathname.startsWith('/js/'))return r.fulfill({contentType:'text/javascript',body:readFileSync('apps/web/public'+u.pathname)});
 if(u.pathname==='/app/resultados/historico/consultar'){
  assert.equal(r.request().method(),'POST');assert.equal(r.request().headers()['x-g360-revisao'],'revisao-ficticia-ci');assert.equal(u.search,'');
  const body=r.request().postDataJSON();assert.equal(body._token,'csrf-ficticio-ci');assert.equal(body._revisao,'revisao-ficticia-ci');const estado=estados.get(body._recurso);assert.ok(estado);
  if(atrasar)await atrasar;
  if(modo==='erro')return r.fulfill({status:503,json:{message:'Indisponível'}});
  const pg=estado.pagina,d=estado.detalhe;
  return r.fulfill({json:{total:27,html:`<p>Página ${pg}</p><button type="button" data-historico-leitura="${ocultarAcionador?'88888888-8888-4888-8888-888888888888':leitura}" data-historico-recurso="${atributo(descritor(pg,true))}">${ocultarAcionador?'Conferir outra leitura':'Conferir tentativa'}</button><button type="button" data-historico-recurso="${atributo(descritor(2))}">Próxima</button>${d?`<div id="tentativa-detalhe"><button type="button" data-historico-voltar="${leitura}" data-historico-recurso="${atributo(descritor(pg))}">Fechar detalhes</button><h3 tabindex="-1" id="tentativa-detalhe-titulo">Tentativa 2</h3></div>`:''}`}});
 }return r.abort();});
 if(framesFoco)await c.addInitScript(frames=>{
 const raf=window.requestAnimationFrame.bind(window);
 window.requestAnimationFrame=callback=>{let restantes=frames;function proximo(tempo){if(restantes-->0)raf(proximo);else callback(tempo);}return raf(proximo);};
},framesFoco);
 const p=await c.newPage(),erros=[];p.on('pageerror',e=>erros.push(e.message));await p.goto('http://g360.test/app/resultados/detalhes');await p.getByRole('button',{name:'Conferir tentativa'}).waitFor();await p.locator('#cartao').click();
 await p.getByRole('button',{name:'Próxima'}).click();await p.getByText('Página 2',{exact:true}).waitFor();assert.equal(principais,1);assert.equal(new URL(p.url()).pathname,'/app/resultados/detalhes');assert.equal(new URL(p.url()).search,'');assert.equal(new URL(p.url()).hash,'');await conferirFoco(p,p.locator('#historico-titulo'));
 await p.getByRole('button',{name:'Conferir tentativa'}).click();await p.locator('#tentativa-detalhe-titulo').waitFor();await conferirFoco(p,p.locator('#tentativa-detalhe-titulo'));
 await p.getByRole('button',{name:'Fechar detalhes'}).click();await p.locator('#tentativa-detalhe').waitFor({state:'detached'});await conferirFoco(p,p.getByRole('button',{name:'Conferir tentativa'}));
 // Falha ao fechar conserva detalhe e foco; quando a leitura some da lista, não foca outra leitura.
 await p.getByRole('button',{name:'Conferir tentativa'}).click();await p.locator('#tentativa-detalhe-titulo').waitFor();await conferirFoco(p,p.locator('#tentativa-detalhe-titulo'));
 modo='erro';await p.getByRole('button',{name:'Fechar detalhes'}).click();await p.getByRole('button',{name:'Tentar novamente'}).waitFor();
 assert.equal(await p.locator('#tentativa-detalhe').isVisible(),true);await conferirFoco(p,p.getByRole('button',{name:'Fechar detalhes'}));
 modo='ok';ocultarAcionador=true;await p.getByRole('button',{name:'Tentar novamente'}).click();await p.locator('#tentativa-detalhe').waitFor({state:'detached'});
 if(framesFoco)console.log('Foco ainda pendente após remover detalhe:',!(await p.locator('#historico-titulo').evaluate(e=>e===document.activeElement)));
 await conferirFoco(p,p.locator('#historico-titulo'));assert.equal(await p.locator('#historico-titulo').getAttribute('tabindex'),'-1');assert.equal(await p.getByRole('button',{name:'Conferir outra leitura'}).evaluate(e=>e===document.activeElement),false);
 ocultarAcionador=false;await p.getByRole('button',{name:'Próxima'}).click();await p.getByRole('button',{name:'Conferir tentativa'}).waitFor();
 modo='erro';await p.getByRole('button',{name:'Próxima'}).click();await p.getByRole('button',{name:'Tentar novamente'}).waitFor();assert.equal(await p.locator('#cartao').innerText(),'Aberto 1');assert.equal(await p.getByRole('button',{name:'Conferir tentativa'}).isVisible(),true);assert.equal(await p.locator('#resultado-tentativas-total').innerText(),'27 leituras registradas');
 modo='ok';await p.getByRole('button',{name:'Tentar novamente'}).click();await p.getByText('Página 2',{exact:true}).waitFor();assert.equal(principais,1);assert.deepEqual(erros,[]);
 console.log('OK: histórico regional com POST real/referência/CSRF/revisão, paginação/detalhe/fechar, foco, URL sem alvos e erro/retry preservando resultado/total.');
}finally{await browser.close();}
