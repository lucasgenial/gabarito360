import {chromium} from 'playwright-core';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';

const html=`<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/css/app.css"><body><main style="padding:24px"><h1>Ensaio fictício</h1>
<button id="sucesso">Sucesso</button><button id="atualizar">Atualizar notificação</button><button id="falha">Falha</button><button id="dialogo">Abrir diálogo</button><button id="erro-dialogo">Erro no diálogo</button>
<section id="regiao"><div data-carga-esqueleto class="g360-carregando"><div class="g360-carga-marca"><img src="/images/g360-carregando.webp" width="40" height="40" alt=""><span>Carregando…</span></div><div class="g360-skeleton"><i></i></div></div><div data-carga-conteudo hidden></div><p data-carga-feedback role="status"></p></section>
<button id="resolver">Resolver região</button><button id="rejeitar">Rejeitar região</button><button id="recarregar">Recarregar região</button>
<dialog id="modal"><h2>Operação fictícia</h2><button id="erro-interno">Falhar</button><button onclick="this.closest('dialog').close()">Fechar diálogo</button></dialog></main>
<div id="g360-notificacoes"></div><script src="/js/carregamento.js"></script><script src="/js/notificacoes.js"></script><script>
const regiao=document.getElementById('regiao'),carga=G360Carga(regiao);let resolver,rejeitar;
function carregar(){carga.executar(()=>new Promise((ok,erro)=>{resolver=ok;rejeitar=erro;}),d=>regiao.querySelector('[data-carga-conteudo]').textContent=d);}
carregar();document.getElementById('resolver').onclick=()=>resolver('Dados completos');document.getElementById('rejeitar').onclick=()=>rejeitar(new Error('Ensaio'));document.getElementById('recarregar').onclick=carregar;
document.getElementById('sucesso').onclick=()=>G360Toast.sucesso('Preferência de teste salva',{id:'operacao',duracao:500});
document.getElementById('atualizar').onclick=()=>{G360Toast.carregando('Preparando…',{id:'operacao'});G360Toast.sucesso('Finalizado sem duplicar',{id:'operacao',duracao:Infinity});};
document.getElementById('falha').onclick=()=>G360Toast.erro('<img src=x onerror=alert(1)>',{id:'falha'});
document.getElementById('dialogo').onclick=()=>document.getElementById('modal').showModal();document.getElementById('erro-interno').onclick=()=>G360Toast.erro('Erro contextual',{id:'dialogo'});
</script></body></html>`;
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try{
 const c=await browser.newContext();await c.route('**/*',r=>{const u=new URL(r.request().url());if(u.hostname!=='g360.test')return r.abort();if(u.pathname==='/ensaio')return r.fulfill({contentType:'text/html',body:html});
 if(/^\/(js|css|images)\//.test(u.pathname))return r.fulfill({contentType:u.pathname.endsWith('.css')?'text/css':u.pathname.endsWith('.webp')?'image/webp':'text/javascript',body:readFileSync('apps/web/public'+u.pathname)});return r.abort();});
 const p=await c.newPage(),erros=[];p.on('pageerror',e=>erros.push(e.message));await p.setViewportSize({width:1440,height:1000});await p.goto('http://g360.test/ensaio');
 const reg=p.locator('#regiao');assert.equal(await reg.getAttribute('aria-busy'),'true');assert.equal(await reg.locator('[data-carga-conteudo]').isVisible(),false);
 await p.locator('#resolver').click();await reg.locator('[data-carga-conteudo]').waitFor();assert.equal(await reg.getAttribute('aria-busy'),'false');
 await p.locator('#recarregar').click();assert.equal(await reg.locator('[data-carga-conteudo]').innerText(),'Dados completos');assert.equal(await reg.locator('[data-carga-esqueleto]').isVisible(),false);
 await p.locator('#rejeitar').click();await reg.getByRole('button',{name:'Tentar novamente'}).waitFor();assert.equal(await reg.locator('[data-carga-conteudo]').innerText(),'Dados completos');
 await reg.getByRole('button',{name:'Tentar novamente'}).click();await p.locator('#resolver').click();assert.equal(await reg.locator('[data-carga-feedback]').innerText(),'');
 await p.locator('#atualizar').click();const toast=p.locator('[data-g360-toast="operacao"]');await toast.waitFor();assert.equal(await toast.count(),1);assert.match(await toast.innerText(),/Finalizado sem duplicar/);
 await p.waitForTimeout(350);const rect=await toast.boundingBox();assert.ok(rect.x>1000&&rect.y>=80,JSON.stringify(rect));assert.equal(await p.locator('#atualizar').evaluate(e=>e===document.activeElement),true);
 await p.locator('#falha').click();assert.equal(await p.locator('[data-g360-toast="falha"] img').count(),0);assert.match(await p.locator('[data-g360-toast="falha"]').innerText(),/onerror/);
 await p.locator('[data-g360-toast="falha"] button').click();await p.locator('[data-g360-toast="falha"]').waitFor({state:'hidden'});
 await toast.getByRole('button',{name:'Fechar notificação'}).press('Escape');await toast.waitFor({state:'hidden'});
 await p.locator('#sucesso').click();await p.locator('[data-g360-toast="operacao"] button').focus();await p.waitForTimeout(700);assert.equal(await toast.isVisible(),true);await p.locator('#falha').focus();await toast.waitFor({state:'hidden',timeout:2500});
 await p.locator('#dialogo').click();await p.locator('#erro-interno').click();assert.equal(await p.locator('dialog [data-g360-toast="dialogo"]').isVisible(),true);await p.getByRole('button',{name:'Fechar diálogo'}).click();
 await p.emulateMedia({reducedMotion:'reduce'});await p.setViewportSize({width:320,height:844});await p.locator('#atualizar').click();await toast.waitFor();
 const b=await toast.boundingBox();assert.ok(b.x>=12&&b.x+b.width<=309);assert.equal(await p.locator('.g360-toast').last().evaluate(e=>getComputedStyle(e).opacity),'1');
 assert.deepEqual(erros,[]);console.log('OK: promises regionais, preservação e retry, Solid Toast real, atualização por ID, XSS, foco/Esc, pausa, diálogo e 320px/reduced-motion.');
}finally{await browser.close();}
