// UR4: produto real, HTTP somente fictício interceptado; sem servidor.
import {chromium} from 'playwright-core';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
const A='a'.repeat(48),B='b'.repeat(48),C='c'.repeat(48),D='d'.repeat(48),E1='epoca-ficticia-a',E2='epoca-ficticia-b';
const destino=ref=>({url:'/app/turmas/detalhes/consultar',pagina:'/app/turmas/detalhes',referencia:ref,metodo:'GET'});
const index={url:'/app/turmas/consultar',pagina:'/app/turmas',referencia:C,metodo:'GET'};
const painel={url:'/app/painel/consultar',pagina:'/app/painel',referencia:D,metodo:'GET'};
const esc=v=>String(v).replaceAll('&','&amp;').replaceAll('"','&quot;');
let ativa=E1,modoSessao='ok';const requests=[],erros=[];
const doc=(conteudo,extra='')=>`<!doctype html><html><head><meta charset="utf-8"><meta name="g360-revisao" content="${ativa}"><meta name="csrf-token" content="csrf-ficticio">${extra}<script src="/js/contexto-aba.js" defer></script><script src="/js/navegacao.js" defer></script></head><body><main>${conteudo}</main></body></html>`;
const links=()=>`<a id="a" href="/app/turmas/detalhes" data-recurso="${esc(JSON.stringify(destino(A)))}">Turma A</a><a id="b" href="/app/turmas/detalhes" data-recurso="${esc(JSON.stringify(destino(B)))}">Turma B</a><button id="nova" onclick='G360Navegacao.abrir(${JSON.stringify(destino(B))},{novaAba:true})'>Nova aba B</button>`;
function campos(r){const raw=r.postData()||'';if(r.headers()['content-type']?.includes('application/json'))return JSON.parse(raw);if(r.headers()['content-type']?.includes('multipart/form-data'))return Object.fromEntries([...raw.matchAll(/name="([^"\r\n]+)"\r\n\r\n([^\r]*)/g)].map(m=>[m[1],m[2]]));return Object.fromEntries(new URLSearchParams(raw));}
try{
 const c=await browser.newContext();
 await c.route('**/*',async route=>{
  const r=route.request(),u=new URL(r.url());assert.equal(u.origin,'http://g360.test');
  if(u.pathname==='/fixture/acesso-confirmado.js')return route.fulfill({contentType:'application/javascript',body:"location.replace('/app/painel');"});
  if(u.pathname.startsWith('/js/'))return route.fulfill({contentType:'application/javascript',body:readFileSync('apps/web/public'+u.pathname)});
  assert.equal(u.search,'');assert.equal(u.hash,'');assert.ok(!/[0-9]/.test(u.pathname));
  const d=campos(r);requests.push({path:u.pathname,metodo:r.method(),nativo:r.isNavigationRequest(),d});
  if(r.method()==='GET'){
   if(u.pathname==='/app/vinculos')return route.fulfill({contentType:'text/html',body:doc(`<section data-selecao-acesso><h1>Escolha seu vínculo</h1><form method="POST" action="/app/vinculos/selecionar"><input type="hidden" name="_token" value="csrf-ficticio"><input type="hidden" name="_revisao" value="${ativa}"><button>Entrar no vínculo</button></form></section>`)});
   if(u.pathname==='/login')return route.fulfill({contentType:'text/html',body:doc('<h1>Acesso ao sistema</h1>')});
   assert.ok(['/app/turmas','/app/turmas/detalhes','/app/painel'].includes(u.pathname));
   const detalhe=u.pathname==='/app/turmas/detalhes';return route.fulfill({contentType:'text/html',body:doc(`<section data-navegacao-estrutura data-exige-selecao="${detalhe?'1':'0'}" data-consulta="${detalhe?destino(A).url:u.pathname==='/app/painel'?painel.url:index.url}"><div data-navegacao-status>Carregando</div></section>`)});
  }
  assert.equal(r.method(),'POST');assert.equal(d._token,'csrf-ficticio');assert.ok(!('id' in d)&&!('controller' in d)&&!('api_url' in d));
  if(u.pathname==='/app/vinculos/selecionar'){assert.equal(d._revisao,ativa);ativa='epoca-ficticia-c';modoSessao='ok';return route.fulfill({contentType:'text/html',body:'<!doctype html><html><head><script src="/fixture/acesso-confirmado.js"></script></head><body>Acesso confirmado</body></html>'});}
  if(d._revisao!==ativa||(r.headers()['x-g360-revisao']&&r.headers()['x-g360-revisao']!==ativa))return route.fulfill({status:409,json:{acesso_alterado:true}});
  if(modoSessao==='sem-contexto')return route.fulfill({status:409,json:{selecionar_vinculo:true,url:'https://example.invalid/ignorar'}});
  if(modoSessao==='expirada')return route.fulfill({status:401,json:{login_necessario:true,url:'https://example.invalid/ignorar'}});
  if(modoSessao==='csrf-expirado')return route.fulfill({status:419,contentType:'text/html',body:'<!doctype html><html><body>Page expired</body></html>'});
  if(modoSessao==='ambos')return route.fulfill({status:409,json:{acesso_alterado:true,selecionar_vinculo:true}});
  const alvo=d._recurso||(u.pathname===painel.url?D:C);assert.ok([A,B,C,D].includes(alvo));const des=alvo===D?painel:alvo===C?index:destino(alvo);
  if(r.isNavigationRequest())return route.fulfill({contentType:'text/html',body:`<!doctype html><html><head><meta name="g360-revisao" content="${ativa}"><script src="/js/navegacao.js" defer></script></head><body><div data-navegacao-redirecionar data-recurso="${esc(JSON.stringify(des))}"><section>Abrindo</section></div></body></html>`});
  assert.equal(r.headers()['x-g360-hidratar'],'1');const titulo=alvo===A?'Turma A':alvo===B?'Turma B':alvo===D?'Painel da escola':'Lista de turmas';
  return route.fulfill({contentType:'text/html',body:doc(`<h1>${titulo}</h1>${links()}`,`<meta name="g360-pagina-renderizada" content="${esc(JSON.stringify(des))}">`)});
 });
 const pagina=async()=>{const p=await c.newPage();p.setDefaultTimeout(10000);p.setDefaultNavigationTimeout(10000);p.on('pageerror',e=>erros.push(e.message));return p;};
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
 const n=requests.length;ativa=E2;await b.reload();await b.getByText('Seu acesso ativo mudou. Esta tela não foi aberta. Selecione o vínculo para continuar.',{exact:true}).waitFor();await b.getByRole('link',{name:'Selecionar vínculo',exact:true}).waitFor();assert.equal(b.url(),'http://g360.test/app/turmas/detalhes');
 assert.equal(requests.slice(n).filter(r=>r.metodo==='POST').at(-1).d._revisao,E1,'Shell nova não renova época antiga');
 assert.equal(await b.locator('h1').textContent(),'Não foi possível abrir esta tela');
 const nativoAntes=requests.filter(r=>r.nativo&&r.metodo==='POST').length;
 modoSessao='sem-contexto';const semContexto=await pagina();await semContexto.goto('http://g360.test/app/turmas');await titulo(semContexto,'Escolha seu vínculo');assert.equal(semContexto.url(),'http://g360.test/app/vinculos');
 modoSessao='expirada';const expirada=await pagina();await expirada.goto('http://g360.test/app/turmas');await titulo(expirada,'Acesso ao sistema');assert.equal(expirada.url(),'http://g360.test/login');
 modoSessao='csrf-expirado';const csrfExpirado=await pagina();await csrfExpirado.goto('http://g360.test/app/turmas');await titulo(csrfExpirado,'Acesso ao sistema');assert.equal(csrfExpirado.url(),'http://g360.test/login');
 modoSessao='ambos';const conflito=await pagina();await conflito.goto('http://g360.test/app/turmas');await conflito.getByRole('link',{name:'Selecionar vínculo',exact:true}).waitFor();assert.equal(conflito.url(),'http://g360.test/app/turmas');
 assert.equal(requests.filter(r=>r.nativo&&r.metodo==='POST').length,nativoAntes,'Recuperação não repete formulário nem arquivo');
 // O302 real é coberto porPHP/CUA; a ponte fictícia evita DNS do redirect não interceptado pelo Playwright.
 modoSessao='ok';const recuperar=await pagina();
 await recuperar.addInitScript(({destino,antiga})=>{if(location.pathname==='/app/vinculos'){const estado={destino,revisao:antiga};sessionStorage.setItem('g360-ur4-pendente',JSON.stringify(estado));history.replaceState({ur4:estado},'',location.pathname);}},{destino:painel,antiga:E1});
 console.log('Recuperação: preparando seleção com transição antiga');
 await recuperar.goto('http://g360.test/app/vinculos');await titulo(recuperar,'Escolha seu vínculo');
 assert.equal(await recuperar.evaluate(()=>sessionStorage.getItem('g360-ur4-pendente')),null,'Seleção descarta handoff abandonado');assert.equal(await recuperar.evaluate(()=>history.state),null);
 console.log('Recuperação: transição antiga descartada');
 const selecoesAntes=requests.filter(r=>r.path==='/app/vinculos/selecionar').length;
 await recuperar.getByRole('button',{name:'Entrar no vínculo',exact:true}).click();await titulo(recuperar,'Painel da escola');
 console.log('Recuperação: painel aberto após uma seleção');
 assert.equal(recuperar.url(),'http://g360.test/app/painel');assert.equal(requests.filter(r=>r.path==='/app/vinculos/selecionar').length,selecoesAntes+1,'Uma única seleção recupera o painel');
 assert.equal(await recuperar.evaluate(()=>history.state.ur4.revisao),'epoca-ficticia-c');
 await b.reload();await b.getByRole('link',{name:'Selecionar vínculo',exact:true}).waitFor();assert.equal(b.url(),'http://g360.test/app/turmas/detalhes');
 assert.equal(requests.filter(r=>r.path===destino(B).url).at(-1).d._revisao,E1,'Recuperar uma aba não autoriza nem renova a outra');
 for(const r of requests){assert.ok(!r.path.includes(A)&&!r.path.includes(B));assert.ok(!r.path.includes('token'));}assert.deepEqual(erros,[]);
 console.log('UR4:2abas, refresh/back/forward sem replay, ponte nova aba GET real, falta de seleção e stale409: OK.');
}finally{await browser.close();}
