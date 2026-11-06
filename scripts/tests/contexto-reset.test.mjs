import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';

const contexto=readFileSync(new URL('../../apps/web/public/js/contexto-aba.js',import.meta.url),'utf8');
const navegacao=readFileSync(new URL('../../apps/web/public/js/navegacao.js',import.meta.url),'utf8');
const antiga='epoca-antiga-ficticia',nova='epoca-nova-ficticia';

/** DOM mínimo; executa ambos scripts reais. A autoridade HTTP simulada é separada do estado do cliente. */
function ambiente({selecao=false,bus=[],servidor={revisao:antiga}}={}){
 const handlers={},elementos=new Map(),chamadas=[];
 class Elemento{
  constructor(){this.dataset={};}
  append(){}prepend(e){elementos.set(e.id,e);}setAttribute(){}focus(){}addEventListener(){}
 }
 class Formulario{submit(){}}
 const main=new Elemento(),anterior={ur4:{destino:{referencia:'a'.repeat(48)},revisao:antiga}},atual={ur4:{destino:{referencia:'b'.repeat(48)},revisao:antiga}};
 const entradas=[anterior,atual],storage=new Map([['g360-ur4-pendente',JSON.stringify(atual.ur4)],['rascunho-preservado','texto-ficticio']]);
 const history={get state(){return entradas[1];},replaceState(v){entradas[1]=v;}};
 const location={origin:'https://app.example.test',pathname:selecao?'/app/vinculos':'/app/turmas/detalhes'};
 location.href=location.origin+location.pathname;
 const doc={readyState:'loading',body:main,
  querySelector(s){if(s==='meta[name="g360-revisao"]')return{content:antiga};if(s==='meta[name="csrf-token"]')return{content:'csrf-ficticio'};if(s==='[data-selecao-acesso]')return selecao?{}:null;return s==='main'?main:null;},
  querySelectorAll(){return[];},getElementById:id=>elementos.get(id),createElement:()=>new Elemento(),
  addEventListener(n,f){(handlers[n]??=[]).push(f);},removeEventListener(){},dispatchEvent(){},
 };
 class Canal{
  constructor(){bus.push(this);}close(){}
  postMessage(data){for(const c of bus)if(c!==this)c.onmessage?.({data});}
 }
 const win={addEventListener(){},removeEventListener(){},BroadcastChannel:Canal,
  async fetch(input,init){chamadas.push({input,init});const valido=init.headers.get('X-G360-Revisao')===servidor.revisao;return new Response(JSON.stringify(valido?{ok:true}:{acesso_alterado:true}),{status:valido?200:409,headers:{'Content-Type':'application/json'}});},
 };
 const ctx={window:win,document:doc,location,history,sessionStorage:{getItem:k=>storage.get(k)??null,setItem:(k,v)=>storage.set(k,v),removeItem:k=>storage.delete(k)},
  URL,Headers,Request,Response,AbortController,FormData,URLSearchParams,Event,CustomEvent:class{},BroadcastChannel:Canal,HTMLFormElement:Formulario,MutationObserver:class{observe(){}disconnect(){}},console,setTimeout,clearTimeout};
 vm.createContext(ctx);vm.runInContext(navegacao,ctx);vm.runInContext(contexto,ctx);
 return{win,doc,handlers,storage,history,entradas,anterior,atual,chamadas,iniciar(){for(const f of [...(handlers.DOMContentLoaded||[])])f();},enviarFormulario(form){for(const f of handlers.submit||[])f({target:form,defaultPrevented:false,preventDefault(){},stopImmediatePropagation(){}});}};
}

test('iniciar seleção limpa apenas handoff e entrada atual da própria aba',()=>{
 const a=ambiente({selecao:true}),b=ambiente();a.iniciar();
 assert.equal(a.storage.has('g360-ur4-pendente'),false);assert.equal(a.history.state,null);
 assert.equal(a.entradas[0],a.anterior);assert.equal(a.storage.get('rascunho-preservado'),'texto-ficticio');
 assert.equal(b.history.state,b.atual);assert.equal(b.storage.has('g360-ur4-pendente'),true);
 assert.equal(a.chamadas.length,0);assert.equal(b.chamadas.length,0);
});

test('callback confirmado não promove revisão de formulário/header nem resgata outra aba',async()=>{
 const bus=[],servidor={revisao:antiga},a=ambiente({bus,servidor}),b=ambiente({bus,servidor});
 a.iniciar();b.iniciar();
 const campo={value:antiga},form={action:'https://app.example.test/app/turmas/atualizar',method:'POST',hasAttribute:()=>false,querySelector:()=>campo,append(){assert.fail('Não criar revisão nova');},matches:()=>false};
 servidor.revisao=nova;a.win.g360ContextoSelecionado(nova);
 assert.equal(a.history.state,null);assert.equal(a.storage.has('g360-ur4-pendente'),false);
 assert.equal(a.entradas[0],a.anterior);assert.equal(b.history.state,b.atual);assert.equal(b.storage.has('g360-ur4-pendente'),true);
 a.enviarFormulario(form);assert.equal(campo.value,antiga);
 const r=await a.win.fetch('/app/turmas/atualizar',{method:'POST',body:'_revisao='+antiga});
 assert.equal(r.status,409);assert.equal(a.chamadas[0].init.headers.get('X-G360-Revisao'),antiga);
 assert.equal(a.chamadas[0].init.body,'_revisao='+antiga);assert.equal(campo.value,antiga);
 const outro=await b.win.fetch('/app/turmas/atualizar',{method:'POST',body:'_revisao='+antiga});
 assert.equal(outro.status,409);assert.equal(b.chamadas.length,0,'Broadcast bloqueia sem reenviar');
});

test('callback sem nova revisão confirmada não limpa o estado de navegação',()=>{
 const a=ambiente();a.iniciar();
 for(const valor of [undefined,null,7,antiga])a.win.g360ContextoSelecionado(valor);
 assert.equal(a.history.state,a.atual);assert.equal(a.storage.has('g360-ur4-pendente'),true);assert.equal(a.chamadas.length,0);
});
