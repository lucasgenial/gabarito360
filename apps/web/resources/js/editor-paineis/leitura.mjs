import{clone,visivel,normalizar,profundidade,filhos}from'./modelo.mjs';
/** Projeção somente de leitura: compactar vazios nunca muda o documento a salvar. */
export function projetarLeitura(state,context,heights){
 const out=clone(state),ctx=clone(context),hidden=new Set(out.sections.filter(s=>!visivel(out,s.id)).map(s=>s.id));
 let changed=hidden.size>0;
 for(const n of Object.values(out.widgets)){
  if(hidden.has(n.parent))n.parent=null;
  const h=heights.get(n.id);if(!n.fixo&&Number.isInteger(h)&&h>0&&h<n.h){n.h=h;ctx.catalogo.widgets.find(w=>w.id===n.id).minH=1;changed=true;}
 }
 if(!changed)return {state,context};
 // Um contêiner vazio fica somente no documento canônico e no quadro de edição.
 out.sections=out.sections.filter(s=>!hidden.has(s.id));
 if(!out.sections.length){const empty=clone(state.sections[0]);empty.parent=null;out.sections=[empty];}
 for(const s of [...out.sections].sort((a,b)=>profundidade(out,b.id)-profundidade(out,a.id)))if(!s.fixo)s.h=4;
 return {state:normalizar(out,ctx,{compact:true}),context:ctx};
}
