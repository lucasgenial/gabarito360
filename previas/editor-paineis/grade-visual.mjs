import {GridStack} from 'gridstack';
import {clone,nodes,obter,filhos,normalizar,visivel,profundidade,MAX_ROWS} from './modelo.mjs';
import {capturarContinuidade} from './continuidade-visual.mjs';
import {conectarGestosGrade} from './gestos-grade.mjs';

/** Uma grade por pai: raiz e contêineres usam exatamente o mesmo sistema. */
export function montarGrade({target,state,context,editing,mobile,ui,callbacks,widgetMarkup}){
 const {escape,btn}=ui,grids=[],limits=new Map(),headers=new Map();let frame=null,before=null,gestureView=null,pointerView=null,gestures=null,destroying=false;
 const shouldShow=n=>editing?(n.kind==='section'||n.active):visivel(state,n.id);
 const controls=n=>editing?'<div class="controles-no">'+btn((n.fixo?'Liberar':'Fixar')+' posição e tamanho de '+n.title,'fixar','data-no-pin="'+n.id+'" aria-pressed="'+n.fixo+'"')+btn('Mover '+n.title+'. Setas movem; Shift e setas redimensionam nos ajustes finos.','arrastar','data-no-mover="'+n.id+'" '+(n.fixo?'disabled':''))+'</div>':'';
 function markup(n,isMobile=false){
  if(n.kind==='widget')return widgetMarkup(n.id,false,{title:escape(n.title),controls:controls(n)});
  const level=profundidade(state,n.id)>0?3:2;
  return '<section class="painel-visual" data-painel-visual="'+n.id+'"><header class="secao-cabecalho"><h'+level+' class="'+(level===2?'titulo-painel':'titulo-subsecao')+'">'+escape(n.title)+'</h'+level+'>'+controls(n)+'</header><div class="secao-corpo">'+group(n.id,isMobile)+'</div></section>';
 }
 function group(parent,isMobile){
  const items=filhos(state,parent).filter(shouldShow);
  return '<div class="'+(isMobile?'arvore-mobile':'grid-stack')+' '+(parent===null?'grade-raiz':'grade-interna')+'" data-parent-grid="'+(parent||'')+'">'+items.map(n=>isMobile?'<div class="mobile-no" data-node="'+n.id+'">'+markup(n,true)+'</div>':'<div class="grid-stack-item" gs-id="'+n.id+'" gs-x="'+n.x+'" gs-y="'+n.y+'" gs-w="'+n.w+'" gs-h="'+n.h+'"'+(n.fixo?' gs-locked="true" gs-no-move="true" gs-no-resize="true"':'')+'><div class="grid-stack-item-content">'+markup(n)+'</div></div>').join('')+'</div>';
 }
 const visible=nodes(state).some(n=>n.kind==='widget'&&n.active);
 target.innerHTML=group(null,mobile)+(!visible&&!editing?'<div class="vazio">Todos os widgets estão ocultos. Reative-os na edição do painel pelo desktop.</div>':'');
 const wrappers=new Set(target.querySelectorAll('.grid-stack-item'));
 const prepareGesture=e=>{const el=e.target.closest('.grid-stack-item:not(.grid-stack-placeholder)');pointerView=wrappers.has(el)&&e.target.closest('[data-no-mover],.ui-resizable-handle')?capturarContinuidade(target,el.getAttribute('gs-id')):null;};
 if(editing)target.addEventListener('mousedown',prepareGesture,true);
 function minPixel(n){if(n.kind==='widget')return callbacks.definitions().widgets.find(w=>w.id===n.id)?.type==='lista'?420:280;const children=filhos(state,n.id).filter(shouldShow);return Math.max(320,...children.map(child=>minPixel(child)*12/child.w+32));}
 // O mínimo depende do conteúdo já posicionado, nunca da altura antiga do pai.
 function refreshBounds(){
  for(const grid of [...grids].reverse())for(const node of grid.engine.nodes){
   if(obter(state,node.id).kind!=='section')continue;
   const head=node.el.querySelector('.painel-visual > .secao-cabecalho');
   const rows=Math.min(8,Math.max(3,Math.ceil((head.getBoundingClientRect().height+24)/40)));
   headers.set(node.id,rows);
   const children=node.subGrid?.engine.nodes||[];
   const bottom=Math.max(3,...children.map(n=>n.y+n.h));
   const bound=limits.get(node.id);bound.minH=rows+bottom;
   grid.update(node.el,{minH:bound.minH});
  }
 }
 function capture(snapshot){
  const next=clone(snapshot),seen=new Set();
  for(const g of grids){const parent=g.el.dataset.parentGrid||null;for(const item of g.engine.nodes){if(seen.has(item.id)||!obter(next,item.id))throw Error('O conjunto de itens mudou durante o gesto.');seen.add(item.id);Object.assign(obter(next,item.id),{parent,x:item.x,y:item.y,w:item.w,h:item.h});}}
  for(const section of next.sections)if(headers.has(section.id))section.headerRows=headers.get(section.id);
  const expected=nodes(snapshot).filter(shouldShow);
  if(expected.some(n=>!seen.has(n.id))||seen.size!==expected.length)throw Error('O gesto não terminou em uma grade válida.');
  for(const n of nodes(next))if(n.parent!==obter(snapshot,n.id).parent)throw Error('Mude a seção somente na aba Distribuição.');
  return next;
 }
 function commit(gesture){
  frame=null;if(destroying||!before||!gestures.current(gesture))return;const snapshot=before,view=capturarContinuidade(target,gestureView.id);if(!view.focus&&[document.body,document.documentElement].includes(document.activeElement))view.focus=gestureView.focus;before=null;gestures.complete(gesture);
  try{refreshBounds();const next=capture(snapshot);for(const anchor of nodes(snapshot).filter(n=>n.fixo&&shouldShow(n))){const n=obter(next,anchor.id);if(['parent','x','y','w','h'].some(k=>n[k]!==anchor[k]))throw Error('Um item fixado não pode mudar de posição ou tamanho.');}
   callbacks.change(normalizar(next,context),view);callbacks.say('Composição atualizada no rascunho.');
  }catch(error){callbacks.restore(snapshot,error.message,gestureView);}
 }
 const schedule=gesture=>{if(!before||destroying)return;if(frame!==null)cancelAnimationFrame(frame);frame=requestAnimationFrame(()=>commit(gesture));};
 if(!mobile){
  for(const el of target.querySelectorAll('[data-parent-grid]')){
   const parent=el.dataset.parentGrid||null,items=filhos(state,parent).filter(shouldShow),minRow=editing&&(parent===null||!items.length)?3:0;
   const grid=GridStack.init({column:12,cellHeight:40,margin:6,float:false,layout:'compact',staticGrid:!editing,animate:false,handle:'[data-no-mover]',minRow,maxRow:MAX_ROWS,acceptWidgets:false,subGridDynamic:false,alwaysShowResizeHandle:editing,resizable:{handles:'se'},disableResize:!editing},el);
   grids.push(grid);
   const width=el.clientWidth;
   for(const node of grid.engine.nodes){const original=obter(state,node.id),def=original.kind==='widget'?callbacks.definitions().widgets.find(w=>w.id===original.id):{minW:4},minW=Math.min(12,Math.max(def.minW,Math.ceil(minPixel(original)/(Math.max(1,width)/12))));const bound={minW,minH:original.kind==='widget'?def.minH:3};limits.set(original.id,bound);grid.update(node.el,{...bound,maxW:12,maxH:MAX_ROWS,locked:original.fixo,noMove:!editing||original.fixo,noResize:!editing||original.fixo});}
  }
  // Depois que as larguras nativas existem, medir os cabeçalhos e propagar
  // mínimos das subgrades de baixo para cima, sem cortar texto ou conteúdo.
  refreshBounds();
  if(editing)gestures=conectarGestosGrade({root:grids[0],grids,wrappers,validNode:id=>{const n=obter(state,id);return n&&!n.fixo;},begin:gesture=>{before=capture(state);gestureView=pointerView?.id===gesture.id?pointerView:capturarContinuidade(target,gesture.id);pointerView=null;},finish:schedule});
 }
 target.querySelectorAll('[data-no-pin]').forEach(b=>b.addEventListener('click',()=>callbacks.pin(b.dataset.noPin)));
 target.querySelectorAll('[data-no-mover]').forEach(b=>b.addEventListener('keydown',e=>{
  if(!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(e.key))return;e.preventDefault();e.stopPropagation();
  if(before)return;
  try{refreshBounds();const next=capture(state),n=obter(next,b.dataset.noMover),bound=limits.get(n.id);if(n.fixo)return;const horizontal=['ArrowLeft','ArrowRight'].includes(e.key),delta=['ArrowLeft','ArrowUp'].includes(e.key)?-1:1,key=e.shiftKey?(horizontal?'w':'h'):(horizontal?'x':'y');n[key]+=delta;
   if(n.w<bound.minW||n.h<bound.minH)throw Error('O conteúdo precisa desse espaço mínimo.');callbacks.change(normalizar(next,context),capturarContinuidade(target,n.id));callbacks.focus('[data-no-mover="'+n.id+'"]');callbacks.say('Item ajustado dentro da grade.');
  }catch(error){callbacks.say(error.message,'erro');}
 }));
 const escapeGesture=e=>{if(e.key==='Escape'&&before){e.preventDefault();const snapshot=before;before=null;gestures?.cancel();if(frame!==null)cancelAnimationFrame(frame);callbacks.restore(snapshot,'Gesto cancelado.',gestureView);}};
 document.addEventListener('keydown',escapeGesture);
 return {destroy(){
  if(destroying)return;destroying=true;before=null;pointerView=null;gestures?.destroy();target.removeEventListener('mousedown',prepareGesture,true);document.removeEventListener('keydown',escapeGesture);if(frame!==null)cancelAnimationFrame(frame);
  // Encerrar DD nos wrappers reais ANTES de destruir as engines. Um helper
  // ativo pode estar no body, fora da grade e de engine.nodes.
  target.querySelectorAll('.grid-stack-item:not(.grid-stack-placeholder)').forEach(el=>wrappers.add(el));const dd=GridStack.getDD();
  for(const el of wrappers){dd.resizable(el,'destroy');dd.draggable(el,'destroy');}
  for(const grid of grids.reverse())if(grid.el?.gridstack)grid.destroy(false);
 },grids};
}
