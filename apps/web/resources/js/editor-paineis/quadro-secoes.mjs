import Sortable from 'sortablejs';
import {clone,filhos,obter,podeMover,transferir,reordenar,validar,caminho} from './modelo.mjs';

/** Distribuição continua em colunas; só Ajustes finos desenha a grade final. */
export function montarQuadro({container,state,context,ui,callbacks}){
 const {escape,icon,btn}=ui,instances=[];let before=null,destroying=false;
 const eligibleParent=(id,parent)=>podeMover(state,id,parent);
 const select=n=>'<label for="destino-'+n.id+'">Seção</label><select id="destino-'+n.id+'" data-destino="'+n.id+'" aria-label="Mover '+escape(n.title)+' para seção"><option value="" '+(n.parent===null?'selected':'')+'>'+ (n.kind==='section'?'Área do painel':'Sem seção')+'</option>'+state.sections.filter(s=>eligibleParent(n.id,s.id)).map(s=>'<option value="'+s.id+'" '+(n.parent===s.id?'selected':'')+'>'+escape(caminho(state,s.id))+'</option>').join('')+(n.kind==='widget'?'<option value="__nova__">Criar nova seção…</option>':'')+'</select>';
 const handle=n=>btn('Mover '+(n.kind==='section'?'painel ':'widget ')+n.title,'arrastar','data-quadro-mover="'+n.id+'" '+(n.fixo?'disabled':''));
 const title=n=>'<button class="titulo-editavel '+(n.kind==='section'?'titulo-subsecao':'titulo-widget')+'" data-quadro-titulo="'+n.id+'">'+escape(n.title)+icon('editar')+'</button>';
 const controls=n=>n.kind==='widget'?'<label class="ativacao"><input type="checkbox" data-quadro-ativo="'+n.id+'" '+(n.active?'checked':'')+'>Mostrar no painel</label>':btn('Remover painel','arquivo','data-quadro-remover="'+n.id+'"',true);
 const arrows=n=>btn('Subir '+n.title,'seta','data-quadro-ordem="'+n.id+'" data-delta="-1"').replace('class="icone"','class="icone tw:-rotate-90"')+btn('Descer '+n.title,'seta','data-quadro-ordem="'+n.id+'" data-delta="1"').replace('class="icone"','class="icone tw:rotate-90"');
 function item(n){
  const section=n.kind==='section';return '<article class="item-no '+(section?'subsecao-quadro':'item-widget')+' '+(n.active===false?'inativo':'')+'" data-item="'+n.id+'"><div class="item-topo">'+handle(n)+'<div>'+title(n)+'</div></div><div class="item-acoes">'+controls(n)+arrows(n)+'</div><div class="transferencia">'+select(n)+'</div>'+(section?list(n.id):'')+'</article>';
 }
 function list(parent){return '<div class="lista-widgets lista-composicao" data-pai="'+(parent||'')+'">'+filhos(state,parent).map(item).join('')+'</div>';}
 const roots=filhos(state,null).filter(n=>n.kind==='section').reverse();
 container.innerHTML='<section class="coluna coluna-avulsos" data-fixa><header class="coluna-topo"><div class="coluna-ordem">Widgets avulsos</div><h2 class="titulo-subsecao">Sem seção</h2></header><div class="lista-widgets lista-composicao" data-pai="">'+filhos(state,null).filter(n=>n.kind==='widget').map(item).join('')+'</div></section>'+roots.map((n,i)=>'<section class="coluna" data-item="'+n.id+'"><header class="coluna-topo"><div class="coluna-ordem"><span>'+(i===0?'Base do painel':i===roots.length-1?'Topo do painel':'Posição '+(i+1))+'</span><span>'+filhos(state,n.id).length+' itens</span></div><div class="coluna-titulo">'+handle(n)+'<div>'+title(n)+'</div></div><div class="item-acoes">'+controls(n)+btn('Mover painel para a esquerda','seta','data-coluna="'+n.id+'" data-delta="-1" '+(i===0?'disabled':'')).replace('class="icone"','class="icone tw:rotate-180"')+btn('Mover painel para a direita','seta','data-coluna="'+n.id+'" data-delta="1" '+(i===roots.length-1?'disabled':''))+'</div><div class="transferencia">'+select(n)+'</div></header>'+list(n.id)+'</section>').join('');
 const all=selector=>[...container.querySelectorAll(selector)];
 all('[data-quadro-mover]').forEach(b=>b.classList.add('mover-composicao'));
 all('[data-quadro-titulo]').forEach(b=>b.addEventListener('click',()=>callbacks.rename(b.dataset.quadroTitulo,b.parentElement)));
 all('[data-quadro-remover]').forEach(b=>b.addEventListener('click',()=>callbacks.remove(b.dataset.quadroRemover)));
 all('[data-quadro-ativo]').forEach(b=>b.addEventListener('change',()=>callbacks.toggle(b.dataset.quadroAtivo,b.checked)));
 all('[data-destino]').forEach(s=>s.addEventListener('change',()=>s.value==='__nova__'?callbacks.create(s.dataset.destino):callbacks.move(s.dataset.destino,s.value||null)));
 function reorder(id,delta,columns=false){
  try{const n=obter(state,id),parent=n.parent,siblings=columns?filhos(state,null).filter(n=>n.kind==='section').reverse():filhos(state,parent),from=siblings.findIndex(n=>n.id===id),to=from+delta;if(from<0||to<0||to>=siblings.length)return;const order=siblings.map(n=>n.id);order.splice(to,0,order.splice(from,1)[0]);const others=filhos(state,parent).filter(n=>!order.includes(n.id)).map(n=>n.id),ids=columns?[...others,...order.reverse()]:order;callbacks.change(reordenar(state,parent,ids,context));callbacks.focus('[data-quadro-mover="'+id+'"]');callbacks.say('Distribuição atualizada no rascunho.');}catch(e){callbacks.restore(state,e.message);}
 }
 all('[data-coluna]').forEach(b=>b.addEventListener('click',()=>reorder(b.dataset.coluna,Number(b.dataset.delta),true)));
 all('[data-quadro-ordem]').forEach(b=>b.addEventListener('click',()=>reorder(b.dataset.quadroOrdem,Number(b.dataset.delta))));
 all('[data-quadro-mover]').forEach(b=>b.addEventListener('keydown',e=>{const id=b.dataset.quadroMover,n=obter(state,id),column=n.kind==='section'&&n.parent===null;if(!(column?['ArrowLeft','ArrowRight']:['ArrowUp','ArrowDown']).includes(e.key))return;e.preventDefault();reorder(id,['ArrowLeft','ArrowUp'].includes(e.key)?-1:1,column);}));
 const destination=el=>el===container?null:el.dataset.pai||null;
 const common={forceFallback:true,fallbackOnBody:true,fallbackClass:'pe-arraste',fallbackTolerance:5,animation:matchMedia('(prefers-reduced-motion:reduce)').matches?0:150,ghostClass:'sortable-ghost',handle:'.mover-composicao',group:{name:'composicao-paineis',pull:true,put:(to,_from,element)=>{
  const id=element.dataset.item;return (to.el!==container||obter(state,id)?.kind==='section')&&eligibleParent(id,destination(to.el));
 }},onStart:()=>{before=clone(state);},onEnd:event=>{
  if(!before||destroying)return;const snapshot=before;before=null;
  try{const id=event.item.dataset.item,parent=destination(event.to),source=obter(snapshot,id).parent;let next=source===parent?clone(snapshot):transferir(snapshot,id,parent,context);
   const shown=[...event.to.children].filter(e=>e.hasAttribute('data-item')).map(e=>e.dataset.item);
   const order=event.to===container?shown.reverse():shown;
   const other=filhos(next,parent).filter(n=>!order.includes(n.id)).map(n=>n.id);next=reordenar(next,parent,[...other,...order],context);validar(next,context);callbacks.change(next);callbacks.focus('[data-quadro-mover="'+id+'"]');callbacks.say('Item movido na distribuição.');
  }catch(error){callbacks.restore(snapshot,error.message);}
 }};
 instances.push(new Sortable(container,{...common,draggable:'.coluna:not([data-fixa])',direction:'horizontal',onMove:e=>!e.related?.hasAttribute('data-fixa')}));
 all('[data-pai]').forEach(el=>instances.push(new Sortable(el,{...common,draggable:'.item-no',emptyInsertThreshold:40,direction:'vertical'})));
 const cancel=e=>{if(e.key==='Escape'&&before){e.preventDefault();const snapshot=before;before=null;callbacks.restore(snapshot,'Gesto cancelado.');}};document.addEventListener('keydown',cancel);
 return {destroy(){destroying=true;document.removeEventListener('keydown',cancel);before=null;instances.forEach(s=>s.destroy());}};
}
