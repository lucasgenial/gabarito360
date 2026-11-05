import {chromium} from 'playwright-core';
import {readFileSync,mkdirSync} from 'node:fs';
import assert from 'node:assert/strict';
import {paginaRevisao,imagemTeste,itensTeste} from './revisao-modal-fixture.mjs';
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try {
 const context=await browser.newContext(), erros=[], fotos=[], posts=[];
 let modo='sucesso',liberarImagem,liberarResposta;
 await context.route('**/*',async route=>{
  const req=route.request(),u=new URL(req.url());
  if(u.hostname!=='g360.test')return route.abort();
  if(u.pathname==='/css/app.css')return route.fulfill({contentType:'text/css',body:readFileSync('apps/web/public/css/app.css','utf8')});
  if(u.pathname==='/js/contexto-aba.js')return route.fulfill({contentType:'text/javascript',body:readFileSync('apps/web/public/js/contexto-aba.js','utf8')});
  if(u.pathname==='/recorte.svg')return route.fulfill({contentType:'image/svg+xml',body:imagemTeste});
  if(u.pathname.includes('/arquivo/original')) {
   fotos.push(req.url());if(modo==='lento')await new Promise(r=>liberarImagem=r);
   return route.fulfill(modo==='erro'?{status:403,json:{message:'Imagem indisponível'},headers:{'Cache-Control':'no-store'}}:{contentType:'image/svg+xml',body:imagemTeste,headers:{'Cache-Control':'no-store'}});
  }
  if(req.method()==='POST') {posts.push(req.postDataJSON());if(modo==='salvando')await new Promise(r=>liberarResposta=r);return route.fulfill({json:{cartao_status:'lido'}});}
  if(u.pathname==='/revisao')return route.fulfill({contentType:'text/html',body:paginaRevisao({tema:u.searchParams.get('tema')})});
  return route.abort();
 });
 const page=await context.newPage();page.on('pageerror',e=>erros.push(e.message));
 await page.setViewportSize({width:1440,height:1000});await page.goto('http://g360.test/revisao');
 const abrir=page.getByRole('button',{name:'Ver cartão inteiro',exact:true}), modal=page.locator('#rev-cartao-modal');
 const original=page.url();modo='lento';await abrir.click();
 await page.getByText('Carregando cartão…',{exact:true}).waitFor({state:'visible'});
 assert.equal(await page.getByRole('button',{name:'Fechar cartão'}).evaluate(e=>e===document.activeElement),true);
 for(const tecla of ['a','b','c','d','e','0','x'])await page.keyboard.press(tecla);
 assert.equal(posts.length,0);assert.match(await page.locator('.rev-progresso').innerText(),/Faltam 2/);
 modo='sucesso';liberarImagem();await page.getByAltText('Cartão-resposta completo').waitFor({state:'visible'});
 assert.equal(await page.getByAltText('Cartão-resposta completo').getAttribute('src'),itensTeste[0].foto_url);
 assert.ok(fotos.every(url=>!new URL(url).searchParams.has('_ctx')));
 assert.equal(context.pages().length,1);assert.equal(page.url(),original);
 for(let i=0;i<9;i++){await page.keyboard.press('Tab');assert.equal(await page.evaluate(()=>!!document.activeElement.closest('#rev-cartao-modal')),true);}
 for(let i=0;i<4;i++){await page.keyboard.press('Shift+Tab');assert.equal(await page.evaluate(()=>!!document.activeElement.closest('#rev-cartao-modal')),true);}
 await page.getByRole('button',{name:'Ampliar',exact:true}).click();
 assert.equal(await page.locator('#rev-cartao-area').evaluate(e=>e.scrollWidth>e.clientWidth),true);
 await page.getByRole('button',{name:'Ajustar à tela',exact:true}).click();
 assert.equal(await page.getByAltText('Cartão-resposta completo').evaluate(e=>getComputedStyle(e).objectFit),'contain');
 await page.keyboard.press('Escape');assert.equal(await modal.evaluate(e=>e.open),false);
 assert.equal(await abrir.evaluate(e=>e===document.activeElement),true);assert.equal(posts.length,0);
 await page.getByRole('button',{name:'Pular',exact:true}).click();await abrir.click();
 await page.getByAltText('Cartão-resposta completo').waitFor({state:'visible'});
 assert.match(await page.locator('#rev-cartao-contexto').innerText(),/Aluno fictício 66/);
 assert.equal(await page.getByAltText('Cartão-resposta completo').getAttribute('src'),itensTeste[1].foto_url);
 await page.getByRole('button',{name:'Fechar cartão'}).click();
 modo='erro';await abrir.click();await page.getByRole('alert').waitFor({state:'visible'});
 assert.equal(await page.getByRole('button',{name:'Ampliar',exact:true}).isDisabled(),true);
 modo='sucesso';await page.getByRole('button',{name:'Tentar novamente'}).click();await page.getByAltText('Cartão-resposta completo').waitFor({state:'visible'});
 assert.equal(await page.evaluate(()=>!!document.activeElement.closest('#rev-cartao-modal')),true);
 assert.equal(fotos.at(-1),fotos.at(-2));await page.keyboard.press('Escape');
 modo='salvando';await page.getByRole('button',{name:'A',exact:true}).click();
 assert.equal(await abrir.isDisabled(),true);assert.equal(await modal.evaluate(e=>e.open),false);
 await page.keyboard.press('a');assert.equal(posts.length,1);
 modo='sucesso';liberarResposta();await page.getByText('Questão 1 · Faltam 1',{exact:true}).waitFor();
 await page.keyboard.press('d');await page.getByRole('heading',{name:'Revisão concluída'}).waitFor();assert.equal(posts.length,2);
 const saida=process.env.REV_MODAL_CAPTURAS;if(saida)mkdirSync(saida,{recursive:true});
 for(const [width,height,tema] of [[2560,1263,'light'],[390,844,'light'],[320,740,'dark']]) {
  await page.setViewportSize({width,height});await page.goto('http://g360.test/revisao?tema='+tema);
  await page.getByRole('button',{name:'Ver cartão inteiro',exact:true}).click();await page.getByAltText('Cartão-resposta completo').waitFor({state:'visible'});
  const m=await modal.boundingBox();assert.ok(m.x>=0&&m.y>=0&&m.x+m.width<=width+1&&m.y+m.height<=height+1,JSON.stringify({width,m}));
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
  if(saida)await page.screenshot({path:`${saida}/modal-${width}-${tema}.png`});
  await page.keyboard.press('Escape');
 }
 await page.setViewportSize({width:390,height:844});await page.goto('http://g360.test/revisao');modo='erro';
 await page.getByRole('button',{name:'Ver cartão inteiro',exact:true}).click();await page.getByRole('alert').waitFor({state:'visible'});
 for(const alvo of [page.getByRole('heading',{name:'Cartão-resposta',exact:true}),page.getByRole('button',{name:'Fechar cartão'}),page.getByRole('button',{name:'Tentar novamente'})]) {
  const r=await alvo.boundingBox();assert.ok(r.y>=0&&r.y+r.height<=844,JSON.stringify(r));
 }
 if(saida)await page.screenshot({path:`${saida}/modal-390-erro.png`});
 assert.deepEqual(erros,[]);console.log('OK: modal, foco, teclado sem POST, cartão correto, zoom, erro/retry, salvamento e 2560/390/320.');
} finally {await browser.close();}
