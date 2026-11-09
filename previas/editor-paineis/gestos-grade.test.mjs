import test from 'node:test';
import assert from 'node:assert/strict';
import {conectarGestosGrade} from './gestos-grade.mjs';

function scene(){
 const handlers={},root={el:{},on(names,cb){for(const name of names.split(' '))handlers[name]=cb;},off(names){for(const name of names.split(' '))delete handlers[name];}};
 const child={el:{}},grandchild={el:{}},grids=[root,child,grandchild],wrappers=new Set(),catalog=new Set(),starts=[],stops=[];
 const element=(id,grid)=>{catalog.add(id);const el={isConnected:true,parentElement:grid.el,getAttribute:()=>id,gridstackNode:{id,grid}};wrappers.add(el);return el;};
 const elements=grids.map((grid,i)=>element('no-'+i,grid));
 const binding=conectarGestosGrade({root,grids,wrappers,validNode:id=>catalog.has(id),begin:g=>starts.push(g),finish:g=>stops.push(g)});
 // Contrato da biblioteca: eventos de qualquer nível chegam somente à raiz.
 const emit=(type,el)=>handlers[type]?.({type},el);
 return {binding,root,child,elements,starts,stops,emit,catalog,handlers};
}

test('a raiz registra e conclui resize de raiz, filho e neto, uma vez por gesto',()=>{
 const s=scene();for(const el of s.elements){s.emit('resizestart',el);const current=s.starts.at(-1);assert.equal(current.grid,el.gridstackNode.grid);s.emit('resizestop',el);s.emit('resizestop',el);assert.equal(s.stops.at(-1),current);s.binding.complete(current);}
 assert.equal(s.starts.length,3);assert.equal(s.stops.length,3);
});
test('stop sem start, tipo/alvo diferente e grade adulterada não concluem o gesto',()=>{
 const s=scene(),el=s.elements[1];s.emit('dragstop',el);assert.equal(s.stops.length,0);s.emit('dragstart',el);s.emit('resizestop',el);assert.equal(s.stops.length,0);s.emit('dragstop',s.elements[2]);assert.equal(s.stops.length,0);el.gridstackNode.grid=s.root;s.emit('dragstop',el);assert.equal(s.stops.length,0);el.gridstackNode.grid=s.child;s.emit('dragstop',el);assert.equal(s.stops.length,1);
});
test('alvo desconectado ou fora do catálogo não inicia nem confirma',()=>{
 const s=scene(),el=s.elements[1];el.isConnected=false;s.emit('resizestart',el);assert.equal(s.starts.length,0);el.isConnected=true;s.catalog.delete('no-1');s.emit('resizestart',el);assert.equal(s.starts.length,0);s.catalog.add('no-1');s.emit('resizestart',el);el.isConnected=false;s.emit('resizestop',el);assert.equal(s.stops.length,0);
});
test('Escape invalida o ticket e um stop tardio não reaplica o rascunho',()=>{
 const s=scene(),el=s.elements[2];s.emit('resizestart',el);const first=s.starts[0];s.binding.cancel();s.emit('resizestop',el);assert.equal(s.stops.length,0);assert.equal(s.binding.current(first),false);s.emit('resizestart',el);const next=s.starts[1];assert.ok(next.epoch>first.epoch);s.binding.complete(first);assert.equal(s.binding.current(next),true);
});
test('desmontagem invalida gesto, remove handlers e bloqueia callback já capturado',()=>{
 const s=scene(),el=s.elements[1],lateStart=s.handlers.resizestart,lateStop=s.handlers.resizestop;s.emit('resizestart',el);const gesture=s.starts[0];s.binding.destroy();lateStop({type:'resizestop'},el);lateStart({type:'resizestart'},el);assert.equal(s.stops.length,0);assert.equal(s.starts.length,1);assert.equal(s.binding.current(gesture),false);assert.deepEqual(Object.keys(s.handlers),[]);
});
