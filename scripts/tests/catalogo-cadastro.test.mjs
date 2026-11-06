import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {ambienteContextual} from './support/ambiente-contextual.mjs';
import {readFileSync} from 'node:fs';
const codigo=readFileSync(new URL('../../apps/web/public/js/catalogo-cadastro.js',import.meta.url),'utf8');
function montar(){
 const nomes=['uf','municipio','busca','status','proximos','localizar'];
 const dom=Object.fromEntries(nomes.map(k=>[k,{value:'',textContent:'',hidden:false,disabled:false,dataset:{},children:[],listeners:{},addEventListener(t,fn){this.listeners[t]=fn;},replaceChildren(...x){this.children=x;this.value='';},add(x){this.children.push(x);}}]));
 const box={dataset:{url:JSON.stringify({url:'/app/catalogo/atendimento/municipios/consultar',pagina:'/app/catalogo/atendimento/municipios',referencia:'referencia-cadastro-ficticia',metodo:'GET'})},querySelector(s){return dom[s.slice(6,-1)];}};
 const requisicoes=[];
 const contexto=ambienteContextual({document:{querySelectorAll:s=>s==='[data-catalogo-localizacao]'?[box]:[]},location:{origin:'http://localhost'},URL,Option:function(text,value){this.text=text;this.value=value;},fetch:(url,opts)=>new Promise(resolve=>requisicoes.push({url:new URL(url),opts,resolve}))});vm.runInContext(codigo,contexto);
 return {dom,requisicoes};
}
const tick=()=>new Promise(r=>setImmediate(r));
test('formulário limpa município e descarta resposta da UF anterior',async()=>{
 const {dom,requisicoes}=montar();dom.uf.value='BA';dom.uf.listeners.change();dom.uf.value='RO';dom.uf.listeners.change();
 assert.equal(requisicoes[0].opts.headers.get('X-Requested-With'),'XMLHttpRequest');assert.equal(dom.municipio.value,'');assert.equal(requisicoes[1].url.pathname,'/app/catalogo/atendimento/municipios/consultar');assert.equal(requisicoes[1].url.search,'');assert.equal(requisicoes[1].opts.method,'POST');const corpo=JSON.parse(requisicoes[1].opts.body);assert.equal(corpo.uf,'RO');assert.equal(corpo._recurso,'referencia-cadastro-ficticia');assert.equal(corpo._token,'csrf-ficticio');assert.equal(corpo._revisao,'revisao-ficticia');assert.equal(requisicoes[1].opts.redirect,'error');
 requisicoes[1].resolve({ok:true,json:async()=>({itens:[{nome:'Município RO',codigo_ibge:'1100001'}],paginacao:{atual:1,ultima:1}})});await tick();
 requisicoes[0].resolve({ok:true,json:async()=>({itens:[{nome:'Município BA',codigo_ibge:'2900001'}],paginacao:{atual:1,ultima:1}})});await tick();
 assert.equal(dom.municipio.children[1].text,'Município RO');assert.equal(dom.municipio.disabled,false);
});
test('erro de rede não cria falsa ausência e permite nova busca',async()=>{
 const {dom,requisicoes}=montar();dom.uf.value='BA';dom.localizar.listeners.click();requisicoes[0].resolve({ok:false});await tick();
 assert.match(dom.status.textContent,/Não foi possível/);assert.equal(dom.municipio.disabled,true);
 dom.localizar.listeners.click();assert.equal(requisicoes.length,2);
});
