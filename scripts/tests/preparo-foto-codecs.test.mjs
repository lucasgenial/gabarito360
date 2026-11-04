import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source=fs.readFileSync(new URL('../../apps/web/public/js/preparo-foto-codecs.js',import.meta.url),'utf8');
function codecs(){const c={self:{},Blob,Uint8Array,DataView};vm.runInNewContext(source,c);return c;}
function box(tipo,...corpos){const b=Buffer.concat(corpos),h=Buffer.alloc(8);h.writeUInt32BE(b.length+8);h.write(tipo,4);return Buffer.concat([h,b]);}
function heif(w,h){const ispe=Buffer.alloc(12);ispe.writeUInt32BE(w,4);ispe.writeUInt32BE(h,8);return box('meta',Buffer.alloc(4),box('iprp',box('ipco',box('ispe',ispe))));}
function arrayBuffer(b){return b.buffer.slice(b.byteOffset,b.byteOffset+b.byteLength);}

test('dimensões HEIF válidas são conferidas nas propriedades, não no conteúdo comprimido',()=>{
 const c=codecs();assert.doesNotThrow(()=>c.conferirHeif(arrayBuffer(heif(4032,3024))));
 // Um trecho comprimido que coincida com ispe não pode virar propriedade.
 const b=Buffer.concat([heif(4032,3024),box('mdat',box('ispe',Buffer.alloc(12,255)))]);
 assert.doesNotThrow(()=>c.conferirHeif(arrayBuffer(b)));
});
test('dimensões excessivas, estruturas truncadas e recursão não são decodificadas',()=>{
 const c=codecs();assert.throws(()=>c.conferirHeif(arrayBuffer(heif(12000,10000))),/48 megapixels/);
 const b=heif(4000,3000);b.writeUInt32BE(b.length+1);assert.throws(()=>c.conferirHeif(arrayBuffer(b)),/inválida/);
 let profundo=box('ispe',Buffer.alloc(12));for(let i=0;i<10;i++)profundo=box('ipco',profundo);
 assert.throws(()=>c.conferirHeif(arrayBuffer(profundo)),/inválida/);
 assert.throws(()=>c.conferirHeif(arrayBuffer(box('mdat',Buffer.alloc(32)))),/conferir/);
});
function dng({marca=true,fora=false,ciclo=false}={}){
 const b=Buffer.alloc(1200);b.write('II');b.writeUInt16LE(42,2);b.writeUInt32LE(8,4);b.writeUInt16LE(3,8);
 function campo(p,tag,tipo,n,valor){b.writeUInt16LE(tag,p);b.writeUInt16LE(tipo,p+2);b.writeUInt32LE(n,p+4);b.writeUInt32LE(valor,p+8);}
 campo(10,marca?50706:50000,1,4,0x401);campo(22,513,4,1,fora?999999:64);campo(34,514,4,1,1100);b.writeUInt32LE(ciclo?8:0,46);b[64]=255;b[65]=216;return arrayBuffer(b);
}
test('DNG usa somente prévia JPEG delimitada e exige marca DNG',async()=>{
 const c=codecs(),b=c.previaDng(dng());assert.equal(b.type,'image/jpeg');assert.equal(b.size,1100);
 const a=new Uint8Array(await b.arrayBuffer());assert.deepEqual(Array.from(a.slice(0,2)),[255,216]);
 assert.throws(()=>c.previaDng(dng({marca:false})),/prévia compatível/);
 assert.throws(()=>c.previaDng(dng({fora:true})),/prévia compatível/);
 assert.equal(c.previaDng(dng({ciclo:true})).size,1100);
});
test('arquivo excessivo é recusado antes de converter',async()=>{
 const c=codecs();let resposta;c.self.postMessage=d=>{resposta=d;};
 await c.self.onmessage({data:{file:new Blob([new Uint8Array(33554433)]),tipo:'heic'}});
 assert.match(resposta.erro,/32 MB/);
});
