import {GridStack} from 'gridstack';
import {project,chartIds,validateLayout,nodeOptions,nativeEngine,captureLayout,prepareLayout,autoLayout,keyboardChange} from './painel-geometria.js';
// Uma instância nativa compacta por seção, sem adaptador de linhas ou alturas compartilhadas.
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
 let api=null,limits=new Map(),layout=[],saved=[],presentation='tabela',savedPresentation='tabela',editing=false,dirty=false,busy=false,invalid=false,uncertain=false,epoch=0,gesture=null;
 const uuid=()=>{if(typeof crypto.randomUUID==='function')return crypto.randomUUID();const b=crypto.getRandomValues(new Uint8Array(16));b[6]=(b[6]&15)|64;b[8]=(b[8]&63)|128;const h=[...b].map(n=>n.toString(16).padStart(2,'0')).join('');return [h.slice(0,8),h.slice(8,12),h.slice(12,16),h.slice(16,20),h.slice(20)].join('-');};
 const requests=new Set(),clone=value=>structuredClone(value),eligible=()=>media.matches&&!invalid&&configEl.isConnected;
 const say=text=>status.textContent=text;
 function list(value){presentation=value;document.querySelectorAll('[data-painel-lista]').forEach(el=>el.dataset.apresentacao=value);document.querySelectorAll('[data-lista-modo]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.listaModo===value)));}
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
  for(const s of sections.values())if(s.grid){s.grid.destroy(false);s.grid=null;for(const record of [...widgets.values()].sort((a,b)=>a.index-b.index))if(record.parent===s.grade){const wrapper=record.el.parentElement;if(wrapper?.classList.contains('grid-stack-item'))wrapper.replaceWith(record.el);record.el.classList.remove('grid-stack-item-content');s.grade.append(record.el);}
   s.grade.classList.remove('grid-stack');s.grade.removeAttribute('data-editando');if(s.style===null)s.grade.removeAttribute('style');else s.grade.setAttribute('style',s.style);
  }gesture=null;
 }
 function render(){if(!eligible())return;
  for(const n of layout){const el=widgets.get(n.id)?.el;if(!el)continue;el.dataset.fixo=String(n.fixo===true);el.dataset.alturaLivre=String(n.altura_livre===true);if(n.orientacao)el.dataset.orientacao=n.orientacao;else delete el.dataset.orientacao;}
  for(const [id,s]of sections){const items=bySection(layout,id);if(!items.length)continue;
   if(!s.grid){s.style=s.grade.getAttribute('style');s.grade.classList.add('grid-stack');
    for(const n of items){const {el}=widgets.get(n.id),wrap=document.createElement('div');wrap.className='grid-stack-item';for(const k of ['id','x','y','w','h'])wrap.setAttribute('gs-'+k,String(n[k]));el.replaceWith(wrap);el.classList.add('grid-stack-item-content');wrap.append(el);}
    // Adotar os nós em float evita um relayout antes de todas as âncoras existirem.
    s.grid=GridStack.init({column:12,cellHeight:48,margin:8,mode:'float',acceptWidgets:false,removable:false,subGridDynamic:false,handle:'.painel-widget-mover',resizable:{handles:'se'},alwaysShowResizeHandle:true,disableDrag:true,disableResize:true,maxRow:200,minRow:1,animate:false,sizeToContent:false},s.grade);
    s.grid.on('dragstart resizestart',(e,el)=>startGesture(s,id,e,el));s.grid.on('dragstop resizestop',(e,el)=>stopGesture(s,id,e,el));
   }
   s.grid.load(items.map(n=>nodeOptions(n,bounds(n))),false);s.grid.mode('compact');
   const displayed=captureLayout(s.grid.engine,items,limits);
   if(displayed.some(n=>{const expected=items.find(item=>item.id===n.id);return ['x','y','w','h'].some(k=>expected[k]!==n[k]);}))throw Error('A disposição nativa não foi restaurada integralmente');
   s.grid.updateOptions({minRow:Math.min(200,Math.max(...items.map(n=>n.y+n.h))+(editing?3:0))});
  }controls();document.dispatchEvent(new CustomEvent('g360:painel-redimensionado'));
 }
 function startGesture(s,section,event,el){if(!editing||busy||uncertain||layout.find(n=>n.id===el.gridstackNode.id)?.fixo===true)return;gesture={section,id:el.gridstackNode.id,before:clone(bySection(layout,section)),epoch};}
 function stopGesture(s,section,event,el){
  const active=gesture;gesture=null;if(!active||active.section!==section)return;
  queueMicrotask(()=>{if(active.epoch!==epoch||!eligible()||!editing||busy||uncertain)return;
   try{s.grid.compact('compact');const next=captureLayout(s.grid.engine,active.before,limits,{resizeId:event.type==='resizestop'?active.id:null});
    acceptSection(section,next);say('Disposição ajustada. Os cards têm tamanhos independentes. Salve para manter.');
   }catch{render();say('Não foi possível encaixar o card respeitando seus limites. A disposição anterior foi mantida.');}
  });
 }
 function keyMove(e,id){if(!editing||busy||uncertain||!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(e.key))return;e.preventDefault();const item=layout.find(n=>n.id===id);if(!item)return;
  const patch=keyboardChange(item,e.key,e.shiftKey,bounds(item));if(!patch)return;
  const before=clone(bySection(layout,item.section)),s=sections.get(item.section);
  try{s.grid.update(widgets.get(id).el.parentElement,patch);s.grid.compact('compact');const next=captureLayout(s.grid.engine,before,limits,{resizeId:e.shiftKey?id:null});
   acceptSection(item.section,next);widgets.get(id).el.querySelector('[data-widget-mover]').focus({preventScroll:true});say('Disposição ajustada pelo teclado. Salve para manter.');
  }catch{render();say('O ajuste não cabe nos limites desta seção. A disposição anterior foi mantida.');}
 }
 async function request(descriptor,body){const abort=new AbortController();requests.add(abort);const timer=setTimeout(()=>abort.abort(),25000);try{const response=await window.G360Requisicao(descriptor,{body:JSON.stringify(body||{}),credentials:'same-origin',headers:{Accept:'application/json'},signal:abort.signal});const d=await response.json();if(!response.ok){const error=new Error(response.status===409?'A organização ou o acesso mudou. Consulte o estado salvo antes de continuar.':'Não foi possível confirmar as preferências. Consulte o estado salvo antes de continuar.');error.status=response.status;throw error;}return d.data;}finally{clearTimeout(timer);requests.delete(abort);}}
 async function load(){if(!eligible())return;const current=++epoch;busy=true;uncertain=false;toolbar.hidden=false;say('Consultando sua disposição salva…');controls();try{const d=await request(config.consultar);if(current!==epoch||!eligible())return;const next=prepare(d);api=d;layout=next;saved=clone(next);savedPresentation=d.apresentacao;list(d.apresentacao);editing=false;dirty=false;render();say('Use Organizar painel para mover ou redimensionar os cards.');}catch(error){if(current!==epoch||!eligible())return;uncertain=true;say((error.status||error.feedback)?error.message:'As preferências estão indisponíveis. Consulte o estado salvo para tentar novamente.');}finally{if(current===epoch){busy=false;controls();}}}
 async function save(){if(!eligible()||!(editing||dirty)||busy||uncertain||!api)return;const current=epoch;busy=true;controls();say('Salvando disposição…');try{const d=await request(config.salvar,{versao:api.versao,chave:uuid(),acao:'salvar',apresentacao:presentation,layouts:{desktop:layout.map(project)}});if(current!==epoch||!eligible())return;const next=prepare(d);api=d;layout=next;saved=clone(next);savedPresentation=d.apresentacao;list(d.apresentacao);editing=false;dirty=false;render();say('Disposição salva.');}catch(error){if(current!==epoch||!eligible())return;uncertain=true;say((error.status||error.feedback)?error.message:'Não foi possível confirmar o salvamento. Consulte o estado salvo antes de continuar.');}finally{if(current===epoch){busy=false;controls();}}}
 button('organizar').addEventListener('click',()=>{if(!eligible()||!api||busy||uncertain)return;editing=true;render();say('Arraste pelo ícone ou use as setas. Shift e setas ajustam o tamanho. Salve ao terminar.');},{signal:life.signal});
 button('salvar').addEventListener('click',save,{signal:life.signal});button('consultar').addEventListener('click',load,{signal:life.signal});
 button('cancelar').addEventListener('click',()=>{if(busy)return;layout=clone(saved);list(savedPresentation);editing=false;dirty=false;render();say('Alterações descartadas.');button('organizar').focus();},{signal:life.signal});
 button('restaurar').addEventListener('click',()=>{if(!eligible()||!api||busy||uncertain)return;layout=prepare({...api,layouts:{...api.layouts,desktop:[]}});dirty=true;render();say('Padrão restaurado nesta tela. Salve para manter ou cancele para voltar.');},{signal:life.signal});
 for(const [id,{el}]of widgets)el.querySelector('[data-widget-mover]')?.addEventListener('keydown',e=>keyMove(e,id),{signal:life.signal});
 for(const [id,{el}]of widgets)el.querySelector('[data-widget-fixar]')?.addEventListener('click',()=>{
  if(!eligible()||!editing||busy||uncertain)return;const current=layout.find(n=>n.id===id);if(!current)return;
  const next=clone(bySection(layout,current.section)),changed=next.find(n=>n.id===id);changed.fixo=!changed.fixo;
  try{const engine=nativeEngine(next,limits),normalized=captureLayout(engine,next,limits);acceptSection(current.section,normalized);say(changed.fixo?'Posição e tamanho fixados. Salve para manter.':'Posição e tamanho liberados. Salve para manter.');}
  catch{render();say('Não foi possível aplicar esse ajuste sem sobrepor outros cards. A disposição anterior foi mantida.');}
 },{signal:life.signal});
 document.addEventListener('g360:painel-orientacao',e=>{
  if(!eligible())return;const {id,orientacao}=e.detail||{},n=layout.find(n=>n.id===id);if(!n||!chartIds.has(id)||!['horizontal','vertical'].includes(orientacao))return;
  if(busy||uncertain){render();return;}n.orientacao=orientacao;dirty=true;controls();say('Apresentação do gráfico alterada. Salve a disposição para manter.');
 },{signal:life.signal});
 document.querySelectorAll('[data-lista-modo]').forEach(b=>b.addEventListener('click',()=>{
  if(invalid||(eligible()&&(busy||uncertain||!api)))return;list(b.dataset.listaModo);
  if(eligible()&&api){dirty=true;controls();say('Apresentação da lista alterada. Salve a disposição para manter.');}
 },{signal:life.signal}));
 function suspend(){++epoch;requests.forEach(c=>c.abort());requests.clear();busy=false;editing=false;dirty=false;api=null;destroyGrids();toolbar.hidden=true;controls();}
 media.addEventListener('change',()=>{suspend();if(eligible())load();},{signal:life.signal});
 document.addEventListener('g360:acesso-alterado',()=>{invalid=true;suspend();document.querySelectorAll('[data-lista-modo]').forEach(b=>b.disabled=true);},{signal:life.signal});
 window.G360PainelGradeDesmontar=()=>{life.abort();suspend();};
 controls();if(eligible())load();
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',iniciar);else iniciar();
