import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
import {build} from '../paineis-grid/node_modules/esbuild/lib/main.js';

// O bundle resolve os imports ESM sem extensão da biblioteca, sem trocar sua engine por mocks.
const dependency=new URL('../paineis-grid/node_modules/',import.meta.url);
const packageInfo=JSON.parse(await readFile(new URL('gridstack/package.json',dependency),'utf8'));
assert.equal(packageInfo.version,'14.1.0');
const compiled=await build({stdin:{contents:'export * from '+JSON.stringify(fileURLToPath(new URL('../../apps/web/resources/js/painel-geometria.js',import.meta.url)))+';export {GridStackEngine} from "gridstack/dist/gridstack-engine";',resolveDir:fileURLToPath(new URL('../paineis-grid/',import.meta.url))},bundle:true,write:false,format:'esm',platform:'node',nodePaths:[fileURLToPath(dependency)]});
const {GridStackEngine,project,validateLayout,nativeEngine,captureLayout,prepareLayout,autoLayout,keyboardChange,keyboardLateralAction,distributeWidths,fillLateral,lateralAction,responsiveMinWidths,lateralFeedback}=await import('data:text/javascript;base64,'+Buffer.from(compiled.outputFiles[0].contents).toString('base64'));
const cap=(section='analises',extra={})=>({section,minW:2,maxW:12,minH:1,maxH:30,defaultW:6,defaultH:4,...extra});
const caps=new Map(['a','b','c'].map(id=>[id,cap()]));
const n=(id,x=0,y=0,w=6,h=4,extra={})=>({id,section:'analises',x,y,w,h,...extra});
const sorted=items=>items.map(project).sort((a,b)=>a.id.localeCompare(b.id));
const positions=items=>sorted(items).map(n=>[n.id,n.x,n.y,n.w,n.h]);
function update(engine,base,limits,id,patch){
 const node=engine.nodes.find(n=>n.id===id);engine.cleanNodes().beginUpdate(node);
 const accepted=engine.moveNodeCheck(node,patch);engine.endUpdate();engine.compact('compact');
 return {accepted,layout:captureLayout(engine,base,limits,{resizeId:('w'in patch||'h'in patch)?id:null})};
}

test('usa GridStackEngine14.1 real, modo compact e encaixe abaixo de vizinho baixo sem linhas',()=>{
 const input=[n('a',0,0,6,10),n('b',6,0,6,4),n('c',6,10,6,3)],before=structuredClone(input),engine=nativeEngine(input,caps);
 assert.ok(engine instanceof GridStackEngine);assert.equal(engine.mode,'compact');
 const result=captureLayout(engine,input,caps);assert.deepEqual(positions(result),[['a',0,0,6,10],['b',6,0,6,4],['c',6,4,6,3]]);assert.deepEqual(input,before);
});
test('mapa legado4+4 preenche largura em memória sem igualar alturas',()=>{
 const input=[n('a',0,8,4,3),n('b',4,8,4,7)],result=prepareLayout(input,input,capsSubset(['a','b']));
 assert.deepEqual(positions(result),[['a',0,0,6,3],['b',6,0,6,7]]);
});
function capsSubset(ids){return new Map(ids.map(id=>[id,caps.get(id)]));}
test('legado seis/sete campos e altura_livre não reativam altura uniforme',()=>{
 const input=[n('a',0,10,6,10,{altura_desejada:3,altura_livre:false}),n('b',6,10,6,4)];
 const result=prepareLayout(input,input,capsSubset(['a','b']));assert.equal(result.find(n=>n.id==='a').h,10);assert.equal(result.find(n=>n.id==='b').h,4);
 assert.equal(result.find(n=>n.id==='a').altura_desejada,3);assert.equal(result.find(n=>n.id==='a').altura_livre,false);assert.ok(!Object.hasOwn(result.find(n=>n.id==='b'),'altura_desejada'));
});
test('mapa bruto não é alterado, snapshot inicial compacto é idempotente no SaveReload e Cancel',()=>{
 const input=[n('a',0,20,6,8),n('b',6,20,6,3),n('c',0,35,4,4)],raw=structuredClone(input);
 const saved=prepareLayout(input,input,caps),snapshot=structuredClone(saved);
 const engine=nativeEngine(saved,caps);const changed=update(engine,saved,caps,'a',{h:12}).layout;assert.notDeepEqual(positions(changed),positions(saved));
 const canceled=prepareLayout(snapshot,snapshot,caps);assert.deepEqual(sorted(canceled),sorted(saved));
 const reloaded=prepareLayout(saved,input,caps);assert.deepEqual(sorted(reloaded),sorted(saved));assert.deepEqual(input,raw);
});
test('locked nativo conserva x/y/w/h e desired durante compactação e resize dos vizinhos',()=>{
 const input=[n('a',4,8,4,12,{fixo:true,altura_desejada:5}),n('b',0,0,4,4),n('c',8,0,4,4)];
 const engine=nativeEngine(input,caps),baseline=captureLayout(engine,input,caps),anchor=baseline.find(n=>n.id==='a');
 const result=update(engine,baseline,caps,'b',{h:15}).layout;assert.deepEqual(result.find(n=>n.id==='a'),anchor);assert.deepEqual([anchor.x,anchor.y,anchor.w,anchor.h],[4,8,4,12]);assert.equal(anchor.altura_desejada,5);
 assert.equal(keyboardChange(anchor,'ArrowRight',false,caps.get('a')),null);assert.equal(keyboardChange(anchor,'ArrowDown',true,caps.get('a')),null);
});
test('proteção pós-engine rejeita movimento direto de pin mesmo que API nativa o aceite',()=>{
 const input=[n('a',0,0,4,4,{fixo:true}),n('b',4,0,4,4)],engine=nativeEngine(input,caps),snapshot=captureLayout(engine,input,caps);
 const node=engine.nodes.find(n=>n.id==='a');engine.moveNodeCheck(node,{x:8});
 assert.throws(()=>captureLayout(engine,snapshot,caps));assert.deepEqual(sorted(captureLayout(nativeEngine(snapshot,caps),snapshot,caps)),sorted(snapshot));
});
test('desfixar libera compactação sem transformar intenção legada em novo tamanho',()=>{
 const limits=capsSubset(['a','b']),input=[n('a',4,15,4,10,{fixo:true,altura_desejada:3}),n('b',0,0,4,4)];
 const fixed=prepareLayout(input,input,limits);const unpinned=fixed.map(n=>n.id==='a'?{...n,fixo:false}:n),result=prepareLayout(unpinned,unpinned,limits);
 assert.equal(result.find(n=>n.id==='a').y,4);assert.equal(result.find(n=>n.id==='a').h,10);assert.equal(result.find(n=>n.id==='a').altura_desejada,3);
});
test('resize atualiza desired=h só no alvo, sem equalizar nem perder opcionais dos demais',()=>{
 const input=[n('a',0,0,6,10,{altura_desejada:3,altura_livre:true}),n('b',6,0,6,4,{altura_desejada:2,fixo:false})],engine=nativeEngine(input,caps),before=captureLayout(engine,input,caps);
 const result=update(engine,before,caps,'a',{h:5}).layout;assert.equal(result.find(n=>n.id==='a').h,5);assert.equal(result.find(n=>n.id==='a').altura_desejada,5);assert.equal(result.find(n=>n.id==='b').h,4);assert.equal(result.find(n=>n.id==='b').altura_desejada,2);assert.equal(result.find(n=>n.id==='b').fixo,false);
});
test('resize apenas horizontal mantém altura e atualiza intenção; no-op não injeta campos',()=>{
 const limits=capsSubset(['a']),input=[n('a',0,0,4,8,{altura_desejada:3})];const engine=nativeEngine(input,limits),before=captureLayout(engine,input,limits);
 const result=update(engine,before,limits,'a',{w:5}).layout;assert.equal(result[0].w,5);assert.equal(result[0].h,8);assert.equal(result[0].altura_desejada,8);
 const legacy=[n('a',0,0,4,8)],unchanged=update(nativeEngine(legacy,limits),legacy,limits,'a',{h:8}).layout;assert.ok(!Object.hasOwn(unchanged[0],'altura_desejada'));
});
test('setas enviam coordenadas e Shift dimensões para o método nativo com limites do catálogo',()=>{
 const base=n('a',3,5,4,8);assert.deepEqual(keyboardChange(base,'ArrowRight',false,caps.get('a')),{x:4});assert.deepEqual(keyboardChange(base,'ArrowUp',false,caps.get('a')),{y:4});assert.deepEqual(keyboardChange(base,'ArrowLeft',true,caps.get('a')),{w:3});assert.deepEqual(keyboardChange(base,'ArrowDown',true,caps.get('a')),{h:9});
 assert.deepEqual(keyboardChange(n('a',0,0,2,1),'ArrowLeft',true,caps.get('a')),{w:2});assert.deepEqual(keyboardChange(n('a',0,0,12,30),'ArrowDown',true,caps.get('a')),{h:30});
 const limits=capsSubset(['a','b']),input=[n('a',0,0,6,4),n('b',6,0,6,4)],engine=nativeEngine(input,limits);const result=update(engine,input,limits,'b',keyboardChange(input[1],'ArrowLeft',false,limits.get('b'))).layout;validateLayout(result,limits,{complete:true});assert.equal(engine.mode,'compact');
});
test('maxRow200 nativo recusa expansão que exigiria201 e preserva o snapshot inteiro',()=>{
 const limits=new Map(),input=[];for(let i=0;i<10;i++){const id='n'+i;limits.set(id,cap());input.push(n(id,0,i*20,12,20));}
 const engine=nativeEngine(input,limits),saved=captureLayout(engine,input,limits),result=update(engine,saved,limits,'n0',{h:21});assert.equal(result.accepted,false);assert.deepEqual(sorted(result.layout),sorted(saved));
});
test('saída inválida da engine rejeita atomicamente sem mutar o modelo aceito',()=>{
 const input=[n('a',0,0,6,4),n('b',6,0,6,4)],snapshot=structuredClone(input),engine=nativeEngine(input,caps);engine.nodes.find(n=>n.id==='a').y=201;
 assert.throws(()=>captureLayout(engine,input,caps));assert.deepEqual(input,snapshot);assert.deepEqual(positions(captureLayout(nativeEngine(input,caps),input,caps)),positions(snapshot));
});
test('completa IDs autorizados faltantes por addNode nativo, mantendo pins e dimensões',()=>{
 const standard=[n('a',0,0,4,4),n('b',4,0,4,8),n('c',8,0,4,3)],user=[n('a',4,10,4,4,{fixo:true})],raw=structuredClone(user);
 const result=prepareLayout(user,standard,caps);assert.deepEqual(new Set(result.map(n=>n.id)),new Set(['a','b','c']));assert.deepEqual(result.find(n=>n.id==='a'),user[0]);assert.equal(result.find(n=>n.id==='b').h,8);assert.equal(result.find(n=>n.id==='c').w,6);assert.deepEqual(user,raw);validateLayout(result,caps,{complete:true});
});
test('fallback inicial também usa autoPosition nativo e nunca força altura comum',()=>{
 const result=autoLayout([n('a',0,0,6,8),n('b',0,0,6,3),n('c',0,0,6,2)],caps);assert.deepEqual(positions(result),[['a',0,0,6,8],['b',6,0,6,3],['c',6,3,6,2]]);
});
test('seções são independentes; não há troca nem colisão cruzando a seção autorizada',()=>{
 const limits=new Map([['a',cap('analises')],['b',cap('planejamento')]]),standard=[n('a'),{...n('b'),section:'planejamento'}];
 const result=prepareLayout(standard,standard,limits);assert.equal(result.length,2);assert.equal(result[0].y,0);assert.equal(result[1].y,0);
 assert.throws(()=>nativeEngine(standard,limits));assert.throws(()=>validateLayout([{...n('a'),section:'planejamento'}],limits));
});
test('mapa inválido não é parcialmente aplicado: tipo, limites, IDs, colisões, bool e enum',()=>{
 const bad=[[n('a'),n('a')],[n('a'),n('b',3,0)],[n('a',-1)],[n('a',0,199)],[n('a',0,0,1)],[{...n('a'),x:'0'}],[{...n('a'),fixo:1}],[{...n('a'),altura_livre:0}],[{...n('a'),altura_desejada:5}],[{...n('a'),orientacao:'vertical'}],[n('unknown')]];
 for(const candidate of bad){const before=structuredClone(candidate);assert.throws(()=>prepareLayout(candidate,[n('a'),n('b',6,0),n('c',0,4)],caps));assert.deepEqual(candidate,before);}
});
test('limite64 e projeção de campos técnicos não permitem criação ou vazamento de conteúdo',()=>{
 const limits=new Map(),standard=[];for(let i=0;i<64;i++){const id='n'+i;limits.set(id,cap('analises',{minW:1}));standard.push(n(id,i%12,Math.floor(i/12),1,1));}
 const result=prepareLayout([],standard,limits);assert.equal(result.length,64);validateLayout(result,limits,{complete:true});assert.throws(()=>prepareLayout([...standard,n('extra')],standard,limits));
 const value=project({...n('a'),fixo:false,altura_livre:true,content:'<img>',el:{},token:'privado'});assert.deepEqual(value,{...n('a'),fixo:false,altura_livre:true});assert.ok(!Object.hasOwn(project(n('a')),'altura_desejada'));
});

const lateralCaps=new Map(['a','b','c','d'].map(id=>[id,cap('analises',{minW:3})]));
test('lateral:6+6 cresce7+5 sem expulsar irmão nem alterar alturas',()=>{
 const before=[n('a',0,0,6,10),n('b',6,0,6,4),n('c',0,10,12,9)],snapshot=structuredClone(before);
 const result=lateralAction(before,lateralCaps,'a',{type:'resize',w:7});
 assert.deepEqual(positions(result).slice(0,2),[['a',0,0,7,10],['b',7,0,5,4]]);assert.equal(result.find(n=>n.id==='c').y,10);assert.deepEqual(before,snapshot);
});
test('lateral:4+4+4 permite5+4+3 e6+3+3; reduzir redistribui de modo determinístico',()=>{
 const before=[n('a',0,0,4,9),n('b',4,0,4,7),n('c',8,0,4,11)];
 const five=lateralAction(before,lateralCaps,'a',{type:'resize',w:5}),six=lateralAction(before,lateralCaps,'a',{type:'resize',w:6});
 assert.deepEqual(five.map(n=>n.w),[5,4,3]);assert.deepEqual(six.map(n=>n.w),[6,3,3]);assert.deepEqual(six.map(n=>n.y),[0,0,0]);assert.deepEqual(six.map(n=>n.h),[9,7,11]);
 const smaller=lateralAction(before,lateralCaps,'a',{type:'resize',w:3});assert.deepEqual(smaller.map(n=>n.w),[3,5,4]);
 assert.deepEqual(fillLateral(five,lateralCaps),five);
});
test('lateral:preferência grande do ativo é limitada pelos mínimos, sem falso veto',()=>{
 const peers=[n('a',0,0,4,9),n('b',4,0,4,9),n('c',8,0,4,9)];
 assert.deepEqual([...distributeWidths(peers,12,lateralCaps,{activeId:'a',preferredWidth:12}).values()],[6,3,3]);
});
test('lateral:clamps de mínimos são resolvidos antes dos máximos em catálogos mistos',()=>{
 const mixed=new Map([['a',cap('analises',{minW:1,maxW:6})],['b',cap('analises',{minW:4})],['c',cap('analises',{minW:4})]]);
 assert.deepEqual([...distributeWidths([n('a',0,0,12),n('b',0,0,3),n('c',0,0,3)],12,mixed).entries()].sort(),[['a',4],['b',4],['c',4]]);
});
test('lateral:inserir entre dois usa snapshot e fica na faixa com ordem/alturas corretas',()=>{
 const before=[n('a',0,0,6,10),n('b',6,0,6,7),n('c',0,10,12,4)];
 const result=lateralAction(before,lateralCaps,'c',{type:'move',targetId:'a',place:'lateral',after:true});
 assert.deepEqual(result.map(n=>n.id),['a','c','b']);assert.deepEqual(result.map(n=>n.y),[0,0,0]);assert.deepEqual(result.map(n=>n.w),[3,6,3]);assert.deepEqual(result.map(n=>n.h),[10,4,7]);
});
test('lateral:nova faixa acima/abaixo e origem vazia desaparece sem igualar h',()=>{
 const before=[n('a',0,0,6,10),n('b',6,0,6,4),n('c',0,10,12,7)];
 const result=lateralAction(before,lateralCaps,'c',{type:'move',targetId:'a',place:'before-band'});
 assert.deepEqual(positions(result),[['a',0,7,6,10],['b',6,7,6,4],['c',0,0,12,7]]);
});
test('lateral:pin central particiona trechos; card alto não une faixas por transitividade',()=>{
 const before=[n('a',4,0,4,12,{fixo:true}),n('b',0,0,4,4),n('c',8,0,4,6),n('d',0,4,4,3)];
 const result=fillLateral(before,lateralCaps);assert.deepEqual(result.find(n=>n.id==='a'),before[0]);assert.equal(result.find(n=>n.id==='d').y,4);assert.equal(result.find(n=>n.id==='d').w,4);
 const resize=lateralAction(before,lateralCaps,'b',{type:'resize',w:9});assert.equal(resize.find(n=>n.id==='b').w,4);assert.deepEqual(resize.find(n=>n.id==='a'),before[0]);
});
test('lateral:mínimo real impossível veta inteiro e não usa piso do viewport no load',()=>{
 const before=[n('a',0,0,4,9),n('b',4,0,4,9),n('c',8,0,4,9)],snapshot=structuredClone(before),floor=new Map([['a',5],['b',5],['c',5]]);
 assert.throws(()=>lateralAction(before,lateralCaps,'a',{type:'resize',w:5},{minWidths:floor}),error=>error.message===lateralFeedback);
 assert.deepEqual(before,snapshot);assert.doesNotThrow(()=>fillLateral(before,lateralCaps));
});
test('lateral:piso efetivo usa grade e margem reais, em vez de largura da janela',()=>{
 const c=new Map([['distribuicao-acertos',cap('analises',{minW:3})],['devolutivas',cap('planejamento',{minW:3})],['agenda-pedagogica',cap('planejamento',{minW:3})]]);
 const large=responsiveMinWidths(c,1800,16),small=responsiveMinWidths(c,900,16);
 assert.equal(large.get('distribuicao-acertos'),3);assert.equal(small.get('distribuicao-acertos'),5);assert.equal(small.get('devolutivas'),4);assert.equal(small.get('agenda-pedagogica'),4);
});
test('lateral:reabrir e Cancel usam projeção inicial estável, preservam opcionais e outras seções',()=>{
 const limits=new Map([...lateralCaps,['e',cap('planejamento',{minW:3})]]),before=[n('a',0,0,4,9,{altura_desejada:3,altura_livre:false}),n('b',4,0,4,4),{...n('e',0,20,6,7),section:'planejamento'}];
 const initial=fillLateral(before,limits),changed=lateralAction(initial,limits,'a',{type:'resize',w:8});assert.equal(changed.find(n=>n.id==='a').altura_desejada,9);assert.deepEqual(changed.find(n=>n.id==='e'),initial.find(n=>n.id==='e'));
 assert.deepEqual(fillLateral(initial,limits),initial);assert.equal(initial.find(n=>n.id==='a').altura_desejada,3);assert.equal(initial.find(n=>n.id==='a').altura_livre,false);
});
test('lateral:prepare não deixa compactação global separar uma faixa salva ao preencher vão anterior',()=>{
 const limits=new Map(['a','b','c','d','e'].map(id=>[id,cap('analises',{minW:3})]));
 const input=[n('a',0,0,6,10),n('b',6,0,6,4),n('c',0,10,12,2),n('d',0,12,6,3),n('e',6,12,6,6)];
 const result=prepareLayout(input,input,limits);assert.deepEqual(positions(result),positions(input));
 assert.deepEqual(prepareLayout(result,input,limits),result);
});
test('lateral:ArrowDown de singleton ultrapassa faixa vizinha e ArrowUp retorna sem alterar alturas',()=>{
 const before=[n('a',0,0,12,9),n('b',0,9,8,10),n('c',8,9,4,9)],action=keyboardLateralAction(before,'a','ArrowDown');
 assert.deepEqual(action,{type:'move',targetId:'c',place:'after-band'});
 const down=lateralAction(before,lateralCaps,'a',action);assert.deepEqual(positions(down),[['a',0,10,12,9],['b',0,0,8,10],['c',8,0,4,9]]);
 const up=lateralAction(down,lateralCaps,'a',keyboardLateralAction(down,'a','ArrowUp'));assert.deepEqual(positions(up),positions(before));
});
test('lateral:duas faixas singleton respeitam limites e pins permanecem imóveis',()=>{
 const before=[n('a',0,0,12,4),n('b',0,4,12,6),n('c',4,30,4,8,{fixo:true})];
 assert.equal(keyboardLateralAction(before,'a','ArrowUp'),null);assert.equal(keyboardLateralAction(before,'c','ArrowDown'),null);
 const moved=lateralAction(before,lateralCaps,'a',keyboardLateralAction(before,'a','ArrowDown'));
 assert.equal(moved.find(n=>n.id==='a').y,6);assert.equal(moved.find(n=>n.id==='b').y,0);assert.deepEqual(moved.find(n=>n.id==='c'),before[2]);
 const singleton=[n('a',0,0,12,9)];assert.equal(keyboardLateralAction(singleton,'a','ArrowDown'),null);
});
