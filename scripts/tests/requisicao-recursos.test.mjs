import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';
const source=readFileSync(new URL('../../apps/web/public/js/contexto-aba.js',import.meta.url),'utf8');
const recurso={url:'/app/cartoes/imagem/consultar',pagina:'/app/cartoes/imagem',referencia:'referencia-opaca-ficticia',metodo:'GET'};
function ambiente(){
 const chamadas=[];
 const document={readyState:'loading',querySelector:s=>s==='meta[name="g360-revisao"]'?{content:'revisao-ficticia'}:s==='meta[name="csrf-token"]'?{content:'csrf-ficticio'}:null,addEventListener(){},removeEventListener(){}};
 const window={fetch:async(input,init)=>{chamadas.push({input,init});return new Response('{}',{headers:{'Content-Type':'application/json'}});},addEventListener(){},removeEventListener(){}};
 class Formulario{submit(){}}
 const contexto={window,document,location:{href:'https://exemplo.test/app/painel',origin:'https://exemplo.test'},URL,Headers,Request,Response,FormData,URLSearchParams,AbortController,Event,HTMLFormElement:Formulario,sessionStorage:{removeItem(){}}};vm.runInNewContext(source,contexto);
 return{pedir:window.G360Requisicao,chamadas,reiniciar:()=>vm.runInNewContext(source,contexto),window};
}
test('consulta por descritor faz POST e mantém alvo/CSRF/revisão somente no corpo e cabeçalhos',async()=>{
 const a=ambiente();await a.pedir(recurso,{method:'GET',body:JSON.stringify({cartao_id:65})});
 assert.equal(a.chamadas.length,1);const c=a.chamadas[0],u=new URL(c.input);assert.equal(u.pathname,recurso.url);assert.equal(u.search,'');assert.equal(c.init.method,'POST');assert.equal(c.init.redirect,'error');
 assert.deepEqual(JSON.parse(c.init.body),{cartao_id:65,_recurso:recurso.referencia,_revisao:'revisao-ficticia',_token:'csrf-ficticio'});
 assert.equal(c.init.headers.get('X-G360-Revisao'),'revisao-ficticia');assert.equal(c.init.headers.get('X-CSRF-TOKEN'),'csrf-ficticio');
});
test('revisão antiga do corpo é preservada para o servidor recusar, sem troca silenciosa',async()=>{
 const a=ambiente();await a.pedir(recurso,{body:JSON.stringify({_revisao:'antiga'})});assert.equal(JSON.parse(a.chamadas[0].init.body)._revisao,'antiga');
});
test('FormData preserva arquivo e campos, não modifica o objeto fornecido',async()=>{
 const a=ambiente(),body=new FormData();body.append('imagem',new Blob(['foto ficticia'],{type:'image/jpeg'}),'foto.jpg');body.append('envio_id','uuid-ficticio');
 await a.pedir(recurso,{body,headers:{'Content-Type':'multipart/form-data'}});const envio=a.chamadas[0].init;
 assert.equal(body.has('_recurso'),false);assert.equal(envio.headers.has('Content-Type'),false);assert.equal(envio.body.get('_recurso'),recurso.referencia);assert.equal(envio.body.get('envio_id'),'uuid-ficticio');assert.equal(await envio.body.get('imagem').text(),'foto ficticia');
});
test('destinos externos, query/fragmento e referências conflitantes não disparam fetch',async()=>{
 const a=ambiente();for(const url of ['https://outra.test/app/arquivo','/app/imagem?id=65','/app/imagem#65','/login','https://usuario:senha@exemplo.test/app/arquivo'])await assert.rejects(a.pedir({...recurso,url}));
 await assert.rejects(a.pedir(recurso,{body:JSON.stringify({_recurso:'outro-alvo'})}));assert.equal(a.chamadas.length,0);
});
test('descritor ausente ou corpo JSON inválido não disparam fetch',async()=>{
 const a=ambiente();await assert.rejects(a.pedir('/app/cartoes/65'));await assert.rejects(a.pedir(recurso,{body:'{' }));await assert.rejects(a.pedir(recurso,{body:'[]'}));assert.equal(a.chamadas.length,0);
});

test('método lógico não vira override HTTP vindo do formulário',async()=>{
 const a=ambiente(),body=new FormData();body.set('_method','PUT');await a.pedir({...recurso,metodo:'PUT'},{body});assert.equal(a.chamadas[0].init.method,'POST');assert.equal(a.chamadas[0].init.body.has('_method'),false);assert.equal(body.get('_method'),'PUT');
});

test('reinstalação após hidratação restaura fetch e não encadeia wrappers antigos',async()=>{
 const a=ambiente();a.reiniciar();await a.window.G360Requisicao(recurso);assert.equal(a.chamadas.length,1);assert.equal(JSON.parse(a.chamadas[0].init.body)._recurso,recurso.referencia);
});

test('transporte não permite seguir redirect mesmo quando o chamador pede follow',async()=>{
 const a=ambiente();await a.pedir(recurso,{redirect:'follow',body:JSON.stringify({dado:'fictício'})});assert.equal(a.chamadas[0].init.redirect,'error');
});
