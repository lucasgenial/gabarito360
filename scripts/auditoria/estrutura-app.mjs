// Views reais renderizadas por EstruturaTailwindTest, somente dados fictícios.
import {chromium} from 'playwright-core';
import {readFileSync,existsSync} from 'node:fs';
import {resolve,join} from 'node:path';
import {tmpdir} from 'node:os';
import assert from 'node:assert/strict';

const base='http://estrutura.test',publico=resolve('apps/web/public');
const fixtures=process.env.TW2_FIXTURES||join(tmpdir(),'g360-tw2-render');
const browser=await chromium.launch({executablePath:process.env.CHROME_PATH,headless:true});
try{
  const contexto=await browser.newContext({viewport:{width:1440,height:650}});
  const erros=[],transporte=[];
  await contexto.route('**/*',async route=>{
    const req=route.request(),url=new URL(req.url());
    assert.equal(url.origin,base,'Nenhuma chamada externa no ensaio');
    if(req.method()!=='GET'){transporte.push(url.pathname);return route.fulfill({status:503,json:{message:'Transporte não disponível no ensaio visual'}});}
    const nome=url.pathname.match(/^\/(turmas|turma|editar)\.html$/)?.[1];
    if(nome){
      const html=readFileSync(join(fixtures,nome+'.html'),'utf8')
        .replaceAll('https://gabarito360.com.br',base).replaceAll('http://localhost',base)
        .replaceAll('https:\\/\\/gabarito360.com.br',base).replaceAll('http:\\/\\/localhost',base);
      return route.fulfill({contentType:'text/html',body:html});
    }
    const p=resolve(publico,'.'+url.pathname);
    if(!p.startsWith(publico+'/')&&!p.startsWith(publico+'\\'))return route.abort();
    if(!/^\/(css|js|fonts|brand|images)\//.test(url.pathname)||!existsSync(p))return route.fulfill({status:404,body:''});
    const tipo=p.endsWith('.css')?'text/css':p.endsWith('.js')?'application/javascript':p.endsWith('.svg')?'image/svg+xml':p.endsWith('.woff2')?'font/woff2':'image/png';
    return route.fulfill({contentType:tipo,body:readFileSync(p)});
  });
  const pagina=await contexto.newPage();pagina.on('pageerror',e=>erros.push(e.message));
  await pagina.goto(base+'/turmas.html');await pagina.evaluate(()=>document.fonts.ready);
  assert.equal(await pagina.locator('.marca-estrutura').count(),1);
  assert.equal(await pagina.locator('.lateral-app').evaluate(e=>e.getBoundingClientRect().width),256);
  const amplo=await pagina.locator('main').evaluate(e=>e.getBoundingClientRect().width);
  await pagina.getByRole('button',{name:'Recolher menu principal',exact:true}).click();
  assert.equal(await pagina.locator('.lateral-app').evaluate(e=>e.getBoundingClientRect().width),80);
  assert.equal(await pagina.locator('main').evaluate(e=>e.getBoundingClientRect().width)-amplo,176);
  await pagina.getByRole('button',{name:'Expandir menu principal',exact:true}).click();
  await pagina.getByRole('button',{name:'Abrir opções da conta',exact:true}).click();
  assert.equal(await pagina.getByText('pessoa@example.test',{exact:true}).isVisible(),true);
  await pagina.getByRole('button',{name:'Trocar vínculo',exact:true}).click();
  assert.equal(await pagina.locator('#troca-contexto-modal').evaluate(e=>e.open),true);
  await pagina.keyboard.press('Escape');
  assert.equal(await pagina.locator('#user-menu-trigger').evaluate(e=>e===document.activeElement),true);
  assert.equal(await pagina.locator('#user-menu').isHidden(),true);
  await pagina.getByRole('button',{name:'Cards',exact:true}).click();
  assert.equal(await pagina.locator('[data-painel-lista]').getAttribute('data-apresentacao'),'cards');
  assert.equal(await pagina.locator('.tabela-acoes').evaluate(e=>getComputedStyle(e).order),'99');
  await pagina.getByRole('button',{name:'Tabela',exact:true}).click();
  assert.equal(await pagina.locator('#turmas-table th').first().textContent(),'Ações');
  await pagina.getByRole('button',{name:'+ Nova turma',exact:true}).click();
  assert.equal(await pagina.locator('#modal-criar').getAttribute('aria-hidden'),'false');
  await pagina.keyboard.press('Escape');
  assert.equal(await pagina.locator('#modal-criar').getAttribute('aria-hidden'),'true');
  for(const arquivo of ['turmas','turma','editar']){
    await pagina.goto(base+'/'+arquivo+'.html');
    for(const largura of [1440,768,320,348,393,440]){
      await pagina.setViewportSize({width:largura,height:650});
      assert.equal(await pagina.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true,arquivo+' '+largura+' sem overflow');
      assert.equal(await pagina.locator('.cabecalho-app').evaluate(e=>e.getBoundingClientRect().height),64);
      if(largura<768){
        assert.equal(await pagina.locator('.lateral-app').isHidden(),true);
        assert.equal(await pagina.locator('.nav-mobile-app').isVisible(),true);
        await pagina.getByRole('button',{name:'Mais',exact:true}).click();
        assert.equal(await pagina.locator('#nav-folha').evaluate(e=>e.open),true);
        await pagina.keyboard.press('Escape');
        assert.equal(await pagina.locator('.nav-inferior-mais').evaluate(e=>e===document.activeElement),true);
        if(arquivo==='turmas')assert.equal(await pagina.locator('[data-painel-lista]').getAttribute('data-apresentacao'),'cards');
      }
    }
    await pagina.getByRole('button',{name:'Mudar para tema escuro',exact:true}).click();
    assert.equal(await pagina.locator('html').getAttribute('data-theme'),'dark');
    assert.equal(await pagina.locator('.marca-estrutura .g360-wordmark').evaluate(e=>getComputedStyle(e).color),'rgb(255, 255, 255)');
    await pagina.getByRole('button',{name:'Mudar para tema claro',exact:true}).click();
  }
  assert.deepEqual(erros,[]);
  assert.deepEqual(transporte,[]);
  console.log('TW2: views reais, reflow, conta/troca, Mais/foco, tabela/cards, modal, 6 larguras, temas e isolamento aprovados.');
}finally{await browser.close();}
