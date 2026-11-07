import test from 'node:test';import assert from 'node:assert/strict';import {normalizeRows,rowsOf,project} from '../../apps/web/resources/js/painel-linhas.js';
const cap=(extra={})=>({minW:2,maxW:12,minH:2,maxH:20,...extra});
const caps=new Map(['a','b','c'].map(id=>[id,cap()]));
const n=(id,x=0,y=0,w=6,h=4,wanted=h)=>({id,section:'analises',x,y,w,h,altura_desejada:wanted});
function assertRows(layout){let y=0;for(const row of rowsOf(layout)){assert.equal(row[0].y,y);assert.ok(row.reduce((s,n)=>s+n.w,0)<=12);assert.equal(new Set(row.map(n=>n.h)).size,1);assert.equal(row[0].h,Math.max(...row.map(n=>n.altura_desejada)));let x=0;for(const n of row){assert.equal(n.x,x);x+=n.w;}y+=row[0].h;}}
test('linhas permitem sobra sem esticar widgets e mantêm altura máxima e entradas intactas',()=>{const input=[[n('a',0,4,4,3),n('b',4,4,4,6)],[],[n('c',0,20,5,4)]],original=structuredClone(input);const r=normalizeRows(input,caps);assertRows(r);assert.deepEqual(r.map(n=>n.w),[4,4,5]);assert.equal(r[0].h,6);assert.equal(r[1].h,6);assert.equal(r[2].y,6);assert.deepEqual(input,original);});
test('resize de largura redistribui irmãos damesmalinha',()=>{const r=normalizeRows([[n('a',0,0,8),n('b',6,0,6)],[n('c',0,4,12)]],caps,'a');assertRows(r);assert.equal(r[0].w,8);assert.equal(r[1].w,4);assert.equal(r[2].w,12);});
test('mínimos incompatíveis fazemwrap conservandoidentidade/ordem e largura do excedente',()=>{const c=new Map(['a','b','c'].map(id=>[id,cap({minW:4})]));const r=normalizeRows([[n('a',0,0,6),n('b',4,0,4),n('c',8,0,4)]],c,'a');assertRows(r);assert.deepEqual(r.map(n=>n.id),['a','b','c']);assert.equal(r[2].y,4);assert.equal(r[2].w,4);});

test('resize com espaço livre mantém largura do irmão',()=>{const r=normalizeRows([[n('a',0,0,6),n('b',4,0,4)]],caps,'a');assertRows(r);assert.deepEqual(r.map(n=>[n.x,n.w]),[[0,6],[6,4]]);});
test('widget único estreito mantém largura após projeção e recarga',()=>{const r=normalizeRows([[n('a',0,0,6)]],caps);const loaded=normalizeRows(rowsOf(structuredClone(r.map(project))),caps);assertRows(loaded);assert.equal(loaded[0].w,6);});
test('par4+4 continua4+4 após projeção e recarga',()=>{const r=normalizeRows([[n('a',0,0,4),n('b',4,0,4)]],caps);const loaded=normalizeRows(rowsOf(structuredClone(r.map(project))),caps);assertRows(loaded);assert.deepEqual(loaded.map(n=>n.w),[4,4]);});
test('altura_desejada sobreviveprojeção/save e permitebaixarmaior apósrecarga',()=>{const r=normalizeRows([[n('a',0,0,6,8,8),n('b',6,0,6,8,4)]],caps);const restored=structuredClone(r.map(project));restored[0].altura_desejada=3;const lowered=normalizeRows(rowsOf(restored),caps);assertRows(lowered);assert.equal(lowered[0].h,4);assert.equal(lowered[1].h,4);assert.equal(lowered[0].altura_desejada,3);});
test('incompatibilidade min/maxaltura separalinhas semclipping',()=>{const c=new Map([['a',cap({minH:12,maxH:20})],['b',cap({minH:2,maxH:8})]]);const r=normalizeRows([[n('a',0,0,6,12),n('b',6,0,6,4)]],c);assertRows(r);assert.equal(r[1].y,12);assert.equal(r[1].h,4);});
test('projeção contém apenasgeometria e altura_desejada, semHTML',()=>{assert.deepEqual(Object.keys(project({...n('a'),content:'<img>',el:{},usuario_id:123})).sort(),['id','section','x','y','w','h','altura_desejada'].sort());});
test('mais de200linhas rejeita semmutarentrada',()=>{const input=Array.from({length:51},()=>[n('a',0,0,12,4)]);const original=structuredClone(input);assert.throws(()=>normalizeRows(input,caps));assert.deepEqual(input,original);});
test('mover o último membro elimina a linha de origem e recompõe a ocupação',()=>{const before=[[n('a',0,0,12,4)],[n('b',0,4,12,4)]];const moved=structuredClone(before);moved[0].push(moved[1].pop());const r=normalizeRows(moved,caps);assertRows(r);assert.equal(rowsOf(r).length,1);assert.deepEqual(r.map(n=>[n.id,n.x,n.y,n.w]),[['a',0,0,6],['b',6,0,6]]);});

function assertGeometry(layout,limits){
 assert.equal(new Set(layout.map(n=>n.id)).size,layout.length);
 for(const item of layout){const c=limits.get(item.id);for(const k of ['x','y','w','h','altura_desejada'])assert.ok(Number.isInteger(item[k]));assert.ok(item.x>=0&&item.y>=0&&item.x+item.w<=12&&item.y+item.h<=200);assert.ok(item.w>=c.minW&&item.w<=c.maxW&&item.h>=c.minH&&item.h<=c.maxH);assert.ok(item.altura_desejada<=item.h);if(item.altura_livre&&!item.fixo)assert.equal(item.h,item.altura_desejada);}
 for(let a=0;a<layout.length;a++)for(let b=a+1;b<layout.length;b++){const x=layout[a],y=layout[b];assert.ok(x.x+x.w<=y.x||y.x+y.w<=x.x||x.y+x.h<=y.y||y.y+y.h<=x.y,`${x.id} colide com ${y.id}`);}
}
const item=(result,id)=>result.find(n=>n.id===id);
const fixed=(id,x,y,w,h)=>({...n(id,x,y,w,h),fixo:true});
const free=(id,x=0,y=0,w=6,h=4)=>({...n(id,x,y,w,h),altura_livre:true});

test('projeção preserva somente os opcionais presentes, inclusive false, sem injetar defaults',()=>{
 const raw={...n('a'),fixo:false,altura_livre:true,orientacao:'vertical',html:'<b>',data:{},usuario_id:5};
 assert.deepEqual(project(raw),{...n('a'),fixo:false,altura_livre:true,orientacao:'vertical'});
 assert.ok(!Object.hasOwn(project(n('a')),'fixo'));assert.ok(!Object.hasOwn(project(n('a')),'altura_livre'));assert.ok(!Object.hasOwn(project(n('a')),'orientacao'));
});
test('legado seis campos mantém a composição anterior e opcionais false não alteram a geometria',()=>{
 const a=n('a',0,0,4,8),b=n('b',4,0,4,10);delete a.altura_desejada;delete b.altura_desejada;
 const legacy=normalizeRows([[a,b]],caps);
 const tagged=normalizeRows([[{...a,fixo:false,altura_livre:false,orientacao:'horizontal'},{...b,fixo:false}]],caps);
 assert.deepEqual(legacy.map(({fixo,altura_livre,orientacao,...rest})=>rest),tagged.map(({fixo,altura_livre,orientacao,...rest})=>rest));
 assert.deepEqual(legacy.map(n=>[n.x,n.y,n.w,n.h,n.altura_desejada]),[[0,0,4,10,8],[4,0,4,10,10]]);
});
test('altura livre não impõe sua altura aos uniformes, que mantêm o máximo do próprio grupo',()=>{
 const result=normalizeRows([[free('a',0,0,4,12),n('b',4,0,4,8),n('c',8,0,4,10)]],caps);
 assertGeometry(result,caps);assert.deepEqual(result.map(n=>[n.id,n.w,n.h]),[['a',4,12],['b',4,10],['c',4,10]]);
 const short=normalizeRows([[free('a',0,0,6,8),n('b',6,0,6,12)]],caps);
 assertGeometry(short,caps);assert.equal(item(short,'a').h,8);assert.equal(item(short,'b').h,12);
});
test('dois cards livres mantêm alturas independentes e a próxima linha começa depois do maior',()=>{
 const result=normalizeRows([[free('a',0,0,4,3),free('b',4,0,4,7)],[n('c',0,7,6,4)]],caps);
 assertGeometry(result,caps);assert.deepEqual(result.map(n=>[n.id,n.y,n.w,n.h]),[['a',0,4,3],['b',0,4,7],['c',7,6,4]]);
});
test('desligar altura livre volta ao máximo uniforme sem perder intenções individuais',()=>{
 const result=normalizeRows([[free('a',0,0,6,3),n('b',6,0,6,8)]],caps);
 const loaded=result.map(project);item(loaded,'a').altura_livre=false;
 const uniform=normalizeRows(rowsOf(loaded),caps);assertGeometry(uniform,caps);assert.equal(item(uniform,'a').h,8);assert.equal(item(uniform,'a').altura_desejada,3);
 item(uniform,'b').altura_desejada=4;const reduced=normalizeRows(rowsOf(uniform),caps);assert.equal(item(reduced,'a').h,4);assert.equal(item(reduced,'b').h,4);
});
test('âncora central mantém a caixa enquanto móveis ocupam faixas contíguas livres',()=>{
 const anchor=fixed('a',4,8,4,10);const result=normalizeRows([[anchor,n('b',0,8,4,4),n('c',8,8,4,6)]],caps);
 assertGeometry(result,caps);assert.deepEqual(item(result,'a'),anchor);assert.deepEqual([item(result,'b').x,item(result,'c').x],[0,8]);assert.equal(item(result,'b').y,8);assert.equal(item(result,'b').h,6);
});
test('compação acima de uma âncora nunca muda seu y nem tenta eliminar espaço necessário',()=>{
 const anchor=fixed('c',0,30,4,10);const result=normalizeRows([[n('a',0,0,4,4)],[n('b',0,8,4,4)],[anchor]],caps);
 assertGeometry(result,caps);assert.equal(item(result,'b').y,4);assert.deepEqual(item(result,'c'),anchor);
});
test('soma de buracos não equivale a um encaixe contíguo',()=>{
 const anchor=fixed('a',3,0,6,10);const result=normalizeRows([[anchor,n('b',0,0,6,4)]],caps);
 assertGeometry(result,caps);assert.equal(item(result,'b').y,10);assert.equal(item(result,'b').w,6);assert.deepEqual(item(result,'a'),anchor);
});
test('prefixo aproveita espaço ao lado da âncora sem esticar ou reordenar o restante',()=>{
 const anchor=fixed('a',0,0,4,10);const result=normalizeRows([[anchor,n('b',4,0,6,4),n('c',0,4,6,4)]],caps);
 assertGeometry(result,caps);assert.deepEqual([item(result,'b').x,item(result,'b').y,item(result,'b').w],[4,0,6]);assert.deepEqual([item(result,'c').x,item(result,'c').y,item(result,'c').w],[4,4,6]);
});
test('resize não redistribui a largura ou altura de uma âncora vizinha',()=>{
 const anchor=fixed('b',8,0,4,9),result=normalizeRows([[n('a',0,0,9,5),anchor]],caps,'a');
 assertGeometry(result,caps);assert.deepEqual(item(result,'b'),anchor);assert.equal(item(result,'a').w,9);assert.equal(item(result,'a').y,9);
});
test('snapshot pré-gesto recupera caixa e intenção da âncora mesmo se engine a deslocou',()=>{
 const anchor={...fixed('a',4,8,4,10),altura_desejada:6};const candidate={...anchor,x:0,y:0,w:12,h:20,altura_desejada:20};
 const input=[[candidate,n('b',0,8,4,4)]],before=structuredClone(input),snap=structuredClone(anchor);
 const result=normalizeRows(input,caps,null,{anchors:[anchor]});assertGeometry(result,caps);assert.deepEqual(item(result,'a'),anchor);assert.deepEqual(input,before);assert.deepEqual(anchor,snap);
});
test('desfixar explicitamente ignora snapshot e permite compactar a antiga âncora',()=>{
 const anchor=fixed('a',4,30,4,10),candidate={...anchor,fixo:false};const result=normalizeRows([[candidate,n('b',0,0,4,4)]],caps,null,{anchors:[anchor]});
 assertGeometry(result,caps);assert.equal(item(result,'a').x,0);assert.equal(item(result,'a').y,0);assert.equal(item(result,'a').fixo,false);
});
test('fixo prevalece na caixa e preserva intenção livre menor até desfixar',()=>{
 const anchor={...fixed('a',4,8,4,10),altura_desejada:6,altura_livre:true};const result=normalizeRows([[anchor]],caps);
 assertGeometry(result,caps);assert.deepEqual([item(result,'a').x,item(result,'a').y,item(result,'a').w,item(result,'a').h],[4,8,4,10]);assert.equal(item(result,'a').altura_desejada,6);
 item(result,'a').fixo=false;const unpinned=normalizeRows(rowsOf(result),caps,null,{anchors:[anchor]});assert.equal(item(unpinned,'a').h,6);
});
test('livres e âncoras sobrevivem a save/reload sem crescer larguras e permitem reduzir intenção',()=>{
 const input=[[fixed('c',8,0,4,12),free('a',0,0,4,9),n('b',4,0,4,5)]],result=normalizeRows(input,caps),loaded=normalizeRows(rowsOf(result.map(project)),caps);
 assertGeometry(loaded,caps);assert.deepEqual(loaded,result);item(loaded,'a').altura_desejada=3;
 const reduced=normalizeRows(rowsOf(loaded),caps);assertGeometry(reduced,caps);assert.equal(item(reduced,'a').h,3);assert.deepEqual(item(reduced,'c'),item(result,'c'));assert.equal(item(reduced,'b').h,5);
});
test('alturas incompatíveis nos uniformes fazem wrap sem afetar livre nem âncora',()=>{
 const limits=new Map([['a',cap({minH:12,maxH:20})],['b',cap({minH:2,maxH:8})],['c',cap()]]);
 const result=normalizeRows([[n('a',0,0,4,12),n('b',4,0,4,4),free('c',8,0,4,3)]],limits);
 assertGeometry(result,limits);assert.equal(item(result,'a').h,12);assert.equal(item(result,'b').h,4);assert.equal(item(result,'b').y,12);assert.equal(item(result,'c').h,3);
});
test('âncoras sobrepostas ou fora dos limites rejeitam sem modificar entrada',()=>{
 for(const input of [[[fixed('a',0,0,6,8),fixed('b',4,0,6,8)]],[[fixed('a',10,0,4,8)]],[[fixed('a',0,195,4,8)]]]){const before=structuredClone(input);assert.throws(()=>normalizeRows(input,caps));assert.deepEqual(input,before);}
});
test('impossibilidade de encaixe até a linha200 rejeita atomicamente',()=>{
 const limits=new Map(),rows=[];for(let i=0;i<10;i++){const id=`fixed${i}`;limits.set(id,cap());rows.push([fixed(id,0,i*20,12,20)]);}limits.set('m',cap());rows.push([free('m',0,0,6,4)]);
 const before=structuredClone(rows);assert.throws(()=>normalizeRows(rows,limits));assert.deepEqual(rows,before);
});
test('orientação e flags não aceitam coerção; ID duplicado ou seção divergente rejeita tudo',()=>{
 const bad=[{fixo:1},{fixo:0},{altura_livre:'true'},{altura_livre:null},{orientacao:'auto'},{x:1.5}];
 for(const extra of bad){const input=[[{...n('a'),...extra}]],before=structuredClone(input);assert.throws(()=>normalizeRows(input,caps));assert.deepEqual(input,before);}
 assert.throws(()=>normalizeRows([[n('a'),n('a')]],caps));assert.throws(()=>normalizeRows([[n('a'),{...n('b'),section:'ensino'}]],caps));
 const scoped=new Map([['a',cap({section:'analises'})]]);assert.throws(()=>normalizeRows([[{...n('a'),section:'ensino'}]],scoped));
});
