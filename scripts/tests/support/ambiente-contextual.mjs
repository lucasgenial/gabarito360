import vm from 'node:vm';
import {readFileSync} from 'node:fs';
const transporte=readFileSync(new URL('../../../apps/web/public/js/contexto-aba.js',import.meta.url),'utf8');

/** DOM mínimo da fixture; executa o transporte de produção sem substituí-lo por um stub. */
export function ambienteContextual(contexto) {
  const doc=contexto.document, selecionar=doc.querySelector?.bind(doc);
  doc.readyState='loading';
  doc.querySelector=selector=>selector==='meta[name="g360-revisao"]'?{content:'revisao-ficticia'}:selector==='meta[name="csrf-token"]'?{content:'csrf-ficticio'}:selecionar?.(selector)||null;
  doc.addEventListener??=()=>{};
  contexto.location={href:'http://localhost/app/catalogo',origin:'http://localhost',...contexto.location};
  Object.assign(contexto,{Headers,Request,Response,FormData,URLSearchParams,AbortController,Event,URL,HTMLFormElement:class{submit(){}},addEventListener(){},removeEventListener(){},sessionStorage:{removeItem(){}}});
  contexto.window=contexto;
  vm.createContext(contexto);vm.runInContext(transporte,contexto);
  return contexto;
}
