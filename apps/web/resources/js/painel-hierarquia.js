/** Grupos verticais: nunca alteram a geometria relativa nem o catálogo de widgets. */
export function orderFromLayout(layout, definitions){
 const ranks=new Map(),cards=new Map();
 for(const n of layout){
  const card=Object.keys(definitions).find(id=>definitions[id].includes(n.section));if(!card)continue;
  if(n.ordem_card===undefined&&n.ordem_secao===undefined)continue;
  for(const key of ['ordem_card','ordem_secao'])if(!Number.isInteger(n[key])||n[key]<0||n[key]>63)throw Error('Ordem de grupo inválida');
  if(cards.has(card)&&cards.get(card)!==n.ordem_card||ranks.has(n.section)&&ranks.get(n.section)!==n.ordem_secao)throw Error('Ordem de grupo inconsistente');
  cards.set(card,n.ordem_card);ranks.set(n.section,n.ordem_secao);
 }
 if(new Set(cards.values()).size!==cards.size)throw Error('Cards com ordem repetida');
 for(const sections of Object.values(definitions)){const values=sections.filter(id=>ranks.has(id)).map(id=>ranks.get(id));if(new Set(values).size!==values.length)throw Error('Seções com ordem repetida');}
 const sort=(ids,map)=>ids.map((id,index)=>({id,index,rank:map.get(id)??index})).sort((a,b)=>a.rank-b.rank||a.index-b.index).map(v=>v.id);
 return {cards:sort(Object.keys(definitions),cards),sections:Object.fromEntries(Object.entries(definitions).map(([id,sections])=>[id,sort(sections,ranks)]))};
}

export function annotateOrder(layout,order){
 return layout.map(n=>{
  const card=order.cards.find(id=>order.sections[id].includes(n.section));
  return card?{...n,ordem_card:order.cards.indexOf(card),ordem_secao:order.sections[card].indexOf(n.section)}:{...n};
 });
}

export function moveGroup(ids,id,target){
 if(!ids.includes(id)||!Number.isInteger(target)||target<0||target>=ids.length)return ids.slice();
 const next=ids.filter(v=>v!==id);next.splice(target,0,id);return next;
}

export function createPanelHierarchy({signal,canEdit,onChange,say}){
 const cards=new Map(),sections=new Map(),anchors=new Map(),definitions={};
 for(const el of document.querySelectorAll('[data-painel-card]')){
  const id=el.dataset.painelCard,items=[...el.querySelectorAll('[data-painel-secao]')].filter(s=>s.closest('[data-painel-card]')===el&&s.querySelector('[data-widget-id]'));
  if(!items.length)continue;
  cards.set(id,el);definitions[id]=items.map(s=>s.dataset.painelSecao);
  for(const section of items){const unit=section.closest('[data-painel-secao-bloco]')||section;sections.set(section.dataset.painelSecao,{el:section,unit,card:id});}
 }
 const makeAnchor=(id,elements)=>{if(!elements.length)return;const anchor=document.createComment('fim do grupo '+id);elements.at(-1).after(anchor);anchors.set(id,anchor);};
 makeAnchor('cards',[...cards.values()]);
 for(const [id,list]of Object.entries(definitions))makeAnchor(id,list.map(v=>sections.get(v).unit));
 let order=orderFromLayout([],definitions),gesture=null,frame=null;
 const handles=[...document.querySelectorAll('[data-painel-grupo-mover]')];
 const record=handle=>{
  const type=handle.dataset.painelGrupoMover;
  if(type==='card'){const el=handle.closest('[data-painel-card]');return el&&cards.has(el.dataset.painelCard)?{type,id:el.dataset.painelCard,unit:el,parent:'cards'}:null;}
  const el=handle.closest('[data-painel-secao]'),item=el&&sections.get(el.dataset.painelSecao);
  return item?{type,id:el.dataset.painelSecao,unit:item.unit,parent:item.card}:null;
 };
 const ids=r=>r.type==='card'?order.cards:order.sections[r.parent];
 const unit=(r,id)=>r.type==='card'?cards.get(id):sections.get(id).unit;
 function draw(layout){
  order=orderFromLayout(layout,definitions);
  const focused=document.activeElement;
  const place=(ids,get,anchor)=>{
   if(!anchor)return;
   const wanted=ids.map(get),current=[...anchor.parentNode.children].filter(el=>wanted.includes(el));
   if(wanted.every((el,i)=>el===current[i]))return;
   for(const el of wanted)anchor.before(el);
  };
  place(order.cards,id=>cards.get(id),anchors.get('cards'));
  for(const [card,list]of Object.entries(order.sections))place(list,id=>sections.get(id).unit,anchors.get(card));
  if(handles.includes(focused)&&focused.isConnected)focused.focus({preventScroll:true});
 }
 function clear(){
  if(frame!==null)cancelAnimationFrame(frame);frame=null;
  if(gesture){gesture.r.unit.classList.remove('painel-grupo-arrastando');try{gesture.handle.releasePointerCapture(gesture.pointer);}catch{}}
  document.querySelectorAll('[data-grupo-insercao]').forEach(el=>delete el.dataset.grupoInsercao);gesture=null;
 }
 function commit(r,target,handle){
  const previous=ids(r),next=moveGroup(previous,r.id,target);if(next.every((v,i)=>v===previous[i]))return;
  const changed=structuredClone(order);if(r.type==='card')changed.cards=next;else changed.sections[r.parent]=next;
  onChange(changed);handle.focus({preventScroll:true});say((r.type==='card'?'Card':'Seção')+' reposicionado. Salve a disposição para manter.');
 }
 function destination(){
  if(!gesture)return;
  const g=gesture,r=g.r,available=ids(r).filter(id=>id!==r.id),parent=anchors.get(r.parent).parentElement,box=parent.getBoundingClientRect();
  document.querySelectorAll('[data-grupo-insercao]').forEach(el=>delete el.dataset.grupoInsercao);
  if(g.x<box.left||g.x>box.right){g.target=null;return;}
  const index=available.findIndex(id=>{const b=unit(r,id).getBoundingClientRect();return g.y<b.top+b.height/2;});
  g.target=index<0?available.length:index;
  if(available.length){const target=unit(r,available[Math.min(g.target,available.length-1)]);target.dataset.grupoInsercao=g.target===available.length?'depois':'antes';}
 }
 function tick(){
  frame=null;if(!gesture||!canEdit()){clear();return;}
  const y=gesture.y,h=innerHeight,delta=y<80?-12:y>h-80?12:0;if(delta)window.scrollBy(0,delta);
  destination();frame=requestAnimationFrame(tick);
 }
 for(const handle of handles){
  handle.addEventListener('keydown',e=>{
   if(!canEdit()||gesture||!['ArrowUp','ArrowDown'].includes(e.key))return;
   const r=record(handle);if(!r)return;e.preventDefault();e.stopPropagation();
   commit(r,ids(r).indexOf(r.id)+(e.key==='ArrowUp'?-1:1),handle);
  },{signal});
  handle.addEventListener('pointerdown',e=>{
   if(!canEdit()||gesture||e.button!==0)return;const r=record(handle);if(!r||ids(r).length<2)return;
   e.preventDefault();e.stopPropagation();handle.focus({preventScroll:true});
   gesture={r,handle,pointer:e.pointerId,x:e.clientX,y:e.clientY,startX:e.clientX,startY:e.clientY,moved:false,target:null};handle.setPointerCapture(e.pointerId);
  },{signal});
  handle.addEventListener('pointermove',e=>{
   if(!gesture||gesture.handle!==handle||gesture.pointer!==e.pointerId)return;
   gesture.x=e.clientX;gesture.y=e.clientY;
   if(!gesture.moved&&Math.hypot(e.clientX-gesture.startX,e.clientY-gesture.startY)>=8){gesture.moved=true;gesture.r.unit.classList.add('painel-grupo-arrastando');frame=requestAnimationFrame(tick);}
   if(gesture.moved){e.preventDefault();destination();}
  },{signal});
  handle.addEventListener('pointerup',e=>{
   if(!gesture||gesture.handle!==handle||gesture.pointer!==e.pointerId)return;
   const g=gesture,allowed=canEdit();destination();const target=g.target;clear();
   if(allowed&&g.moved&&target!==null)commit(g.r,target,handle);
  },{signal});
  handle.addEventListener('pointercancel',clear,{signal});handle.addEventListener('lostpointercapture',clear,{signal});
 }
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&gesture){e.preventDefault();clear();say('Movimento do grupo cancelado.');}},{signal});
 window.addEventListener('blur',clear,{signal});window.addEventListener('resize',clear,{signal});signal.addEventListener('abort',clear,{once:true});
 return {render:draw,annotate:layout=>annotateOrder(layout,order),cancel:clear,active:()=>gesture!==null,controls:()=>{
  const enabled=canEdit();if(!enabled)clear();
  for(const h of handles){const r=record(h);h.hidden=!enabled||!r||ids(r).length<2;h.disabled=!enabled;}
 }};
}
