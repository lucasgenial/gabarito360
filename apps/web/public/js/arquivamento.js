/* Confirmação reversível; o estado e a autorização são revalidados pela API. */
document.addEventListener('submit',function(evento){
 const form=evento.target;if(!form.matches('form[data-arquivamento-confirmar]'))return;
 if(!window.confirm(form.dataset.arquivamentoConfirmar)){evento.preventDefault();evento.stopImmediatePropagation();}
},true);
