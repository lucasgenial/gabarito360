import test from 'node:test';
import assert from 'node:assert/strict';
import {baseResultados,posicaoAjuda} from '../../apps/web/resources/js/painel-indicadores.js';
import {compactReadingLayout} from '../../apps/web/resources/js/painel-leitura.js';

test('zero confirmado, exclusões e ausência de base são estados diferentes',()=>{
 assert.equal(baseResultados(0,0,0),'Base: 0 de 0 resultados; 0 sem percentual válido.');
 assert.equal(baseResultados(2,3,1),'Base: 2 de 3 resultados; 1 sem percentual válido.');
 for(const v of [[null,0,0],[0,undefined,0],['2',3,1],[3,2,0],[-1,1,2]])assert.match(baseResultados(...v),/não foram informadas/);
});
test('ajuda cabe no celular e inverte para cima perto do rodapé',()=>{
 assert.deepEqual(posicaoAjuda({left:280,top:700,bottom:744},{width:288,height:240},{width:320,height:844}),{left:16,top:452});
 assert.deepEqual(posicaoAjuda({left:50,top:100,bottom:144},{width:320,height:240},{width:1440,height:900}),{left:50,top:152});
});

test('leitura reduz vazio e retira o espaço da próxima faixa sem alterar o mapa salvo',()=>{
 const input=[{id:'a',section:'s',x:0,y:0,w:6,h:12,altura_desejada:12,orientacao:'vertical'},{id:'b',section:'s',x:6,y:0,w:6,h:12},{id:'c',section:'s',x:0,y:12,w:12,h:9}],before=structuredClone(input);
 const view=compactReadingLayout(input,new Map([['a',3],['b',3]]));
 assert.deepEqual(view.map(n=>[n.id,n.x,n.y,n.w,n.h]),[['a',0,0,6,3],['b',6,0,6,3],['c',0,3,12,9]]);
 assert.deepEqual(input,before);assert.equal(view[0].altura_desejada,3);assert.equal(view[0].orientacao,'vertical');assert.ok(!Object.hasOwn(view[1],'altura_desejada'));
});
test('pin com dados conserva caixa; pin vazio mantém x/y só com altura transitória',()=>{
 const input=[{id:'a',section:'s',x:0,y:2,w:6,h:12,fixo:true},{id:'b',section:'s',x:6,y:0,w:6,h:12},{id:'c',section:'s',x:6,y:12,w:6,h:9,fixo:true}];
 const view=compactReadingLayout(input,new Map([['b',3],['c',3]]));
 assert.deepEqual(view[0],input[0]);assert.deepEqual(view[2],{...input[2],h:3});assert.deepEqual(view[1],{...input[1],h:3});
 assert.equal(input[2].h,9);
});
test('com dados ou sem altura menor confirmada não compacta a disposição',()=>{
 const input=[{id:'a',section:'s',x:0,y:8,w:6,h:9},{id:'b',section:'s',x:6,y:10,w:6,h:12}];
 for(const h of [new Map(),new Map([['a',0],['b',NaN]]),new Map([['a',10],['b',12]])])assert.deepEqual(compactReadingLayout(input,h),input);
});
test('leitura mista não corta dados, não sobrepõe cards e não muda larguras',()=>{
 const input=[{id:'a',section:'s',x:0,y:0,w:6,h:12},{id:'b',section:'s',x:6,y:0,w:6,h:12},{id:'c',section:'s',x:6,y:12,w:6,h:9},{id:'d',section:'s',x:0,y:12,w:6,h:9}];
 const view=compactReadingLayout(input,new Map([['b',3]]));
 assert.equal(view[0].h,12);assert.equal(view[2].y,3);assert.equal(view[3].y,12);
 for(let i=0;i<view.length;i++){assert.equal(view[i].x,input[i].x);assert.equal(view[i].w,input[i].w);for(let j=0;j<i;j++){const a=view[i],b=view[j];assert.ok(!(a.x<b.x+b.w&&a.x+a.w>b.x&&a.y<b.y+b.h&&a.y+a.h>b.y));}}
});
