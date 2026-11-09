/** Continuidade de uma edição na mesma tela, sem navegar ou salvar dados. */
export function capturarContinuidade(root,id){
 const anchor=[...root.querySelectorAll('.grid-stack-item:not(.grid-stack-placeholder)')].find(el=>el.getAttribute('gs-id')===id);
 const active=document.activeElement,owner=root.contains(active)?active.closest('.grid-stack-item'):null;
 const focus=owner?{element:active,node:owner.getAttribute('gs-id'),index:[...owner.querySelectorAll('button,a,input,select,textarea,[tabindex]')].indexOf(active),tag:active.tagName,label:active.getAttribute('aria-label')}:null;
 return {id,top:anchor?.getBoundingClientRect().top??null,x:window.scrollX,y:window.scrollY,focus};
}

export function manterContinuidade(view,draw){
 if(!view)return draw();
 const main=document.querySelector('main'),html=document.documentElement;
 const previousMin=main.style.minHeight,previousAnchor=html.style.overflowAnchor;
 // Desmontar a grade não deve reduzir o documento a zero entre duas versões.
 main.style.minHeight=main.getBoundingClientRect().height+'px';html.style.overflowAnchor='none';
 let target=view.y;
 try{
  draw();
  const anchor=[...main.querySelectorAll('.grid-stack-item:not(.grid-stack-placeholder)')].find(el=>el.getAttribute('gs-id')===view.id);
  if(anchor&&view.top!==null)target=window.scrollY+anchor.getBoundingClientRect().top-view.top;
  // Restaurar somente o controle destruído por esta remontagem. Não disputar
  // o foco de um controle externo ou escolhido por outra interação.
  if(view.focus&&!view.focus.element.isConnected&&[document.body,html].includes(document.activeElement)){
   const owner=[...main.querySelectorAll('.grid-stack-item')].find(el=>el.getAttribute('gs-id')===view.focus.node);
   const control=owner?.querySelectorAll('button,a,input,select,textarea,[tabindex]')[view.focus.index];
   if(control&&control.tagName===view.focus.tag&&control.getAttribute('aria-label')===view.focus.label&&!control.disabled)control.focus({preventScroll:true});
  }
 }finally{
  if(previousMin)main.style.minHeight=previousMin;else main.style.removeProperty('min-height');
  window.scrollTo({left:view.x,top:Math.max(0,target),behavior:'instant'});
  requestAnimationFrame(()=>{window.scrollTo({left:view.x,top:Math.max(0,target),behavior:'instant'});if(previousAnchor)html.style.overflowAnchor=previousAnchor;else html.style.removeProperty('overflow-anchor');});
 }
}
