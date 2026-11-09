import test from 'node:test';import assert from 'node:assert/strict';import{readFileSync}from'node:fs';
import{projetarLeitura}from'../../apps/web/resources/js/editor-paineis/leitura.mjs';
import{criarFeedback}from'../../apps/web/resources/js/editor-paineis/feedback.mjs';
const source=readFileSync(new URL('../../apps/web/resources/js/editor-paineis.js',import.meta.url),'utf8');
const extract=(start,end)=>source.slice(source.indexOf(start),source.indexOf(end));
test('JSON tardio não repopula o editor depois de contexto invalidado',async()=>{
 let finish,entered=false;const response={ok:true,status:200,json:()=>{entered=true;return new Promise(r=>finish=r);}};
 const scope=new Function('window',`const requests=new Set(),descriptorHeaders={},cfg={isConnected:true},life=new AbortController();let epoch=0,invalid=false,etag=null;${extract(' async function request(',' async function revalidate(')}return{request,invalidate(){epoch++;invalid=true;},pending(){return requests.size;}};`)({G360Requisicao:async()=>response});
 const pending=scope.request({url:'/app/recurso',referencia:'ficticia'});while(!entered)await Promise.resolve();scope.invalidate();finish({data:{versao:999}});await assert.rejects(pending,{name:'AbortError'});assert.equal(scope.pending(),0);
});
test('revalidação tardia não reduz versão; mesma versão preserva rascunho; versão nova bloqueia sem descartar',()=>{
 const run=new Function('incoming',`let state={rascunho:'preservar'},entry={},standard={},context={scope:'contexto'},revision=1,catalogKey='catalogo',editing=true,dirty=true,uncertain=false,invalid=false,etag='',presentation='tabela',savedPresentation='',lastCheck=0;const prepare=x=>x,clone=structuredClone,capturarContinuidade=()=>null,root={},registry={area:{querySelector:()=>null}},controls=()=>{},render=()=>{},say=()=>{},invalidate=()=>{invalid=true;};${extract(' function adopt(',' async function request(')}adopt({context:{scope:'contexto'},state:{remoto:true},standard:{},revision:incoming,etag:'novo',catalogKey:'catalogo',presentation:'tabela'});return {state,dirty,editing,uncertain};`);
 for(const version of [0,1,2]){const s=run(version);assert.equal(s.state.rascunho,'preservar');assert.equal(s.dirty,true);assert.equal(s.editing,true);assert.equal(s.uncertain,version===2);}
});
test('ack2xx inválido não encerra edição nem repete PUT',async()=>{
 const run=new Function(`let state={schema:3,context:{}},context={},revision=0,catalogKey='ficticio',presentation='tabela',editing=true,preview=false,busy=false,uncertain=false,invalid=false,requests=0;const clone=structuredClone,eligible=()=>true,validar=()=>{},controls=()=>{},request=async()=>{requests++;return{malformado:true};},prepare=()=>{throw Error('Recibo inválido');},say=()=>{},config={salvar:{}},crypto={randomUUID:()=> 'ficticia'};${extract(' async function save(',' function confirmAction(')}return save().then(()=>({editing,uncertain,busy,requests}));`);
 assert.deepEqual(await run(),{editing:true,uncertain:true,busy:false,requests:1});
});
test('vazios compactam apenas leitura e conservam a composição canônica',()=>{
 const b=JSON.parse(readFileSync(new URL('../../apps/web/tests/Fixtures/editor-turma.json',import.meta.url),'utf8')),e=b.data.editor,context={scope:e.contexto,catalogo:e.catalogo},state={...e.composicao,context:{scope:e.contexto}},before=structuredClone(state);
 const out=projetarLeitura(state,context,new Map([['distribuicao-acertos',3],['situacoes-registradas',3]]));assert.deepEqual(state,before);assert.equal(state.widgets['distribuicao-acertos'].h,9);assert.equal(out.state.widgets['distribuicao-acertos'].h,3);assert.equal(context.catalogo.widgets.find(w=>w.id==='distribuicao-acertos').minH,8);
});
test('ACK confirmado gera um toast de sucesso somente depois da adoção',async()=>{
 const run=new Function(`let state={schema:3,context:{}},context={},revision=0,catalogKey='ficticio',presentation='tabela',editing=true,preview=false,busy=false,uncertain=false,invalid=false,requests=0;const calls=[],clone=structuredClone,eligible=()=>true,validar=()=>{},controls=()=>{},request=async()=>{requests++;return{};},prepare=()=>calls.push('validado'),adopt=()=>{calls.push('adotado');return true;},feedback={notificar:(m,t)=>calls.push([m,t])},button=()=>null,say=()=>{},focus=()=>{},channel=null,config={salvar:{}},crypto={randomUUID:()=> 'ficticia'};${extract(' async function save(',' function confirmAction(')}return save().then(()=>({calls,requests,editing,busy}));`);
 assert.deepEqual(await run(),{calls:['validado','adotado',['Disposição salva.','sucesso']],requests:1,editing:false,busy:false});
});
function elemento(){return {textContent:'',hidden:true,className:'',attrs:{},setAttribute(k,v){this.attrs[k]=v;}};}
test('toast não duplica texto no status; movimento não dispara toast; erros continuam contextuais',()=>{
 const status=elemento(),aviso=elemento(),calls=[];const f=criarFeedback({status,aviso,notificacoes:()=>({sucesso:(...args)=>calls.push(args)})});
 f.notificar('Disposição salva.','sucesso');assert.equal(calls.length,1);assert.equal(status.textContent,'');assert.equal(aviso.hidden,true);
 f.dizer('Item ajustado.');assert.equal(status.textContent,'Item ajustado.');assert.equal(calls.length,1);assert.equal(aviso.hidden,true);
 f.dizer('Rascunho preservado. Consulte o estado salvo.','atencao');assert.equal(aviso.hidden,false);assert.equal(aviso.attrs.role,'status');assert.equal(status.textContent,'');assert.equal(calls.length,1);
 f.dizer('Não foi possível salvar.','erro');assert.equal(aviso.attrs.role,'alert');assert.equal(aviso.attrs['aria-live'],'assertive');f.limpar();assert.equal(aviso.hidden,true);
});
test('biblioteca indisponível conserva confirmação contextual acessível',()=>{
 const status=elemento(),aviso=elemento(),f=criarFeedback({status,aviso,notificacoes:()=>null});f.notificar('Disposição salva.','sucesso');assert.equal(aviso.hidden,false);assert.equal(aviso.textContent,'Disposição salva.');assert.equal(aviso.className,'g-aviso sucesso');assert.equal(aviso.attrs.role,'status');assert.equal(status.textContent,'');
});
test('falha do toast não sobe para o fluxo de gravação já confirmado',()=>{
 const status=elemento(),aviso=elemento(),fechados=[],f=criarFeedback({status,aviso,notificacoes:()=>({sucesso(){throw Error('Falha na apresentação');},fechar:id=>fechados.push(id)})});assert.doesNotThrow(()=>f.notificar('Disposição salva.','sucesso'));assert.equal(aviso.textContent,'Disposição salva.');assert.equal(aviso.hidden,false);assert.deepEqual(fechados,['editor-paineis-resultado']);
});
test('ponte de uma página desmontada não descarta a confirmação',()=>{
 const status=elemento(),aviso=elemento(),f=criarFeedback({status,aviso,notificacoes:()=>({sucesso:()=>undefined})});f.notificar('Disposição salva.','sucesso');assert.equal(aviso.textContent,'Disposição salva.');assert.equal(aviso.hidden,false);
});
