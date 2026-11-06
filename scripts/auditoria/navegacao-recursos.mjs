// UR4: produto real, HTTP somente fictício interceptado; sem servidor.
import {chromium} from 'playwright-core';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
const A='a'.repeat(48),B='b'.repeat(48),C='c'.repeat(48),E1='epoca-ficticia-a',E2='epoca-ficticia-b';
const destino=ref=>({url:'/app/turmas/detalhes/consultar',pagina:'/app/turmas/detalhes',referencia:ref,metodo:'GET'});
const index={url:'/app/turmas/consultar',pagina:'/app/turmas',referencia:C,metodo:'GET'};
const esc=v=>String(v).replaceAll('&','&amp;').replaceAll('"','&quot;');
let ativa=E1;const requests=[],erros=[];
const doc=(conteudo,extra='')=>`<!doctype html><html><head><meta charset="utf-8"><meta name="g360-revisao" content="${ativa}"><meta name="csrf-token" content="csrf-ficticio">${extra}<script src="/js/contexto-aba.js" defer></script><script src="/js/navegacao.js" defer></script></head><body><main>${conteudo}</main></body></html>`;
const links=()=>`<a id="a" href="/app/turmas/detalhes" data-recurso="${esc(JSON.stringify(destino(A)))}">Turma A</a><a id="b" href="/app/turmas/detalhes" data-recurso="${esc(JSON.stringify(destino(B)))}">Turma B</a><button id="nova" onclick='G360Navegacao.abrir(${JSON.stringify(destino(B))},{novaAba:true})'>Nova aba B</button>`;
function campos(r){const raw=r.postData()||'';if(r.headers()['content-type']?.includes('application/json'))return JSON.parse(raw);if(r.headers()['content-type']?.includes('multipart/form-data'))return Object.fromEntries([...raw.matchAll(/name="([^"\r\n]+)"\r\n\r\n([^\r]*)/g)].map(m=>[m[1],m[2]]));return Object.fromEntries(new URLSearchParams(raw));}
try{
 const c=await browser.newContext();
 await c.route('**/*',async route=>{
  const r=route.request(),u=new URL(r.url());assert.equal(u.origin,'http://g360.test');
  if(u.pathname.startsWith('/js/'))return route.fulfill({contentType:'application/javascript',body:readFileSync('apps/web/public'+u.pathname)});
  assert.equal(u.search,'');assert.equal(u.hash,'');assert.ok(!/[0-9]/.test(u.pathname));
  const d=campos(r);requests.push({path:u.pathname,metodo:r.method(),nativo:r.isNavigationRequest(),d});
  if(r.method()==='GET'){
   assert.ok(['/app/turmas','/app/turmas/detalhes','/app/painel'].includes(u.pathname));
   const detalhe=u.pathname==='/app/turmas/detalhes';return route.fulfill({contentType:'text/html',body:doc(`<section data-navegacao-estrutura data-exige-selecao="${detalhe?'1':'0'}" data-consulta="${detalhe?destino(A).url:index.url}"><div data-navegacao-status>Carregando</div></section>`)});
  }
  assert.equal(r.method(),'POST');assert.equal(d._token,'csrf-ficticio');assert.ok(!('id' in d)&&!('controller' in d)&&!('api_url' in d));
  if(d._revisao!==ativa||(r.headers()['x-g360-revisao']&&r.headers()['x-g360-revisao']!==ativa))return route.fulfill({status:409,json:{acesso_alterado:true}});
  const alvo=d._recurso||C;assert.ok([A,B,C].includes(alvo));const des=alvo===C?index:destino(alvo);
  if(r.isNavigationRequest())return route.fulfill({contentType:'text/html',body:`<!doctype html><html><head><meta name="g360-revisao" content="${ativa}"><script src="/js/navegacao.js" defer></script></head><body><div data-navegacao-redirecionar data-recurso="${esc(JSON.stringify(des))}"><section>Abrindo</section></div></body></html>`});
  assert.equal(r.headers()['x-g360-hidratar'],'1');const titulo=alvo===A?'Turma A':alvo===B?'Turma B':'Lista de turmas';
  return route.fulfill({contentType:'text/html',body:doc(`<h1>${titulo}</h1>${links()}`,`<meta name="g360-pagina-renderizada" content="${esc(JSON.stringify(des))}">`)});
 });
 const pagina=async()=>{const p=await c.newPage();p.on('pageerror',e=>erros.push(e.message));return p;};
 const a=await pagina(),b=await pagina();const titulo=async(p,n)=>p.getByRole('heading',{name:n,exact:true}).waitFor();
 await a.goto('http://g360.test/app/turmas');await b.goto('http://g360.test/app/turmas');await titulo(a,'Lista de turmas');await titulo(b,'Lista de turmas');
 await a.locator('#a').click();await titulo(a,'Turma A');await b.locator('#b').click();await titulo(b,'Turma B');
 assert.equal(a.url(),b.url());assert.equal(a.url(),'http://g360.test/app/turmas/detalhes');
 assert.equal(await a.evaluate(()=>history.state.ur4.destino.referencia),A);assert.equal(await b.evaluate(()=>history.state.ur4.destino.referencia),B);
 await a.reload();await titulo(a,'Turma A');await a.locator('#b').click();await titulo(a,'Turma B');
 await a.goBack();await titulo(a,'Turma A');await a.goForward();await titulo(a,'Turma B');
 assert.equal(requests.filter(r=>r.nativo&&r.metodo==='POST').length,0,'Histórico não repete POST nativo');
 const [nova]=await Promise.all([c.waitForEvent('page'),a.locator('#nova').click()]);nova.on('pageerror',e=>erros.push(e.message));
 await nova.waitForURL('http://g360.test/app/turmas/detalhes');await titulo(nova,'Turma B');assert.equal(await nova.evaluate(()=>history.state.ur4.destino.referencia),B);
 const nativePosts=requests.filter(r=>r.nativo&&r.metodo==='POST').length;assert.equal(nativePosts,1);
 await nova.reload();await titulo(nova,'Turma B');assert.equal(requests.filter(r=>r.nativo&&r.metodo==='POST').length,nativePosts);
 const vazia=await pagina();await vazia.goto('http://g360.test/app/turmas/detalhes');await vazia.getByText('Abra este registro pela lista para continuar.',{exact:true}).waitFor();
 const n=requests.length;ativa=E2;await b.reload();await b.getByText('Confira seu acesso e abra o registro novamente pela lista.',{exact:true}).waitFor();
 assert.equal(requests.slice(n).filter(r=>r.metodo==='POST').at(-1).d._revisao,E1,'Shell nova não renova época antiga');
 assert.equal(await b.locator('h1').textContent(),'Não foi possível abrir esta tela');
 for(const r of requests){assert.ok(!r.path.includes(A)&&!r.path.includes(B));assert.ok(!r.path.includes('token'));}assert.deepEqual(erros,[]);
 console.log('UR4:2abas, refresh/back/forward sem replay, ponte nova aba GET real, falta de seleção e stale409: OK.');
}finally{await browser.close();}
