import assert from 'node:assert/strict';
import {readFileSync,existsSync} from 'node:fs';
import {resolve,join} from 'node:path';
import {tmpdir} from 'node:os';
import {chromium} from 'playwright-core';
import {orderFromLayout,annotateOrder,moveGroup} from '../../apps/web/resources/js/painel-hierarquia.js';

const definitions={resultados:['estudantes','periodo-agenda','analises'],acompanhamento:['planejamento','ensino','decisoes']};
const original=[{id:'a',section:'estudantes',x:0,y:0,w:6,h:12,fixo:true},{id:'b',section:'analises',x:6,y:0,w:6,h:9,orientacao:'horizontal'}];
assert.deepEqual(orderFromLayout([],definitions),{cards:['resultados','acompanhamento'],sections:definitions});
const changed={cards:['acompanhamento','resultados'],sections:{resultados:['analises','periodo-agenda','estudantes'],acompanhamento:['decisoes','ensino','planejamento']}};
const annotated=annotateOrder(original,changed);assert.equal(annotated[0].ordem_secao,2);assert.equal(annotated[0].ordem_card,1);
assert.equal(annotated[0].fixo,true);assert.equal(annotated[1].orientacao,'horizontal');assert.equal(original[0].ordem_card,undefined);
assert.deepEqual(moveGroup(['a','b','c'],'a',2),['b','c','a']);assert.deepEqual(moveGroup(['a','b'],'a',-1),['a','b']);
assert.throws(()=>orderFromLayout([{...annotated[0],ordem_card:'1'}],definitions));
assert.throws(()=>orderFromLayout([annotated[0],{...annotated[1],ordem_secao:2}],definitions));

const fixture=JSON.parse(readFileSync('apps/api/tests/Fixtures/organizacao-painel.json','utf8'));
const initial={...fixture,versao:0,apresentacao:'tabela',layouts:{desktop:[],mobile:[]},repetido:false,origem_disposicao:'perfil'};
const base='http://hierarquia.test',publico=resolve('apps/web/public'),fixtures=process.env.HIERARQUIA_FIXTURES||join(tmpdir(),'g360-tw2-render');
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try{
 const context=await browser.newContext({viewport:{width:1440,height:1000}}),page=await context.newPage(),errors=[],writes=[];
 let saved=structuredClone(initial),conflict=false;
 page.on('pageerror',e=>errors.push(e.message));
 await context.route('**/*',async route=>{
  const req=route.request(),url=new URL(req.url());assert.equal(url.origin,base,'Sem chamadas externas');
  if(req.method()==='POST'&&url.pathname.startsWith('/app/paineis/preferencias/')){
   assert.equal(url.search,'');const body=req.postDataJSON();
   if(body.acao==='salvar'){
    writes.push(body);if(conflict||body.versao!==saved.versao)return route.fulfill({status:409,json:{message:'A organização mudou.'}});
    saved={...saved,versao:saved.versao+1,layouts:structuredClone(body.layouts),apresentacao:body.apresentacao,origem_disposicao:'usuario'};
   }
   return route.fulfill({json:{data:saved}});
  }
  assert.equal(req.method(),'GET','Nenhuma operação de negócio');
  if(url.pathname==='/hierarquia.html'){
   const html=readFileSync(join(fixtures,'hierarquia.html'),'utf8').replaceAll('https://gabarito360.com.br',base).replaceAll('http://localhost',base)
    .replaceAll('https:\\/\\/gabarito360.com.br',base).replaceAll('http:\\/\\/localhost',base);
   return route.fulfill({contentType:'text/html',body:html});
  }
  const path=resolve(publico,'.'+url.pathname);
  if(!path.startsWith(publico+'/')&&!path.startsWith(publico+'\\'))return route.abort();
  if(!/^\/(css|js|fonts|brand|images)\//.test(url.pathname)||!existsSync(path))return route.fulfill({status:404,body:''});
  const type=path.endsWith('.css')?'text/css':path.endsWith('.js')?'application/javascript':path.endsWith('.svg')?'image/svg+xml':path.endsWith('.woff2')?'font/woff2':'image/png';
  return route.fulfill({contentType:type,body:readFileSync(path)});
 });
 const sections=()=>page.locator('[data-painel-card=resultados] [data-painel-secao]').evaluateAll(es=>es.map(e=>e.dataset.painelSecao));
 const cards=()=>page.locator('[data-painel-card]').evaluateAll(es=>es.map(e=>e.dataset.painelCard));
 const organize=()=>page.getByRole('button',{name:'Organizar painel',exact:true}).click();
 const save=()=>page.getByRole('button',{name:'Salvar disposição',exact:true}).click();
 const reset=()=>page.getByRole('button',{name:'Restaurar padrão',exact:true}).click();
 const waitSaved=()=>page.getByText('Disposição salva.',{exact:true}).waitFor({state:'visible'});
 await page.goto(base+'/hierarquia.html');await organize();
 assert.deepEqual(await sections(),definitions.resultados);assert.equal(await page.locator('[data-painel-secao=resumo] [data-painel-grupo-mover]').count(),0);
 assert.equal(await page.locator('[data-painel-grupo-mover]:visible').count(),8);
 await page.getByRole('button',{name:'Mover seção Estudantes. Arraste ou use as setas para cima e para baixo.',exact:true}).press('ArrowDown');
 assert.deepEqual(await sections(),['periodo-agenda','estudantes','analises']);
 await page.getByRole('button',{name:'Mover card Resultados da turma. Arraste ou use as setas para cima e para baixo.',exact:true}).press('ArrowDown');
 assert.deepEqual(await cards(),['acompanhamento','resultados']);
 await page.getByRole('button',{name:'Fixar posição e tamanho de Distribuição dos acertos',exact:true}).click();
 await save();await waitSaved();assert.equal(writes.length,1);
 const node=saved.layouts.desktop.find(n=>n.id==='distribuicao-acertos');assert.equal(node.fixo,true);assert.equal(node.ordem_card,1);
 const payload=structuredClone(saved.layouts.desktop);
 await page.reload();await organize();assert.deepEqual(await cards(),['acompanhamento','resultados']);assert.deepEqual(await sections(),['periodo-agenda','estudantes','analises']);
 assert.equal(await page.locator('[data-painel-secao=analises]').evaluate(e=>e.parentElement.hasAttribute('data-painel-graficos')),true,'Contexto dos gráficos acompanha a seção inteira');
 await reset();assert.deepEqual(await sections(),definitions.resultados);await page.getByRole('button',{name:'Cancelar',exact:true}).first().click();
 assert.deepEqual(await sections(),['periodo-agenda','estudantes','analises']);assert.equal(writes.length,1,'Cancelar não grava');
 await organize();await reset();await save();await waitSaved();assert.equal(writes.length,2);
 assert.equal(saved.layouts.desktop.find(n=>n.id==='alunos-destaques').ordem_secao,0,'Restaurar envia ranks padrão explícitos');
 assert.equal(saved.layouts.desktop.find(n=>n.id==='metas').ordem_card,1);
 await page.reload();await organize();assert.deepEqual(await sections(),definitions.resultados);assert.deepEqual(await cards(),Object.keys(definitions));
 // Mesmo gesto de ponteiro serve para seção e card; o teclado atende qualquer distância da página.
 await page.setViewportSize({width:1440,height:2200});
 const handle=page.locator('[data-painel-secao=estudantes] [data-painel-grupo-mover]');await handle.scrollIntoViewIfNeeded();
 const start=await handle.boundingBox(),target=await page.locator('[data-painel-secao=periodo-agenda]').boundingBox();
 await page.mouse.move(start.x+start.width/2,start.y+start.height/2);await page.mouse.down();
 await page.mouse.move(start.x+start.width/2+12,start.y+start.height/2+12);
 await page.mouse.move(start.x+start.width/2,target.y+target.height-4,{steps:8});await page.mouse.up();
 assert.deepEqual(await sections(),['periodo-agenda','estudantes','analises'],'Ponteiro move título e widgets juntos');
 await page.getByRole('button',{name:'Cancelar',exact:true}).first().click();assert.deepEqual(await sections(),definitions.resultados);
 const beforeWrites=writes.length;await page.setViewportSize({width:390,height:844});
 assert.equal(await page.locator('[data-painel-grupo-mover]:visible').count(),0);assert.equal(await page.locator('[data-painel-organizacao]').isHidden(),true);
 await page.setViewportSize({width:1440,height:1000});await organize();assert.deepEqual(await sections(),definitions.resultados);assert.equal(writes.length,beforeWrites,'Breakpoint não grava preferências');
 conflict=true;await page.getByRole('button',{name:'Mover seção Estudantes. Arraste ou use as setas para cima e para baixo.',exact:true}).press('ArrowDown');await save();
 await page.getByRole('button',{name:'Consultar estado salvo',exact:true}).waitFor({state:'visible'});
 assert.equal(writes.length,beforeWrites+1,'409 não reexecuta o salvamento');assert.equal(await page.getByRole('button',{name:'Salvar disposição',exact:true}).isDisabled(),true);
 assert.deepEqual(errors,[]);assert.equal(payload.length,saved.layouts.desktop.length,'Sem excluir ou duplicar widgets');
 console.log('Painéis: grupos/cards, ponteiro/teclado, pins, persistência/reload, restaurar/cancelar, mobile e409 aprovados.');
}finally{await browser.close();}
