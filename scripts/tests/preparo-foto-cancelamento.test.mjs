import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
function editor(){
 const workers=[],timers=new Map();let contador=0;
 class Worker{constructor(){workers.push(this);this.encerramentos=0;}postMessage(){}terminate(){this.encerramentos++;}}
 const c={window:{},document:{currentScript:{src:'http://qa.test/js/preparo-foto.js'}},URL,Worker,DOMException,
  setTimeout:fn=>{const id=++contador;timers.set(id,fn);return id;},clearTimeout:id=>timers.delete(id)};
 vm.runInNewContext(fs.readFileSync(new URL('../../apps/web/public/js/preparo-foto.js',import.meta.url),'utf8'),c);
 const e=Object.create(c.window.G360PreparoFoto.prototype);e.root={dataset:{codecVersion:'teste'}};return {e,workers,timers};
}
test('cancelar conversão encerra worker, timer e promessa que retinha o original',async()=>{
 const {e,workers,timers}=editor(),p=e.converter(new Blob(['ficticio']),'heic');
 e.cancelarConversao();await assert.rejects(p,err=>err.name==='AbortError');
 assert.equal(workers[0].encerramentos,1);assert.equal(timers.size,0);assert.equal(e.worker,null);assert.equal(e.cancelarConversao,null);
});
test('resultado atrasado de conversão cancelada não interfere na escolha seguinte',async()=>{
 const {e,workers,timers}=editor(),p=e.converter(new Blob(['antigo']),'heic');e.cancelarConversao();await assert.rejects(p);
 const p2=e.converter(new Blob(['novo']),'heic'),foto=new Blob(['preparado']);
 workers[0].onmessage({data:{blob:new Blob(['obsoleto'])}});assert.equal(e.worker,workers[1]);
 workers[1].onmessage({data:{blob:foto}});assert.equal(await p2,foto);assert.equal(timers.size,0);assert.equal(e.worker,null);
});
test('prazo de conversão libera recursos e retorna orientação de nova foto',async()=>{
 const {e,workers,timers}=editor(),p=e.converter(new Blob(['ficticio']),'heic');[...timers.values()][0]();
 await assert.rejects(p,/modo padrão/);assert.equal(timers.size,0);assert.equal(workers[0].encerramentos,1);
});
