import {GridStackEngine} from 'gridstack/dist/gridstack-engine';

export const chartIds=new Set(['distribuicao-acertos','resultados-avaliacao','periodos-escolares','habilidades','reavaliacoes']);
const own=(value,key)=>Object.prototype.hasOwnProperty.call(value,key);
const geometry=['x','y','w','h'];
const optional=['altura_desejada','fixo','altura_livre','orientacao'];

/** CSS local desenha a grade; pixels visuais interpretam o ponteiro. O utilitário
 * público do GridStack retorna a escala inversa, inclusive para ancestrais com zoom. */
export function gridCoordinateMetrics({localWidth,visualWidth,cellHeight,inverseScaleY}){
 if(![localWidth,visualWidth,cellHeight,inverseScaleY].every(v=>Number.isFinite(v)&&v>0))throw Error('Escala da grade inválida');
 return {cellWidthLocal:localWidth/12,cellHeightLocal:cellHeight,cellWidthVisual:visualWidth/12,cellHeightVisual:cellHeight/inverseScaleY};
}

export function previewCssBox(n,metrics){
 return {left:n.x*metrics.cellWidthLocal,top:n.y*metrics.cellHeightLocal,width:n.w*metrics.cellWidthLocal,height:n.h*metrics.cellHeightLocal};
}

// A prévia só possui estas quatro propriedades. Não restaurar opacity/position
// capturadas depois de o DD iniciar, pois sua limpeza pertence à biblioteca.
export function snapshotPreviewCss(style){
 return ['left','top','width','height'].map(key=>[key,style.getPropertyValue(key),style.getPropertyPriority(key)]);
}

export function restorePreviewCss(style,snapshot){
 for(const [key,value,priority]of snapshot){if(value==='')style.removeProperty(key);else style.setProperty(key,value,priority);}
}

export function pointerResizeDelta(start,point,metrics,scrollY=0){
 const clientDy=point.y-start.y;
 return {dw:Math.round((point.x-start.x)/metrics.cellWidthVisual),dh:clientDy===0?0:Math.round((point.y+scrollY-start.pageY)/metrics.cellHeightVisual)};
}

/** Somente o contrato de transporte, sem DOM, conteúdo ou estado interno do GridStack. */
export function project(n){
 const result={id:n.id,section:n.section,x:n.x,y:n.y,w:n.w,h:n.h};
 for(const key of optional)if(own(n,key))result[key]=n[key];
 return result;
}

export function validateLayout(items,limits,{complete=false,anchors=[]}={}){
 if(!Array.isArray(items)||items.length>64)throw Error('Disposição inválida');
 const seen=new Map();
 for(const n of items){
  if(!n||typeof n.id!=='string'||seen.has(n.id)||!limits.has(n.id))throw Error('Card não autorizado ou repetido');
  const c=limits.get(n.id);
  if(n.section!==c.section||!geometry.every(k=>Number.isInteger(n[k])))throw Error('Geometria ou seção inválida');
  if(n.x<0||n.y<0||n.x+n.w>12||n.y+n.h>200||n.w<c.minW||n.w>c.maxW||n.h<c.minH||n.h>c.maxH)throw Error('Card fora dos limites');
  if(own(n,'altura_desejada')&&(!Number.isInteger(n.altura_desejada)||n.altura_desejada<c.minH||n.altura_desejada>n.h))throw Error('Altura desejada inválida');
  for(const key of ['fixo','altura_livre'])if(own(n,key)&&typeof n[key]!=='boolean')throw Error('Opção inválida');
  if(own(n,'orientacao')&&(!chartIds.has(n.id)||!['horizontal','vertical'].includes(n.orientacao)))throw Error('Orientação inválida');
  for(const other of seen.values())if(other.section===n.section&&n.x<other.x+other.w&&n.x+n.w>other.x&&n.y<other.y+other.h&&n.y+n.h>other.y)throw Error('Os cards não podem se sobrepor');
  seen.set(n.id,n);
 }
 if(complete&&seen.size!==limits.size)throw Error('A disposição não contém todos os cards autorizados');
 for(const anchor of anchors){
  const n=seen.get(anchor.id);
  if(!n||n.section!==anchor.section||geometry.some(k=>n[k]!==anchor[k]))throw Error('A posição ou o tamanho de um card fixado mudou');
 }
 return items.map(project);
}

export function nodeOptions(n,c){
 return {id:n.id,x:n.x,y:n.y,w:n.w,h:n.h,minW:c.minW,maxW:c.maxW,minH:c.minH,maxH:c.maxH,
  locked:n.fixo===true,noMove:n.fixo===true,noResize:n.fixo===true,sizeToContent:false};
}

/** Engine real, sem DOM. Nós validados são semeados sem relayout intermediário. */
export function nativeEngine(items,limits,{compact=true}={}){
 const valid=validateLayout(items,limits);
 if(new Set(valid.map(n=>n.section)).size>1)throw Error('Uma instância por seção');
 const engine=new GridStackEngine({column:12,maxRow:200,mode:'compact',nodes:valid.map(n=>nodeOptions(n,limits.get(n.id)))});
 for(const node of engine.nodes)engine.prepareNode(node);
 if(compact)engine.compact('compact');
 return engine;
}

/** Ler geometria nativa sempre comparando com o conjunto e as âncoras anteriores. */
export function captureLayout(engine,before,limits,{resizeId=null,anchors=before.filter(n=>n.fixo===true)}={}){
 if(engine.nodes.length!==before.length)throw Error('O conjunto de cards mudou');
 const records=new Map(before.map(n=>[n.id,n])),seen=new Set();
 const result=engine.nodes.map(node=>{
  const previous=records.get(node.id);if(!previous||seen.has(node.id))throw Error('Card desconhecido ou repetido');seen.add(node.id);
  const n=project(previous);for(const k of geometry)n[k]=node[k];
  if(node.id===resizeId&&(node.w!==previous.w||node.h!==previous.h))n.altura_desejada=n.h;
  return n;
 });
 return validateLayout(result,limits,{anchors});
}

/** Canonicalização visual em memória. Nenhuma escrita de preferências acontece aqui. */
export function prepareLayout(user,standard,limits){
 const defaults=validateLayout(standard,limits,{complete:true});
 const current=validateLayout(user.length?user:defaults,limits),present=new Set(current.map(n=>n.id));
 const sections=new Set([...limits.values()].map(c=>c.section)),out=[];
 for(const section of sections){
  const before=current.filter(n=>n.section===section),missing=defaults.filter(n=>n.section===section&&!present.has(n.id));
  const engine=nativeEngine(before,limits,{compact:false});
  // Apenas componentes autorizados ausentes: a biblioteca encontra o espaço, sem algoritmo de linhas.
  engine.batchUpdate();
  for(const n of missing){
   const opts=nodeOptions(n,limits.get(n.id));delete opts.x;delete opts.y;opts.autoPosition=true;
   engine.addNode(opts);before.push(project(n));
  }
  engine.batchUpdate(false,false);
  // Uma eventual âncora nova nasce na posição que a biblioteca lhe encontrou.
  const anchors=current.filter(n=>n.section===section&&n.fixo===true);
  out.push(...captureLayout(engine,before,limits,{anchors}));
 }
 const valid=validateLayout(out,limits,{complete:true});
 try{return fillLateral(valid,limits);}catch(error){if(error.feedback)return valid;throw error;}
}

/** Fallback legado sem padrao.desktop: o posicionamento inicial também é nativo. */
export function autoLayout(items,limits){
 if(!Array.isArray(items)||items.length>64||new Set(items.map(n=>n.id)).size!==items.length)throw Error('Padrão inválido');
 const out=[];
 for(const section of new Set([...limits.values()].map(c=>c.section))){
  const before=items.filter(n=>n.section===section),engine=nativeEngine([],limits,{compact:false});
  engine.batchUpdate();
  for(const n of before){validateLayout([n],limits);const opts=nodeOptions(n,limits.get(n.id));delete opts.x;delete opts.y;opts.autoPosition=true;engine.addNode(opts);}
  engine.batchUpdate(false,false);out.push(...captureLayout(engine,before,limits,{anchors:[]}));
 }
 const valid=validateLayout(out,limits,{complete:true});
 try{return fillLateral(valid,limits);}catch(error){if(error.feedback)return valid;throw error;}
}

/** Teclado fornece coordenadas à API nativa, não troca membros de uma fila. */
export function keyboardChange(n,key,shift,c){
 if(n.fixo===true||!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(key))return null;
 if(shift){
  if(key==='ArrowLeft'||key==='ArrowRight')return {w:Math.max(c.minW,Math.min(c.maxW,n.w+(key==='ArrowRight'?1:-1)))};
  return {h:Math.max(c.minH,Math.min(c.maxH,n.h+(key==='ArrowDown'?1:-1)))};
 }
 if(key==='ArrowLeft'||key==='ArrowRight')return {x:Math.max(0,Math.min(12-n.w,n.x+(key==='ArrowRight'?1:-1)))};
 return {y:Math.max(0,Math.min(200-n.h,n.y+(key==='ArrowDown'?1:-1)))};
}

export const lateralFeedback='Não há largura suficiente neste trecho. Solte acima ou abaixo.';
function cannotFit(){const error=new Error(lateralFeedback);error.feedback=true;throw error;}
export function bandsOf(items){
 const bands=[];for(const n of [...items].sort((a,b)=>a.y-b.y||a.x-b.x)){
  let band=bands.find(b=>b.y===n.y);if(!band)bands.push(band={y:n.y,items:[]});band.items.push(project(n));
 }return bands;
}

/** Faixa singleton cruza a vizinha; separar um membro de faixa múltipla cria outra faixa. */
export function keyboardLateralAction(items,id,key){
 const current=items.find(n=>n.id===id);if(!current||current.fixo===true)return null;
 const bands=bandsOf(items.filter(n=>n.section===current.section)),at=bands.findIndex(b=>b.items.some(n=>n.id===id)),band=bands[at],index=band.items.findIndex(n=>n.id===id);
 if(key==='ArrowUp'||key==='ArrowDown'){
  const direction=key==='ArrowUp'?-1:1,place=direction<0?'before-band':'after-band';
  if(band.items.length>1)return {type:'move',targetId:id,place};
  const adjacent=bands[at+direction];if(!adjacent)return null;
  return {type:'move',targetId:adjacent.items[direction<0?0:adjacent.items.length-1].id,place};
 }
 if(key!=='ArrowLeft'&&key!=='ArrowRight')return null;
 const direction=key==='ArrowLeft'?-1:1;let peer=band.items[index+direction],after=direction>0;
 if(!peer){const adjacent=bands[at+direction]?.items;if(!adjacent)return null;peer=direction<0?adjacent[adjacent.length-1]:adjacent[0];after=direction<0;}
 return {type:'move',targetId:peer.id,place:'lateral',after};
}

/** Repartição inteira limitada, proporcional e estável na ordem indicada pelo gesto. */
export function distributeWidths(items,capacity,limits,{activeId=null,preferredWidth=null,minWidths=new Map()}={}){
 if(!Number.isInteger(capacity)||capacity<1||capacity>12||!items.length)cannotFit();
 const min=n=>Math.max(limits.get(n.id).minW,minWidths.get(n.id)??0),max=n=>limits.get(n.id).maxW;
 if(items.some(n=>min(n)>max(n))||items.reduce((s,n)=>s+min(n),0)>capacity)cannotFit();
 const total=Math.min(capacity,items.reduce((s,n)=>s+max(n),0)),out=new Map(),active=items.find(n=>n.id===activeId);
 let others=items,remaining=total;
 if(active){others=items.filter(n=>n!==active);const lower=Math.max(min(active),total-others.reduce((s,n)=>s+max(n),0)),upper=Math.min(max(active),total-others.reduce((s,n)=>s+min(n),0));
  const requested=preferredWidth??active.w;if(!Number.isInteger(requested))cannotFit();
  const width=Math.max(lower,Math.min(upper,requested));out.set(active.id,width);remaining-=width;
 }
 let pending=[...others];
 while(pending.length){const weight=pending.reduce((sum,n)=>sum+n.w,0),offers=pending.map((n,index)=>({n,index,exact:remaining*n.w/weight}));
  const lower=offers.filter(o=>o.exact<min(o.n));
  const bounded=lower.length?lower:offers.filter(o=>o.exact>max(o.n));
  if(bounded.length){for(const o of bounded){const w=o.exact<min(o.n)?min(o.n):max(o.n);out.set(o.n.id,w);remaining-=w;}pending=pending.filter(n=>!out.has(n.id));continue;}
  for(const o of offers){const w=Math.floor(o.exact);out.set(o.n.id,w);remaining-=w;}
  offers.sort((a,b)=>(b.exact-Math.floor(b.exact))-(a.exact-Math.floor(a.exact))||a.index-b.index);
  for(const o of offers)if(remaining){out.set(o.n.id,out.get(o.n.id)+1);remaining--;}
  break;
 }
 return out;
}

export function minimumPixels(id){
 if(['alunos-destaques','alunos-atencao','agenda-pedagogica','agenda-administrativa','proximos-compromissos','responsabilidade-docente-aplicacao'].includes(id))return 280;
 if(['cadastros-turno','comparacao-turmas','cobertura-disciplinas','distribuicao-acertos','resultados-avaliacao','situacoes-registradas','periodos-escolares','habilidades','reavaliacoes'].includes(id))return 300;
 return 240;
}
export function responsiveMinWidths(limits,gridWidth,gap=16){
 if(!Number.isFinite(gridWidth)||gridWidth<=0||!Number.isFinite(gap)||gap<0)cannotFit();
 return new Map([...limits].map(([id,c])=>[id,Math.max(c.minW,Math.ceil(12*(minimumPixels(id)+gap)/gridWidth))]));
}

function intervalsAt(engine,y){
 const taken=Array(12).fill(false);for(const n of engine.nodes)if(n.y<=y&&n.y+n.h>y)for(let x=n.x;x<n.x+n.w;x++)taken[x]=true;
 const result=[];for(let x=0;x<12;){if(taken[x]){x++;continue;}const start=x;while(x<12&&!taken[x])x++;result.push({x:start,w:x-start});}return result;
}
const distance=(center,slot)=>center<slot.x?slot.x-center:center>slot.x+slot.w?center-slot.x-slot.w:0;

/** Faixas por topo, com h próprio; a engine nativa verifica e reserva as áreas reais. */
function arrangeBands(bands,limits,{activeId=null,preferredWidth=null,minWidths=new Map(),affected=new Set()}={}){
 const records=bands.flatMap(b=>b.items),ids=new Set();
 for(const n of records){validateLayout([n],limits);if(ids.has(n.id))throw Error('Card repetido');ids.add(n.id);}
 const pins=records.filter(n=>n.fixo===true);validateLayout(pins,limits);
 const engine=nativeEngine(pins,limits,{compact:false});engine.mode='float';
 let floor=0;
 for(const band of bands){const moving=band.items.filter(n=>n.fixo!==true);if(!moving.length)continue;
  const ownPins=band.items.filter(n=>n.fixo===true),anchorY=ownPins.length?ownPins[0].y:null;
  if(ownPins.some(n=>n.y!==anchorY))cannotFit();
  let accepted=null;
  for(let y=anchorY??floor;y<200&&!accepted;y++){
   if(anchorY!==null&&y!==anchorY)break;
   const slots=intervalsAt(engine,y);if(!slots.length)continue;
   const buckets=slots.map(slot=>({slot,items:[]}));
   for(const n of moving){const center=n.x+n.w/2;const chosen=[...buckets].sort((a,b)=>distance(center,a.slot)-distance(center,b.slot)||a.slot.x-b.slot.x)[0];chosen.items.push(n);}
   const proposal=[];let fits=true;
   for(const {slot,items}of buckets){if(!items.length)continue;let widths;
    // Não estreitar uma faixa alheia só para forçá-la num vão vertical anterior.
    if((band.preserveWidth||!items.some(n=>n.id===activeId))&&slot.w<Math.min(12,items.reduce((sum,n)=>sum+n.w,0))){fits=false;break;}
    try{widths=distributeWidths(items,slot.w,limits,{activeId,preferredWidth,minWidths:items.some(n=>affected.has(n.id))?minWidths:new Map()});}catch{fits=false;break;}
    let x=slot.x;for(const n of items){const candidate={...project(n),x,y,w:widths.get(n.id)};x+=candidate.w;
     if(!engine.isAreaEmpty(candidate.x,candidate.y,candidate.w,candidate.h)||candidate.y+candidate.h>200){fits=false;break;}proposal.push(candidate);
    }if(!fits)break;
   }
   if(fits&&proposal.length===moving.length)accepted=proposal;
  }
  if(!accepted)cannotFit();
  for(const n of accepted)engine.addNode({...nodeOptions(n,limits.get(n.id)),locked:true,noMove:true,noResize:true});
  floor=accepted[0].y+1;
 }
 const result=captureLayout(engine,records,limits,{anchors:pins});
 const original=new Map(records.map(n=>[n.id,n]));
 for(const n of result)if(n.id===activeId&&(n.w!==original.get(n.id).w||n.h!==original.get(n.id).h))n.altura_desejada=n.h;
 return validateLayout(result,limits,{anchors:pins});
}

export function fillLateral(items,limits){
 validateLayout(items,limits);const out=[];
 for(const section of new Set(items.map(n=>n.section)))out.push(...arrangeBands(bandsOf(items.filter(n=>n.section===section)),limits));
 return validateLayout(out,limits,{anchors:items.filter(n=>n.fixo===true)});
}

/** Intenção é capturada antes da colisão nativa: faixa de origem/índice ou topo/rodapé. */
export function lateralAction(before,limits,id,action,{minWidths=new Map()}={}){
 validateLayout(before,limits);const current=before.find(n=>n.id===id);if(!current||current.fixo===true)cannotFit();
 const section=current.section,local=before.filter(n=>n.section===section),bands=bandsOf(local),source=bands.find(b=>b.items.some(n=>n.id===id));
 const moving=project(current),affected=new Set(source.items.map(n=>n.id));
 if(action.type==='resize'){
  if(action.h!==undefined){if(!Number.isInteger(action.h))cannotFit();moving.h=Math.max(limits.get(id).minH,Math.min(limits.get(id).maxH,action.h));if(moving.h!==current.h)moving.altura_desejada=moving.h;}
  source.items=source.items.map(n=>n.id===id?moving:n);
 }else if(action.type==='move'){
  const target=bands.find(b=>b.items.some(n=>n.id===action.targetId));if(!target||(action.targetId===id&&action.place==='lateral'))cannotFit();
  source.items=source.items.filter(n=>n.id!==id);
  if(action.place==='before-band'||action.place==='after-band')bands.splice(bands.indexOf(target)+(action.place==='after-band'?1:0),0,{y:target.y,items:[moving],preserveWidth:true});
  else if(action.place==='lateral'){
   const at=target.items.findIndex(n=>n.id===action.targetId),peer=target.items[at];if(peer.fixo===true)cannotFit();
   moving.x=peer.x;moving.w=peer.w;target.items.splice(at+(action.after?1:0),0,moving);for(const n of target.items)affected.add(n.id);
  }else cannotFit();
 }else cannotFit();
 // Antes de buscar outra altura, o conjunto lateral deve caber no trecho escolhido do snapshot.
 const activeBand=bands.find(b=>b.items.some(n=>n.id===id));
 if(action.type==='resize'||action.place==='lateral'){
  const targetY=action.type==='resize'?current.y:local.find(n=>n.id===action.targetId).y;
  const blockers=local.filter(n=>n.fixo===true||(!activeBand.items.some(m=>m.id===n.id)&&n.y<targetY));
  const space=nativeEngine(blockers,limits,{compact:false});space.mode='float';
  const slots=intervalsAt(space,targetY),center=action.type==='move'?(action.after?moving.x+moving.w-.01:moving.x+.01):current.x+current.w/2;
  const slot=[...slots].sort((a,b)=>distance(center,a)-distance(center,b)||a.x-b.x)[0];if(!slot)cannotFit();
  const group=activeBand.items.filter(n=>n.fixo!==true&&distance(n.x+n.w/2,slot)===0);
  if(!group.some(n=>n.id===id))group.push(moving);
  distributeWidths(group,slot.w,limits,{activeId:id,preferredWidth:action.w??current.w,minWidths});
 }
 const next=arrangeBands(bands.filter(b=>b.items.length),limits,{activeId:id,preferredWidth:action.w??current.w,minWidths,affected});
 const result=[...before.filter(n=>n.section!==section),...next];
 const active=result.find(n=>n.id===id);if(active.w!==current.w||active.h!==current.h)active.altura_desejada=active.h;
 return validateLayout(result,limits,{anchors:before.filter(n=>n.fixo===true)});
}
