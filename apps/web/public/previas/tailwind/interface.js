'use strict';

// Prévia isolada: interações locais, sem transporte, autenticação ou persistência.
(() => {
  const selecionar = valor => document.querySelector(valor);
  const selecionarTodos = valor => [...document.querySelectorAll(valor)];
  const raiz = document.documentElement;
  function icone(nome) {
    const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');
    svg.classList.add('icone');svg.setAttribute('aria-hidden','true');
    const uso=document.createElementNS('http://www.w3.org/2000/svg','use');
    uso.setAttribute('href','#i-'+nome);svg.append(uso);return svg;
  }
  function notificar(texto) {
    const regiao=selecionar('#notificacoes');
    const caixa=document.createElement('div');caixa.className='notificacao';
    const mensagem=document.createElement('p');mensagem.className='tw:flex-1 tw:text-sm tw:leading-5';mensagem.textContent=texto;
    const fechar=document.createElement('button');fechar.type='button';fechar.className='botao botao-icone botao-discreto';fechar.setAttribute('aria-label','Fechar notificação');fechar.title='Fechar notificação';fechar.append(icone('fechar'));
    fechar.addEventListener('click',()=>{caixa.remove();});
    caixa.append(icone('info'),mensagem,fechar);regiao.replaceChildren(caixa);
    // Persistente até fechar: não desaparece durante leitura/foco, nem exige pausa.
  }
  selecionarTodos('[data-toast]').forEach(botao=>botao.addEventListener('click',()=>notificar(botao.dataset.toast)));

  const tema=selecionar('#alternar-tema');
  tema.addEventListener('click',()=>{
    const escuro=raiz.dataset.tema!=='escuro';raiz.dataset.tema=escuro?'escuro':'claro';
    const nome='Mudar para tema '+(escuro?'claro':'escuro');tema.setAttribute('aria-label',nome);tema.title=nome;tema.replaceChildren(icone(escuro?'sol':'lua'));
  });
  const menu=selecionar('#menu-lateral'),abrirMenu=selecionar('#abrir-menu'),fundo=selecionar('#fundo-menu');
  const fundoInerte=[selecionar('main'),selecionar('header'),selecionar('footer')];
  function fecharMenu(devolver=true) {
    menu.dataset.aberto='false';fundo.hidden=true;abrirMenu.setAttribute('aria-expanded','false');
    fundoInerte.forEach(elemento=>{elemento.inert=false;});
    if(devolver)abrirMenu.focus();
  }
  abrirMenu.addEventListener('click',()=>{
    menu.dataset.aberto='true';fundo.hidden=false;abrirMenu.setAttribute('aria-expanded','true');
    fundoInerte.forEach(elemento=>{elemento.inert=true;});
    menu.querySelector('[data-menu-fechar]').focus();
  });
  fundo.addEventListener('click',()=>fecharMenu());
  selecionar('[data-menu-fechar]').addEventListener('click',()=>fecharMenu());
  document.addEventListener('keydown',evento=>{
    if(evento.key==='Escape'&&menu.dataset.aberto==='true')fecharMenu();
    if(evento.key==='Tab'&&menu.dataset.aberto==='true'){
      const alvos=[...menu.querySelectorAll('a,button')].filter(el=>el.getClientRects().length);
      const primeiro=alvos[0],ultimo=alvos.at(-1);
      if(evento.shiftKey&&document.activeElement===primeiro){evento.preventDefault();ultimo.focus();}
      else if(!evento.shiftKey&&document.activeElement===ultimo){evento.preventDefault();primeiro.focus();}
    }
  });
  const links=selecionarTodos('.lista-menu a');
  function marcarSecao() {
    const atual=location.hash||'#composicao';
    links.forEach(link=>{if(link.hash===atual)link.setAttribute('aria-current','location');else link.removeAttribute('aria-current');});
  }
  links.forEach(link=>link.addEventListener('click',()=>{
    if(menu.dataset.aberto==='true'){
      fecharMenu(false);const titulo=document.querySelector(link.hash+' h2');
      if(titulo){titulo.tabIndex=-1;requestAnimationFrame(()=>titulo.focus({preventScroll:true}));}
    }
  }));
  window.addEventListener('hashchange',marcarSecao);marcarSecao();

  const abas=selecionarTodos('[role=tab]');
  function ativarAba(aba,foco=false) {
    abas.forEach(item=>{
      const ativa=item===aba;item.setAttribute('aria-selected',String(ativa));item.tabIndex=ativa?0:-1;
      document.getElementById(item.getAttribute('aria-controls')).hidden=!ativa;
    });if(foco)aba.focus();
  }
  abas.forEach((aba,indice)=>{
    aba.addEventListener('click',()=>ativarAba(aba));
    aba.addEventListener('keydown',evento=>{
      let alvo;
      if(evento.key==='ArrowRight')alvo=abas[(indice+1)%abas.length];
      if(evento.key==='ArrowLeft')alvo=abas[(indice+abas.length-1)%abas.length];
      if(evento.key==='Home')alvo=abas[0];if(evento.key==='End')alvo=abas.at(-1);
      if(alvo){evento.preventDefault();ativarAba(alvo,true);}
    });
  });
  selecionarTodos('[popovertarget]').forEach(botao=>{
    const popover=document.getElementById(botao.getAttribute('popovertarget'));
    popover.addEventListener('toggle',evento=>botao.setAttribute('aria-expanded',String(evento.newState==='open')));
  });

  const tabela=selecionar('#registros'),modos=selecionarTodos('button[data-modo]');
  modos.forEach(botao=>botao.addEventListener('click',()=>{
    tabela.dataset.modo=botao.dataset.modo;modos.forEach(item=>item.setAttribute('aria-pressed',String(item===botao)));
  }));
  const normalizar=texto=>texto.normalize('NFD').replace(/\p{Diacritic}/gu,'').toLocaleLowerCase('pt-BR');
  selecionar('#buscar-aluno').addEventListener('input',evento=>{
    const termo=normalizar(evento.target.value.trim());let total=0;
    selecionarTodos('#registros tbody tr').forEach(linha=>{linha.hidden=!normalizar(linha.dataset.nome).includes(termo);if(!linha.hidden)total++;});
    selecionar('#total-registros').textContent=total+' registro'+(total===1?'':'s')+' fictício'+(total===1?'':'s');selecionar('#busca-vazia').hidden=total!==0;
  });

  const nome=selecionar('#nome-turma'),ano=selecionar('#ano');
  function marcarCampo(campo,erro,invalido) {
    campo.setAttribute('aria-invalid',String(invalido));selecionar(erro).hidden=!invalido;return invalido;
  }
  selecionar('#validar-campos').addEventListener('click',()=>{
    const nomeInvalido=marcarCampo(nome,'#nome-erro',nome.value.trim()===''||!nome.validity.valid);
    const anoInvalido=marcarCampo(ano,'#ano-erro',!Number.isInteger(ano.valueAsNumber)||!ano.validity.valid);
    if(nomeInvalido||anoInvalido)(nomeInvalido?nome:ano).focus();else notificar('Campos válidos no exemplo. Nenhum dado foi enviado ou gravado.');
  });
  selecionar('#limpar-campos').addEventListener('click',()=>{
    nome.value='';ano.value='2026';selecionar('#observacao').value='';
    marcarCampo(nome,'#nome-erro',false);marcarCampo(ano,'#ano-erro',false);nome.focus();
  });

  const dialogo=selecionar('#dialogo'),filtros=selecionar('#filtros-exemplo'),confirmar=selecionar('#dialogo-confirmar');
  let ultimoAcionador,modo;
  const textos={
    arquivar:['Arquivar turma — exemplo','O histórico seria preservado e a turma sairia da lista de turmas em uso. Aqui demonstramos somente a confirmação; nenhum registro será alterado.','Concluir demonstração'],
    filtro:['Filtrar indicadores — exemplo','Escolha um recorte para conferir a apresentação do filtro aplicado. Os números da prévia continuam fictícios.','Aplicar exemplo'],
    revisao:['Como avaliar esta prévia','Navegue pelas seções, teste botões e estados, alterne tema e apresentação dos registros e abra Propriedades e regras. O padrão será aplicado ao sistema após sua aprovação.','Entendi']
  };
  selecionarTodos('[data-dialogo]').forEach(botao=>botao.addEventListener('click',()=>{
    ultimoAcionador=botao;modo=botao.dataset.dialogo;
    const [titulo,descricao,acao]=textos[modo];selecionar('#dialogo-titulo').textContent=titulo;selecionar('#dialogo-descricao').textContent=descricao;confirmar.textContent=acao;
    confirmar.classList.toggle('botao-perigo',modo==='arquivar');confirmar.classList.toggle('botao-principal',modo!=='arquivar');filtros.hidden=modo!=='filtro';
    dialogo.showModal();selecionar('#dialogo-cancelar').focus();
  }));
  selecionarTodos('[data-dialogo-fechar]').forEach(botao=>botao.addEventListener('click',()=>dialogo.close()));
  dialogo.addEventListener('close',()=>ultimoAcionador?.focus());
  confirmar.addEventListener('click',()=>{
    if(modo==='filtro'){
      const disciplina=selecionar('#disciplina-filtro').value,periodo=selecionar('#periodo-filtro').value;
      const ativo=disciplina!=='Todas as disciplinas'||periodo!=='Todo o período';
      const recorte=selecionar('#recorte-aplicado');recorte.hidden=!ativo;recorte.textContent=ativo?'Dados filtrados · '+disciplina+' · '+periodo:'';
    }
    dialogo.close();if(modo==='arquivar')notificar('Demonstração concluída. Nenhum registro foi arquivado.');
  });
  const repetir=selecionar('#repetir-imagem');
  repetir.addEventListener('click',()=>{
    repetir.disabled=true;const estado=selecionar('#imagem-erro');estado.setAttribute('aria-busy','true');selecionar('[data-imagem-mensagem]').textContent='Aguarde…';
    setTimeout(()=>{estado.removeAttribute('aria-busy');selecionar('[data-imagem-mensagem]').textContent='Exemplo carregado';repetir.disabled=false;notificar('Repetição simulada localmente, sem enviar arquivos ou consultar serviços.');},600);
  });
})();
