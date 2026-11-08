import {chromium} from 'playwright-core';
import {createServer} from 'node:http';
import {readFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import assert from 'node:assert/strict';

const arquivo=valor=>fileURLToPath(new URL(valor,import.meta.url));
const rotas=new Map([
  ['/previas/tailwind/index.html',['text/html','../../apps/web/public/previas/tailwind/index.html']],
  ['/previas/tailwind/interface.css',['text/css','../../apps/web/public/previas/tailwind/interface.css']],
  ['/previas/tailwind/interface.js',['text/javascript','../../apps/web/public/previas/tailwind/interface.js']],
  ['/brand/gabarito360-icon.svg',['image/svg+xml','../../apps/web/public/brand/gabarito360-icon.svg']],
  ['/brand/gabarito360-icon-dark.svg',['image/svg+xml','../../apps/web/public/brand/gabarito360-icon-dark.svg']],
  ['/images/avatares/educadores-v1.png',['image/png','../../apps/web/public/images/avatares/educadores-v1.png']],
  ['/brand/gabarito360-email.png',['image/png','../../apps/web/public/brand/gabarito360-email.png']],
  ['/brand/gabarito360-email-dark.png',['image/png','../../apps/web/public/brand/gabarito360-email-dark.png']],
  ['/fonts/brand/Figtree-ExtraBold.woff2',['font/woff2','../../apps/web/public/fonts/brand/Figtree-ExtraBold.woff2']],
  ['/fonts/brand/AlbertSans-ExtraBold.woff2',['font/woff2','../../apps/web/public/fonts/brand/AlbertSans-ExtraBold.woff2']],
  ['/favicon.ico',['image/x-icon','../../apps/web/public/favicon.ico']],
]);
const servidor=createServer((req,res)=>{
  const rota=rotas.get(new URL(req.url,'http://localhost').pathname);
  if(!rota){res.writeHead(404);res.end();return;}
  res.setHeader('Content-Type',rota[0]);res.end(readFileSync(arquivo(rota[1])));
});
await new Promise(resolve=>servidor.listen(0,'127.0.0.1',resolve));
const base='http://127.0.0.1:'+servidor.address().port;
let navegador;
try{
  navegador=await chromium.launch({executablePath:process.env.CHROME_PATH||'/usr/bin/google-chrome',headless:true,args:['--no-sandbox']});
  const contexto=await navegador.newContext({viewport:{width:1440,height:1000}});
  const pagina=await contexto.newPage(),erros=[],requests=[];
  pagina.on('pageerror',erro=>erros.push(erro.message));
  pagina.on('request',r=>requests.push({url:r.url(),metodo:r.method()}));
  await pagina.goto(base+'/previas/tailwind/index.html');
  assert.equal(await pagina.locator('form').count(),0);
  assert.equal(await pagina.locator('#marca-cabecalho .marca-simbolo svg:visible').count(),1);
  await pagina.evaluate(()=>document.fonts.ready);
  assert.equal(await pagina.locator('.marca-nome').evaluate(e=>getComputedStyle(e).fontSize),'18px');
  assert.equal(await pagina.locator('.marca-simbolo').evaluate(e=>e.getBoundingClientRect().width),32);
  assert.equal(await pagina.locator('#menu-lateral img').count(),0);
  const larguraExpandida=await pagina.locator('main').evaluate(e=>e.getBoundingClientRect().width);
  await pagina.getByRole('button',{name:'Recolher menu principal',exact:true}).click();
  assert.equal(await pagina.locator('main').evaluate(e=>e.inert),false);
  assert.equal(await pagina.locator('#fundo-menu').count(),0);
  assert.equal(await pagina.locator('#menu-lateral').evaluate(e=>e.getBoundingClientRect().width),80);
  assert.equal(Math.round(await pagina.locator('main').evaluate(e=>e.getBoundingClientRect().width)-larguraExpandida),176);
  await pagina.getByRole('button',{name:'Expandir menu principal',exact:true}).click();
  assert.equal(await pagina.locator('#menu-lateral').evaluate(e=>e.getBoundingClientRect().width),256);
  assert.equal(await pagina.locator('#meu-espaco').count(),1);
  assert.equal(await pagina.locator('#espaco-lateral-experimento').isVisible(),true);
  assert.equal(await pagina.locator('#espaco-lateral-experimento details,#espaco-lateral-experimento summary').count(),0);
  assert.equal(await pagina.locator('#espaco-lateral-experimento dl').isVisible(),true);
  assert.equal(await pagina.locator('#menu-lateral').evaluate(e=>getComputedStyle(e).backgroundColor),'rgb(255, 255, 255)');
  await pagina.locator('#meu-espaco summary').click();
  assert.equal(await pagina.locator('#contexto-espaco').evaluate(e=>Math.abs(e.getBoundingClientRect().width-e.closest('.faixa-espaco').getBoundingClientRect().width)<1),true);
  await pagina.locator('#meu-espaco summary').click();
  assert.equal(await pagina.locator('#registros thead th').last().textContent(),'Ações');
  assert.equal(await pagina.locator('#registros tbody tr').first().locator('td').last().getAttribute('class'),'celula-acoes');
  assert.equal(await pagina.locator('#registros .celula-acoes button[aria-label]').count(),6);
  assert.equal(await pagina.locator('#avatar-turma-exemplo').evaluate(e=>e.clientWidth),64);

  assert.equal(await pagina.locator('link[rel=stylesheet]').count(),1);
  const altura=alvo=>alvo.evaluate(e=>Math.round(e.getBoundingClientRect().height));
  for(const [nome,minimo] of [['pq',32],['md',40],['gd',48],['xg',56]]){
    assert.equal(await altura(pagina.getByRole('button',{name:nome,exact:true})),minimo);
  }
  const largo=pagina.getByRole('button',{name:'pq + largura lg',exact:true});
  assert.equal(await altura(largo),32);
  assert.equal(await pagina.getByRole('button',{name:'Desabilitado',exact:true}).isDisabled(),true);
  assert.equal(await pagina.getByRole('button',{name:'Carregando',exact:true}).isDisabled(),true);

  await pagina.getByRole('button',{name:'Cards',exact:true}).click();
  assert.equal(await pagina.locator('#registros').getAttribute('data-modo'),'cards');
  assert.equal(await pagina.locator('#registros tbody tr').count(),3);
  for(const linha of await pagina.locator('#registros[data-modo=cards] tbody tr').all()){
    const pos=await linha.evaluate(e=>{const a=e.querySelector('.celula-acoes').getBoundingClientRect(),n=e.querySelector('.celula-nome').getBoundingClientRect(),s=e.querySelector('[data-rotulo="Situação"]').getBoundingClientRect(),r=e.getBoundingClientRect();return {footer:a.top>=s.bottom-1&&a.top>=n.bottom,left:Math.abs(a.left-n.left)<1,bottom:r.bottom-a.bottom<8};});
    assert.deepEqual(pos,{footer:true,left:true,bottom:true});
  }
  await pagina.getByRole('searchbox',{name:'Buscar aluno no exemplo'}).fill('Bianca');
  assert.equal(await pagina.locator('#registros tbody tr:visible').count(),1);
  await pagina.getByRole('button',{name:'Tabela',exact:true}).click();
  assert.equal(await pagina.locator('#registros tbody tr:visible').count(),1);
  await pagina.getByRole('searchbox',{name:'Buscar aluno no exemplo'}).fill('sem-correspondencia');
  assert.equal(await pagina.locator('#busca-vazia').isVisible(),true);
  await pagina.getByRole('searchbox',{name:'Buscar aluno no exemplo'}).fill('');

  await pagina.getByRole('button',{name:'Arquivar turma',exact:true}).click();
  assert.equal(await pagina.locator('#dialogo-cancelar').evaluate(e=>e===document.activeElement),true);
  await pagina.keyboard.press('Escape');

  assert.equal(await pagina.getByRole('button',{name:'Arquivar turma',exact:true}).evaluate(e=>e===document.activeElement),true);

  const fundos=[];
  for(const tipo of ['Informação','Sucesso','Atenção','Erro']){
    await pagina.getByRole('button',{name:tipo,exact:true}).click();
    fundos.push(await pagina.locator('.notificacao').evaluate(e=>getComputedStyle(e).backgroundColor));
    assert.equal(await pagina.locator('.notificacao').getAttribute('role'),tipo==='Erro'?'alert':'status');
  }
  assert.equal(new Set(fundos).size,4);
  await pagina.getByRole('button',{name:'Arquivar turma',exact:true}).click();
  await pagina.getByRole('button',{name:'Concluir demonstração',exact:true}).click();
  assert.equal(await pagina.locator('#registros tbody tr').count(),3);

  assert.equal(await pagina.locator('#campos-turma-exemplo').getAttribute('data-motor-validacao'),'FormValidation');
  await pagina.getByRole('button',{name:'Validar exemplo',exact:true}).click();
  await pagina.locator('#nome-erro').waitFor({state:'visible'});
  assert.equal(await pagina.locator('#nome-turma').getAttribute('aria-invalid'),'true');
  await pagina.locator('#nome-turma:focus').waitFor({state:'visible'});
  assert.equal(await pagina.locator('#nome-turma').evaluate(e=>e.getBoundingClientRect().top>=64),true);
  assert.equal(await pagina.locator('#nome-turma').evaluate(e=>e===document.activeElement),true);
  await pagina.getByRole('button',{name:'Validar exemplo',exact:true}).focus();
  await pagina.getByRole('button',{name:'Validar exemplo',exact:true}).press('Enter');
  await pagina.locator('#nome-turma:focus').waitFor({state:'visible'});
  assert.equal(await pagina.locator('#nome-turma').evaluate(e=>e.getBoundingClientRect().top>=64),true);
  assert.equal(await pagina.locator('#nome-turma').evaluate(e=>e.getBoundingClientRect().bottom<innerHeight),true);
  await pagina.locator('#nome-turma').fill('Turma fictícia');await pagina.locator('#ano').fill('2026');
  await pagina.getByRole('button',{name:'Validar exemplo',exact:true}).click();
  assert.equal(await pagina.locator('#nome-erro').isHidden(),true);
  assert.equal(await pagina.locator('#ano').getAttribute('required'),'');
  for(const anoInvalido of ['1999','2026.5','']){
    await pagina.locator('#ano').fill(anoInvalido);
    await pagina.getByRole('button',{name:'Validar exemplo',exact:true}).click();
    await pagina.locator('#ano-erro').waitFor({state:'visible'});
    assert.equal(await pagina.locator('#ano').getAttribute('aria-invalid'),'true');
  }
  await pagina.locator('#ano').fill('2026');
  await pagina.getByRole('button',{name:'Validar exemplo',exact:true}).click();
  await pagina.locator('#ano-erro').waitFor({state:'hidden'});
  await pagina.getByRole('button',{name:'Mudar para tema escuro',exact:true}).click();
  assert.equal(await pagina.locator('html').getAttribute('data-tema'),'escuro');
  assert.equal(await pagina.locator('#menu-lateral').evaluate(e=>getComputedStyle(e).backgroundColor),'rgb(24, 34, 52)');
  assert.equal(await pagina.locator('#marca-cabecalho').evaluate(e=>getComputedStyle(e).color),'rgb(255, 255, 255)');
  assert.equal(await pagina.locator('#marca-cabecalho .g360-simbolo-circulo').evaluate(e=>getComputedStyle(e).fill),'rgb(255, 255, 255)');
  await pagina.getByRole('button',{name:'Mudar para tema claro',exact:true}).click();

  await pagina.getByRole('tab',{name:'Alunos',exact:true}).focus();await pagina.keyboard.press('ArrowRight');
  assert.equal(await pagina.getByRole('tab',{name:'Provas',exact:true}).getAttribute('aria-selected'),'true');
  await pagina.getByRole('tab',{name:'Visão geral',exact:true}).click();
  await pagina.locator('#composicao').getByRole('button',{name:'Acerto médio',exact:true}).click();
  assert.equal(await pagina.locator('#ajuda-acerto').evaluate(e=>e.matches(':popover-open')),true);
  await pagina.keyboard.press('Escape');

  for(const largura of [390,320]){
    await pagina.setViewportSize({width:largura,height:844});
    assert.equal(await altura(pagina.getByRole('button',{name:'pq',exact:true})),44);
    assert.equal(await pagina.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true);
    assert.equal(await pagina.locator('#registros').getAttribute('data-modo'),'cards');
    assert.equal(await pagina.getByRole('button',{name:'gd',exact:true}).evaluate(e=>Math.round(e.getBoundingClientRect().height)),48);
    assert.equal(await pagina.getByRole('button',{name:'xg',exact:true}).evaluate(e=>Math.round(e.getBoundingClientRect().height)),56);
    await pagina.locator('#meu-espaco summary').click();
    assert.equal(await pagina.locator('#meu-espaco').getAttribute('open'),'');
    assert.equal(await pagina.locator('#contexto-espaco').evaluate(e=>Math.abs(e.getBoundingClientRect().width-e.closest('.faixa-espaco').getBoundingClientRect().width)<1),true);
    assert.equal(await pagina.locator('#nome-escola-exemplo').evaluate(e=>e.scrollWidth<=e.clientWidth),true);
    assert.equal(await pagina.locator('#nome-escola-exemplo').evaluate(e=>e.clientWidth>=100),true);
    assert.equal(await pagina.locator('#perfil-exemplo').evaluate(e=>e.scrollWidth<=e.clientWidth),true);
    await pagina.locator('#meu-espaco summary').click();
    assert.equal(await pagina.getByRole('button',{name:'Meu espaço',exact:true}).count(),0);
    await pagina.getByRole('button',{name:'Abrir menu da conta',exact:true}).click();
    assert.equal(await pagina.getByText('marina@example.test',{exact:true}).isVisible(),true);
    await pagina.getByRole('button',{name:'Trocar vínculo',exact:true}).click();
    assert.equal(await pagina.locator('#menu-conta').evaluate(e=>e.matches(':popover-open')),false);
    await pagina.getByRole('button',{name:'Professor — escola fictícia',exact:true}).click();
    assert.equal(await pagina.locator('#perfil-exemplo').textContent(),'Professor');
    await pagina.locator('#composicao .propriedades summary').click();
    await pagina.getByLabel('Vínculos fictícios disponíveis').selectOption('1');
    await pagina.getByRole('button',{name:'Abrir menu da conta',exact:true}).click();
    assert.equal(await pagina.locator('#trocar-vinculo').isHidden(),true);
    await pagina.getByRole('button',{name:'Meu perfil',exact:true}).click();
    await pagina.getByLabel('Vínculos fictícios disponíveis').selectOption('2');
    await pagina.getByRole('button',{name:'Abrir menu da conta',exact:true}).click();
    assert.equal(await pagina.locator('#trocar-vinculo').isVisible(),true);
    await pagina.getByRole('button',{name:'Sair',exact:true}).click();
    await pagina.locator('#composicao .propriedades summary').click();
    assert.equal(await pagina.locator('header').evaluate(e=>e.getBoundingClientRect().height<=90),true);
    assert.equal(await pagina.locator('#menu-lateral').isHidden(),true);
    assert.equal(await pagina.locator('#avatar-turma-exemplo').evaluate(e=>e.clientWidth),56);
    assert.equal(await pagina.locator('#contexto-espaco').evaluate(e=>getComputedStyle(e).gridTemplateColumns.split(' ').length),1);
    assert.equal(await pagina.locator('#marca-cabecalho .marca-simbolo').evaluate(e=>e.clientWidth),32);
    await pagina.getByRole('button',{name:'Abrir todas as seções',exact:true}).click();
    assert.equal(await pagina.locator('#menu-mobile').evaluate(e=>e.open),true);
    assert.equal(await pagina.locator('#menu-mobile').evaluate(e=>e.getBoundingClientRect().bottom<=innerHeight),true);
    await pagina.keyboard.press('Escape');
    await pagina.locator('#menu-mobile').waitFor({state:'hidden'});
    assert.equal(await pagina.locator('#abrir-menu-mobile').evaluate(e=>e===document.activeElement),true);
    assert.equal(await pagina.getByRole('navigation',{name:'Seções da prévia no celular'}).isVisible(),true);
  }
  for(const largura of [768,1024]){
    await pagina.setViewportSize({width:largura,height:900});
    assert.equal(await pagina.locator('#menu-lateral').isVisible(),true);
    assert.equal(await pagina.getByRole('navigation',{name:'Seções da prévia no celular'}).isHidden(),true);
    assert.equal(await pagina.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true);
    await pagina.locator('#meu-espaco summary').click();
    assert.equal(await pagina.locator('#contexto-espaco').evaluate(e=>getComputedStyle(e).gridTemplateColumns.split(' ').length),2);
    await pagina.locator('#meu-espaco summary').click();
  }
  for(const largura of [320,348,375,393,440]){
    await pagina.setViewportSize({width:largura,height:956});
    const grupo=pagina.locator('#acoes-turma-exemplo');
    const pos=await grupo.locator('button').evaluateAll(bs=>bs.map(b=>({top:b.getBoundingClientRect().top,left:b.getBoundingClientRect().left,right:b.getBoundingClientRect().right,height:b.getBoundingClientRect().height})));
    assert.equal(new Set(pos.map(p=>p.top)).size,1,'Grupo em uma linha: '+largura);
    assert.equal(pos.every(p=>p.height>=44&&p.left>=0&&p.right<=largura),true);
    assert.equal(await grupo.locator('[data-acao-compactavel] .rotulo-acao').isVisible(),largura>=375);
    assert.equal(await grupo.locator('.contexto-acao').first().isVisible(),largura>=440);
    assert.equal(await grupo.getByRole('button',{name:'Arquivar turma',exact:true}).locator('.rotulo-acao').isVisible(),true);
    assert.equal(await pagina.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),true);
    await grupo.getByRole('button',{name:'Arquivar turma',exact:true}).click();
    await pagina.locator('#dialogo').waitFor({state:'visible'});
    await pagina.keyboard.press('Escape');
    assert.equal(await grupo.getByRole('button',{name:'Arquivar turma',exact:true}).evaluate(e=>e===document.activeElement),true);
  }
  await pagina.setViewportSize({width:1440,height:1000});
  // Estabilizar resize e rolagem antes de testar a legenda por foco.
  // Esses eventos fecham legendas já abertas; não fazem parte da interação testada.
  await pagina.locator('#acoes-turma-exemplo [data-acao-compactavel]').scrollIntoViewIfNeeded();
  await pagina.evaluate(()=>new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve))));
  await pagina.locator('#acoes-turma-exemplo [data-acao-compactavel]').focus();
  await pagina.locator('#legenda-acao:popover-open').waitFor({state:'visible'});
  assert.equal(await pagina.locator('#legenda-acao').textContent(),'Editar turma 3º A');
  await pagina.keyboard.press('Escape');
  assert.equal(await pagina.locator('#legenda-acao').evaluate(e=>e.matches(':popover-open')),false);
  assert.deepEqual(erros,[]);
  assert.equal(requests.every(r=>r.metodo==='GET'&&new URL(r.url).origin===base),true);
  assert.equal(requests.some(r=>/\/api\/|\/app\//.test(new URL(r.url).pathname)),false);
  console.log('Prévia: escala, largura, estados, tabela/cards, busca, confirmação local, validação, tema, abas, ajuda e menu móvel aprovados; sem transporte de dados.');
}finally{
  await navegador?.close();await new Promise(resolve=>servidor.close(resolve));
}
