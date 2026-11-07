/* Apresentação local dos gráficos; persistência pertence ao motor do painel. */
(function () {
 'use strict';
 if(window.G360PainelGraficosOpcoes){window.G360PainelGraficosOpcoes.iniciar();return;}
 var ids=new Set(['distribuicao-acertos','resultados-avaliacao','periodos-escolares','habilidades','reavaliacoes']);
 function inicial(widget){
  var el=widget.querySelector('[data-grafico]');
  if(!el)return 'vertical';
  if(el.dataset.grafico==='avaliacoes')return 'horizontal';
  if(el.dataset.grafico==='distribuicao')return el.clientWidth<600?'horizontal':'vertical';
  try{var d=JSON.parse(el.dataset.grafico);return d.horizontal||(el.clientWidth<420&&d.categorias.length>2)?'horizontal':'vertical';}catch(_){return 'vertical';}
 }
 function atualizar(widget){
  if(!ids.has(widget.dataset.widgetId))return;
  var orientacao=widget.dataset.orientacao;
  if(orientacao!=='horizontal'&&orientacao!=='vertical')widget.dataset.orientacao=orientacao=inicial(widget);
  var proxima=orientacao==='horizontal'?'vertical':'horizontal';
  widget.querySelectorAll('[data-grafico-orientacao]').forEach(function(b){
   var nome=proxima==='horizontal'?'Barras horizontais':'Barras verticais';
   b.setAttribute('aria-label',nome);b.title=nome;
   b.querySelectorAll('[data-orientacao-horizontal]').forEach(function(s){s.hidden=proxima!=='horizontal';});
   b.querySelectorAll('[data-orientacao-vertical]').forEach(function(s){s.hidden=proxima!=='vertical';});
  });
 }
 function iniciar(){document.querySelectorAll('[data-widget-id]').forEach(atualizar);}
 document.addEventListener('click',function(e){
  var b=e.target.closest('[data-grafico-orientacao]');if(!b||b.disabled)return;
  var w=b.closest('[data-widget-id]');if(!w||!ids.has(w.dataset.widgetId))return;
  atualizar(w);w.dataset.orientacao=w.dataset.orientacao==='horizontal'?'vertical':'horizontal';atualizar(w);
  document.dispatchEvent(new CustomEvent('g360:painel-orientacao',{detail:{id:w.dataset.widgetId,orientacao:w.dataset.orientacao}}));
 });
 document.addEventListener('g360:painel-redimensionado',iniciar);
 new MutationObserver(function(ms){if(ms.some(function(m){return m.type==='childList'&&Array.from(m.addedNodes).some(function(n){return n.nodeType===1&&(n.matches('[data-widget-id]')||n.querySelector('[data-widget-id]'));});}))iniciar();}).observe(document.documentElement,{subtree:true,childList:true});
 window.G360PainelGraficosOpcoes={iniciar:iniciar};
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',iniciar);else iniciar();
})();
