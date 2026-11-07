import calendarjs from '@calendarjs/ce';
// Dados demonstrativos locais, sem opções remotas, edição, URLs ou persistência.
const ids = new Set(['agenda-pedagogica','proximos-compromissos','agenda-administrativa']);
const texto = value => String(value ?? '').replace(/</g,'‹').replace(/>/g,'›').slice(0,700);
function iniciar(){
 document.querySelectorAll('[data-agenda-demo]').forEach(el=>{
  if(el.dataset.agendaPronta==='true')return;
  const widget=el.closest('[data-widget-id]');
  if(!widget||widget.dataset.demo!=='true'||!ids.has(widget.dataset.widgetId))return;
  let itens;try{itens=JSON.parse(el.dataset.agendaDemo)}catch{return}
  if(!Array.isArray(itens)||!itens.length||itens.length>32||!itens.every(item=>item&&/^\d{4}-\d{2}-\d{2}$/.test(item.data)&&/^\d{2}:\d{2}$/.test(item.hora)))return;
  const data=itens.map(item=>{
   return {date:item.data+'T12:00:00',title:texto(item.titulo),subtitle:texto(item.hora+' · '+item.situacao),description:texto(item.responsavel)};
  });
  try{
   calendarjs.Timeline(el,{data,type:'default',format:'dd/mm',align:'left',order:'asc',controls:false,editable:false,remote:false,message:'Nenhum compromisso neste exemplo.'});
   // Calendar.js é o renderer. Este adaptador limita o componente à leitura de dados demonstrativos.
   const visual=el.querySelector('.lm-timeline');if(!visual)throw Error('Agenda não renderizada');
   visual.setAttribute('role','list');visual.setAttribute('aria-label','Compromissos demonstrativos');
   visual.querySelectorAll('.lm-timeline-item').forEach(item=>item.setAttribute('role','listitem'));
   visual.querySelectorAll('.lm-timeline-navigation,.lm-timeline-edit').forEach(n=>n.remove());
   visual.querySelectorAll('[onclick]').forEach(n=>n.removeAttribute('onclick'));
   el.dataset.agendaPronta='true';
   const alternativa=widget.querySelector('[data-agenda-alternativa]');if(alternativa)alternativa.classList.add('painel-valores-acessiveis');
  }catch{
   el.replaceChildren();el.dataset.agendaFalhou='true';
   const alternativa=widget.querySelector('[data-agenda-alternativa]');if(alternativa)alternativa.classList.remove('painel-valores-acessiveis');
  }
 });
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',iniciar);else iniciar();
new MutationObserver(m=>{if(m.some(x=>Array.from(x.addedNodes).some(n=>n.nodeType===1&&(n.matches('[data-agenda-demo]')||n.querySelector('[data-agenda-demo]')))))iniciar()}).observe(document.documentElement,{childList:true,subtree:true});
