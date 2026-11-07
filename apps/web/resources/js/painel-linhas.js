export const project=n=>({id:n.id,section:n.section,x:n.x,y:n.y,w:n.w,h:n.h,altura_desejada:n.altura_desejada??n.h});
export function rowsOf(layout){const rows=[];for(const n of [...layout].sort((a,b)=>a.y-b.y||a.x-b.x)){let r=rows.find(r=>r[0].y===n.y);if(!r)rows.push(r=[]);r.push({...n});}return rows;}
function widths(row,limits,priority){
 const result=new Map(row.map(n=>{const c=limits.get(n.id);return[n.id,Math.max(c.minW,Math.min(c.maxW,n.w))];}));
 let excess=[...result.values()].reduce((a,b)=>a+b,0)-12;
 // Espaço livre permanece livre. Só reduzir irmãos quando as larguras não cabem.
 while(excess>0){const eligible=row.filter(n=>n.id!==priority&&result.get(n.id)>limits.get(n.id).minW);if(!eligible.length)throw Error('Limites de largura incompatíveis');eligible.sort((a,b)=>(result.get(b.id)-limits.get(b.id).minW)-(result.get(a.id)-limits.get(a.id).minW));result.set(eligible[0].id,result.get(eligible[0].id)-1);excess--;}
 return result;
}
export function normalizeRows(input,limits,priority=null){
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
