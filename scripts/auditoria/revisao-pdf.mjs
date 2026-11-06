import {chromium} from 'playwright-core';
import {readFileSync,mkdirSync} from 'node:fs';
import {createRequire} from 'node:module';
import assert from 'node:assert/strict';
import {paginaRevisao,imagemRaster} from './revisao-modal-fixture.mjs';
const require=createRequire(import.meta.url),{PDFDocument}=require('../../apps/web/public/js/libs/pdf-lib-1.17.1/pdf-lib.min.js');
const doc=await PDFDocument.create();doc.addPage([200,300]);doc.addPage([300,200]);const pdfTeste=Buffer.from(await doc.save());
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try {
 const raster=await imagemRaster(browser);
 const context=await browser.newContext(),erros=[],fotos=[],posts=[];let modo='sucesso',modoRecorte='sucesso',liberarRecorte,liberarImagem,liberarResposta;
 await context.route('**/*',async route=>{
  const req=route.request(),u=new URL(req.url());if(u.hostname!=='g360.test')return route.abort();
  if(u.pathname.startsWith('/js/')||u.pathname==='/css/app.css') {
   assert.ok(!u.pathname.includes('..'));return route.fulfill({contentType:u.pathname.endsWith('.css')?'text/css':'text/javascript',body:readFileSync('apps/web/public'+u.pathname)});
  }
  if(u.pathname.endsWith('/imagens/endireitada')) {
   assert.equal(req.headers()['x-g360-revisao'],'revisao-ficticia-ci');assert.equal(u.search,'');
   if(modoRecorte==='lento')await new Promise(r=>liberarRecorte=r);
   return route.fulfill(modoRecorte==='erro'?{status:403,json:{message:'Recusada'}}:modoRecorte==='mime'?{contentType:'text/html',body:'Não é imagem'}:{contentType:'image/png',body:raster});
  }
  if(u.pathname.endsWith('/imagens/original')) {
   assert.equal(req.headers()['x-g360-revisao'],'revisao-ficticia-ci');fotos.push(req.url());if(modo==='lento')await new Promise(r=>liberarImagem=r);
   return route.fulfill(modo==='erro'?{status:403,json:{message:'Indisponível'}}:modo==='pdf'?{contentType:'application/pdf',body:pdfTeste}:{contentType:'image/png',body:raster});
  }
  if(req.method()==='POST'){posts.push(req.postDataJSON());if(modo==='salvando')await new Promise(r=>liberarResposta=r);return route.fulfill({json:{cartao_status:'lido'}});}
  if(u.pathname==='/revisao')return route.fulfill({contentType:'text/html',body:paginaRevisao({tema:u.searchParams.get('tema'),semFoto:u.searchParams.has('semfoto')})});
  return route.abort();
 });
 const page=await context.newPage();page.on('pageerror',e=>erros.push(e.message));
 // Relógio somente do ensaio: executa o callback real de25s em100ms nas páginas ?prazo=1.
 await page.addInitScript(()=>{
  const set=window.setTimeout.bind(window),clear=window.clearTimeout.bind(window);
  window.__recorteTimers=new Set();window.__recortePrazos=[];
  window.setTimeout=(fn,ms,...args)=>{
   if(ms!==25000)return set(fn,ms,...args);
   window.__recortePrazos.push(ms);let id;
   id=set((...values)=>{window.__recorteTimers.delete(id);fn(...values);},location.search.includes('prazo=1')&&window.__recortePrazos.length===1?100:ms,...args);
   window.__recorteTimers.add(id);return id;
  };
  window.clearTimeout=id=>{window.__recorteTimers.delete(id);return clear(id);};
 });
 const preview=page.locator('#rev-previa'),painel=page.locator('#rev-documento-painel'),modal=page.locator('#rev-cartao-modal');
 const canvas=page.getByRole('img',{name:/Cartão-resposta completo — página/});
 const pronto=()=>canvas.waitFor({state:'visible',timeout:20000});
 const eventos=alvo=>alvo.evaluate(el=>Object.fromEntries(['contextmenu','dragstart','copy','cut'].map(tipo=>{const e=new Event(tipo,{bubbles:true,cancelable:true});return [tipo,!el.dispatchEvent(e)];})));
 const saida=process.env.REV_MODAL_CAPTURAS;if(saida)mkdirSync(saida,{recursive:true});
 await page.setViewportSize({width:1440,height:1000});await page.goto('http://g360.test/revisao');await pronto();
 assert.equal(await preview.getByRole('img',{name:'Linha da questão 1 no cartão'}).isVisible(),true);
 assert.equal(await painel.isVisible(),true);assert.ok(await page.evaluate(()=>document.getElementById('rev-documento-painel').getBoundingClientRect().bottom<document.querySelector('footer').getBoundingClientRect().top));const [a,b]=await Promise.all([preview.boundingBox(),page.locator('.rev-respostas').boundingBox()]);assert.ok(a.y+a.height<=b.y+1);
 assert.equal(await page.evaluate(()=>leitor.pdf.numPages),1);assert.ok(fotos.length===1);
 const area=await page.locator('.rev-leitor-area').boundingBox(),c=await canvas.boundingBox();assert.ok(c.width<=area.width&&c.height<=area.height);
 assert.deepEqual(await eventos(canvas),{contextmenu:true,dragstart:true,copy:true,cut:true});
 assert.equal(await page.getByText('Aluno fictício 65',{exact:true}).first().evaluate(el=>{const e=new Event('copy',{bubbles:true,cancelable:true});return el.dispatchEvent(e);}),true);
 if(saida)await page.screenshot({path:saida+'/desktop1440.png'});
 await page.getByRole('button',{name:'Pular',exact:true}).click();await pronto();assert.equal(fotos.length,1,'Mesma pessoa não recarrega documento');
 modo='lento';await page.getByRole('button',{name:'Pular',exact:true}).click();await page.getByText('Carregando cartão em PDF…').waitFor();assert.equal(await canvas.count(),0);
 modo='sucesso';await page.getByRole('button',{name:'Pular',exact:true}).click();await pronto();liberarImagem();await pronto();assert.equal(await page.evaluate(()=>leitor.url.includes('/65/')),true);
 for(const [width,height,tema] of [[2560,1263,'light'],[1099,900,'light'],[390,844,'light'],[320,740,'dark']]) {
  await page.setViewportSize({width,height});await page.goto('http://g360.test/revisao?tema='+tema);
  assert.equal(await preview.getByRole('img',{name:'Linha da questão 1 no cartão'}).isVisible(),true);
  const alternativas=await page.locator('.rev-resposta').evaluateAll(bs=>bs.map(b=>{const r=b.getBoundingClientRect();return {x:r.x,y:r.y,w:r.width,h:r.height};}));
  assert.equal(new Set(alternativas.map(b=>Math.round(b.y))).size,1,'Alternativas permanecem na mesma linha');assert.ok(alternativas.every(b=>b.w>=44&&b.h>=48));
  if(width>=1100){await pronto();if(saida)await page.screenshot({path:saida+`/desktop${width}.png`});continue;}
  const qtd=fotos.length;assert.equal(await painel.isVisible(),false);assert.equal(await canvas.count(),0);assert.equal(fotos.length,qtd);
  if(saida)await page.screenshot({path:saida+`/revisao${width}-${tema}.png`});
  await page.getByRole('button',{name:'Visualizar gabarito',exact:true}).click();await pronto();
  const box=await modal.boundingBox();assert.ok(box.x>=0&&box.y>=0&&box.x+box.width<=width+1&&box.y+box.height<=height+1);
  const antes=posts.length;await page.keyboard.press('a');await page.keyboard.press('0');assert.equal(posts.length,antes);
  await page.getByRole('button',{name:'Ampliar PDF',exact:true}).click();await page.waitForFunction(()=>document.querySelector('[data-zoom]').textContent==='125%');
  await page.getByRole('button',{name:'Ajustar à página',exact:true}).click();await page.waitForFunction(()=>document.querySelector('[data-zoom]').textContent==='Ajustado');
  if(saida)await page.screenshot({path:saida+`/modal${width}-${tema}.png`});
  await page.keyboard.press('Escape');assert.equal(await page.getByRole('button',{name:'Visualizar gabarito',exact:true}).evaluate(e=>e===document.activeElement),true);
 }
 await page.goto('http://g360.test/revisao');modo='erro';await page.getByRole('button',{name:'Visualizar gabarito',exact:true}).click();await page.getByRole('alert').waitFor();
 if(saida)await page.screenshot({path:saida+'/erro320.png'});
 modo='sucesso';await page.getByRole('button',{name:'Tentar novamente',exact:true}).click();await pronto();await page.keyboard.press('Escape');
 modo='pdf';await page.getByRole('button',{name:'Visualizar gabarito',exact:true}).click();await pronto();assert.equal(await page.evaluate(()=>leitor.pdf.numPages),2);
 await page.getByRole('button',{name:'Próxima página',exact:true}).click();await page.getByRole('img',{name:'Cartão-resposta completo — página 2 de 2',exact:true}).waitFor();await page.keyboard.press('Escape');
 assert.ok(fotos.every(url=>new URL(url).search==='' && new URL(url).pathname.startsWith('/app/cartoes/')));
 modo='sucesso';await page.goto('http://g360.test/revisao');modo='salvando';
 const qtdPosts=posts.length;await page.getByRole('button',{name:'A',exact:true}).click();
 assert.equal(await page.getByRole('button',{name:'Visualizar gabarito',exact:true}).isDisabled(),true);await page.keyboard.press('a');assert.equal(posts.length,qtdPosts+1);
 assert.equal(await page.locator('.rev-opcoes button:disabled').count(),7);assert.equal(await page.getByText('Salvando resposta…',{exact:true}).isVisible(),true);
 if(saida)await page.screenshot({path:saida+'/salvando320.png'});
 modo='sucesso';liberarResposta();await page.waitForFunction(()=>FILA.length===2);
 await page.getByRole('button',{name:'B',exact:true}).click();await page.waitForFunction(()=>FILA.length===1);
 await page.getByRole('button',{name:'C, resposta atual',exact:true}).click();await page.getByRole('heading',{name:'Revisão concluída',exact:true}).waitFor();
 assert.equal(await canvas.count(),0);await page.getByRole('button',{name:'Voltar à última',exact:true}).click();assert.equal(await preview.isVisible(),true);
 await page.goto('http://g360.test/revisao?semfoto=1');assert.equal(await page.getByRole('button',{name:'Visualizar gabarito',exact:true}).count(),0);
 // Recorte protegido: não registrar respostas sem imagem, nem reaproveitar resposta tardia de outra questão.
 modoRecorte='lento';await page.goto('http://g360.test/revisao');
 await page.getByText('Carregando trecho da marcação…',{exact:true}).waitFor();
 const antesRecorte=posts.length;assert.equal(await page.getByRole('button',{name:'A',exact:true}).isDisabled(),true);
 await page.keyboard.press('a');assert.equal(posts.length,antesRecorte);
 while(!liberarRecorte)await page.waitForTimeout(10);
 const soltarAntigo=liberarRecorte;modoRecorte='sucesso';await page.evaluate(()=>pular());
 await page.waitForFunction(()=>document.querySelector('.rev-recorte').getAttribute('aria-busy')==='false');
 const blobAtual=await page.locator('.rev-recorte').evaluate(el=>el.style.backgroundImage);assert.ok(blobAtual.includes('blob:'));
 soltarAntigo();await page.waitForTimeout(70);assert.equal(await page.locator('.rev-recorte').evaluate(el=>el.style.backgroundImage),blobAtual);
 for(const falha of ['mime','erro']) {
  modoRecorte=falha;await page.goto('http://g360.test/revisao');
  const feedback=page.locator('#rev-recorte-feedback');await feedback.getByRole('button',{name:'Tentar novamente',exact:true}).waitFor();
  assert.equal(await page.getByRole('button',{name:'A',exact:true}).isDisabled(),true);
  await page.keyboard.press('a');assert.equal(posts.length,antesRecorte);
  modoRecorte='lento';liberarRecorte=null;await feedback.getByRole('button',{name:'Tentar novamente',exact:true}).click();
  assert.equal(await feedback.evaluate(el=>el===document.activeElement),true,'Retry mantém foco em região estável durante carga');
  while(!liberarRecorte)await page.waitForTimeout(10);
  modoRecorte='sucesso';liberarRecorte();
  await page.waitForFunction(()=>document.querySelector('.rev-recorte').getAttribute('aria-busy')==='false');
  assert.equal(await page.getByRole('button',{name:'A',exact:true}).isEnabled(),true);
  assert.equal(await page.evaluate(()=>document.activeElement.matches('.rev-opcoes .rev-resposta')),true,'Sucesso move foco da região para as respostas');
  assert.equal(await page.evaluate(()=>window.__recorteTimers.size),0);
 }
 // Prazo real configurado continua25s; somente o relógio local acelera sua expiração.
 modoRecorte='lento';liberarRecorte=null;await page.goto('http://g360.test/revisao?prazo=1');
 const prazoFeedback=page.locator('#rev-recorte-feedback');await prazoFeedback.getByRole('button',{name:'Tentar novamente',exact:true}).waitFor();
 assert.deepEqual(await page.evaluate(()=>window.__recortePrazos),[25000]);
 assert.equal(await page.evaluate(()=>cargaRecorte.abort.signal.aborted),true);
 assert.equal(await page.getByRole('button',{name:'A',exact:true}).isDisabled(),true);
 assert.equal(await page.evaluate(()=>window.__recorteTimers.size),0);
 await page.keyboard.press('a');assert.equal(posts.length,antesRecorte);
 const liberarExpirado=liberarRecorte;modoRecorte='sucesso';
 await prazoFeedback.getByRole('button',{name:'Tentar novamente',exact:true}).click();
 await page.waitForFunction(()=>document.querySelector('.rev-recorte').getAttribute('aria-busy')==='false');
 assert.equal(await page.evaluate(()=>document.activeElement.matches('.rev-opcoes .rev-resposta')),true);
 liberarExpirado();
 assert.deepEqual(erros,[]);console.log('OK: recorte preservado, painel/PDF real, modal/foco/atalhos, troca/latência, erro/retry, páginas, URL sem credenciais e 2560/1440/1099/390/320.');
} finally {await browser.close();}
