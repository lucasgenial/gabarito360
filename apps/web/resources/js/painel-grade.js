import {GridStack} from 'gridstack';
import {rowsOf,normalizeRows,project} from './painel-linhas.js';
// GridStack cuida dos gestos; o adaptador organiza linhas apenas dentro da seção autorizada.
function iniciar(){
 const configEl=document.getElementById('painel-grade-config');if(!configEl)return;
 window.G360PainelGradeDesmontar?.();
 let config;try{config=JSON.parse(configEl.textContent)}catch{return}
 const life=new AbortController(),media=matchMedia('(min-width:1024px) and (min-height:700px)');
 const toolbar=document.querySelector('[data-painel-organizacao]');if(!toolbar)return;
 const button=name=>toolbar.querySelector('[data-grade-'+name+']');
 const status=toolbar.querySelector('[data-grade-status]'),actions=toolbar.querySelector('[data-grade-acoes]');
 const widgets=new Map([...document.querySelectorAll('[data-widget-id]')].filter(el=>el.dataset.section!=='resumo').map((el,index)=>[el.dataset.widgetId,{el,index,parent:el.parentElement}]));
 const sections=new Map([...document.querySelectorAll('[data-painel-secao]')].filter(el=>el.dataset.painelSecao!=='resumo').map(el=>[el.dataset.painelSecao,{el,grade:el.querySelector('.painel-secao-grade'),grid:null,style:null}]));
 let api=null,limits=new Map(),layout=[],saved=[],presentation='tabela',savedPresentation='tabela',editing=false,dirty=false,busy=false,invalid=false,uncertain=false,epoch=0,gesture=null,resizeOrigin=null,point={x:0,y:0};
 const chartIds=new Set(['distribuicao-acertos','resultados-avaliacao','periodos-escolares','habilidades','reavaliacoes']);
 const uuid=()=>{if(typeof crypto.randomUUID==='function')return crypto.randomUUID();const b=crypto.getRandomValues(new Uint8Array(16));b[6]=(b[6]&15)|64;b[8]=(b[8]&63)|128;const h=[...b].map(n=>n.toString(16).padStart(2,'0')).join('');return [h.slice(0,8),h.slice(8,12),h.slice(12,16),h.slice(16,20),h.slice(20)].join('-');};
 const requests=new Set(),clone=value=>structuredClone(value),eligible=()=>media.matches&&!invalid&&configEl.isConnected;
 const say=text=>status.textContent=text;
 function list(value){presentation=value;document.querySelectorAll('[data-painel-lista]').forEach(el=>el.dataset.apresentacao=value);document.querySelectorAll('[data-lista-modo]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.listaModo===value)));}
 function controls(){
  toolbar.hidden=!eligible()||(api&&limits.size===0&&!dirty);button('organizar').hidden=editing||(api&&limits.size===0);button('organizar').disabled=busy||!api||uncertain;
  actions.hidden=!(editing||dirty);actions.querySelectorAll('button').forEach(b=>b.disabled=busy||uncertain);
  button('consultar').hidden=!uncertain;button('consultar').disabled=busy;
  document.querySelectorAll('[data-lista-modo]').forEach(b=>b.disabled=invalid||(eligible()&&(busy||uncertain||!api)));
  for(const [id,{el}]of widgets){const n=layout.find(n=>n.id===id),fixed=n?.fixo===true,free=n?.altura_livre===true;
   for(const attr of ['mover','fixar','altura-livre']){const b=el.querySelector('[data-widget-'+attr+']');if(!b)continue;b.hidden=!editing||!eligible()||!n;b.disabled=busy||uncertain||(fixed&&attr!=='fixar');}
   const pin=el.querySelector('[data-widget-fixar]');if(pin){pin.setAttribute('aria-pressed',String(fixed));pin.title=fixed?'Liberar posição e tamanho':'Fixar posição e tamanho';pin.setAttribute('aria-label',(fixed?'Liberar':'Fixar')+' posição e tamanho de '+el.querySelector('h3').textContent);}
   const height=el.querySelector('[data-widget-altura-livre]');if(height){height.setAttribute('aria-pressed',String(free));height.title=fixed?'Libere a posição para alterar a altura':free?'Usar altura da linha':'Altura independente da linha';}
   const chart=el.querySelector('[data-grafico-orientacao]');if(chart)chart.disabled=busy||uncertain||invalid;
  }
  for(const s of sections.values())if(s.grid){const enabled=editing&&!busy&&!uncertain;s.grid.enableMove(enabled);s.grid.enableResize(enabled);s.grid.el.dataset.editando=String(enabled);for(const n of bySection(layout,s.el.dataset.painelSecao)){const wrap=widgets.get(n.id).el.parentElement;s.grid.update(wrap,{locked:n.fixo===true,noMove:!enabled||n.fixo===true,noResize:!enabled||n.fixo===true});}}
 }
 function bounds(n){return limits.get(n.id);}
 function normalize(rows,priority=null,anchors=null){return normalizeRows(rows,limits,priority,anchors?{anchors}:undefined);}
 function bySection(items,id){return items.filter(n=>n.section===id);}
 function defaults(){const out=[];for(const id of sections.keys()){
  const rows=[];let row=[],width=0;
  for(const [key,{el}]of widgets){const c=limits.get(key);if(!c||el.dataset.section!==id)continue;
   const w=Math.max(c.minW,Math.min(c.maxW,Number(el.dataset.defaultW)||c.defaultW)),h=Math.max(c.minH,Math.min(c.maxH,Number(el.dataset.defaultH)||c.defaultH));
   if(row.length&&width+w>12){rows.push(row);row=[];width=0;}row.push({id:key,section:id,x:width,y:0,w,h,altura_desejada:h});width+=w;
  }if(row.length)rows.push(row);out.push(...normalize(rows));
 }return out;}
 function incomplete(){const error=new Error('A tela não carregou todos os cards ou seus padrões. Recarregue a página antes de salvar a disposição.');error.feedback=true;return error;}
 function prepare(d){
  if(!d||!Number.isInteger(d.versao)||!Array.isArray(d.catalogo)||!Array.isArray(d.layouts?.desktop)||!['tabela','cards'].includes(d.apresentacao))throw Error('Resposta indisponível');
  const next=new Map();for(const c of d.catalogo){const item=widgets.get(c.id),b=c.desktop;
   if(!item||!item.el.isConnected||item.el.dataset.section!==c.section||item.el.closest('[data-painel-secao]')?.dataset.painelSecao!==c.section)throw incomplete();
   if(next.has(c.id)||!b||!['minW','maxW','minH','maxH','defaultW','defaultH'].every(k=>Number.isInteger(b[k]))||b.minW<1||b.maxW>12||b.minH<1||b.maxH>200)throw Error('Limites indisponíveis');
   next.set(c.id,{...b,section:c.section});
  }limits=next;
  const read=items=>{const result=[],seen=new Set();for(const raw of items){
   if(!limits.has(raw.id))continue;
   if(seen.has(raw.id)||bounds(raw).section!==raw.section||!['x','y','w','h'].every(k=>Number.isInteger(raw[k]))||!Number.isInteger(raw.altura_desejada??raw.h)||['fixo','altura_livre'].some(k=>Object.hasOwn(raw,k)&&typeof raw[k]!=='boolean')||(Object.hasOwn(raw,'orientacao')&&(!chartIds.has(raw.id)||!['horizontal','vertical'].includes(raw.orientacao))))throw Error('Disposição inválida');
   const c=bounds(raw),wanted=raw.altura_desejada??raw.h;
   if(raw.x<0||raw.y<0||raw.x+raw.w>12||raw.y+raw.h>200||raw.w<c.minW||raw.w>c.maxW||raw.h<c.minH||raw.h>c.maxH||wanted<c.minH||wanted>raw.h)throw Error('Disposição inválida');
   seen.add(raw.id);result.push(project(raw));
  }return result;};
  const hasRoleDefault=d.padrao!==undefined;
  if(hasRoleDefault&&!Array.isArray(d.padrao?.desktop))throw incomplete();
  const standard=hasRoleDefault?read(d.padrao.desktop):defaults();
  if(hasRoleDefault&&standard.length!==limits.size)throw incomplete();
  const server=d.layouts.desktop.length?read(d.layouts.desktop):clone(standard),seen=new Set(server.map(n=>n.id)),result=[];
  for(const section of sections.keys()){
   const rows=rowsOf(bySection(server,section));
   for(const row of rowsOf(bySection(standard,section))){const missing=row.filter(n=>!seen.has(n.id));if(missing.length)rows.push(missing);}
   result.push(...normalize(rows));
  }return result;
 }
 function destroyGrids(){
  for(const s of sections.values())if(s.grid){s.grid.destroy(false);s.grid=null;for(const record of [...widgets.values()].sort((a,b)=>a.index-b.index))if(record.parent===s.grade){const wrapper=record.el.parentElement;if(wrapper?.classList.contains('grid-stack-item'))wrapper.replaceWith(record.el);record.el.classList.remove('grid-stack-item-content');s.grade.append(record.el);}
   s.grade.classList.remove('grid-stack');s.grade.removeAttribute('data-editando');if(s.style===null)s.grade.removeAttribute('style');else s.grade.setAttribute('style',s.style);
  }gesture=null;
 }
 function render(){if(!eligible())return;
  for(const n of layout){const el=widgets.get(n.id)?.el;if(!el)continue;el.dataset.fixo=String(n.fixo===true);el.dataset.alturaLivre=String(n.altura_livre===true);if(n.orientacao)el.dataset.orientacao=n.orientacao;else delete el.dataset.orientacao;}
  for(const [id,s]of sections){const items=bySection(layout,id);if(!items.length)continue;
   if(!s.grid){s.style=s.grade.getAttribute('style');s.grade.classList.add('grid-stack');
    for(const n of items){const {el}=widgets.get(n.id),wrap=document.createElement('div');wrap.className='grid-stack-item';for(const k of ['id','x','y','w','h'])wrap.setAttribute('gs-'+k,String(n[k]));el.replaceWith(wrap);el.classList.add('grid-stack-item-content');wrap.append(el);}
    s.grid=GridStack.init({column:12,cellHeight:48,margin:8,mode:'float',acceptWidgets:false,removable:false,subGridDynamic:false,handle:'.painel-widget-mover',resizable:{handles:'se'},alwaysShowResizeHandle:true,disableDrag:true,disableResize:true,maxRow:200,minRow:1,animate:false,sizeToContent:false},s.grade);
    s.grid.on('dragstart resizestart',(e,el)=>startGesture(s,id,e,el));s.grid.on('dragstop resizestop',(e,el)=>stopGesture(s,id,e,el));
   }
   s.grid.load(items.map(n=>{const b=bounds(n);return {id:n.id,x:n.x,y:n.y,w:n.w,h:n.h,minW:b.minW,maxW:b.maxW,minH:b.minH,maxH:b.maxH,locked:n.fixo===true,noMove:n.fixo===true,noResize:n.fixo===true,sizeToContent:false};}),false);
   s.grid.updateOptions({minRow:Math.min(200,Math.max(...items.map(n=>n.y+n.h))+(editing?3:0))});
  }controls();document.dispatchEvent(new CustomEvent('g360:painel-redimensionado'));
 }
 function startGesture(s,section,event,el){if(!editing||busy||uncertain||layout.find(n=>n.id===el.gridstackNode.id)?.fixo===true)return;const rows=rowsOf(bySection(layout,section));gesture={section,id:el.gridstackNode.id,rows:clone(rows),epoch,resizeStart:resizeOrigin?.id===el.gridstackNode.id?{...resizeOrigin}:null,cellHeight:s.grid.getCellHeight(true)*(s.grade.getBoundingClientRect().width/s.grade.clientWidth),rects:rows.map(row=>{
  const rects=row.map(n=>{const r=widgets.get(n.id).el.getBoundingClientRect();return {id:n.id,center:r.x+r.width/2+scrollX,top:r.top+scrollY,bottom:r.bottom+scrollY};});return {items:rects,top:Math.min(...rects.map(r=>r.top)),bottom:Math.max(...rects.map(r=>r.bottom))};
 })};}
 function stopGesture(s,section,event,el){
  const active=gesture;gesture=null;if(!active||active.section!==section)return;
  const requested={w:el.gridstackNode.w,h:el.gridstackNode.h},position={x:point.x+scrollX,y:point.y+scrollY},resize=event.type==='resizestop';
  // O autoscroll da página num resize horizontal não representa uma nova altura desejada.
  const verticalIntent=resize&&active.resizeStart?Math.round((point.y-active.resizeStart.clientY)/active.cellHeight):null;
  const verticalRows=verticalIntent===null?null:(verticalIntent===0?0:Math.round((position.y-active.resizeStart.pageY)/active.cellHeight));resizeOrigin=null;
  queueMicrotask(()=>{if(active.epoch!==epoch||!eligible()||!editing||busy||uncertain)return;
   let rows=clone(active.rows);const moved=rows.flat().find(n=>n.id===active.id);if(!moved)return;
   try{if(resize){moved.w=requested.w;if(verticalRows!==null){if(verticalRows!==0){const c=bounds(moved);moved.altura_desejada=Math.max(c.minH,Math.min(c.maxH,moved.h+verticalRows));}}else if(requested.h!==moved.h)moved.altura_desejada=requested.h;}else{
    rows=rows.map(row=>row.filter(n=>n.id!==moved.id));const i=active.rects.findIndex(r=>position.y>=r.top&&position.y<=r.bottom);
    if(i>=0){const at=rows[i].findIndex(n=>position.x<active.rects[i].items.find(r=>r.id===n.id).center);rows[i].splice(at<0?rows[i].length:at,0,moved);}else{const at=active.rects.findIndex(r=>r.top>position.y);rows.splice(at<0?rows.length:at,0,[moved]);}
   }
   const normalized=normalize(rows,resize?moved.id:null,active.rows.flat().filter(n=>n.fixo===true));layout=[...layout.filter(n=>n.section!==section),...normalized];dirty=true;render();say(resize?'Tamanho ajustado. Os cards com altura independente mantêm seu próprio tamanho. Salve para manter.':'Posição ajustada dentro da seção. Salve para manter.');
   }catch{render();say('Não foi possível encaixar o card respeitando seus limites. A disposição anterior foi mantida.');}
  });
 }
 function keyMove(e,id){if(!editing||busy||uncertain||!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(e.key))return;e.preventDefault();const item=layout.find(n=>n.id===id);if(!item||item.fixo===true)return;let rows=rowsOf(bySection(layout,item.section)),row=rows.find(r=>r.some(n=>n.id===id)),n=row.find(n=>n.id===id),priority=null;
  try{if(e.shiftKey){const c=bounds(item);if(['ArrowLeft','ArrowRight'].includes(e.key)){n.w=Math.max(c.minW,Math.min(c.maxW,n.w+(e.key==='ArrowRight'?1:-1)));priority=id;}else n.altura_desejada=Math.max(c.minH,Math.min(c.maxH,n.altura_desejada+(e.key==='ArrowDown'?1:-1)));}
   else if(['ArrowLeft','ArrowRight'].includes(e.key)){const a=row.indexOf(n),b=a+(e.key==='ArrowRight'?1:-1);if(b<0||b>=row.length)return;[row[a],row[b]]=[row[b],row[a]];}
   else{const ri=rows.indexOf(row),to=ri+(e.key==='ArrowDown'?1:-1);row.splice(row.indexOf(n),1);if(to<0)rows.unshift([n]);else if(to>=rows.length)rows.push([n]);else rows[to].push(n);}
   layout=[...layout.filter(a=>a.section!==item.section),...normalize(rows,priority,bySection(layout,item.section).filter(n=>n.fixo===true))];dirty=true;render();widgets.get(id).el.querySelector('[data-widget-mover]').focus({preventScroll:true});say('Disposição ajustada pelo teclado. Salve para manter.');
  }catch{say('O ajuste não cabe nos limites desta seção.');}
 }
 async function request(descriptor,body){const abort=new AbortController();requests.add(abort);const timer=setTimeout(()=>abort.abort(),25000);try{const response=await window.G360Requisicao(descriptor,{body:JSON.stringify(body||{}),credentials:'same-origin',headers:{Accept:'application/json'},signal:abort.signal});const d=await response.json();if(!response.ok){const error=new Error(response.status===409?'A organização ou o acesso mudou. Consulte o estado salvo antes de continuar.':'Não foi possível confirmar as preferências. Consulte o estado salvo antes de continuar.');error.status=response.status;throw error;}return d.data;}finally{clearTimeout(timer);requests.delete(abort);}}
 async function load(){if(!eligible())return;const current=++epoch;busy=true;uncertain=false;toolbar.hidden=false;say('Consultando sua disposição salva…');controls();try{const d=await request(config.consultar);if(current!==epoch||!eligible())return;const next=prepare(d);api=d;layout=next;saved=clone(next);savedPresentation=d.apresentacao;list(d.apresentacao);editing=false;dirty=false;render();say('Use Organizar painel para mover ou redimensionar os cards.');}catch(error){if(current!==epoch||!eligible())return;uncertain=true;say((error.status||error.feedback)?error.message:'As preferências estão indisponíveis. Consulte o estado salvo para tentar novamente.');}finally{if(current===epoch){busy=false;controls();}}}
 async function save(){if(!eligible()||!(editing||dirty)||busy||uncertain||!api)return;const current=epoch;busy=true;controls();say('Salvando disposição…');try{const d=await request(config.salvar,{versao:api.versao,chave:uuid(),acao:'salvar',apresentacao:presentation,layouts:{desktop:layout.map(project)}});if(current!==epoch||!eligible())return;const next=prepare(d);api=d;layout=next;saved=clone(next);savedPresentation=d.apresentacao;list(d.apresentacao);editing=false;dirty=false;render();say('Disposição salva.');}catch(error){if(current!==epoch||!eligible())return;uncertain=true;say((error.status||error.feedback)?error.message:'Não foi possível confirmar o salvamento. Consulte o estado salvo antes de continuar.');}finally{if(current===epoch){busy=false;controls();}}}
 button('organizar').addEventListener('click',()=>{if(!eligible()||!api||busy||uncertain)return;editing=true;render();say('Arraste pelo ícone ou use as setas. Shift e setas ajustam o tamanho. Salve ao terminar.');},{signal:life.signal});
 button('salvar').addEventListener('click',save,{signal:life.signal});button('consultar').addEventListener('click',load,{signal:life.signal});
 button('cancelar').addEventListener('click',()=>{if(busy)return;layout=clone(saved);list(savedPresentation);editing=false;dirty=false;render();say('Alterações descartadas.');button('organizar').focus();},{signal:life.signal});
 button('restaurar').addEventListener('click',()=>{if(!eligible()||!api||busy||uncertain)return;layout=prepare({...api,layouts:{...api.layouts,desktop:[]}});dirty=true;render();say('Padrão restaurado nesta tela. Salve para manter ou cancele para voltar.');},{signal:life.signal});
 for(const [id,{el}]of widgets)el.querySelector('[data-widget-mover]')?.addEventListener('keydown',e=>keyMove(e,id),{signal:life.signal});
 for(const [id,{el}]of widgets)for(const [attribute,field]of [['fixar','fixo'],['altura-livre','altura_livre']])el.querySelector('[data-widget-'+attribute+']')?.addEventListener('click',()=>{
  if(!eligible()||!editing||busy||uncertain)return;const current=layout.find(n=>n.id===id);if(!current||(field==='altura_livre'&&current.fixo===true))return;
  const next=clone(layout),changed=next.find(n=>n.id===id);changed[field]=!changed[field];
  try{const anchors=bySection(layout,current.section).filter(n=>n.fixo===true),normalized=normalize(rowsOf(bySection(next,current.section)),null,anchors);layout=[...layout.filter(n=>n.section!==current.section),...normalized];dirty=true;render();say(field==='fixo'?(changed.fixo?'Posição e tamanho fixados. Salve para manter.':'Posição e tamanho liberados. Salve para manter.'):(changed.altura_livre?'Altura independente ativada. Salve para manter.':'Altura da linha restaurada. Salve para manter.'));}
  catch{say('Não foi possível aplicar esse ajuste sem sobrepor outros cards. A disposição anterior foi mantida.');}
 },{signal:life.signal});
 document.addEventListener('g360:painel-orientacao',e=>{
  if(!eligible())return;const {id,orientacao}=e.detail||{},n=layout.find(n=>n.id===id);if(!n||!chartIds.has(id)||!['horizontal','vertical'].includes(orientacao))return;
  if(busy||uncertain){render();return;}n.orientacao=orientacao;dirty=true;controls();say('Apresentação do gráfico alterada. Salve a disposição para manter.');
 },{signal:life.signal});
 document.querySelectorAll('[data-lista-modo]').forEach(b=>b.addEventListener('click',()=>{
  if(invalid||(eligible()&&(busy||uncertain||!api)))return;list(b.dataset.listaModo);
  if(eligible()&&api){dirty=true;controls();say('Apresentação da lista alterada. Salve a disposição para manter.');}
 },{signal:life.signal}));
 document.addEventListener('pointerdown',e=>{if(!editing)return;const handle=e.target.closest('.ui-resizable-handle'),node=handle?.closest('.grid-stack-item')?.gridstackNode;resizeOrigin=node?{id:node.id,clientY:e.clientY,pageY:e.clientY+scrollY}:null;},{signal:life.signal,capture:true});
 document.addEventListener('pointermove',e=>{if(editing)point={x:e.clientX,y:e.clientY};},{signal:life.signal});
 function suspend(){++epoch;requests.forEach(c=>c.abort());requests.clear();busy=false;editing=false;dirty=false;api=null;destroyGrids();toolbar.hidden=true;controls();}
 media.addEventListener('change',()=>{suspend();if(eligible())load();},{signal:life.signal});
 document.addEventListener('g360:acesso-alterado',()=>{invalid=true;suspend();document.querySelectorAll('[data-lista-modo]').forEach(b=>b.disabled=true);},{signal:life.signal});
 window.G360PainelGradeDesmontar=()=>{life.abort();suspend();};
 controls();if(eligible())load();
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',iniciar);else iniciar();
