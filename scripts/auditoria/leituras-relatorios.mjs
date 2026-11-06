import {chromium} from 'playwright-core';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import {imagemRaster} from './revisao-modal-fixture.mjs';

const browser = await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try {
  const raster=await imagemRaster(browser);const c = await browser.newContext();
  let ativos=0,pico=0,falhar=true,consultas=[],soltar;
  const pendentes=[];
  const descritor=(n,versao=1)=>({url:'/app/cartoes/miniatura/consultar',pagina:'/app/cartoes/miniatura',referencia:n.toString(16).padStart(48,'0'),metodo:'GET',versao});
  const botao=n=>`<button data-miniatura-cartao="${n}" data-miniatura-versao="1" data-miniatura-url='${JSON.stringify(descritor(n))}'><img data-miniatura-imagem><span data-miniatura-carga>Carregando</span><span data-miniatura-erro>Tentar novamente</span></button>`;
  const thumbs=`<meta name="g360-revisao" content="revisao-ficticia-ci"><meta name="csrf-token" content="csrf-ficticio"><script src="/js/contexto-aba.js"></script><main id="root">${Array.from({length:6},(_,n)=>botao(n+1)).join('')}</main><script src="/js/cartoes-miniaturas.js"></script><script>window.thumbs=criarMiniaturasCartoes(document.getElementById('root'),()=>{});thumbs.atualizar();</script>`;
  const validar=`<form id="documento-validacao" action="/validar"><input name="codigo" value="G360-${'A'.repeat(32)}"><input name="_token" value="ficticio"><input id="arquivo-documento" type="file"><button>Conferir</button></form><div id="documento-status" hidden></div><script src="/js/validacao-documento.js"></script>`;
  await c.route('**/*',async r=>{
    const u=new URL(r.request().url());
    if(u.hostname!=='localhost')return r.abort();
    if(u.pathname==='/miniaturas')return r.fulfill({contentType:'text/html',body:thumbs});
    if(u.pathname==='/conferir')return r.fulfill({contentType:'text/html',body:validar});
    if(u.pathname.startsWith('/js/'))return r.fulfill({contentType:'text/javascript',body:readFileSync('apps/web/public'+u.pathname)});
    if(u.pathname==='/app/cartoes/miniatura/consultar'){
      assert.equal(u.search,'');assert.equal(r.request().method(),'POST');const dados=r.request().postDataJSON();const numero=parseInt(dados._recurso,16);assert.ok(numero>=1&&numero<=6);assert.equal(dados._token,'csrf-ficticio');assert.equal(r.request().headers()['x-g360-revisao'],'revisao-ficticia-ci');ativos++;pico=Math.max(pico,ativos);
      await new Promise(resolve=>pendentes.push(resolve));ativos--;
      return r.fulfill(numero===1&&falhar?{status:503}:{contentType:'image/png',body:raster});
    }
    if(u.pathname==='/validar'){
      consultas.push(r.request().postDataJSON());
      await new Promise(resolve=>{soltar=resolve;});
      return r.fulfill({json:{data:{estado:consultas.at(-1).sha256?'arquivo_confirmado':'emissao_encontrada'}}});
    }
    return r.abort();
  });
  const p=await c.newPage(); const erros=[];p.on('pageerror',e=>erros.push(e.message));
  await p.goto('http://localhost/miniaturas',{waitUntil:'domcontentloaded'});
  await p.waitForFunction(()=>document.querySelectorAll('[aria-busy="true"]').length===6);
  await p.waitForTimeout(100);assert.equal(ativos,4);
  while(pendentes.length||ativos){pendentes.splice(0).forEach(f=>f());await p.waitForTimeout(70);}
  await p.locator('[data-estado="erro"]').waitFor();assert.equal(pico,4);
  assert.equal(await p.locator('[data-estado="pronta"]').count(),5);
  falhar=false;await p.locator('[data-estado="erro"]').click();
  await p.waitForTimeout(70);pendentes.splice(0).forEach(f=>f());
  await p.waitForFunction(()=>document.querySelectorAll('[data-estado="pronta"]').length===6);
  // Polling com URL renovada reutiliza a mesma versão de imagem já carregada.
  await p.evaluate(html=>{document.getElementById('root').innerHTML=html;thumbs.atualizar();},Array.from({length:6},(_,n)=>botao(n+1)).join('').replaceAll('\"versao\":1','\"versao\":2'));
  assert.equal(await p.locator('[data-estado="pronta"]').count(),6);assert.equal(ativos,0);
  await p.goto('http://localhost/conferir');
  const bytes=Buffer.from('%PDF-1.4\nArquivo fictício para hash local.');
  await p.locator('#arquivo-documento').setInputFiles({name:'ficticio.pdf',mimeType:'application/pdf',buffer:bytes});
  await p.getByRole('button',{name:'Conferir',exact:true}).click();
  await p.waitForFunction(()=>document.getElementById('documento-validacao').getAttribute('aria-busy')==='true');
  assert.equal(await p.locator('[name="codigo"]').isDisabled(),true);
  assert.equal(await p.locator('#arquivo-documento').isDisabled(),true);
  while(!soltar)await p.waitForTimeout(20);soltar();
  await p.locator('[data-estado="arquivo_confirmado"]').waitFor();
  assert.deepEqual(Object.keys(consultas[0]).sort(),['codigo','sha256']);
  assert.equal(consultas[0].sha256,createHash('sha256').update(bytes).digest('hex'));
  assert.equal(await p.locator('[name="codigo"]').isEnabled(),true);
  assert.deepEqual(erros,[]);
  console.log('OMR3: fila4, erro/retry regional, polling preservado, hash local sem upload e bloqueio de seleção durante consulta: OK.');
} finally {await browser.close();}
