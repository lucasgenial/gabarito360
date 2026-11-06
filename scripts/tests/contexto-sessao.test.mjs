import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';
const codigo=readFileSync(new URL('../../apps/web/public/js/contexto-aba.js',import.meta.url),'utf8');
const modulo={exports:{}};vm.runInNewContext(codigo,{module:modulo,URL});
const destino=modulo.exports.destino, origem='https://app.example.test/app/turmas';
test('navegação tem módulo estável sem identificador de vínculo',()=>{
 assert.equal(destino('/turmas?busca=Teste',origem),'https://app.example.test/app/turmas?busca=Teste');
 assert.equal(destino('/acessos/vinculo-5/turmas?_ctx=ficticio&acesso=vinculo-5',origem),'https://app.example.test/app/turmas');
 assert.equal(destino('/app/painel',origem),'https://app.example.test/app/painel');
 assert.equal(destino('/vinculos',origem),'https://app.example.test/app/vinculos');
});
test('não reescreve sites externos, arquivos públicos nem API',()=>{
 for(const path of ['/api/v1/health','/js/app.js','/login','/brand/marca.svg','/validar-documento'])assert.equal(destino(path,origem),new URL(path,origem).href);
 assert.equal(destino('https://externo.example.test/turmas',origem),'https://externo.example.test/turmas');
});
function ambiente(){
 const handlers={}, chamadas=[], elementos=new Map();
 class Elemento{
  constructor(){this.filhos=[];this.atributos={};this.dataset={};}
  append(...els){this.filhos.push(...els);}prepend(el){elementos.set(el.id,el);}setAttribute(k,v){this.atributos[k]=v;}focus(){}addEventListener(){}
 }
 class Formulario{submit(){this.enviado=true;}}
 const main=new Elemento(), canais=[];
 const doc={readyState:'loading',querySelector:s=>s==='meta[name="g360-revisao"]'?{content:'revisao-ficticia-fixada-na-tela'}:main,getElementById:id=>elementos.get(id),createElement:()=>new Elemento(),addEventListener:(n,f)=>{handlers[n]=f;},dispatchEvent(){},body:main};
 class Canal{constructor(){canais.push(this);}postMessage(m){this.enviada=m;}}
 const win={fetch:async(input,init)=>{chamadas.push({input,init});return new Response(JSON.stringify({ok:true}),{headers:{'Content-Type':'application/json'}});},addEventListener(){},BroadcastChannel:Canal};
 const ctx={window:win,document:doc,location:{href:origem,origin:new URL(origem).origin},URL,Headers,Request,Response,AbortController,Event,CustomEvent:class{},BroadcastChannel:Canal,HTMLFormElement:Formulario,MutationObserver:class{observe(){}},sessionStorage:{removeItem(){}},console};
 vm.runInNewContext(codigo,ctx);return{win,handlers,chamadas,canais,elementos};
}
test('fetch transporta revisão em cabeçalho preservando método e corpo do Request',async()=>{
 const a=ambiente();const input=new Request('https://app.example.test/app/alunos',{method:'POST',body:'nome=Ficticio',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':'csrf-ficticio'}});
 await a.win.fetch(input);const c=a.chamadas[0];assert.equal(c.input.method,'POST');assert.equal(await c.input.text(),'nome=Ficticio');
 assert.equal(c.init.headers.get('X-G360-Revisao'),'revisao-ficticia-fixada-na-tela');assert.equal(c.init.headers.get('X-CSRF-TOKEN'),'csrf-ficticio');assert.equal(new URL(c.input.url).search,'');
});
test('não envia a revisão a outra origem',async()=>{
 const a=ambiente();await a.win.fetch('https://externo.example.test/foto',{headers:{Accept:'image/png'}});assert.equal(a.chamadas[0].init.headers['X-G360-Revisao'],undefined);
});
test('formulário antigo não recebe revisão nova silenciosamente',()=>{
 const a=ambiente(), campo={value:'revisao-antiga'};let anexos=0;
 const form={action:'https://app.example.test/turmas',method:'post',hasAttribute:()=>false,querySelector:()=>campo,append:()=>anexos++};
 a.handlers.submit({target:form});assert.equal(campo.value,'revisao-antiga');assert.equal(anexos,0);assert.equal(form.action,'https://app.example.test/app/turmas');
});
test('troca em outra aba bloqueia novos envios sem requisição nem replay',async()=>{
 const a=ambiente();a.canais[0].onmessage({data:{tipo:'selecionado',revisao:'revisao-nova'}});
 const r=await a.win.fetch('/app/alunos',{method:'POST',body:'nome=Ficticio'});assert.equal(r.status,409);assert.equal(a.chamadas.length,0);
 let impedido=false;a.handlers.submit({target:{},preventDefault(){impedido=true;},stopImmediatePropagation(){}});assert.equal(impedido,true);assert.ok(a.elementos.has('contexto-expirado'));
});
