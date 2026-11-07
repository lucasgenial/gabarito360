import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
// public/js pertence a package type=module; executar o ramo CommonJS da UMD explicitamente.
const source=fs.readFileSync(new URL('../../apps/web/public/js/painel-geometria.js',import.meta.url),'utf8');
const commonjs={exports:{}};
vm.runInThisContext('(function(module){'+source+'\n})')(commonjs);
const geometry=commonjs.exports;
const widget=(id,section='analises',extra={})=>({id,section,minW:2,maxW:12,minH:2,maxH:20,defaultW:4,defaultH:3,...extra});
const item=(id,x,y,w=4,h=3,section='analises')=>({id,section,x,y,w,h});
const catalog=[widget('a'),widget('b'),widget('c'),widget('outra','agenda')];
const initial=[item('a',0,0),item('b',4,0),item('c',8,0),item('outra',0,0,4,3,'agenda')];
const clone=value=>structuredClone(value);
function frozen(value){Object.freeze(value);if(value&&typeof value==='object')Object.values(value).forEach(v=>{if(v&&typeof v==='object')frozen(v);});return value;}
function rejected(result,before,error){assert.equal(result.ok,false);assert.equal(result.error,error);assert.deepEqual(result.layout,before);assert.deepEqual(result.changed,[]);}

test('UMD publica o mesmo contrato no navegador sem DOM',()=>{
 const context={};vm.runInNewContext(fs.readFileSync(new URL('../../apps/web/public/js/painel-geometria.js',import.meta.url),'utf8'),context);
 assert.equal(context.G360PainelGeometria.columns,12);assert.equal(typeof context.G360PainelGeometria.resize,'function');
});
test('movimento livre preserva vizinhos, tamanho, ordem e entradas congeladas',()=>{
 const source=frozen(clone(initial)),snapshot=clone(source),cfg=frozen(clone(catalog));
 const r=geometry.move(source,cfg,'a',1,5);assert.equal(r.ok,true);assert.deepEqual(r.changed,['a']);
 assert.deepEqual(r.layout[0],item('a',1,5));assert.deepEqual(r.layout.slice(1),source.slice(1));assert.deepEqual(source,snapshot);
 assert.deepEqual(geometry.move(r.layout,cfg,'a',1,5).changed,[]);
});
test('swap explícito move somente dois e mantém a terceira posição',()=>{
 const r=geometry.move(initial,catalog,'a',4,0,'b');assert.equal(r.ok,true);
 assert.deepEqual(r.layout,[item('a',4,0),item('b',0,0),initial[2],initial[3]]);assert.deepEqual(r.changed,['a','b']);
 rejected(geometry.move(initial,catalog,'a',4,0),initial,'collision');
 rejected(geometry.move(initial,catalog,'a',5,0,'b'),initial,'invalid_target');
 rejected(geometry.move(initial,catalog,'a',0,0,'a'),initial,'invalid_target');
});
test('swap entre tamanhos diferentes conserva dimensões quando os dois cabem',()=>{
 const source=[item('a',0,0,6,4),item('b',0,5,3,2),item('c',8,0,4,3)];
 const r=geometry.move(source,catalog,'a',0,5,'b');assert.equal(r.ok,true);
 assert.deepEqual(r.layout,[item('a',0,5,6,4),item('b',0,0,3,2),source[2]]);
});
test('swap com tamanho diferente não empurra vizinho e falha atomico',()=>{
 const source=[item('a',0,0,8,3),item('b',8,0,4,3),item('c',0,5,4,3)];
 rejected(geometry.move(source,catalog,'a',8,0,'b'),source,'out_of_bounds');
 const source2=[item('a',0,0,6,4),item('b',6,0,2,2),item('c',8,0,4,3)];
 rejected(geometry.move(source2,catalog,'a',6,0,'b'),source2,'collision');
});
test('seções são isoladas e não se pode mudar seção pelo layout salvo',()=>{
 rejected(geometry.move(initial,catalog,'a',0,0,'outra'),initial,'cross_section');
 const edited=clone(initial);edited[0].section='agenda';assert.equal(geometry.validate(edited,catalog).error,'cross_section');
 assert.equal(geometry.validate(initial,catalog).ok,true); // mesmas coordenadas em seções distintas são válidas
});
test('resize muda somente área do alvo e rejeita colisões, frações e limites',()=>{
 const source=[item('a',0,0),item('b',8,0),item('c',0,7)];
 const r=geometry.resize(source,catalog,'a',6,5);assert.equal(r.ok,true);assert.deepEqual(r.layout,[item('a',0,0,6,5),source[1],source[2]]);
 rejected(geometry.resize(source,catalog,'a',10,4),source,'collision');
 for(const [w,h] of [[1,3],[13,3],[4,1],[4,21],[4.5,3],[true,3],[NaN,3],[4,Infinity]])rejected(geometry.resize(source,catalog,'a',w,h),source,'out_of_bounds');
 const small=[widget('a','analises',{maxH:6})];rejected(geometry.resize([source[0]],small,'a',4,7),[source[0]],'out_of_bounds');
});
test('limites da seção e coordenadas negativas não são arredondados',()=>{
 const opts={maxRows:30,sectionRows:{analises:20}};
 for(const [x,y] of [[-1,0],[12,0],[0,-1],[0,18],[0.5,0],[NaN,0]])rejected(geometry.move(initial,catalog,'a',x,y,null,opts),initial,'out_of_bounds');
});
test('novo card ocupa o primeiro buraco livre, sem compactação ou ordenação',()=>{
 const source=[item('c',8,0),item('a',0,4)],cfg=[...catalog,widget('novo')];
 const r=geometry.insert(source,cfg,'novo');assert.equal(r.ok,true);assert.deepEqual(r.layout,[...source,item('novo',0,0)]);
 rejected(geometry.insert(initial,catalog,'nao-autorizado'),initial,'unauthorized_widget');
 rejected(geometry.insert(initial,catalog,'a'),initial,'duplicate_widget');
 const full=[item('a',0,0,12,20)],tiny=[widget('a'),widget('novo')];
 rejected(geometry.insert(full,tiny,'novo',{maxRows:20}),full,'no_space');
});
test('remover acesso preserva buracos e jamais reintroduz ID revogado',()=>{
 const cfg=catalog.filter(w=>w.id!=='b'),r=geometry.removeUnauthorized(initial,cfg);assert.equal(r.ok,true);
 assert.deepEqual(r.layout,[initial[0],initial[2],initial[3]]);assert.deepEqual(r.changed,['b']);
});
test('reconciliação preserva posições e dimensões, adiciona autorizados sem reflow',()=>{
 const cfg=[...catalog.filter(w=>w.id!=='b'),widget('novo')];
 const r=geometry.reconcile(initial,cfg,[]);assert.equal(r.ok,true);
 assert.deepEqual(r.layout,[initial[0],initial[2],initial[3],item('novo',4,0)]);
 const changedState=cfg.map(w=>({...w,estado:'funcional'}));
 assert.deepEqual(geometry.reconcile(r.layout,changedState,[]).layout,r.layout);
 assert.deepEqual(geometry.reconcile(null,catalog,initial).layout,initial);
});
test('salvo inválido não mescla um subconjunto nem usa defaults silenciosamente',()=>{
 const corrupted=clone(initial);corrupted[1].x=1;
 rejected(geometry.reconcile(corrupted,catalog,initial),corrupted,'collision');
 const full=[item('a',0,0,12,20)],cfg=[widget('a'),widget('b')];
 rejected(geometry.reconcile(full,cfg,[],{maxRows:20}),full,'no_space');
});
test('layouts adversariais são recusados sem normalização silenciosa',()=>{
 const invalid=[null,{},'[]',false,[...initial,initial[0]],initial.map((i,n)=>n?i:{...i,x:'0'}),
  initial.map((i,n)=>n?i:{...i,w:0}),initial.map((i,n)=>n?i:{...i,y:Number.MAX_SAFE_INTEGER}),
  initial.map((i,n)=>n?i:{...i,id:'__proto__'}),initial.map((i,n)=>n?i:{...i,extra:'conteudo'})];
 for(const value of invalid){const r=geometry.validate(value,catalog);assert.equal(r.ok,false);assert.deepEqual(r.layout,value);}
 assert.equal(geometry.validate(initial,[...catalog,catalog[0]]).error,'invalid_catalog');
 assert.equal(geometry.validate(initial,catalog,{maxRows:Infinity}).error,'invalid_catalog');
 assert.equal(geometry.validate(initial,catalog,{sectionRows:{analises:0}}).error,'invalid_catalog');
 for(const options of [false,null,12,{maxRows:513},{sectionRows:[]}])assert.equal(geometry.validate(initial,catalog,options).error,'invalid_catalog');
 assert.equal(geometry.resize(initial,catalog,'ausente',4,3).error,'unknown_widget');
 assert.equal(geometry.move(initial,catalog,'ausente',0,0).error,'unknown_widget');
 assert.equal(geometry.validate(Array(257).fill(initial[0]),catalog).error,'invalid_layout');
});
test('resultado não compartilha objetos do layout original, não há estado responsivo ou global',()=>{
 const source=clone(initial),r=geometry.validate(source,catalog);r.layout[0].x=3;assert.equal(source[0].x,0);
 const again=geometry.validate(initial,catalog);assert.deepEqual(again.layout,initial);
 assert.equal('mobile' in geometry,false);assert.equal('save' in geometry,false);assert.equal('compact' in geometry,false);
});
