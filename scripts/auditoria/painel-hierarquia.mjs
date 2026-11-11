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

const initial=JSON.parse(readFileSync('apps/web/tests/Fixtures/editor-turma.json','utf8'));
const base='https://hierarquia.test',publico=resolve('apps/web/public'),fixtures=process.env.HIERARQUIA_FIXTURES||join(tmpdir(),'g360-tw2-render');
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try{
 const context=await browser.newContext({viewport:{width:1440,height:1000}}),page=await context.newPage(),errors=[],writes=[];
 let saved=structuredClone(initial),conflict=false;
 page.on('pageerror',e=>errors.push(e.message));
 await context.route('**/*',async route=>{
  const req=route.request(),url=new URL(req.url());assert.equal(url.origin,base,'Sem chamadas externas');
  if(req.method()==='POST'&&url.pathname.startsWith('/app/')){
   assert.equal(url.search,'');const body=req.postDataJSON();assert.equal(req.headers()['x-g360-painel-editor'],'3');
   assert.ok(body._recurso&&body._token&&body._revisao,'UR4, CSRF e revisão no transporte');
   if(body.acao==='salvar'){
    writes.push(body);if(conflict||body.versao!==saved.data.versao)return route.fulfill({status:409,json:{message:'A organização mudou.'}});
    assert.equal('context'in body.composicao,false);assert.equal(body.composicao.schema,3);
    saved=structuredClone(saved);saved.data.versao++;saved.data.editor.composicao=structuredClone(body.composicao);saved.data.editor.origem='usuario';saved.data.apresentacao=body.apresentacao;
    saved.etag='"g360-editor-'+String(saved.data.versao).padStart(64,'0')+'"';
   }else if(req.headers()['if-none-match']===saved.etag)return route.fulfill({json:{nao_modificado:true,etag:saved.etag}});
   return route.fulfill({json:saved});
  }
  assert.equal(req.method(),'GET','Nenhuma operação de negócio');
  if(url.pathname==='/editor-turma.html'){
   let html=readFileSync(join(fixtures,'editor-turma.html'),'utf8').replaceAll('https://gabarito360.com.br',base).replaceAll('http://localhost',base)
    .replaceAll('https:\\/\\/gabarito360.com.br',base).replaceAll('http:\\/\\/localhost',base);
   html=html.replace(/(<script type="application\/json" id="painel-editor-config">)([\s\S]*?)(<\/script>)/,(_s,start,json,end)=>{const cfg=JSON.parse(json);cfg.bootstrap=saved;return start+JSON.stringify(cfg).replaceAll('<','\\u003c')+end;});
   return route.fulfill({contentType:'text/html',body:html});
  }
  const path=resolve(publico,'.'+url.pathname);
  if(!path.startsWith(publico+'/')&&!path.startsWith(publico+'\\'))return route.abort();
  if(!/^\/(css|js|fonts|brand|images)\//.test(url.pathname)||!existsSync(path))return route.fulfill({status:404,body:''});
  const type=path.endsWith('.css')?'text/css':path.endsWith('.js')?'application/javascript':path.endsWith('.svg')?'image/svg+xml':path.endsWith('.woff2')?'font/woff2':'image/png';
  return route.fulfill({contentType:type,body:readFileSync(path)});
 });
 const organize=()=>page.getByRole('button',{name:'Organizar painel',exact:true}).click();
 const save=()=>page.getByRole('button',{name:'Salvar disposição',exact:true}).click();
 const waitSaved=()=>page.getByText('Disposição salva.',{exact:true}).waitFor({state:'visible'});
 await page.goto(base+'/editor-turma.html');assert.equal(await page.evaluate(()=>isSecureContext&&typeof crypto.randomUUID==='function'),true,'Ensaio reproduz o HTTPS da aplicação');await organize();
 assert.equal(writes.length,0,'SSR abre sem gravar');assert.equal(await page.getByRole('tab',{name:'Distribuição por seção',exact:true}).isVisible(),true);
 assert.equal(await page.locator('[data-painel-secao=resumo] [data-no-mover]').count(),0,'KPIs permanecem fora da composição');
 await page.locator('[data-destino="distribuicao-acertos"]').selectOption('');
 await page.locator('[data-quadro-ativo="devolutivas"]').uncheck();
 await page.locator('[data-quadro-titulo="distribuicao-acertos"]').click();await page.getByLabel('Novo título',{exact:true}).fill('Minha análise <teste>');await page.getByRole('button',{name:'Confirmar título',exact:true}).click();
 await page.getByRole('button',{name:'Novo painel',exact:true}).click();await page.getByLabel('Nome do painel',{exact:true}).fill('Painel vazio pessoal');await page.getByRole('button',{name:'Criar painel',exact:true}).click();
 await page.getByRole('dialog',{name:'Novo painel',exact:true}).waitFor({state:'hidden'});
 await page.getByRole('tab',{name:'Ajustes finos',exact:true}).click();
 await page.locator('[data-no-mover="participacao"]').press('Shift+ArrowDown');assert.match(await page.locator('[data-editor-status]').textContent(),/ajustado/);
 await page.locator('[data-no-pin="distribuicao-acertos"]').click();assert.equal(await page.locator('[data-no-pin="distribuicao-acertos"]').getAttribute('aria-pressed'),'true');
 const antesSave=await page.locator('h1').boundingBox();await save();await waitSaved();assert.equal(writes.length,1);
 assert.equal(await page.locator('[data-editor-status]').textContent(),'','Não duplica sucesso em parágrafo solto');assert.equal(await page.locator('#g360-notificacoes [data-g360-toast]').count(),1);assert.deepEqual(await page.locator('h1').boundingBox(),antesSave,'Toast não desloca o painel');
 assert.equal(saved.data.editor.composicao.widgets['distribuicao-acertos'].parent,null);assert.equal(saved.data.editor.composicao.widgets['distribuicao-acertos'].fixo,true);assert.equal(saved.data.editor.composicao.widgets.devolutivas.active,false);assert.equal(saved.data.editor.composicao.widgets.participacao.h,10);
 assert.equal(await page.locator('[data-editor-leitura]').getByText('Painel vazio pessoal',{exact:true}).count(),0,'Contêiner vazio não ocupa leitura');
 await page.reload();await organize();assert.equal(await page.getByRole('button',{name:'Painel vazio pessoal',exact:true}).count(),1,'Vazio persiste no editor');
 await page.getByRole('button',{name:'Cancelar',exact:true}).click();assert.equal(writes.length,1);
 await page.setViewportSize({width:390,height:844});await page.locator('[data-editor-leitura]>.arvore-mobile').waitFor({state:'attached'});assert.equal(await page.locator('[data-editor-organizar]').isVisible(),false);assert.equal(await page.locator('[data-editor-leitura]>.arvore-mobile').count(),1);assert.equal(await page.locator('.rodape-app').isVisible(),false);assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);assert.equal(writes.length,1,'Mobile não grava');
 await page.setViewportSize({width:1440,height:1000});await organize();await page.getByRole('tab',{name:'Ajustes finos',exact:true}).click();
 await page.locator('[data-no-mover="participacao"]').press('Shift+ArrowDown');await page.setViewportSize({width:390,height:844});assert.equal(await page.locator('[data-editor-suspenso]').isVisible(),true);await page.setViewportSize({width:1440,height:1000});assert.equal(await page.locator('[gs-id="participacao"]').getAttribute('gs-h'),'11');
 await page.getByRole('button',{name:'Cancelar',exact:true}).click();assert.equal(await page.locator('[gs-id="participacao"]').getAttribute('gs-h'),'10');
 await organize();await page.getByRole('button',{name:'Restaurar padrão',exact:true}).click();assert.equal(await page.getByRole('dialog',{name:'Restaurar padrão do perfil?',exact:true}).isVisible(),true);await page.getByRole('button',{name:'Confirmar',exact:true}).click();conflict=true;await save();
 await page.getByRole('button',{name:'Consultar estado salvo',exact:true}).waitFor({state:'visible'});assert.equal(writes.length,2,'409 não reexecuta PUT');assert.equal(await page.getByRole('button',{name:'Salvar disposição',exact:true}).isDisabled(),true);
 assert.equal(await page.locator('[data-editor-aviso]').isVisible(),true,'Conflito continua visível junto dos controles');assert.match(await page.locator('[data-editor-aviso]').innerText(),/rascunho/i);
 assert.deepEqual(errors,[]);assert.equal(Object.keys(saved.data.editor.composicao.widgets).length,initial.data.editor.catalogo.widgets.length,'Sem excluir/duplicar widgets');
 console.log('Editor real: hierarquia, avulso, título escapado, pin, teclado, visibilidade, SaveReload, padrão/Cancelar, viewport, mobile e409 aprovados.');
}finally{await browser.close();}
