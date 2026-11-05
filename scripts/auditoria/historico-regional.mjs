import {chromium} from 'playwright-core';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
const html=`<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><body><h1>Resultado preservado</h1><button id="cartao">Cartão resposta</button><p id="resultado-tentativas-total">Carregando tentativas…</p><section id="historico-tentativas" aria-busy="true"><h2 id="historico-titulo" tabindex="-1">Histórico de tentativas</h2><div data-carga-esqueleto>Carregando</div><div data-carga-conteudo hidden></div><p data-carga-feedback role="status"></p></section><script src="/js/carregamento.js"></script><script src="/js/resultado-historico.js"></script><script>let cliques=0;document.getElementById('cartao').onclick=()=>document.getElementById('cartao').textContent='Aberto '+(++cliques);iniciarHistoricoResultado('/resultado-historico');</script></body></html>`;
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try{
 const c=await browser.newContext();let principais=0,modo='ok',atrasar;await c.route('**/*',async r=>{const u=new URL(r.request().url());if(u.hostname!=='g360.test')return r.abort();
 if(u.pathname==='/resultados/1/4'){principais++;return r.fulfill({contentType:'text/html',body:html});}
 if(u.pathname.startsWith('/js/'))return r.fulfill({contentType:'text/javascript',body:readFileSync('apps/web/public'+u.pathname)});
 if(u.pathname==='/resultado-historico'){
  if(atrasar)await atrasar;
  if(modo==='erro')return r.fulfill({status:503,json:{message:'Indisponível'}});
  const base='/resultados/1/4?from=correcao&_ctx=ficticio',d=u.searchParams.has('tentativa'),pg=u.searchParams.get('tentativas_pagina')||1;
  return r.fulfill({json:{total:27,html:`<p>Página ${pg}</p><a href="${base}&tentativa=abc#tentativa-detalhe">Conferir tentativa</a><a href="${base}&tentativas_pagina=2#historico-tentativas">Próxima</a>${d?`<div id="tentativa-detalhe"><a data-historico-voltar="abc" href="${base}#historico-tentativas">Fechar detalhes</a><h3 tabindex="-1" id="tentativa-detalhe-titulo">Tentativa 2</h3></div>`:''}`}});
 }return r.abort();});
 const p=await c.newPage(),erros=[];p.on('pageerror',e=>erros.push(e.message));await p.goto('http://g360.test/resultados/1/4?from=correcao&_ctx=ficticio');await p.getByRole('link',{name:'Conferir tentativa'}).waitFor();await p.locator('#cartao').click();
 await p.getByRole('link',{name:'Próxima'}).click();await p.getByText('Página 2',{exact:true}).waitFor();assert.equal(principais,1);assert.match(p.url(),/tentativas_pagina=2/);assert.equal(await p.locator('#historico-titulo').evaluate(e=>e===document.activeElement),true);
 await p.getByRole('link',{name:'Conferir tentativa'}).click();await p.locator('#tentativa-detalhe-titulo').waitFor();assert.equal(await p.locator('#tentativa-detalhe-titulo').evaluate(e=>e===document.activeElement),true);
 await p.getByRole('link',{name:'Fechar detalhes'}).click();await p.locator('#tentativa-detalhe').waitFor({state:'detached'});assert.equal(await p.getByRole('link',{name:'Conferir tentativa'}).evaluate(e=>e===document.activeElement),true);
 modo='erro';await p.getByRole('link',{name:'Próxima'}).click();await p.getByRole('button',{name:'Tentar novamente'}).waitFor();assert.equal(await p.locator('#cartao').innerText(),'Aberto 1');assert.equal(await p.getByRole('link',{name:'Conferir tentativa'}).isVisible(),true);assert.equal(await p.locator('#resultado-tentativas-total').innerText(),'27 tentativas registradas');
 modo='ok';await p.getByRole('button',{name:'Tentar novamente'}).click();await p.getByText('Página 2',{exact:true}).waitFor();assert.equal(principais,1);assert.deepEqual(erros,[]);
 console.log('OK: histórico regional com paginação/detalhe/fechar, foco, URL/contexto, erro/retry sem apagar o resultado nem inflar o total.');
}finally{await browser.close();}
