const optional=['fixo','altura_livre','orientacao'];
const own=(n,key)=>Object.prototype.hasOwnProperty.call(n,key);
export const project=n=>{
 const result={id:n.id,section:n.section,x:n.x,y:n.y,w:n.w,h:n.h,altura_desejada:n.altura_desejada??n.h};
 for(const key of optional)if(own(n,key))result[key]=n[key];
 return result;
};
export function rowsOf(layout){const rows=[];for(const n of [...layout].sort((a,b)=>a.y-b.y||a.x-b.x)){let r=rows.find(r=>r[0].y===n.y);if(!r)rows.push(r=[]);r.push({...n});}return rows;}
function widths(row,limits,priority){
 const result=new Map(row.map(n=>{const c=limits.get(n.id);return[n.id,Math.max(c.minW,Math.min(c.maxW,n.w))];}));
 let excess=[...result.values()].reduce((a,b)=>a+b,0)-12;
 // Espaço livre permanece livre. Só reduzir irmãos quando as larguras não cabem.
 while(excess>0){const eligible=row.filter(n=>n.id!==priority&&result.get(n.id)>limits.get(n.id).minW);if(!eligible.length)throw Error('Limites de largura incompatíveis');eligible.sort((a,b)=>(result.get(b.id)-limits.get(b.id).minW)-(result.get(a.id)-limits.get(a.id).minW));result.set(eligible[0].id,result.get(eligible[0].id)-1);excess--;}
 return result;
}
function legacyRows(input,limits,priority=null){
 const chunks=[],prepared=[];
 for(const original of input){let pending=original.map(n=>({...n}));
  if(priority&&pending.some(n=>n.id===priority)){
   const active=pending.find(n=>n.id===priority);const spill=[];
   while(pending.length>1&&active.w+pending.filter(n=>n!==active).reduce((v,n)=>v+limits.get(n.id).minW,0)>12){const i=pending.findLastIndex(n=>n!==active);spill.unshift(pending.splice(i,1)[0]);}
   if(spill.length){prepared.push(pending);pending=spill;}
  }
  prepared.push(pending);
 }
 for(const pending of prepared){
  let group=[];
  for(const n of pending){const c=limits.get(n.id);if(!c)throw Error('Widget não autorizado');n.altura_desejada=Math.max(c.minH,Math.min(c.maxH,n.altura_desejada??n.h));
   const proposal=[...group,n],minWidth=proposal.reduce((sum,n)=>sum+limits.get(n.id).minW,0),height=Math.max(...proposal.map(n=>n.altura_desejada)),maxH=Math.min(...proposal.map(n=>limits.get(n.id).maxH));
   if(group.length&&(minWidth>12||height>maxH)){chunks.push(group);group=[];}group.push(n);
  }
  if(group.length)chunks.push(group);
 }
 const out=[];let y=0;
 for(const row of chunks){if(!row.length)continue;for(const n of row){const c=limits.get(n.id);n.altura_desejada=Math.max(c.minH,Math.min(c.maxH,n.altura_desejada??n.h));}
  const h=Math.max(...row.map(n=>n.altura_desejada)),w=widths(row,limits,priority);let x=0;
  for(const n of row){out.push({...project(n),x,y,w:w.get(n.id),h});x+=w.get(n.id);}if(x>12)throw Error('Linha excede a largura disponível');y+=h;
 }
 if(y>200)throw Error('O conjunto excede a altura disponível');return out;
}

function validatedRows(input,limits,anchors){
 if(!Array.isArray(input)||input.some(row=>!Array.isArray(row)))throw Error('Disposição inválida');
 const seen=new Set(),sections=new Set();
 const pinned=new Map((anchors??[]).map(n=>[n.id,n]));
 return input.map(row=>row.map(raw=>{
  if(!raw||typeof raw!=='object'||typeof raw.id!=='string'||seen.has(raw.id)||!limits.has(raw.id))throw Error('Widget não autorizado ou repetido');
  seen.add(raw.id);const c=limits.get(raw.id);
  if(typeof raw.section!=='string'||(c.section&&c.section!==raw.section))throw Error('Seção inválida');
  sections.add(raw.section);if(sections.size>1)throw Error('As seções devem ser organizadas separadamente');
  for(const key of ['minW','maxW','minH','maxH'])if(!Number.isInteger(c[key]))throw Error('Limites inválidos');
  if(c.minW<1||c.maxW>12||c.minW>c.maxW||c.minH<1||c.maxH>200||c.minH>c.maxH)throw Error('Limites inválidos');
  for(const key of ['fixo','altura_livre'])if(own(raw,key)&&typeof raw[key]!=='boolean')throw Error('Opção inválida');
  if(own(raw,'orientacao')&&!['horizontal','vertical'].includes(raw.orientacao))throw Error('Orientação inválida');
  let n={...raw};
  // A referência pré-gesto impede que o GridStack desloque uma âncora por efeito colateral.
  if(n.fixo&&pinned.has(n.id))for(const key of ['x','y','w','h','altura_desejada']){
   if(key==='altura_desejada')n[key]=pinned.get(n.id)[key]??pinned.get(n.id).h;
   else n[key]=pinned.get(n.id)[key];
  }
  if(!['x','y','w','h'].every(key=>Number.isInteger(n[key]))||!Number.isInteger(n.altura_desejada??n.h))throw Error('Geometria inválida');
  if(n.fixo){
   if(n.x<0||n.y<0||n.x+n.w>12||n.y+n.h>200||n.w<c.minW||n.w>c.maxW||n.h<c.minH||n.h>c.maxH||(n.altura_desejada??n.h)<c.minH||(n.altura_desejada??n.h)>n.h)throw Error('Âncora fora dos limites');
  }else{
   n.w=Math.max(c.minW,Math.min(c.maxW,n.w));
   n.altura_desejada=Math.max(c.minH,Math.min(c.maxH,n.altura_desejada??n.h));
  }
  return n;
 }));
}

function rowHeights(row,limits){
 const regular=row.filter(n=>!n.altura_livre);
 const common=regular.length?Math.max(...regular.map(n=>n.altura_desejada)):0;
 if(regular.some(n=>common>limits.get(n.id).maxH))return null;
 return new Map(row.map(n=>[n.id,n.altura_livre?n.altura_desejada:common]));
}

function occupiedRows(input,limits,priority){
 const occupied=Array.from({length:200},()=>new Uint8Array(12));
 const out=[];
 const fits=(x,y,w,h)=>{
  if(x<0||y<0||x+w>12||y+h>200)return false;
  for(let row=y;row<y+h;row++)for(let col=x;col<x+w;col++)if(occupied[row][col])return false;
  return true;
 };
 const reserve=n=>{
  if(!fits(n.x,n.y,n.w,n.h))throw Error('Os cards fixos colidem ou não há espaço disponível');
  for(let row=n.y;row<n.y+n.h;row++)for(let col=n.x;col<n.x+n.w;col++)occupied[row][col]=1;
  out.push(project(n));
 };
 for(const n of input.flat())if(n.fixo)reserve(n);
 // A ordem lógica permanece estável. Espaços impostos por âncoras não são compactados à força.
 let cursor=0;
 for(const original of input){
  const anchors=original.filter(n=>n.fixo);
  if(anchors.length)cursor=Math.max(cursor,Math.min(...anchors.map(n=>n.y)));
  let pending=original.filter(n=>!n.fixo);
  while(pending.length){
   let placement=null,count=0;
   for(let y=cursor;y<200&&!placement;y++){
    // Tentar o maior prefixo que cabe mantém a ordem e aproveita as faixas ao lado de âncoras.
    for(let size=pending.length;size>0;size--){
     const group=pending.slice(0,size),minWidth=group.reduce((sum,n)=>sum+limits.get(n.id).minW,0);
     if(minWidth>12)continue;
     const active=group.find(n=>n.id===priority);
     if(active&&active.w+group.filter(n=>n!==active).reduce((sum,n)=>sum+limits.get(n.id).minW,0)>12)continue;
     const hs=rowHeights(group,limits);if(!hs)continue;
     const ws=widths(group,limits,priority),positions=[];let x=0;
     for(const n of group){
      const w=ws.get(n.id),h=hs.get(n.id);
      while(x+w<=12&&!fits(x,y,w,h))x++;
      if(x+w>12)break;
      positions.push({...project(n),x,y,w,h});x+=w;
     }
     if(positions.length===group.length){placement=positions;count=size;break;}
    }
   }
   if(!placement)throw Error('O conjunto excede o espaço disponível');
   for(const n of placement)reserve(n);
   cursor=Math.max(...placement.map(n=>n.y+n.h));
   pending=pending.slice(count);
  }
 }
 return out.sort((a,b)=>a.y-b.y||a.x-b.x);
}

/** Resolve uma seção. options.anchors aceita a geometria fixa capturada antes de um gesto. */
export function normalizeRows(input,limits,priority=null,options={}){
 const rows=validatedRows(input,limits,options.anchors);
 // Sem recursos ativos, preservar a composição e os cálculos aprovados da versão anterior.
 if(!rows.flat().some(n=>n.fixo||n.altura_livre))return legacyRows(rows,limits,priority);
 return occupiedRows(rows,limits,priority);
}
