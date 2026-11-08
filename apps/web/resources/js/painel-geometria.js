import {GridStackEngine} from 'gridstack/dist/gridstack-engine';

export const chartIds=new Set(['distribuicao-acertos','resultados-avaliacao','periodos-escolares','habilidades','reavaliacoes']);
const own=(value,key)=>Object.prototype.hasOwnProperty.call(value,key);
const geometry=['x','y','w','h'];
const optional=['altura_desejada','fixo','altura_livre','orientacao'];

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
  engine.compact('compact');out.push(...captureLayout(engine,before,limits,{anchors}));
 }
 return validateLayout(out,limits,{complete:true});
}

/** Fallback legado sem padrao.desktop: o posicionamento inicial também é nativo. */
export function autoLayout(items,limits){
 if(!Array.isArray(items)||items.length>64||new Set(items.map(n=>n.id)).size!==items.length)throw Error('Padrão inválido');
 const out=[];
 for(const section of new Set([...limits.values()].map(c=>c.section))){
  const before=items.filter(n=>n.section===section),engine=nativeEngine([],limits,{compact:false});
  engine.batchUpdate();
  for(const n of before){validateLayout([n],limits);const opts=nodeOptions(n,limits.get(n.id));delete opts.x;delete opts.y;opts.autoPosition=true;engine.addNode(opts);}
  engine.batchUpdate(false,false);engine.compact('compact');out.push(...captureLayout(engine,before,limits,{anchors:[]}));
 }
 return validateLayout(out,limits,{complete:true});
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
