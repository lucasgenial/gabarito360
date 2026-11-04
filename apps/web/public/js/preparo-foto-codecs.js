/* Conversões locais e descartáveis. Não envia o original ao servidor. */
'use strict';
function validarDimensoes(w,h){if(!w||!h||w*h>48000000)throw new Error('Esta foto tem resolução muito alta. Tire outra foto com até 48 megapixels.');}
function conferirJpeg(b){
 var v=new DataView(b),p=2;
 while(p+9<v.byteLength){if(v.getUint8(p++)!==255)break;var m=v.getUint8(p++);if(m===217||m===218)break;if(m===216||m===1)continue;var n=v.getUint16(p);if(n<2||p+n>v.byteLength)break;
  if([192,193,194,195,197,198,199,201,202,203,205,206,207].includes(m)){validarDimensoes(v.getUint16(p+5),v.getUint16(p+3));return;}p+=n;}
 throw new Error('Não foi possível conferir a prévia deste RAW/DNG. Escolha a versão JPG/HEIC da foto.');
}
function conferirHeif(b){
 var a=new Uint8Array(b),v=new DataView(b),total=0,caixas=0;
 function ler(inicio,fim,nivel){
  if(nivel>8)throw new Error('Estrutura HEIC inválida.');
  for(var p=inicio;p+8<=fim;){
   if(++caixas>4096)throw new Error('Esta foto contém muitas imagens.');
   var tamanho=v.getUint32(p),cab=8,tipo=String.fromCharCode(...a.slice(p+4,p+8));
   if(tamanho===1){if(p+16>fim||v.getUint32(p+8)!==0)throw new Error('Estrutura HEIC inválida.');tamanho=v.getUint32(p+12);cab=16;}
   if(tamanho===0)tamanho=fim-p;
   if(tamanho<cab||p+tamanho>fim)throw new Error('Estrutura HEIC inválida.');
   if(tipo==='ispe'){if(tamanho<cab+12)throw new Error('Estrutura HEIC inválida.');validarDimensoes(v.getUint32(p+cab+4),v.getUint32(p+cab+8));total++;}
   if(['meta','iprp','ipco'].includes(tipo))ler(p+cab+(tipo==='meta'?4:0),p+tamanho,nivel+1);
   p+=tamanho;
  }
 }
 ler(0,a.length,0);if(!total)throw new Error('Não foi possível conferir esta foto HEIC. Escolha outro arquivo ou tire uma nova foto.');
}
async function reduzir(bitmap){
 try{validarDimensoes(bitmap.width,bitmap.height);var k=Math.min(1,1600/Math.max(bitmap.width,bitmap.height)),c=new OffscreenCanvas(Math.round(bitmap.width*k),Math.round(bitmap.height*k));c.getContext('2d').drawImage(bitmap,0,0,c.width,c.height);return await c.convertToBlob({type:'image/jpeg',quality:0.94});}finally{bitmap.close();}
}
function previaDng(b){
 var v=new DataView(b),little=v.getUint16(0)===0x4949,pendentes=[v.getUint32(4,little)],vistos=new Set(),candidatos=[],dng=false;
 function lista(p,t,n){if(n>128||![3,4].includes(t))return [];var tam=t===3?2:4,pos=n*tam<=4?p+8:v.getUint32(p+8,little),r=[];if(pos+n*tam>v.byteLength)return r;for(var i=0;i<n;i++)r.push(t===3?v.getUint16(pos+i*tam,little):v.getUint32(pos+i*tam,little));return r;}
 while(pendentes.length&&vistos.size<32){var off=pendentes.shift();if(!off||vistos.has(off)||off+2>v.byteLength)continue;vistos.add(off);var n=v.getUint16(off,little);if(n>512||off+2+n*12+4>v.byteLength)continue;var campos={};
  for(var i=0;i<n;i++){var p=off+2+i*12,tag=v.getUint16(p,little),t=v.getUint16(p+2,little),ct=v.getUint32(p+4,little);if(tag===50706&&t===1&&ct===4)dng=true;if([513,514,273,279,330].includes(tag))campos[tag]=lista(p,t,ct);}
  pendentes.push(v.getUint32(off+2+n*12,little));if(campos[330])pendentes.push(...campos[330]);
  for(var par of [[513,514],[273,279]])if(campos[par[0]]?.length===1&&campos[par[1]]?.length===1){var inicio=campos[par[0]][0],tam=campos[par[1]][0];if(tam>=1024&&tam<=8388608&&inicio+tam<=v.byteLength&&v.getUint16(inicio)===0xffd8)candidatos.push({inicio,tam});}
 }
 if(!dng||!candidatos.length)throw new Error('Este RAW/DNG não tem uma prévia compatível. Escolha a versão JPG/HEIC da foto ou tire outra no modo padrão.');
 candidatos.sort((a,b)=>b.tam-a.tam);return new Blob([b.slice(candidatos[0].inicio,candidatos[0].inicio+candidatos[0].tam)],{type:'image/jpeg'});
}
self.onmessage=async function(e){
 try{
  var f=e.data.file;if(!(f instanceof Blob)||f.size>33554432)throw new Error('Escolha uma foto com até 32 MB.');
  var b=await f.arrayBuffer(),bitmap;
  if(e.data.tipo==='heic'){
   // Confere resolução declarada antes de alocar o quadro RGBA do decodificador.
   conferirHeif(b);
   var codec=await import('./libs/heic-to-1.6.7.js');if(!await codec.isHeic(f))throw new Error('Formato de foto não reconhecido.');bitmap=await codec.heicTo({blob:f,type:'bitmap'});
  }else if(e.data.tipo==='dng'){var previa=previaDng(b);conferirJpeg(await previa.arrayBuffer());bitmap=await createImageBitmap(previa,{imageOrientation:'from-image'});}
  else throw new Error('Formato de foto não reconhecido.');
  self.postMessage({blob:await reduzir(bitmap)});
 }catch(err){self.postMessage({erro:(err instanceof RangeError||err instanceof TypeError)?'Não foi possível preparar esta foto. Escolha outro arquivo ou tire uma nova foto.':(err.message||'Não foi possível preparar esta foto. Escolha outro arquivo ou tire uma nova foto.')});}
};
