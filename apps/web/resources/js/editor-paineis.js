import {montarGrade} from './editor-paineis/grade-visual.mjs';
import {montarQuadro} from './editor-paineis/quadro-secoes.mjs';
import {registrarWidgets} from './editor-paineis/registro.mjs';
import {projetarLeitura} from './editor-paineis/leitura.mjs';
import {capturarContinuidade,manterContinuidade} from './editor-paineis/continuidade-visual.mjs';
import {clone,validar,titulo,transferir,ativar,nodes,obter,profundidade,normalizar,adicionarPainel,removerPainel,caminho} from './editor-paineis/modelo.mjs';

const escape=v=>String(v).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const tagValida=t=>typeof t==='string'&&/^"g360-editor-[a-f0-9]{64}"$/.test(t);

/** Uma composição autenticada por página, em memória. Nenhum dado de layout em storage/cookies. */
function iniciar(){
 const cfg=document.getElementById('painel-editor-config'),root=document.querySelector('[data-editor-painel]');if(!cfg||!root)return;
 window.G360EditorPainelDesmontar?.();
 let config;try{config=JSON.parse(cfg.textContent);}catch{return;}
 const life=new AbortController(),requests=new Set(),$=s=>root.querySelector(s),all=s=>[...root.querySelectorAll(s)],button=n=>$('[data-editor-'+n+']');
 const media=matchMedia('(min-width:1024px) and (min-height:700px) and (hover:hover) and (pointer:fine)'),mobile=matchMedia('(max-width:767px)');
 const icon=name=>($('[data-editor-icones]').content.querySelector('[data-icone="'+name+'"]')?.innerHTML||'').replace('class="nav-icone"','class="icone"');
 const btn=(label,ic,attrs='',text=false)=>'<button type="button" class="btn btn-secondary btn-sm botao '+(text?'':'botao-icone')+'" aria-label="'+escape(label)+'" title="'+escape(label)+'" '+attrs+'>'+icon(ic)+(text?escape(label):'')+'</button>';
 const registry=registrarWidgets(root,config.painel,icon),ui={escape,icon,btn},frozenControls=new Map(),emptyHeights=new Map();
 let context,state,entry,standard,revision,etag,catalogKey,presentation='tabela',savedPresentation='tabela',mounted=null,editing=false,preview=false,tab='distribuicao',busy=false,uncertain=false,invalid=false,epoch=0,dirty=false,lastCheck=0,checking=null,channel=null,pendingConfirm=null,creationWidget=null,creationDone=false;
 const eligible=()=>media.matches&&!invalid&&cfg.isConnected;
 const say=(text,type='info')=>{const s=$('[data-editor-status]');s.textContent=text;s.dataset.tipo=type;};
 const on=(el,event,fn)=>el?.addEventListener(event,fn,{signal:life.signal});
 const focus=selector=>requestAnimationFrame(()=>{if(invalid)return;const el=$(selector);if(el&&!el.disabled&&el.getClientRects().length)el.focus({preventScroll:true});});
 const descriptorHeaders={'Accept':'application/json','X-G360-Painel-Editor':'3'};
 function list(){const mode=mobile.matches?'cards':presentation;registry.area.querySelectorAll('[data-painel-lista]').forEach(el=>el.dataset.apresentacao=mode);registry.area.querySelectorAll('[data-lista-modo]').forEach(b=>{b.setAttribute('aria-pressed',String(b.dataset.listaModo===mode));b.closest('[role=group]').hidden=mobile.matches;});}
 function controls(){
  button('organizar').hidden=editing||!media.matches;button('organizar').disabled=busy||!state||uncertain||invalid;
  button('consultar').hidden=!uncertain;button('consultar').disabled=busy||invalid;
  all('[data-editor-edicao] button,[data-editor-edicao] select,[data-editor-edicao] input').forEach(b=>{if(busy||uncertain||invalid){if(!frozenControls.has(b))frozenControls.set(b,b.disabled);b.disabled=true;}else if(frozenControls.has(b)){b.disabled=frozenControls.get(b);frozenControls.delete(b);}});
  // Pins govern the mover even after generic pending-state controls have run.
  if(state)all('[data-no-mover]').forEach(b=>b.disabled=busy||uncertain||invalid||obter(state,b.dataset.noMover)?.fixo);
  if(state)all('[data-quadro-mover]').forEach(b=>b.disabled=busy||uncertain||invalid||obter(state,b.dataset.quadroMover)?.fixo);
  button('cancelar').disabled=busy||invalid;
  for(const grid of mounted?.grids||[]){grid.enableMove(editing&&!preview&&eligible()&&!busy&&!uncertain);grid.enableResize(editing&&!preview&&eligible()&&!busy&&!uncertain);}
 }
 function stopView(){mounted?.destroy();mounted=null;registry.guardar();$('[data-editor-quadro]').replaceChildren();$('[data-editor-fino]').replaceChildren();$('[data-editor-leitura]').replaceChildren();}
 function definitions(){return context.catalogo;}
 function changed(next,view=null){if(!eligible()||busy||uncertain)return;state=validar(next,context);dirty=true;render(view);}
 function restore(snapshot,text,view=null){state=clone(snapshot);render(view);say(text,'erro');}
 const callbacks={definitions,change:changed,restore,say,focus,rename,create:createPanel,move:(id,parent)=>{try{changed(transferir(state,id,parent,context));focus('[data-destino="'+id+'"]');}catch(e){render();say(e.message,'erro');}},toggle:(id,active)=>{try{changed(ativar(state,id,active,context));focus('[data-quadro-ativo="'+id+'"]');}catch(e){render();say(e.message,'erro');}},remove:id=>confirmAction('Remover '+obter(state,id).title+'?','Os widgets e as subseções irão para o nível acima. Nenhum dado será excluído. A mudança só será mantida ao salvar.',()=>{try{changed(removerPainel(state,id,context));focus('[data-editor-novo]');}catch(e){say(e.message,'erro');}}),pin:id=>{const next=clone(state);obter(next,id).fixo=!obter(next,id).fixo;changed(next);focus('[data-no-pin="'+id+'"]');}};
 function render(view=null){
  if(!state||invalid)return;
  const working=editing&&eligible()&&!preview;
  for(const [id,{el}]of registry.records)if(el.dataset.painelVazio==='true'&&el.isConnected){const css=getComputedStyle(el),head=el.querySelector('.painel-widget-cabecalho'),body=el.querySelector('.painel-widget-corpo');if(head&&body){const px=head.offsetHeight+body.scrollHeight+['paddingTop','paddingBottom','borderTopWidth','borderBottomWidth','rowGap'].reduce((v,k)=>v+(parseFloat(css[k])||0),0)+16;emptyHeights.set(id,Math.max(4,Math.ceil(px/40)));}}
  manterContinuidade(view,()=>{
   stopView();$('[data-editor-edicao]').hidden=!working;$('[data-editor-preview]').hidden=!(editing&&preview&&eligible());$('[data-editor-suspenso]').hidden=!(editing&&!eligible());$('[data-editor-leitura]').hidden=working;
   all('[data-editor-aba]').forEach(b=>{const selected=b.dataset.editorAba===tab;b.setAttribute('aria-selected',String(selected));b.tabIndex=selected?0:-1;});
   $('#pe-distribuicao').hidden=tab!=='distribuicao';$('#pe-fino').hidden=tab!=='fino';
   if(working&&tab==='distribuicao')mounted=montarQuadro({container:$('[data-editor-quadro]'),state,context,ui,callbacks});
   else{
    const target=working?$('[data-editor-fino]'):$('[data-editor-leitura]');
    // Mobile consumes the saved desktop composition while an interrupted draft stays in memory.
    const canonical=editing&&!eligible()?entry:state,reading=working?{state:canonical,context}:projetarLeitura(canonical,context,emptyHeights),showing=reading.state;
    mounted=montarGrade({target,state:showing,context:reading.context,editing:working,mobile:mobile.matches,ui,callbacks:{...callbacks,definitions:()=>reading.context.catalogo},widgetMarkup:id=>'<div data-editor-widget-slot="'+id+'"></div>',prepareWidgets:t=>registry.inserir(t,showing,working)});
   }
   list();controls();document.dispatchEvent(new CustomEvent('g360:painel-redimensionado'));
  });
 }
 function prepare(body){
  const d=body?.data,e=d?.editor;
  if(!d||!Number.isInteger(d.versao)||d.versao<0||!['tabela','cards'].includes(d.apresentacao)||!tagValida(body.etag)||!e||e.schema!==3||e.indisponivel||!/^[a-f0-9]{64}$/.test(e.contexto)||!/^[a-f0-9]{64}$/.test(e.catalogo_chave))throw Error('Não foi possível confirmar a composição. A apresentação original foi preservada.');
  const nextContext={scope:e.contexto,catalogo:e.catalogo};registry.validar(e.catalogo);
  const read=s=>validar({...clone(s),context:{scope:e.contexto}},nextContext);
  return {context:nextContext,state:read(e.composicao),standard:read(e.padrao),revision:d.versao,etag:body.etag,catalogKey:e.catalogo_chave,presentation:d.apresentacao};
 }
 function adopt(body,{initial=false}={}){
  const next=prepare(body);
  if(context&&(next.context.scope!==context.scope||next.catalogKey!==catalogKey)){invalidate('Seu acesso ou os widgets disponíveis mudaram. Atualize a página para continuar.');return false;}
  // Um GET iniciado antes de um Save confirmado pode terminar depois dele.
  if(Number.isInteger(revision)&&next.revision<revision)return false;
  const changedVersion=revision!==next.revision,changedRepresentation=etag!==next.etag;
  if(!initial&&editing&&changedVersion){uncertain=true;say('Há uma nova disposição salva. Seu rascunho foi preservado. Consulte o estado salvo antes de continuar.','atencao');controls();return false;}
  if(!initial&&editing){etag=next.etag;lastCheck=Date.now();controls();return true;}
  const view=state?capturarContinuidade(root,null):null;
  ({context,state,standard,revision,etag,catalogKey,presentation}=next);entry=clone(state);savedPresentation=presentation;dirty=false;uncertain=false;
  const summary=registry.area.querySelector('[data-painel-secao="resumo"]');if(summary)summary.after(root);
  if(initial||changedVersion||changedRepresentation)render(view);else controls();lastCheck=Date.now();return true;
 }
 async function request(descriptor,{body,conditional=false}={}){
  const controller=new AbortController(),ticket=epoch;requests.add(controller);const timeout=setTimeout(()=>controller.abort(),20000);
  try{const headers={...descriptorHeaders};if(conditional&&tagValida(etag))headers['If-None-Match']=etag;
   const r=await window.G360Requisicao(descriptor,{headers,body:body?JSON.stringify(body):undefined,signal:controller.signal});
   if(ticket!==epoch||invalid)throw new DOMException('Acesso alterado','AbortError');
   const data=await r.json();if(ticket!==epoch||invalid||!cfg.isConnected)throw new DOMException('Acesso alterado','AbortError');if(!r.ok){const error=new Error(r.status===409?'A disposição ou o acesso mudou. Seu rascunho foi preservado.':r.status===429?'Aguarde antes de consultar novamente.':data.message||'Não foi possível confirmar a disposição.');error.status=r.status;throw error;}
   return data;
  }finally{clearTimeout(timeout);requests.delete(controller);}
 }
 async function revalidate({force=false,replace=false}={}){
  if(invalid||!cfg.isConnected||document.visibilityState==='hidden'||checking||(!force&&Date.now()-lastCheck<30000))return checking;
  if(busy)return;lastCheck=Date.now();
  checking=(async()=>{try{
   let body=await request(config.consultar,{conditional:!replace&&!!state});
   if(body.nao_modificado===true){if(state&&body.etag===etag){lastCheck=Date.now();return;}body=await request(config.consultar);}
   if(replace){prepare(body);editing=false;preview=false;}
   adopt(body,{initial:!state});
  }catch(e){if(invalid||e.name==='AbortError'&&life.signal.aborted)return;uncertain=true;say(e.message||'Não foi possível confirmar a disposição.','erro');controls();}finally{checking=null;}})();
  return checking;
 }
 async function save(){
  if(!eligible()||busy||uncertain||!editing)return;const candidate=clone(state);delete candidate.context;
  try{validar(state,context);}catch(e){say(e.message,'erro');return;}
  busy=true;controls();
  try{
   const body=await request(config.salvar,{body:{versao:revision,chave:crypto.randomUUID(),acao:'salvar',catalogo_chave:catalogKey,composicao:candidate,apresentacao:presentation}});
   // Only this acknowledged transaction exits editing; no automatic write retry.
   prepare(body);editing=false;preview=false;if(!adopt(body,{initial:true}))return;say('Disposição salva.');
   channel?.postMessage({tipo:'invalidar',contexto:context.scope,versao:revision});focus('[data-editor-organizar]');
  }catch(e){if(!invalid){uncertain=e.status!==422;say(e.message,'erro');}}
  finally{busy=false;controls();}
 }
 function confirmAction(title,text,run){if(busy||invalid)return;const dialog=$('[data-editor-confirmacao]');pendingConfirm=run;$('#pe-confirmar-titulo').textContent=title;$('[data-editor-confirmar-texto]').textContent=text;dialog.showModal();button('nao').focus();}
 on(button('nao'),'click',()=>$('[data-editor-confirmacao]').close());
 on(button('sim'),'click',()=>{const run=pendingConfirm;pendingConfirm=null;$('[data-editor-confirmacao]').close();run?.();});
 on($('[data-editor-confirmacao]'),'close',()=>pendingConfirm=null);
 function rename(id,parent){
  if(!eligible()||busy||uncertain)return;const n=obter(state,id),max=n.kind==='section'?60:80,wrap=document.createElement('span');wrap.className='renomear';wrap.setAttribute('role','group');
  wrap.innerHTML='<input aria-label="Novo título" maxlength="'+max+'" value="'+escape(n.title)+'"><span class="erro" role="alert"></span>'+btn('Confirmar título','confirmar','data-confirmar-titulo')+btn('Cancelar edição do título','fechar','data-cancelar-titulo')+btn('Restaurar título padrão','atualizar','data-restaurar-titulo');parent.replaceChildren(wrap);const input=wrap.querySelector('input');input.focus();input.select();
  const cancel=()=>{render();focus('[data-quadro-titulo="'+id+'"]');};wrap.querySelector('[data-cancelar-titulo]').addEventListener('click',cancel);
  wrap.querySelector('[data-restaurar-titulo]').addEventListener('click',()=>{input.value=n.kind==='section'?n.originalTitle:definitions().widgets.find(w=>w.id===id).title;input.focus();});
  const apply=()=>{try{const next=clone(state);obter(next,id).title=titulo(input.value,max);changed(next);focus('[data-quadro-titulo="'+id+'"]');}catch(e){wrap.querySelector('.erro').textContent=e.message;input.setAttribute('aria-invalid','true');}};
  wrap.querySelector('[data-confirmar-titulo]').addEventListener('click',apply);wrap.addEventListener('keydown',e=>{if(e.key==='Escape'){e.preventDefault();e.stopPropagation();cancel();}if(e.key==='Enter'){e.preventDefault();apply();}});
 }
 function createPanel(widgetId=null){
  if(!eligible()||busy||uncertain)return;creationWidget=widgetId;creationDone=false;const form=$('[data-editor-novo-form]');form.reset();$('[data-editor-novo-erro]').textContent='';
  form.elements.pai.innerHTML='<option value="">Sem seção · área do painel</option>'+state.sections.filter(n=>profundidade(state,n.id)===0).map(n=>'<option value="'+n.id+'">'+escape(caminho(state,n.id))+'</option>').join('');
  const current=widgetId?obter(state,widgetId).parent:null;if(current&&[...form.elements.pai.options].some(o=>o.value===current))form.elements.pai.value=current;
  $('[data-editor-novo-dialog]').showModal();form.elements.titulo.focus();
 }
 on($('[data-editor-novo-form]'),'submit',e=>{e.preventDefault();if(!eligible()||busy||uncertain)return;const form=e.currentTarget;try{const id='pessoal-'+crypto.randomUUID();let next=adicionarPainel(state,form.elements.titulo.value,id,form.elements.pai.value||null,context);if(creationWidget)next=transferir(next,creationWidget,id,context);creationDone=true;$('[data-editor-novo-dialog]').close();changed(next);focus('[data-quadro-titulo="'+id+'"]');}catch(error){$('[data-editor-novo-erro]').textContent=error.message;}});
 on(button('novo-cancelar'),'click',()=>$('[data-editor-novo-dialog]').close());
 on($('[data-editor-novo-dialog]'),'close',()=>{if(!creationDone){render();focus(creationWidget?'[data-destino="'+creationWidget+'"]':'[data-editor-novo]');}});
 on(button('novo'),'click',()=>createPanel());on(button('salvar'),'click',save);
 on(button('organizar'),'click',()=>{if(!eligible()||busy||uncertain||!state)return;entry=clone(state);savedPresentation=presentation;editing=true;preview=false;render();focus('#pe-tab-'+tab);});
 on(button('cancelar'),'click',()=>{state=clone(entry);presentation=savedPresentation;editing=false;preview=false;dirty=false;render();say(uncertain?'Rascunho descartado. Consulte o estado salvo para continuar.':'Alterações descartadas.');focus('[data-editor-organizar]');});
 on(button('restaurar'),'click',()=>confirmAction('Restaurar padrão do perfil?','A disposição padrão será aplicada ao rascunho. Salve para manter a mudança.',()=>{changed(clone(standard));say('Padrão aplicado ao rascunho.');}));
 on(button('consultar'),'click',()=>{const run=()=>revalidate({force:true,replace:true});if(editing&&dirty)confirmAction('Abrir a disposição salva?','O rascunho local será descartado. Nenhuma operação será reenviada.',run);else run();});
 on(button('ver'),'click',()=>{preview=true;render();focus('[data-editor-voltar]');});on(button('voltar'),'click',()=>{preview=false;render();focus('#pe-tab-'+tab);});
 for(const b of all('[data-editor-aba]')){on(b,'click',()=>{if(!busy&&!uncertain){tab=b.dataset.editorAba;render();focus('#pe-tab-'+tab);}});on(b,'keydown',e=>{if(['ArrowLeft','ArrowRight','Home','End'].includes(e.key)){e.preventDefault();if(busy||uncertain)return;tab=e.key==='Home'?'distribuicao':e.key==='End'?'fino':tab==='fino'?'distribuicao':'fino';render();focus('#pe-tab-'+tab);}});}
 registry.area.querySelectorAll('[data-lista-modo]').forEach(b=>on(b,'click',()=>{if(busy||invalid||editing&&uncertain)return;presentation=b.dataset.listaModo;if(editing)dirty=true;list();}));
 on(document,'g360:painel-orientacao',e=>{if(!state||invalid||busy||uncertain)return;const n=state.widgets[e.detail?.id];if(n&&['vertical','horizontal'].includes(e.detail.orientacao)){n.orientacao=e.detail.orientacao;if(editing)dirty=true;}});
 const resize=()=>{if(invalid)return;list();if(!state)return;for(const d of all('dialog[open]'))d.close();const view=capturarContinuidade(root,null);render(view);};on(media,'change',resize);on(mobile,'change',resize);
 on(window,'focus',()=>revalidate());on(document,'visibilitychange',()=>{if(document.visibilityState==='visible')revalidate();});
 function invalidate(message){invalid=true;epoch++;requests.forEach(r=>r.abort());mounted?.destroy();mounted=null;state=entry=standard=context=null;channel?.close();registry.guardar();root.querySelectorAll('[data-editor-quadro],[data-editor-fino],[data-editor-leitura]').forEach(el=>el.replaceChildren());all('dialog[open]').forEach(d=>d.close());$('[data-editor-edicao]').hidden=true;controls();say(message,'atencao');}
 on(document,'g360:acesso-alterado',()=>invalidate('O acesso ativo mudou. Abra o painel atual para continuar.'));
 on(window,'pageshow',e=>{if(e.persisted)location.reload();});
 on(window,'pagehide',()=>{epoch++;requests.forEach(r=>r.abort());mounted?.destroy();mounted=null;channel?.close();state=entry=standard=context=null;cfg.textContent='';root.querySelectorAll('[data-editor-quadro],[data-editor-fino],[data-editor-leitura]').forEach(el=>el.replaceChildren());});
 window.G360EditorPainelDesmontar=()=>{epoch++;life.abort();requests.forEach(r=>r.abort());mounted?.destroy();mounted=null;channel?.close();registry.restaurar();window.G360EditorPainelDesmontar=null;};
 try{list();if(config.bootstrap){adopt(config.bootstrap,{initial:true});config.bootstrap=null;cfg.textContent='';}else{say('A organização personalizada está indisponível. A apresentação original foi preservada.','atencao');uncertain=true;controls();}
  if(context&&'BroadcastChannel'in window){channel=new BroadcastChannel('g360-editor-paineis');on(channel,'message',e=>{const d=e.data;if(d?.tipo!=='invalidar'||d.contexto!==context?.scope||!Number.isInteger(d.versao)||d.versao<=revision)return;if(editing){uncertain=true;say('Há outra disposição salva. Seu rascunho foi preservado. Consulte o estado salvo.','atencao');controls();}else revalidate({force:true});});}
 }catch(e){mounted?.destroy();mounted=null;registry.restaurar();uncertain=true;state=null;say(e.message,'erro');controls();}
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',iniciar,{once:true});else iniciar();
