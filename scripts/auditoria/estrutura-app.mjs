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
    if(/^\/ordenacao-(completa|parcial)\.html$/.test(url.pathname)){
      const completa=url.pathname.includes('completa');
      const linhas=Array.from({length:25},(_,i)=>{
        const notas=['10,0','2,7','2,7','—'],datas=['02/02/2026','31/01/2026','01/02/2026','—'];
        return `<tr data-teste="${i+1}"><td data-rotulo="Nome">Pessoa ${i+1}</td><td data-rotulo="Nota">${notas[i%4]}</td><td data-rotulo="Data">${datas[i%4]}</td><td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor"><form method="POST"><input name="_token" value="csrf-ficticio" type="hidden"><input type="checkbox" name="selecionados[]" value="${i+1}" checked><button type="button">Conferir</button></form></div></td></tr>`;
      }).join('');
      const tabela=`<table class="table tabela-padrao" id="teste-ordenacao"><thead><tr><th>Nome</th><th>Nota</th><th>Data</th><th class="tabela-coluna-acoes">Ações</th></tr></thead><tbody>${linhas}</tbody></table>`;
      const html=readFileSync(join(fixtures,'membros.html'),'utf8').replace(/<table\b[\s\S]*?<\/table>/,tabela)
        .replaceAll('https://gabarito360.com.br',base).replaceAll('http://localhost',base)
        .replace('data-colecao-completa="true"',`data-colecao-completa="${completa}"`);
      return route.fulfill({contentType:'text/html',body:html});
    }
    const nome=url.pathname.match(/^\/(turmas|turma|editar|membros)\.html$/)?.[1];
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
  const conferirRodape=async()=>{
    const movel=await pagina.evaluate(()=>innerWidth<768);
    assert.equal(await pagina.locator('.rodape-app').isVisible(),!movel);
    assert.match((await pagina.locator('.rodape-app').textContent()).trim(),/^Gabarito360 - v\d+\.\d+\.\d+ - Homologação$/u,'Rodapé usa o formato definido pelo responsável');
    if(movel){const espaco=await pagina.locator('.conteudo-app').evaluate(e=>parseFloat(getComputedStyle(e).paddingBottom));assert.ok(espaco>=56&&espaco<136,'Reserva mobile conserva o menu e retira a faixa do rodapé');return;}
    const medidas=await pagina.locator('.rodape-app').evaluate(e=>({x:e.getBoundingClientRect().left,y:e.getBoundingClientRect().top,largura:e.getBoundingClientRect().width,fim:e.getBoundingClientRect().bottom,viewport:innerWidth,altura:innerHeight,lateral:document.querySelector('.lateral-app').getBoundingClientRect().width,movel:innerWidth<768}));
    assert.equal(medidas.x,medidas.movel?0:medidas.lateral);
    assert.equal(Math.round(medidas.x+medidas.largura),medidas.viewport);
    assert.ok(medidas.y>=0 && medidas.fim<=medidas.altura,'Rodapé dentro da área visível');
  };
  await conferirRodape();
  await pagina.getByRole('button',{name:'Recolher menu principal',exact:true}).click();
  await conferirRodape();
  await pagina.getByRole('button',{name:'Expandir menu principal',exact:true}).click();
  await pagina.getByRole('button',{name:'Filtrar turmas',exact:true}).click();
  assert.equal(await pagina.locator('#filtros-turmas').evaluate(e=>e.open),true);
  const buscaInicial=await pagina.locator('#filtros-turmas-busca').inputValue();
  await pagina.getByLabel('Nome ou série',{exact:true}).fill('Rascunho descartado');
  await pagina.keyboard.press('Escape');
  await pagina.waitForFunction(()=>!document.getElementById('filtros-turmas').open && document.querySelector('[data-turmas-filtros-abrir]')===document.activeElement).catch(async erro=>{
    console.error(await pagina.evaluate(()=>({aberto:document.getElementById('filtros-turmas').open,foco:document.activeElement?.outerHTML.slice(0,500)})));
    throw erro;
  });
  assert.equal(await pagina.locator('[data-turmas-filtros-abrir]').evaluate(e=>e===document.activeElement),true);
  await pagina.getByRole('button',{name:'Filtrar turmas',exact:true}).click();
  assert.equal(await pagina.locator('#filtros-turmas-busca').inputValue(),buscaInicial);
  await pagina.getByLabel('Ano letivo',{exact:true}).fill('2025');
  const transporteInicial=await pagina.locator('#filtros-turmas input[type=hidden]').evaluateAll(itens=>itens.map(e=>[e.name,e.value]));
  await pagina.getByRole('button',{name:'Limpar campos',exact:true}).click();
  assert.equal(await pagina.locator('#filtros-turmas-ano').inputValue(),'');
  assert.deepEqual(await pagina.locator('#filtros-turmas input[type=hidden]').evaluateAll(itens=>itens.map(e=>[e.name,e.value])),transporteInicial);
  await pagina.getByRole('button',{name:'Cancelar',exact:true}).click();
  assert.equal(await pagina.locator('#filtros-turmas').evaluate(e=>e.open),false);
  await pagina.getByRole('button',{name:'Abrir opções da conta',exact:true}).click();
  assert.equal(await pagina.getByText('pessoa@example.test',{exact:true}).isVisible(),true);
  await pagina.getByRole('button',{name:'Trocar vínculo',exact:true}).click();
  assert.equal(await pagina.locator('#troca-contexto-modal').evaluate(e=>e.open),true);
  await pagina.keyboard.press('Escape');
  await pagina.waitForFunction(()=>!document.getElementById('troca-contexto-modal').open && document.activeElement?.id==='user-menu-trigger');
  assert.equal(await pagina.locator('#user-menu-trigger').evaluate(e=>e===document.activeElement),true);
  assert.equal(await pagina.locator('#user-menu').isHidden(),true);
  await pagina.getByRole('button',{name:'Cards',exact:true}).click();
  assert.equal(await pagina.locator('[data-painel-lista]').getAttribute('data-apresentacao'),'cards');
  assert.equal(await pagina.locator('.tabela-acoes').evaluate(e=>getComputedStyle(e).order),'99');
  await pagina.getByRole('button',{name:'Tabela',exact:true}).click();
  assert.equal(await pagina.locator('#turmas-table th').last().textContent(),'Ações');
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
      await conferirRodape();
      if(largura<768){
        assert.equal(await pagina.locator('.lateral-app').isHidden(),true);
        assert.equal(await pagina.locator('.nav-mobile-app').isVisible(),true);
        await pagina.getByRole('button',{name:'Mais',exact:true}).click();
        assert.equal(await pagina.locator('#nav-folha').evaluate(e=>e.open),true);
        await pagina.keyboard.press('Escape');
        await pagina.waitForFunction(()=>!document.getElementById('nav-folha').open && document.querySelector('.nav-inferior-mais')===document.activeElement);
        assert.equal(await pagina.locator('.nav-inferior-mais').evaluate(e=>e===document.activeElement),true);
        if(arquivo==='turmas')assert.equal(await pagina.locator('[data-painel-lista]').getAttribute('data-apresentacao'),'cards');
      }
    }
    await pagina.getByRole('button',{name:'Mudar para tema escuro',exact:true}).click();
    assert.equal(await pagina.locator('html').getAttribute('data-theme'),'dark');
    assert.equal(await pagina.locator('.cabecalho-app').evaluate(e=>getComputedStyle(e).backgroundColor),'rgb(24, 34, 52)');
    assert.equal(await pagina.locator('.lateral-app').evaluate(e=>getComputedStyle(e).backgroundColor),'rgb(24, 34, 52)');
    assert.equal(await pagina.locator('.marca-estrutura .g360-wordmark').evaluate(e=>getComputedStyle(e).color),'rgb(255, 255, 255)');
    await pagina.getByRole('button',{name:'Mudar para tema claro',exact:true}).click();
  }
  assert.deepEqual(erros,[]);
  // Equipe exercita grupos reais com links, formulários POST e vários estados/permissões.
  await pagina.goto(base+'/membros.html');
  for(const largura of [1440,1024,768,390,320]){
    await pagina.setViewportSize({width:largura,height:740});
    assert.equal(await pagina.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true,'Equipe sem overflow de página '+largura);
    assert.equal((await pagina.locator('table thead th').last().textContent()).trim(),'Ações');
    const linhas=await pagina.locator('table tbody tr').evaluateAll(itens=>itens.map(tr=>{
      const td=tr.querySelector('td.tabela-acoes'),r=td.getBoundingClientRect(),outros=[...tr.children].filter(e=>e!==td).map(e=>e.getBoundingClientRect());
      return {ultima:tr.lastElementChild===td,direita:outros.every(e=>e.right<=r.left+1),botoes:[...td.querySelectorAll('a,button')].map(b=>{const v=b.getBoundingClientRect();return {texto:b.textContent.trim(),integro:b.scrollWidth<=b.clientWidth+1,dentro:v.left>=r.left-1&&v.right<=r.right+1,altura:v.height};})};
    }));
    for(const l of linhas){assert.ok(l.ultima);if(largura>720)assert.ok(l.direita,'Ações à direita dos dados');for(const b of l.botoes){assert.ok(b.integro,b.texto+' íntegro '+largura);assert.ok(b.dentro,b.texto+' dentro da coluna '+largura);assert.ok(b.altura>=32);}}
  }
  for(const modo of ['completa','parcial']){
    await pagina.setViewportSize({width:1440,height:740});await pagina.goto(base+'/ordenacao-'+modo+'.html');
    assert.equal(await pagina.locator('#teste-ordenacao th .tabela-ordenar').count(),3);
    assert.equal(await pagina.locator('#teste-ordenacao th:last-child button').count(),0);
    const linhas=pagina.locator('#teste-ordenacao tbody tr');
    await pagina.getByRole('button',{name:'Ordenar por Nota em ordem crescente',exact:true}).click();
    assert.equal(await linhas.first().getAttribute('data-teste'),'2');assert.equal(await linhas.nth(1).getAttribute('data-teste'),'3','Empates estáveis');
    assert.equal(await linhas.last().getAttribute('data-teste'),'24','Ausentes por último');
    assert.equal(await pagina.locator('th[aria-sort=ascending]').textContent(),'Nota');
    assert.equal(await linhas.evaluateAll(itens=>itens.filter(e=>!e.hidden&&e.getBoundingClientRect().height>0).length),modo==='completa'?20:25);
    await pagina.getByRole('button',{name:'Ordenar por Nota em ordem decrescente',exact:true}).press('Space');
    assert.equal(await linhas.first().getAttribute('data-teste'),'1','10 é maior que 2,7');
    await pagina.getByRole('button',{name:'Ordenar por Data em ordem crescente',exact:true}).click();
    assert.equal(await linhas.first().getAttribute('data-teste'),'2','31 de janeiro antes de 1 de fevereiro');
    assert.equal(await pagina.locator('th[aria-sort]').count(),1);
    assert.equal(await pagina.locator('#teste-ordenacao input[name="selecionados[]"]:checked').count(),25);
    assert.equal(await pagina.locator('#teste-ordenacao input[name="_token"][value="csrf-ficticio"]').count(),25);
    assert.equal(await pagina.locator('#teste-ordenacao button:not(.tabela-ordenar)').count(),25);
    if(modo==='parcial')assert.match(await pagina.locator('[data-tabela-ordenacao-status]').textContent(),/^Ordenação dos registros desta página/);
    await pagina.setViewportSize({width:390,height:740});await pagina.locator('[data-tabela-ordem]').selectOption('0:desc');
    assert.equal(await linhas.first().getAttribute('data-teste'),'25','Cards seguem a mesma ordenação');
  }
  assert.deepEqual(transporte,[]);
  console.log('TW2: views reais, reflow, conta/troca, Mais/foco, tabela/cards, modal, 6 larguras, temas e isolamento aprovados.');
}finally{await browser.close();}
