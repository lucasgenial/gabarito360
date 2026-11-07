/* Intenção explícita no modal; a API revalida estado, versão, chave e permissão. */
(function(){
 'use strict';
 const modal=document.querySelector('[data-arquivamento-modal]');if(!modal)return;
 const confirmados=new WeakSet(),enviados=new WeakSet();let formulario,acionador;
 const confirmar=modal.querySelector('[data-arquivamento-confirmar-acao]');
 document.addEventListener('submit',function(evento){
  const form=evento.target;if(!form.matches('form[data-arquivamento-confirmar]'))return;
  if(evento.defaultPrevented){confirmados.delete(form);return;}
  if(confirmados.has(form)){confirmados.delete(form);enviados.add(form);return;}
  evento.preventDefault();evento.stopImmediatePropagation();if(enviados.has(form)||!form.reportValidity())return;
  formulario=form;acionador=evento.submitter||form.querySelector('button[type="submit"]');
  modal.querySelector('[data-arquivamento-titulo]').textContent=form.dataset.arquivamentoTitulo;
  modal.querySelector('[data-arquivamento-registro]').textContent=form.dataset.arquivamentoNome;
  const arquivar=form.dataset.arquivamentoVerbo==='Arquivar';
  modal.querySelector('[data-arquivamento-efeito]').textContent=arquivar?'O cadastro sairá das listas de ativos. As provas, os resultados, as imagens e o histórico serão preservados. Você poderá desarquivá-lo depois.':'O cadastro voltará a ficar disponível nas listas de ativos. O histórico anterior será preservado.';
  modal.querySelector('[data-arquivamento-confirmar-rotulo]').textContent=form.dataset.arquivamentoTitulo;
  confirmar.disabled=false;modal.showModal();modal.querySelector('.g360-arquivar-acoes [data-arquivamento-cancelar]').focus();
 },true);
 modal.querySelectorAll('[data-arquivamento-cancelar]').forEach(b=>b.addEventListener('click',()=>modal.close()));
 modal.addEventListener('close',()=>acionador?.focus());
 confirmar.addEventListener('click',()=>{
  if(!formulario||confirmar.disabled||enviados.has(formulario))return;
  confirmar.disabled=true;const form=formulario,botao=acionador;modal.close();confirmados.add(form);form.requestSubmit(botao);
 });
})();
