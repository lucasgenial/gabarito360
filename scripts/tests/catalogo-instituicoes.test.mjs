import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import {ambienteContextual} from './support/ambiente-contextual.mjs';
import {readFileSync} from 'node:fs';
const codigo=readFileSync(new URL('../../apps/web/public/js/catalogo-instituicoes.js',import.meta.url),'utf8');
function montar() {
  const ids=['catalogo-busca','cat-uf','cat-municipio','cat-municipio-busca','cat-status','cat-anterior','cat-proximo','cat-carregar','cat-resultados','cat-resultados-status'];
  const dom=Object.fromEntries(ids.map(id=>[id,{value:'',textContent:'',hidden:false,disabled:false,children:[],listeners:{},
    addEventListener(tipo,fn){this.listeners[tipo]=fn;},replaceChildren(...o){this.children=o;this.value='';},add(o){this.children.push(o);}}]));
  dom['catalogo-busca'].dataset={municipios:JSON.stringify({url:'/app/minhas-instituicoes/municipios/consultar',pagina:'/app/minhas-instituicoes/municipios',referencia:'referencia-instituicoes-ficticia',metodo:'GET'})};
  const requisicoes=[];
  const contexto=ambienteContextual({document:{getElementById:id=>dom[id]},location:{origin:'http://localhost',href:'http://localhost/app/minhas-instituicoes/buscar'},URL,
    Option:function(text,value){this.text=text;this.value=value;},fetch:(url,init)=>new Promise(resolve=>requisicoes.push({url:new URL(url),init,resolve}))});vm.runInContext(codigo,contexto);
  return {dom,requisicoes};
}
const tick=()=>new Promise(resolve=>setImmediate(resolve));
test('trocar UF limpa seleção e descarta resposta atrasada de outra UF',async()=>{
  const {dom,requisicoes}=montar();
  dom['cat-municipio'].value='2900001';dom['cat-uf'].value='BA';dom['cat-uf'].listeners.change();
  assert.equal(dom['cat-municipio'].value,'');
  dom['cat-uf'].value='RO';dom['cat-uf'].listeners.change();
  assert.equal(requisicoes[1].url.pathname,'/app/minhas-instituicoes/municipios/consultar');
  assert.equal(requisicoes[1].url.search,'');assert.equal(requisicoes[1].init.method,'POST');
  const corpo=JSON.parse(requisicoes[1].init.body);assert.equal(corpo.uf,'RO');assert.equal(corpo._recurso,'referencia-instituicoes-ficticia');assert.equal(corpo._token,'csrf-ficticio');assert.equal(corpo._revisao,'revisao-ficticia');assert.equal(requisicoes[1].init.redirect,'error');
  requisicoes[1].resolve({ok:true,json:async()=>({itens:[{nome:'Município RO',codigo_ibge:'1100001'}],paginacao:{total:1,ultima:1}})});await tick();
  requisicoes[0].resolve({ok:true,json:async()=>({itens:[{nome:'Município BA',codigo_ibge:'2900001'}],paginacao:{total:1,ultima:1}})});await tick();
  assert.equal(dom['cat-municipio'].children[1].text,'Município RO');assert.equal(dom['cat-municipio'].disabled,false);
  dom['catalogo-busca'].listeners.change({target:{name:'uf'}});assert.equal(dom['cat-resultados'].hidden,true);
});
test('falha de rede mostra indisponibilidade e permite tentar novamente',async()=>{
  const {dom,requisicoes}=montar();dom['cat-uf'].value='BA';dom['cat-carregar'].listeners.click();
  requisicoes[0].resolve({ok:false});await tick();
  assert.match(dom['cat-status'].textContent,/Não foi possível/);assert.doesNotMatch(dom['cat-status'].textContent,/Nenhum município/);
  assert.equal(dom['cat-municipio'].disabled,false);
});
