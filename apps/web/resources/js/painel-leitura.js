/** Projeção efêmera. Nunca usar este resultado como mapa do editor ou payload de Save. */
export function compactReadingLayout(items,heights){
 const out=items.map(n=>({...n}));let reduced=false;
 for(const n of out){
  const h=heights.get(n.id);
  if(Number.isInteger(h)&&h>0&&h<n.h){n.h=h;reduced=true;if(Object.hasOwn(n,'altura_desejada'))n.altura_desejada=Math.min(n.altura_desejada,h);}
 }
 if(!reduced)return out;
 const collides=(a,b)=>a.x<b.x+b.w&&a.x+a.w>b.x&&a.y<b.y+b.h&&a.y+a.h>b.y;
 const placed=out.filter(n=>n.fixo===true);
 for(const n of out.filter(n=>n.fixo!==true).sort((a,b)=>a.y-b.y||a.x-b.x)){
  for(let y=0;y<n.y;y++){const candidate={...n,y};if(!placed.some(p=>collides(candidate,p))){n.y=y;break;}}
  placed.push(n);
 }
 return out;
}
