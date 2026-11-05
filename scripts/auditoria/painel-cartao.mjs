import {chromium} from 'playwright-core';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
import {imagemTeste} from './revisao-modal-fixture.mjs';
const parcial=readFileSync('apps/web/resources/views/correcao/_documento.blade.php','utf8').split("@push('scripts')")[0]
 .replace(/\{\{ \$documentoContexto \}\}/g,'Pessoa fictícia · Prova de teste').replace(/\{\{--[\s\S]*?--\}\}/g,'');
const foto='/api/v1/cartoes/65/arquivo/original?signature=ficticia';
const html=`<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/css/app.css"><body><main style="padding:24px"><h1>Resultado fictício</h1><button type="button" id="doc-abrir" class="btn btn-secondary">Visualizar cartão-resposta</button><div class="documento-estacao"><div class="card card-pad"><h2>Questão por questão</h2><p>Resultado preservado.</p></div>${parcial}</div></main><footer aria-label="Versão do software" style="position:fixed;bottom:0;height:44px;width:100%">Ensaio</footer><script src="/js/leitor-cartao.js"></script><script src="/js/painel-cartao.js"></script><script>criarPainelCartao(${JSON.stringify(foto)});</script></body></html>`;
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try{
 const c=await browser.newContext();let modo='ok',fotos=0;const erros=[];
 await c.route('**/*',r=>{const u=new URL(r.request().url());if(u.hostname!=='g360.test')return r.abort();
  if(u.pathname==='/ensaio')return r.fulfill({contentType:'text/html',body:html});
  if(u.pathname.startsWith('/api/')){assert.equal(u.pathname+u.search,foto);fotos++;return r.fulfill(modo==='erro'?{status:403,json:{message:'Recusada'}}:{contentType:'image/svg+xml',body:imagemTeste});}
  if(u.pathname.startsWith('/js/')||u.pathname.startsWith('/css/'))return r.fulfill({contentType:u.pathname.endsWith('.css')?'text/css':'text/javascript',body:readFileSync('apps/web/public'+u.pathname)});
  return r.abort();});
 const p=await c.newPage();p.on('pageerror',e=>erros.push(e.message));const img=p.getByRole('img',{name:'Cartão-resposta completo — página 1 de 1',exact:true}),modal=p.getByRole('dialog',{name:'Cartão-resposta',exact:true});
 await p.setViewportSize({width:1440,height:1000});await p.goto('http://g360.test/ensaio');
 assert.equal(fotos,0);assert.equal(await p.locator('#doc-painel').isVisible(),false);assert.equal(await p.locator('#doc-abrir').getAttribute('aria-expanded'),'false');
 await p.getByRole('button',{name:'Visualizar cartão-resposta',exact:true}).click();await img.waitFor();assert.equal(fotos,1);
 assert.equal(await p.getByRole('button',{name:'Ocultar cartão-resposta',exact:true}).getAttribute('aria-expanded'),'true');
 assert.ok(await p.evaluate(()=>document.getElementById('doc-painel').getBoundingClientRect().bottom<document.querySelector('footer').getBoundingClientRect().top));
 await p.getByRole('button',{name:'Ocultar cartão-resposta',exact:true}).click();assert.equal(await img.count(),0);assert.equal(await p.locator('#doc-painel').isVisible(),false);
 for(const width of [1099,390,320]){
  await p.setViewportSize({width,height:844});assert.equal(await p.locator('#doc-abrir').getAttribute('aria-haspopup'),'dialog');
  await p.getByRole('button',{name:'Visualizar cartão-resposta',exact:true}).click();await img.waitFor();assert.equal(await modal.isVisible(),true);
  const b=await modal.boundingBox();assert.ok(b.x>=0&&b.x+b.width<=width+1&&b.y+b.height<=845);
  await p.keyboard.press('Escape');assert.equal(await p.locator('#doc-abrir').evaluate(e=>e===document.activeElement),true);assert.equal(await img.count(),0);
 }
 modo='erro';await p.getByRole('button',{name:'Visualizar cartão-resposta',exact:true}).click();await p.getByRole('alert').waitFor();
 modo='ok';await p.getByRole('button',{name:'Tentar novamente',exact:true}).click();await img.waitFor();
 await p.setViewportSize({width:1440,height:1000});assert.equal(await modal.isVisible(),false);await img.waitFor();assert.equal(await p.locator('#doc-painel').isVisible(),true);
 await p.getByRole('button',{name:'Ocultar cartão-resposta',exact:true}).click();assert.equal(await p.getByText('Resultado preservado.',{exact:true}).isVisible(),true);assert.deepEqual(erros,[]);
 console.log('OK: painel sob demanda/ocultar, modal1099/390/320, foco, resize, erro/retry e resultado preservado.');
}finally{await browser.close();}
