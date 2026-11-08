import {GridStack} from 'gridstack';
import {compactReadingLayout} from './painel-leitura.js';
import {project,chartIds,validateLayout,nodeOptions,captureLayout,prepareLayout,autoLayout,keyboardChange,keyboardLateralAction,bandsOf,fillLateral,lateralAction,responsiveMinWidths,lateralFeedback,gridCoordinateMetrics,previewCssBox,pointerResizeDelta,snapshotPreviewCss,restorePreviewCss} from './painel-geometria.js';

/** Encerramento ordenado. O DD pode chamar stop sincronicamente em destroy(). */
export class PanelGestureLifecycle{
 stopping=false;
 stop({active,wrappers,dd,invalidate,cancelPreview,restore,dispose}){
  if(this.stopping)return false;
  this.stopping=true;
  try{
   if(active)active.cancelled=true;
   invalidate();cancelPreview();
   // Não consultar engine.nodes: o ativo pode apontar para o placeholder nativo.
   try{for(const wrapper of new Set(wrappers)){dd.resizable(wrapper,'destroy');dd.draggable(wrapper,'destroy');}}
   finally{if(active)restore(active);}
   dispose();return true;
  }finally{this.stopping=false;}
 }
}
// GridStack executa gestos; a transação lateral preserva a faixa e as alturas individuais.
function iniciar(){
 const configEl=document.getElementById('painel-grade-config');if(!configEl)return;
 window.G360PainelGradeDesmontar?.();
 let config;try{config=JSON.parse(configEl.textContent)}catch{return}
 const mobile=matchMedia('(max-width:767px)');
 const life=new AbortController(),media=matchMedia('(min-width:1024px) and (min-height:700px)');
 const toolbar=document.querySelector('[data-painel-organizacao]');if(!toolbar)return;
 const button=name=>toolbar.querySelector('[data-grade-'+name+']');
 const status=toolbar.querySelector('[data-grade-status]'),actions=toolbar.querySelector('[data-grade-acoes]');
 const widgets=new Map([...document.querySelectorAll('[data-widget-id]')].filter(el=>el.dataset.section!=='resumo').map((el,index)=>[el.dataset.widgetId,{el,index,parent:el.parentElement}]));
 const sections=new Map([...document.querySelectorAll('[data-painel-secao]')].filter(el=>el.dataset.painelSecao!=='resumo').map(el=>[el.dataset.painelSecao,{el,grade:el.querySelector('.painel-secao-grade'),grid:null,style:null}]));
 let api=null,limits=new Map(),layout=[],saved=[],presentation='tabela',savedPresentation='tabela',editing=false,dirty=false,busy=false,invalid=false,uncertain=false,epoch=0,gesture=null,armedWrapper=null,point={x:0,y:0},previewFrame=null,readingFrame=null;
 const uuid=()=>{if(typeof crypto.randomUUID==='function')return crypto.randomUUID();const b=crypto.getRandomValues(new Uint8Array(16));b[6]=(b[6]&15)|64;b[8]=(b[8]&63)|128;const h=[...b].map(n=>n.toString(16).padStart(2,'0')).join('');return [h.slice(0,8),h.slice(8,12),h.slice(12,16),h.slice(16,20),h.slice(20)].join('-');};
 const interactions=new PanelGestureLifecycle();
 const requests=new Set(),clone=value=>structuredClone(value),eligible=()=>!interactions.stopping&&media.matches&&!invalid&&configEl.isConnected;
 const say=text=>status.textContent=text;
 function list(value){
  // A preferência salva continua sendo a apresentação desktop; mobile usa cards.
  presentation=value;const atual=mobile.matches?'cards':value;
  document.querySelectorAll('[data-painel-lista]').forEach(el=>el.dataset.apresentacao=atual);
  document.querySelectorAll('[data-lista-modo]').forEach(b=>{b.setAttribute('aria-pressed',String(b.dataset.listaModo===atual));const grupo=b.closest('[role=group]');if(grupo)grupo.hidden=mobile.matches;});
 }
 mobile.addEventListener('change',()=>list(presentation),{signal:life.signal});
 function controls(){
  toolbar.hidden=!eligible()||(api&&limits.size===0&&!dirty);button('organizar').hidden=editing||(api&&limits.size===0);button('organizar').disabled=busy||!api||uncertain;
  actions.hidden=!(editing||dirty);actions.querySelectorAll('button').forEach(b=>b.disabled=busy||uncertain);
  button('consultar').hidden=!uncertain;button('consultar').disabled=busy;
  document.querySelectorAll('[data-lista-modo]').forEach(b=>b.disabled=invalid||(eligible()&&(busy||uncertain||!api)));
  for(const [id,{el}]of widgets){const n=layout.find(n=>n.id===id),fixed=n?.fixo===true;
   for(const attr of ['mover','fixar']){const b=el.querySelector('[data-widget-'+attr+']');if(!b)continue;b.hidden=!editing||!eligible()||!n;b.disabled=busy||uncertain||(fixed&&attr!=='fixar');}
   const pin=el.querySelector('[data-widget-fixar]');if(pin){pin.setAttribute('aria-pressed',String(fixed));pin.title=fixed?'Liberar posição e tamanho':'Fixar posição e tamanho';pin.setAttribute('aria-label',(fixed?'Liberar':'Fixar')+' posição e tamanho de '+(el.querySelector('[data-widget-titulo],h2,h3,h4')?.textContent?.trim()||id));}
   // O campo antigo continua no transporte; não governa mais a geometria nem tem controle na UI.
   const height=el.querySelector('[data-widget-altura-livre]');if(height)height.hidden=true;
   const chart=el.querySelector('[data-grafico-orientacao]');if(chart)chart.disabled=busy||uncertain||invalid;
  }
  for(const s of sections.values())if(s.grid){const enabled=editing&&!busy&&!uncertain;s.grid.enableMove(enabled);s.grid.enableResize(enabled);s.grid.el.dataset.editando=String(enabled);for(const n of bySection(layout,s.el.dataset.painelSecao)){const wrap=widgets.get(n.id).el.parentElement;s.grid.update(wrap,{locked:n.fixo===true,noMove:!enabled||n.fixo===true,noResize:!enabled||n.fixo===true});}}
 }
 function bounds(n){return limits.get(n.id);}
 function bySection(items,id){return items.filter(n=>n.section===id);}
 function acceptSection(section,next){
  const previous=layout,wasDirty=dirty,candidate=[...layout.filter(n=>n.section!==section),...next];
  validateLayout(candidate,limits,{complete:true});
  const comparable=items=>JSON.stringify(items.map(project).sort((a,b)=>a.id.localeCompare(b.id)));
  try{layout=candidate;dirty=dirty||comparable(previous)!==comparable(candidate);render();}
  catch(error){layout=previous;dirty=wasDirty;throw error;}
 }
 function defaults(){return autoLayout([...widgets].filter(([id])=>limits.has(id)).map(([id,{el}])=>{
  const c=limits.get(id),w=Math.max(c.minW,Math.min(c.maxW,Number(el.dataset.defaultW)||c.defaultW)),h=Math.max(c.minH,Math.min(c.maxH,Number(el.dataset.defaultH)||c.defaultH));
  return {id,section:c.section,x:0,y:0,w,h};
 }),limits);}
 function incomplete(){const error=new Error('A tela não carregou todos os cards ou seus padrões. Recarregue a página antes de salvar a disposição.');error.feedback=true;return error;}
 function prepare(d){
  if(!d||!Number.isInteger(d.versao)||!Array.isArray(d.catalogo)||!Array.isArray(d.layouts?.desktop)||!['tabela','cards'].includes(d.apresentacao))throw Error('Resposta indisponível');
  const next=new Map();for(const c of d.catalogo){const item=widgets.get(c.id),b=c.desktop;
   if(!item||!item.el.isConnected||item.el.dataset.section!==c.section||item.el.closest('[data-painel-secao]')?.dataset.painelSecao!==c.section)throw incomplete();
   if(next.has(c.id)||!b||!['minW','maxW','minH','maxH','defaultW','defaultH'].every(k=>Number.isInteger(b[k]))||b.minW<1||b.maxW>12||b.minW>b.maxW||b.minH<1||b.maxH>200||b.minH>b.maxH||b.defaultW<b.minW||b.defaultW>b.maxW||b.defaultH<b.minH||b.defaultH>b.maxH)throw Error('Limites indisponíveis');
   next.set(c.id,{...b,section:c.section});
  }limits=next;
  const read=items=>validateLayout(items,limits);
  const hasRoleDefault=d.padrao!==undefined;
  if(hasRoleDefault&&!Array.isArray(d.padrao?.desktop))throw incomplete();
  const standard=hasRoleDefault?read(d.padrao.desktop):defaults();
  if(hasRoleDefault&&standard.length!==limits.size)throw incomplete();
  return prepareLayout(read(d.layouts.desktop),standard,limits);
 }
 function destroyGrids(){
  if(interactions.stopping)return;
  if(readingFrame!==null)cancelAnimationFrame(readingFrame);readingFrame=null;
  const wrappers=[];
  for(const s of sections.values())if(s.grid)for(const record of widgets.values())if(record.parent===s.grade){const wrapper=record.el.parentElement;if(wrapper?.classList.contains('grid-stack-item'))wrappers.push(wrapper);}
  interactions.stop({active:gesture,wrappers,dd:GridStack.getDD(),invalidate:()=>{++epoch;gesture=null;armedWrapper=null;},cancelPreview:()=>{if(previewFrame!==null)cancelAnimationFrame(previewFrame);previewFrame=null;},restore:restorePreview,dispose:()=>{
   for(const s of sections.values())if(s.grid){s.grid.destroy(false);s.grid=null;for(const record of [...widgets.values()].sort((a,b)=>a.index-b.index))if(record.parent===s.grade){const wrapper=record.el.parentElement;if(wrapper?.classList.contains('grid-stack-item'))wrapper.replaceWith(record.el);record.el.classList.remove('grid-stack-item-content');s.grade.append(record.el);}
    s.grade.classList.remove('grid-stack');s.grade.removeAttribute('data-editando');if(s.style===null)s.grade.removeAttribute('style');else s.grade.setAttribute('style',s.style);
   }
  }});
 }
 function render(){if(!eligible())return;
  for(const n of layout){const el=widgets.get(n.id)?.el;if(!el)continue;el.dataset.fixo=String(n.fixo===true);el.dataset.alturaLivre=String(n.altura_livre===true);if(n.orientacao)el.dataset.orientacao=n.orientacao;else delete el.dataset.orientacao;}
  for(const [id,s]of sections){const items=bySection(layout,id);if(!items.length)continue;
   if(!s.grid){s.style=s.grade.getAttribute('style');s.grade.classList.add('grid-stack');
    for(const n of items){const {el}=widgets.get(n.id),wrap=document.createElement('div');wrap.className='grid-stack-item';for(const k of ['id','x','y','w','h'])wrap.setAttribute('gs-'+k,String(n[k]));el.replaceWith(wrap);el.classList.add('grid-stack-item-content');wrap.append(el);}
    // Adotar os nós em float evita um relayout antes de todas as âncoras existirem.
    s.grid=GridStack.init({column:12,cellHeight:48,margin:8,mode:'float',acceptWidgets:false,removable:false,subGridDynamic:false,handle:'.painel-widget-mover',resizable:{handles:'se'},alwaysShowResizeHandle:true,disableDrag:true,disableResize:true,maxRow:200,minRow:1,animate:false,sizeToContent:false},s.grade);
    s.grid.on('dragstart resizestart',(e,el)=>startGesture(s,id,e,el));s.grid.on('dragstop resizestop',(e,el)=>stopGesture(s,id,e,el));
    s.grid.on('drag resize',schedulePreview);
   }
   // Locked temporário + top impede o pack de load() de restaurar coordenadas antigas em float.
   // Não é o pin do usuário e nunca entra no DTO. Após aplicar o lote, controls() restaura os pins reais.
   const apply=(records,caps)=>{
    for(const node of s.grid.engine.nodes)s.grid.update(node.el,{locked:true,noMove:true,noResize:true});
    s.grid.mode('top');s.grid.load(records.map(n=>({...nodeOptions(n,caps.get(n.id)),locked:true,noMove:true,noResize:true})),false);s.grid.mode('float');
    const displayed=captureLayout(s.grid.engine,records,caps);
    if(displayed.some(n=>{const expected=records.find(item=>item.id===n.id);return ['x','y','w','h'].some(k=>expected[k]!==n[k]);}))throw Error('A disposição nativa não foi restaurada integralmente');
   };
   apply(items,limits);
   let shown=items;
   if(!editing){
    const heights=new Map();
    for(const n of items){const el=widgets.get(n.id).el;if(el.dataset.painelVazio!=='true')continue;
     // Estes filhos têm flex0/altura natural no estado vazio, mesmo no wrapper canônico.
     const css=getComputedStyle(el),header=el.querySelector('.painel-widget-cabecalho'),body=el.querySelector('.painel-widget-corpo');
     const size=header.offsetHeight+body.scrollHeight+['paddingTop','paddingBottom','borderTopWidth','borderBottomWidth','rowGap'].reduce((v,k)=>v+(parseFloat(css[k])||0),0)+16;
     heights.set(n.id,Math.max(1,Math.ceil(size/s.grid.getCellHeight(true))));
    }
    shown=compactReadingLayout(items,heights);
    if(shown.some((n,i)=>n.h!==items[i].h||n.y!==items[i].y))apply(shown,new Map([...limits].map(([key,c])=>[key,{...c,minH:1}])));
   }
   s.grid.updateOptions({minRow:Math.min(200,Math.max(...shown.map(n=>n.y+n.h))+(editing?3:0))});
  }controls();document.dispatchEvent(new CustomEvent('g360:painel-redimensionado'));
 }
 function actionMinimums(s){
  const first=s.grid.engine.nodes[0]?.el,card=first?.querySelector('[data-widget-id]');
  const gap=first&&card?Math.max(0,first.getBoundingClientRect().width-card.getBoundingClientRect().width):16;
  return responsiveMinWidths(limits,s.grade.getBoundingClientRect().width,gap);
 }
 function restorePreview(active){
  if(active.ghost){const {el,value,priority}=active.ghost;if(value==='')el.style.removeProperty('opacity');else el.style.setProperty('opacity',value,priority);}
  // No drag o ativo é o helper fixed do DD; a biblioteca restaura suas próprias coordenadas.
  for(const [id,style]of active.styles){if(id===active.id&&!active.resize)continue;const wrap=widgets.get(id)?.el.parentElement;if(!wrap?.classList.contains('grid-stack-item'))continue;restorePreviewCss(wrap.style,style);}
  if(active.placeholder){const {el,style,attributes}=active.placeholder;if(style===null)el.removeAttribute('style');else el.setAttribute('style',style);for(const [name,value]of attributes){if(value===null)el.removeAttribute(name);else el.setAttribute(name,value);}}
  delete sections.get(active.section).grade.dataset.previaValida;
 }
 function drawPreview(active,proposal,valid=true){
  const s=sections.get(active.section),paint=(el,n)=>{for(const [key,value]of Object.entries(previewCssBox(n,active.metrics)))el.style[key]=value+'px';};
  for(const n of bySection(proposal,active.section)){const wrap=widgets.get(n.id).el.parentElement;
   // Ghost segue ponteiro/offset nativo. Só os vizinhos recebem coordenadas locais da grade.
   if(n.id===active.id&&!active.resize)continue;
   paint(wrap,n);
  }
  const placeholder=s.grade.querySelector(':scope > .grid-stack-placeholder'),target=proposal.find(n=>n.id===active.id);
  if(placeholder&&target){
   if(!active.placeholder)active.placeholder={el:placeholder,style:placeholder.getAttribute('style'),attributes:['gs-x','gs-y','gs-w','gs-h','aria-hidden','data-previa-valida'].map(name=>[name,placeholder.getAttribute(name)])};
   // Reutilizar o único placeholder nativo elimina o retângulo antigo na faixa de origem.
   paint(placeholder,target);
   for(const key of ['x','y','w','h']){placeholder.style.setProperty('--gs-'+key,String(target[key]));placeholder.setAttribute('gs-'+key,String(target[key]));}
   if(valid)placeholder.style.removeProperty('--accent');else placeholder.style.setProperty('--accent','var(--danger)');
   placeholder.setAttribute('aria-hidden','true');placeholder.dataset.previaValida=String(valid);
  }
 }
 function dragIntent(active){
  const x=point.x+scrollX,y=point.y+scrollY,g=active.grade;
  if(x<g.left||x>g.right||y<g.top-24||y>g.bottom+24)throw Error(lateralFeedback);
  const bands=active.bands;let index=bands.findLastIndex(b=>y>=b.top);if(index<0)index=0;
  const band=bands[index],bottom=bands[index+1]?.top??band.bottom,zone=Math.min(56,Math.max(20,(bottom-band.top)*.18));
  let place=y<band.top+zone?'before-band':y>bottom-zone?'after-band':'lateral';
  const peers=band.items.filter(n=>n.id!==active.id);if(!peers.length)return {type:'move',targetId:active.id,place:y<band.top?'before-band':'after-band'};
  const at=peers.findIndex(n=>x<n.left+n.width/2),peer=peers[at<0?peers.length-1:at],after=at<0;
  const boundary=place==='before-band'?band.top+zone:bottom-zone;
  if(active.intent?.band===index&&active.intent.action.place!==place&&Math.abs(y-boundary)<8)return active.intent.action;
  const action={type:'move',targetId:peer.id,place,after};active.intent={band:index,action};return action;
 }
 function previewGesture(){
  const active=gesture;if(!active||active.epoch!==epoch||!eligible()||!editing||busy||uncertain)return;
  const s=sections.get(active.section),current=active.before.find(n=>n.id===active.id);
  try{let action;
   if(active.resize){const {dw,dh}=pointerResizeDelta(active.start,point,active.metrics,scrollY);
    action={type:'resize'};if(dw)action.w=current.w+dw;if(dh)action.h=current.h+dh;
   }else action=dragIntent(active);
   const minimums=action.type==='resize'&&action.w===undefined?new Map():actionMinimums(s);
   const proposal=lateralAction(active.before,limits,active.id,action,{minWidths:minimums});
   active.proposal=proposal;active.lastValid=proposal;s.grade.dataset.previaValida='true';drawPreview(active,proposal);
   if(active.feedback!=='valid'){say('Prévia ajustada. Solte para confirmar a disposição.');active.feedback='valid';}
  }catch{active.proposal=null;s.grade.dataset.previaValida='false';drawPreview(active,active.lastValid??active.before,false);if(active.feedback!=='invalid'){say(lateralFeedback);active.feedback='invalid';}}
 }
 function schedulePreview(){if(interactions.stopping||!gesture||previewFrame!==null)return;previewFrame=requestAnimationFrame(()=>{previewFrame=null;previewGesture();});}
 function startGesture(s,section,event,el){
  if(!eligible()||!s.grid||el.gridstackNode?.grid!==s.grid||!editing||busy||uncertain||layout.find(n=>n.id===el.gridstackNode.id)?.fixo===true)return;
  const before=clone(layout),rect=s.grade.getBoundingClientRect(),styles=new Map(),rectangles=new Map();
  const transform=GridStack.Utils.getValuesFromTransformedElement(s.grade),metrics=gridCoordinateMetrics({localWidth:s.grade.clientWidth,visualWidth:rect.width,cellHeight:s.grid.getCellHeight(true),inverseScaleY:transform.yScale});
  for(const n of bySection(before,section)){const wrap=widgets.get(n.id).el.parentElement,r=wrap.getBoundingClientRect();styles.set(n.id,snapshotPreviewCss(wrap.style));rectangles.set(n.id,{id:n.id,left:r.left+scrollX,top:r.top+scrollY,bottom:r.bottom+scrollY,width:r.width});}
  gesture={section,id:el.gridstackNode.id,wrapper:el,grid:s.grid,before,styles,epoch,resize:event.type==='resizestart',start:{x:point.x,y:point.y,pageY:point.y+scrollY},metrics,grade:{left:rect.left+scrollX,right:rect.right+scrollX,top:rect.top+scrollY,bottom:rect.bottom+scrollY},bands:bandsOf(bySection(before,section)).map(b=>{const items=b.items.map(n=>rectangles.get(n.id));return {items,top:Math.min(...items.map(r=>r.top)),bottom:Math.max(...items.map(r=>r.bottom))};})};
  if(!gesture.resize){gesture.ghost={el,value:el.style.getPropertyValue('opacity'),priority:el.style.getPropertyPriority('opacity')};el.style.setProperty('opacity','0.20');}
  // Vizinhos não são expulsos pela colisão intermediária: a prévia exibe o lote calculado.
  for(const node of s.grid.engine.nodes)if(node.id!==gesture.id)s.grid.update(node.el,{locked:true,noMove:true,noResize:true});
  actions.querySelectorAll('button').forEach(b=>b.disabled=true);
  document.querySelectorAll('[data-lista-modo],[data-grafico-orientacao],[data-widget-fixar]').forEach(b=>b.disabled=true);
  schedulePreview();
 }
 function stopGesture(s,section,event,el){
  if(interactions.stopping)return;
  const active=gesture;if(!active||active.section!==section||active.wrapper!==el||active.grid!==s.grid)return;if(!active.cancelled)previewGesture();gesture=null;
  if(previewFrame!==null)cancelAnimationFrame(previewFrame);previewFrame=null;
  queueMicrotask(()=>{if(active.epoch!==epoch||!eligible()||!editing||busy||uncertain)return;
   restorePreview(active);
   if(active.cancelled){render();say('Movimento cancelado. A disposição anterior foi mantida.');return;}
   try{if(!active.proposal)throw Error(lateralFeedback);acceptSection(section,bySection(active.proposal,section));say('Disposição ajustada. As alturas individuais foram mantidas. Salve para manter.');}
   catch{render();say(lateralFeedback);}
  });
 }
 function keyMove(e,id){if(!editing||busy||uncertain||!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(e.key))return;e.preventDefault();const item=layout.find(n=>n.id===id);if(!item)return;
  if(item.fixo===true||gesture)return;const s=sections.get(item.section);let action;
  if(e.shiftKey){const patch=keyboardChange(item,e.key,true,bounds(item));action={type:'resize',...patch};}
  else action=keyboardLateralAction(layout,id,e.key);
  if(!action){say('O card já está no limite deste ajuste.');return;}
  try{const minimums=action.type==='resize'&&action.w===undefined?new Map():actionMinimums(s),next=lateralAction(layout,limits,id,action,{minWidths:minimums});
   const previous=bySection(layout,item.section),adjusted=bySection(next,item.section);
   if(adjusted.every(n=>{const original=previous.find(p=>p.id===n.id);return ['x','y','w','h'].every(k=>n[k]===original[k]);})){say('O card já está no limite deste ajuste.');return;}
   acceptSection(item.section,bySection(next,item.section));widgets.get(id).el.querySelector('[data-widget-mover]').focus({preventScroll:true});say('Disposição ajustada pelo teclado. Salve para manter.');
  }catch{render();say(lateralFeedback);}
 }
 async function request(descriptor,body){const abort=new AbortController();requests.add(abort);const timer=setTimeout(()=>abort.abort(),25000);try{const response=await window.G360Requisicao(descriptor,{body:JSON.stringify(body||{}),credentials:'same-origin',headers:{Accept:'application/json'},signal:abort.signal});const d=await response.json();if(!response.ok){const error=new Error(response.status===409?'A organização ou o acesso mudou. Consulte o estado salvo antes de continuar.':'Não foi possível confirmar as preferências. Consulte o estado salvo antes de continuar.');error.status=response.status;throw error;}return d.data;}finally{clearTimeout(timer);requests.delete(abort);}}
 async function load(){if(!eligible())return;const current=++epoch;busy=true;uncertain=false;toolbar.hidden=false;say('Consultando sua disposição salva…');controls();try{const d=await request(config.consultar);if(current!==epoch||!eligible())return;const next=prepare(d);api=d;layout=next;saved=clone(next);savedPresentation=d.apresentacao;list(d.apresentacao);editing=false;dirty=false;render();say('Use Organizar painel para mover ou redimensionar os cards.');}catch(error){if(current!==epoch||!eligible())return;uncertain=true;say((error.status||error.feedback)?error.message:'As preferências estão indisponíveis. Consulte o estado salvo para tentar novamente.');}finally{if(current===epoch){busy=false;controls();}}}
 async function save(){if(!eligible()||!(editing||dirty)||busy||uncertain||!api||gesture)return;const current=epoch;busy=true;controls();say('Salvando disposição…');try{const d=await request(config.salvar,{versao:api.versao,chave:uuid(),acao:'salvar',apresentacao:presentation,layouts:{desktop:layout.map(project)}});if(current!==epoch||!eligible())return;const next=prepare(d);api=d;layout=next;saved=clone(next);savedPresentation=d.apresentacao;list(d.apresentacao);editing=false;dirty=false;render();say('Disposição salva.');}catch(error){if(current!==epoch||!eligible())return;uncertain=true;say((error.status||error.feedback)?error.message:'Não foi possível confirmar o salvamento. Consulte o estado salvo antes de continuar.');}finally{if(current===epoch){busy=false;controls();}}}
 button('organizar').addEventListener('click',()=>{if(!eligible()||!api||busy||uncertain)return;editing=true;render();say('Arraste pelo ícone ou use as setas. Shift e setas ajustam o tamanho. Salve ao terminar.');},{signal:life.signal});
 button('salvar').addEventListener('click',save,{signal:life.signal});button('consultar').addEventListener('click',load,{signal:life.signal});
 button('cancelar').addEventListener('click',()=>{if(busy)return;layout=clone(saved);list(savedPresentation);editing=false;dirty=false;render();say('Alterações descartadas.');button('organizar').focus();},{signal:life.signal});
 button('restaurar').addEventListener('click',()=>{if(!eligible()||!api||busy||uncertain)return;layout=prepare({...api,layouts:{...api.layouts,desktop:[]}});dirty=true;render();say('Padrão restaurado nesta tela. Salve para manter ou cancele para voltar.');},{signal:life.signal});
 for(const [id,{el}]of widgets)el.querySelector('[data-widget-mover]')?.addEventListener('keydown',e=>keyMove(e,id),{signal:life.signal});
 for(const [id,{el}]of widgets)el.querySelector('[data-widget-fixar]')?.addEventListener('click',()=>{
  if(!eligible()||!editing||busy||uncertain)return;const current=layout.find(n=>n.id===id);if(!current)return;
  const next=clone(bySection(layout,current.section)),changed=next.find(n=>n.id===id);changed.fixo=!changed.fixo;
  try{const normalized=fillLateral(next,limits);acceptSection(current.section,normalized);say(changed.fixo?'Posição e tamanho fixados. Salve para manter.':'Posição e tamanho liberados. Salve para manter.');}
  catch{render();say('Não foi possível aplicar esse ajuste sem sobrepor outros cards. A disposição anterior foi mantida.');}
 },{signal:life.signal});
 document.addEventListener('g360:painel-orientacao',e=>{
  if(!eligible()||gesture)return;const {id,orientacao}=e.detail||{},n=layout.find(n=>n.id===id);if(!n||!chartIds.has(id)||!['horizontal','vertical'].includes(orientacao))return;
  if(busy||uncertain){render();return;}n.orientacao=orientacao;dirty=true;controls();say('Apresentação do gráfico alterada. Salve a disposição para manter.');
 },{signal:life.signal});
 document.querySelectorAll('[data-lista-modo]').forEach(b=>b.addEventListener('click',()=>{
  if(gesture||invalid||(eligible()&&(busy||uncertain||!api)))return;list(b.dataset.listaModo);
  if(eligible()&&api){dirty=true;controls();say('Apresentação da lista alterada. Salve a disposição para manter.');}
 },{signal:life.signal}));
 document.addEventListener('pointerdown',e=>{point={x:e.clientX,y:e.clientY};
  if(!eligible()||!editing||busy||uncertain)return;
  const wrapper=e.target.closest?.('.grid-stack-item'),handle=e.target.closest?.('[data-widget-mover],.ui-resizable-handle');
  if(wrapper&&handle&&[...widgets.values()].some(record=>record.el.parentElement===wrapper))armedWrapper=wrapper;
 },{signal:life.signal,capture:true});
 document.addEventListener('pointermove',e=>{point={x:e.clientX,y:e.clientY};if(gesture)schedulePreview();},{signal:life.signal,capture:true});
 document.addEventListener('pointerup',e=>{point={x:e.clientX,y:e.clientY};armedWrapper=null;},{signal:life.signal,capture:true});
 function cancelGesture(){if((!gesture&&!armedWrapper)||interactions.stopping)return;const focus=document.activeElement;destroyGrids();render();if(eligible()&&focus?.isConnected)focus.focus?.({preventScroll:true});say('Movimento cancelado. A disposição anterior foi mantida.');}
 document.addEventListener('keydown',e=>{if(e.key==='Escape')cancelGesture();},{signal:life.signal,capture:true});
 document.addEventListener('pointercancel',cancelGesture,{signal:life.signal,capture:true});
 function scheduleReading(){if(readingFrame!==null||!eligible()||editing||!api)return;readingFrame=requestAnimationFrame(()=>{readingFrame=null;if(eligible()&&!editing&&api)render();});}
 window.addEventListener('resize',()=>{cancelGesture();scheduleReading();},{signal:life.signal});
 document.fonts?.ready.then(()=>{if(!life.signal.aborted)scheduleReading();});
 function suspend(){++epoch;requests.forEach(c=>c.abort());requests.clear();busy=false;editing=false;dirty=false;api=null;destroyGrids();toolbar.hidden=true;controls();}
 media.addEventListener('change',()=>{suspend();if(eligible())load();},{signal:life.signal});
 document.addEventListener('g360:acesso-alterado',()=>{invalid=true;suspend();document.querySelectorAll('[data-lista-modo]').forEach(b=>b.disabled=true);},{signal:life.signal});
 window.G360PainelGradeDesmontar=()=>{life.abort();suspend();};
 list(presentation);controls();if(eligible())load();
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',iniciar);else iniciar();
