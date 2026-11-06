/* Interações locais de turmas; dados e navegação permanecem separados. */
(function () {
  'use strict';
  let modalAtivo = null;
  let acionador = null;
  function focaveis(modal) {
    return [...modal.querySelectorAll('button:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href], [tabindex="0"]')]
      .filter(elemento => elemento.getClientRects().length > 0);
  }
  function mostrarModal(id, aberto) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.toggle('open', aberto);
    modal.setAttribute('aria-hidden', String(!aberto));
    document.body.style.overflow = aberto ? 'hidden' : '';
    if (aberto) {
      acionador = document.activeElement;
      modalAtivo = modal;
      (modal.querySelector('input:not([type="hidden"]):not([disabled])') || focaveis(modal)[0])?.focus();
    } else if (modalAtivo === modal) {
      modalAtivo = null;
      acionador?.focus();
      acionador = null;
    }
  }
  document.querySelectorAll('[data-turma-abrir]').forEach(botao => {
    botao.addEventListener('click', () => mostrarModal(botao.dataset.turmaAbrir, true));
  });
  document.querySelectorAll('[data-turma-fechar]').forEach(botao => {
    botao.addEventListener('click', () => mostrarModal(botao.dataset.turmaFechar, false));
  });
  ['modal-editar', 'modal-criar', 'modal-importar'].forEach(id => {
    const modal = document.getElementById(id);
    modal?.addEventListener('click', e => { if (e.target === modal) mostrarModal(id, false); });
  });
  document.addEventListener('keydown', e => {
    if (!modalAtivo) return;
    if (e.key === 'Escape') { e.preventDefault(); mostrarModal(modalAtivo.id, false); }
    if (e.key === 'Tab' && modalAtivo) {
      const itens = focaveis(modalAtivo);
      const primeiro = itens[0], ultimo = itens[itens.length - 1];
      if (e.shiftKey && (document.activeElement === primeiro || !modalAtivo.contains(document.activeElement))) {
        e.preventDefault(); ultimo?.focus();
      } else if (!e.shiftKey && (document.activeElement === ultimo || !modalAtivo.contains(document.activeElement))) {
        e.preventDefault(); primeiro?.focus();
      }
    }
  });
  const aprovados = new WeakSet();
  let dialogo, formularioConfirmado, submitter;
  function confirmarFormulario(form, botao) {
    if (!dialogo) {
      dialogo=document.createElement('dialog');dialogo.className='tu1-dialog';dialogo.setAttribute('aria-labelledby','tu1-confirmar-titulo');
      const titulo=document.createElement('h2');titulo.id='tu1-confirmar-titulo';titulo.textContent='Confirmar ação';
      const texto=document.createElement('p');texto.dataset.confirmacaoTexto='';
      const acoes=document.createElement('div');acoes.className='tu1-acoes';
      [['Cancelar','fechar'],['Confirmar','confirmar']].forEach(([rotulo,icone],i)=>{
        const b=document.createElement('button');b.type='button';b.className='btn '+(i?'btn-primary':'btn-secondary');
        const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.setAttribute('viewBox','0 0 24 24');svg.setAttribute('width','20');svg.setAttribute('height','20');svg.setAttribute('aria-hidden','true');svg.setAttribute('fill','none');svg.setAttribute('stroke','currentColor');svg.setAttribute('stroke-width','2');
        const path=document.createElementNS('http://www.w3.org/2000/svg','path');path.setAttribute('d',icone==='fechar'?'M6 6l12 12M6 18 18 6':'m5 12 4 4L19 6');svg.append(path);b.append(svg,document.createTextNode(' '+rotulo));
        b.addEventListener('click',()=>{dialogo.close();if(i){aprovados.add(formularioConfirmado);formularioConfirmado.requestSubmit(submitter||undefined);}});acoes.append(b);
      });
      dialogo.append(titulo,texto,acoes);document.body.append(dialogo);dialogo.addEventListener('close',()=>submitter?.focus());
    }
    formularioConfirmado=form;submitter=botao;dialogo.querySelector('[data-confirmacao-texto]').textContent=form.dataset.turmaConfirmar;dialogo.showModal();dialogo.querySelector('button').focus();
  }
  document.querySelectorAll('[data-turma-confirmar]').forEach(form => {
    form.addEventListener('submit', e => {
      if(aprovados.has(form)){aprovados.delete(form);return;}
      e.preventDefault();if(form.reportValidity())confirmarFormulario(form,e.submitter);
    });
  });
  const filtro = document.querySelector('[data-turma-filtrar-alunos]');
  filtro?.addEventListener('input', () => {
    const termo = filtro.value.toLowerCase().trim();
    let visiveis = 0;
    document.querySelectorAll('#alunos-tbody tr[data-nome]').forEach(row => {
      const mostrar = !termo || row.dataset.nome.includes(termo);
      row.style.display = mostrar ? '' : 'none';
      if (mostrar) visiveis++;
    });
    const vazio = document.getElementById('no-results');
    if (vazio) vazio.style.display = visiveis === 0 && termo ? 'block' : 'none';
  });
  const professor = document.getElementById('professor-selecionado');
  const vinculo = document.getElementById('professor-vinculo');
  if (professor && vinculo) {
    const atualizar = () => { vinculo.value = professor.selectedOptions[0]?.dataset.vinculo || ''; };
    professor.addEventListener('change', atualizar);
    atualizar();
  }
})();
