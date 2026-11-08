'use strict';
import {criarValidacaoTurma} from './validacao-formulario.js';
import {iniciarLegendasAcoes} from './legendas-acoes.js';

// Prévia isolada: interações locais, sem transporte, autenticação ou persistência.
(() => {
  iniciarLegendasAcoes();
  const selecionar = valor => document.querySelector(valor);
  const selecionarTodos = valor => [...document.querySelectorAll(valor)];
  const raiz = document.documentElement;
  function icone(nome) {
    const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');
    svg.classList.add('icone');svg.setAttribute('aria-hidden','true');
    const uso=document.createElementNS('http://www.w3.org/2000/svg','use');
    uso.setAttribute('href','#i-'+nome);svg.append(uso);return svg;
  }
  const tiposNotificacao={informacao:{titulo:'Informação',icone:'info'},sucesso:{titulo:'Sucesso',icone:'confirmar'},atencao:{titulo:'Atenção',icone:'alerta'},erro:{titulo:'Erro',icone:'alerta'}};
  function notificar(texto,tipo='informacao') {
    if(!Object.hasOwn(tiposNotificacao,tipo))tipo='informacao';
    const definicao=tiposNotificacao[tipo];
    const regiao=selecionar('#notificacoes');
    const caixa=document.createElement('div');caixa.className='notificacao notificacao-'+tipo;caixa.setAttribute('role',tipo==='erro'?'alert':'status');caixa.setAttribute('aria-atomic','true');
    const conteudo=document.createElement('div');conteudo.className='tw:flex-1 tw:text-sm tw:leading-5';
    const titulo=document.createElement('p');titulo.className='tw:font-semibold';titulo.textContent=definicao.titulo;
    const mensagem=document.createElement('p');mensagem.className='tw:mt-1';mensagem.textContent=texto;conteudo.append(titulo,mensagem);
    const fechar=document.createElement('button');fechar.type='button';fechar.className='botao botao-icone botao-discreto';fechar.setAttribute('aria-label','Fechar notificação');fechar.title='Fechar notificação';fechar.append(icone('fechar'));
    fechar.addEventListener('click',()=>{caixa.remove();});
    caixa.append(icone(definicao.icone),conteudo,fechar);regiao.replaceChildren(caixa);
    // Persistente até fechar: não desaparece durante leitura/foco, nem exige pausa.
  }
  selecionarTodos('[data-toast]').forEach(botao=>botao.addEventListener('click',()=>notificar(botao.dataset.toast,botao.dataset.toastTipo)));
  const mostrarNotificacao=selecionar('#feedback [data-toast]');
  const tipos=document.createElement('div');tipos.className='linha-acoes tw:mt-4';tipos.setAttribute('role','group');tipos.setAttribute('aria-label','Tipos de notificação');
  mostrarNotificacao.replaceWith(tipos);mostrarNotificacao.textContent='Informação';mostrarNotificacao.dataset.toastTipo='informacao';tipos.append(mostrarNotificacao);
  for(const [tipo,texto] of [['sucesso','Exemplo de operação concluída, sem gravação de dados.'],['atencao','Confira as informações antes de continuar.'],['erro','Não foi possível concluir o exemplo. Nenhum dado foi alterado.']]){
    const botao=document.createElement('button');botao.type='button';botao.className='botao botao-secundario';botao.textContent=tiposNotificacao[tipo].titulo;
    botao.addEventListener('click',()=>notificar(texto,tipo));tipos.append(botao);
  }

  const tema=selecionar('#alternar-tema');
  tema.addEventListener('click',()=>{
    const escuro=raiz.dataset.tema!=='escuro';raiz.dataset.tema=escuro?'escuro':'claro';
    const nome='Mudar para tema '+(escuro?'claro':'escuro');tema.setAttribute('aria-label',nome);tema.title=nome;tema.replaceChildren(icone(escuro?'sol':'lua'));
  });
  const estrutura=selecionar('.estrutura-referencia'),abrirMenu=selecionar('#abrir-menu');
  const conta=selecionar('#menu-conta'),avatar=selecionar('#abrir-conta');
  function fecharConta(){if(conta.matches(':popover-open'))conta.hidePopover();}
  selecionarTodos('[data-conta-feedback]').forEach(botao=>botao.addEventListener('click',()=>{fecharConta();avatar.focus();notificar(botao.dataset.contaFeedback);}));
  conta.addEventListener('keydown',evento=>{if(evento.key==='Escape'){evento.preventDefault();fecharConta();avatar.focus();}});
  const menuDesktop=matchMedia('(min-width:768px)'),telaAmpla=matchMedia('(min-width:1280px)');
  function definirMenuDesktop(modo){
    estrutura.dataset.menuDesktop=modo;const expandido=modo==='expandido';
    abrirMenu.setAttribute('aria-expanded',String(expandido));
    const rotulo=expandido?'Recolher menu principal':'Expandir menu principal';abrirMenu.setAttribute('aria-label',rotulo);abrirMenu.title=rotulo;
  }
  definirMenuDesktop(telaAmpla.matches?'expandido':'compacto');
  abrirMenu.addEventListener('click',()=>{fecharConta();definirMenuDesktop(estrutura.dataset.menuDesktop==='expandido'?'compacto':'expandido');});
  const menuMobile=selecionar('#menu-mobile'),abrirMenuMobile=selecionar('#abrir-menu-mobile');let devolverMenuMobile=true;
  abrirMenuMobile.addEventListener('click',()=>{fecharConta();devolverMenuMobile=true;menuMobile.showModal();abrirMenuMobile.setAttribute('aria-expanded','true');menuMobile.querySelector('[data-menu-mobile-fechar]').focus();});
  selecionar('[data-menu-mobile-fechar]').addEventListener('click',()=>menuMobile.close());
  menuMobile.addEventListener('close',()=>{abrirMenuMobile.setAttribute('aria-expanded','false');if(devolverMenuMobile)(menuDesktop.matches?abrirMenu:abrirMenuMobile).focus();});
  menuMobile.addEventListener('click',evento=>{if(evento.target!==menuMobile)return;const r=menuMobile.getBoundingClientRect();if(evento.clientX<r.left||evento.clientX>r.right||evento.clientY<r.top||evento.clientY>r.bottom)menuMobile.close();});
  menuDesktop.addEventListener('change',()=>{if(menuMobile.open)menuMobile.close();definirMenuDesktop(telaAmpla.matches?'expandido':'compacto');});
  const links=selecionarTodos('.lista-menu a,.lista-menu-mobile a,a[data-secao]');
  function marcarSecao(){const atual=location.hash||'#composicao';links.forEach(link=>{if(link.hash===atual)link.setAttribute('aria-current','location');else link.removeAttribute('aria-current');});}
  selecionarTodos('.lista-menu-mobile a').forEach(link=>link.addEventListener('click',()=>{
    devolverMenuMobile=false;menuMobile.close();const titulo=document.querySelector(link.hash+' h2');if(titulo){titulo.tabIndex=-1;requestAnimationFrame(()=>titulo.focus({preventScroll:true}));}
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
  const controlesModos=modos[0].parentElement,modoMobile=matchMedia('(max-width:767px)');let apresentacaoPreferida='tabela';
  const avisoModo=document.createElement('p');avisoModo.className='ajuda tw:px-4 tw:py-2';avisoModo.textContent='Apresentação em cards no celular';controlesModos.parentElement.after(avisoModo);
  function atualizarModo(){
    const modo=modoMobile.matches?'cards':apresentacaoPreferida;tabela.dataset.modo=modo;
    if(modoMobile.matches&&controlesModos.contains(document.activeElement))selecionar('#buscar-aluno').focus({preventScroll:true});
    controlesModos.hidden=modoMobile.matches;avisoModo.hidden=!modoMobile.matches;
    modos.forEach(botao=>botao.setAttribute('aria-pressed',String(botao.dataset.modo===modo)));
  }
  modoMobile.addEventListener('change',atualizarModo);atualizarModo();
  modos.forEach(botao=>botao.addEventListener('click',()=>{
    apresentacaoPreferida=botao.dataset.modo;atualizarModo();
  }));
  const normalizar=texto=>texto.normalize('NFD').replace(/\p{Diacritic}/gu,'').toLocaleLowerCase('pt-BR');
  selecionar('#buscar-aluno').addEventListener('input',evento=>{
    const termo=normalizar(evento.target.value.trim());let total=0;
    selecionarTodos('#registros tbody tr').forEach(linha=>{linha.hidden=!normalizar(linha.dataset.nome).includes(termo);if(!linha.hidden)total++;});
    selecionar('#total-registros').textContent=total+' registro'+(total===1?'':'s')+' fictício'+(total===1?'':'s');selecionar('#busca-vazia').hidden=total!==0;
  });

  const nome=selecionar('#nome-turma'),ano=selecionar('#ano');
  const validacao=criarValidacaoTurma(selecionar('#campos-turma-exemplo')),validar=selecionar('#validar-campos');
  validar.addEventListener('click',async()=>{
    if(validar.disabled)return;validar.disabled=true;validar.setAttribute('aria-busy','true');
    try{if(await validacao.validarTudo())notificar('Campos válidos no exemplo. Nenhum dado foi enviado ou gravado.','sucesso');}
    finally{validar.disabled=false;validar.removeAttribute('aria-busy');}
  });
  selecionar('#limpar-campos').addEventListener('click',()=>{nome.value='';ano.value='2026';selecionar('#observacao').value='';validacao.limparEstados();nome.focus();});

  const dialogo=selecionar('#dialogo'),filtros=selecionar('#filtros-exemplo'),confirmar=selecionar('#dialogo-confirmar');
  const trocarVinculo=selecionar('#trocar-vinculo'),quantidadeVinculos=selecionar('#quantidade-vinculos');
  const opcoesVinculo=document.createElement('div');opcoesVinculo.className='tw:mt-4 tw:grid tw:gap-3';opcoesVinculo.id='opcoes-vinculo';opcoesVinculo.hidden=true;
  for(const perfil of ['Coordenação','Professor']){
    const botao=document.createElement('button');botao.type='button';botao.className='botao botao-secundario botao-lg';botao.textContent=perfil+' — escola fictícia';
    botao.addEventListener('click',()=>{selecionarTodos('[data-perfil-exemplo]').forEach(dado=>{dado.textContent=perfil;});dialogo.close();notificar('Perfil fictício alterado na prévia. A sessão do aplicativo não foi modificada.');});opcoesVinculo.append(botao);
  }
  filtros.before(opcoesVinculo);
  quantidadeVinculos.addEventListener('change',()=>{trocarVinculo.hidden=quantidadeVinculos.value==='1';});
  trocarVinculo.hidden=quantidadeVinculos.value==='1';
  let ultimoAcionador,modo;
  const textos={
    arquivar:['Arquivar turma — exemplo','O histórico seria preservado e a turma sairia da lista de turmas em uso. Aqui demonstramos somente a confirmação; nenhum registro será alterado.','Concluir demonstração'],
    filtro:['Filtrar indicadores — exemplo','Escolha um recorte para conferir a apresentação do filtro aplicado. Os números da prévia continuam fictícios.','Aplicar exemplo'],
    revisao:['Como avaliar esta prévia','Navegue pelas seções, teste botões e estados, alterne tema e apresentação dos registros e abra Propriedades e regras. O padrão será aplicado ao sistema após sua aprovação.','Entendi'],
    vinculos:['Trocar vínculo — exemplo','Dois vínculos fictícios na mesma escola. A seleção altera apenas a apresentação desta prévia.','Fechar']
  };
  selecionarTodos('[data-dialogo]').forEach(botao=>botao.addEventListener('click',()=>{
    ultimoAcionador=conta.contains(botao)?avatar:botao;modo=botao.dataset.dialogo;fecharConta();
    const [titulo,descricao,acao]=textos[modo];selecionar('#dialogo-titulo').textContent=titulo;selecionar('#dialogo-descricao').textContent=descricao;confirmar.textContent=acao;
    confirmar.classList.toggle('botao-perigo',modo==='arquivar');confirmar.classList.toggle('botao-principal',modo!=='arquivar');filtros.hidden=modo!=='filtro';
    opcoesVinculo.hidden=modo!=='vinculos';confirmar.hidden=modo==='vinculos';selecionar('#dialogo-cancelar').textContent=modo==='vinculos'?'Fechar':'Cancelar';
    if(modo==='arquivar'){
      const registro=botao.closest('tr[data-nome]')?.querySelector('.celula-nome strong')?.textContent;
      selecionar('#dialogo-titulo').textContent=registro?'Arquivar cadastro — exemplo':'Arquivar turma 3º A — exemplo';
      selecionar('#dialogo-descricao').textContent=registro
        ? registro+': o cadastro sairia da lista de registros em uso, com o histórico preservado. Esta é apenas uma demonstração; nenhum registro será alterado.'
        : 'A turma 3º A sairia da lista de turmas em uso, com o histórico preservado. Esta é apenas uma demonstração; nenhum registro será alterado.';
    }
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
