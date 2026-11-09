/** Move os próprios nós autorizados da view; nunca clona tabelas, gráficos ou formulários. */
export function registrarWidgets(root,painel,icon){
 const area=root.closest('[data-turma-painel]')||root.closest('main');
 if(!area)throw Error('Área do painel indisponível.');
 if(painel==='turmas'){
  const relacao=area.querySelector('#relacao-turmas');
  if(relacao){relacao.dataset.widgetId='turmas-lista';relacao.dataset.section='cadastros';relacao.classList.add('painel-widget');const h=relacao.querySelector('.painel-relacao-cabecalho');h?.classList.add('painel-widget-cabecalho');h?.querySelector('h2')?.setAttribute('data-widget-titulo','');}
 }
 const records=new Map([...area.querySelectorAll('[data-widget-id]')].filter(el=>el.dataset.section!=='resumo').map(el=>{
  const anchor=document.createComment('posição original do widget');el.before(anchor);
  return [el.dataset.widgetId,{el,anchor,style:el.getAttribute('style')}];
 }));
 const sources=[...area.querySelectorAll('[data-painel-secao],[data-painel-card]')].filter(el=>el.dataset.painelSecao!=='resumo'&&!el.contains(root));
 const hidden=new Map(sources.map(el=>[el,el.hidden]));const parking=document.createDocumentFragment();
 function guardar(){for(const {el}of records.values()){el.dataset.editorEstacionado='true';parking.append(el);}}
 function restaurar(){for(const {el,anchor,style}of records.values()){delete el.dataset.editorEstacionado;anchor.after(el);if(style===null)el.removeAttribute('style');else el.setAttribute('style',style);el.querySelectorAll('[data-editor-controle]').forEach(b=>b.remove());el.querySelectorAll('[data-widget-fixar],[data-widget-mover]').forEach(b=>b.hidden=true);}for(const [el,value]of hidden)el.hidden=value;}
 function inserir(target,state,editing){
  for(const slot of target.querySelectorAll('[data-editor-widget-slot]')){
   const id=slot.dataset.editorWidgetSlot,record=records.get(id),n=state.widgets[id];if(!record||!n)throw Error('Widget indisponível nesta tela.');
   const el=record.el;delete el.dataset.editorEstacionado;el.dataset.orientacao=n.orientacao;
   let header=el.querySelector('.painel-widget-cabecalho');if(!header){header=document.createElement('header');header.className='painel-widget-cabecalho';const h=document.createElement('h3');h.dataset.widgetTitulo='';header.append(h);el.prepend(header);}
   const title=header.querySelector('[data-widget-titulo],h2,h3,h4');if(title){const text=title.querySelector('button')||title;text.textContent=n.title;}
   let controls=header.querySelector('.painel-widget-controles');if(!controls){controls=document.createElement('div');controls.className='painel-widget-controles';header.append(controls);}
   for(const [action,old,ic]of [['pin','fixar','fixar'],['mover','mover','arrastar']]){
    let b=controls.querySelector('[data-widget-'+old+']');if(!b){b=document.createElement('button');b.type='button';b.className='btn btn-secondary btn-sm painel-widget-'+old;b.dataset.editorControle='';b.setAttribute('data-widget-'+old,'');b.innerHTML=icon(ic);controls.append(b);}
    b.setAttribute('data-no-'+action,id);b.hidden=!editing;b.disabled=n.fixo&&action==='mover';b.setAttribute('aria-label',(action==='mover'?'Mover ':n.fixo?'Liberar posição e tamanho de ':'Fixar posição e tamanho de ')+n.title);b.title=b.getAttribute('aria-label');if(action==='pin')b.setAttribute('aria-pressed',String(n.fixo));
   }
   el.querySelectorAll('[data-widget-altura-livre]').forEach(b=>b.hidden=true);
   slot.replaceWith(el);
  }
  for(const el of sources)el.hidden=true;
  document.dispatchEvent(new CustomEvent('g360:painel-redimensionado'));
 }
 return {area,records,guardar,inserir,restaurar,validar(defs){const ids=defs.widgets.map(w=>w.id);if(ids.length!==records.size||ids.some(id=>!records.has(id)))throw Error('A tela não carregou todos os widgets autorizados. Recarregue antes de organizar.');}};
}
