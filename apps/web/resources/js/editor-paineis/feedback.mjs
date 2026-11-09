/** Feedback discreto do editor; movimentos não geram pilhas de notificações. */
export function criarFeedback({status,aviso,notificacoes=()=>window.G360Toast}){
 const limpar=()=>{status.textContent='';aviso.hidden=true;aviso.textContent='';};
 function contextual(mensagem,tipo){
  status.textContent='';aviso.hidden=true;aviso.className='g-aviso '+tipo;
  aviso.setAttribute('role',tipo==='erro'?'alert':'status');
  aviso.setAttribute('aria-live',tipo==='erro'?'assertive':'polite');
  aviso.textContent=mensagem;aviso.hidden=false;
 }
 return {
  limpar,
  dizer(mensagem,tipo='info'){
   if(tipo==='erro'||tipo==='atencao'){contextual(mensagem,tipo);return;}
   limpar();status.textContent=mensagem;
  },
  notificar(mensagem,tipo='info',opcoes={}){
   limpar();const dados={id:'editor-paineis-resultado',...opcoes};let biblioteca;
   try{biblioteca=notificacoes();if(typeof biblioteca?.[tipo]==='function'&&biblioteca[tipo](mensagem,dados)!=null)return;}
   catch{try{biblioteca?.fechar?.(dados.id);}catch{/* O fallback não pode falhar junto da biblioteca. */}}
   // Falha de apresentação nunca transforma um ACK confirmado em gravação incerta.
   contextual(mensagem,tipo);
  }
 };
}
