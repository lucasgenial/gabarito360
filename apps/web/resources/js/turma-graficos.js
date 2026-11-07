import {init,use} from 'echarts/core';
import {BarChart,PieChart} from 'echarts/charts';
import {GridComponent,TooltipComponent,LegendComponent,AriaComponent,TitleComponent} from 'echarts/components';
import {SVGRenderer} from 'echarts/renderers';
use([BarChart,PieChart,GridComponent,TooltipComponent,LegendComponent,AriaComponent,TitleComponent,SVGRenderer]);

// Dados vêm do DTO autorizado do painel. Nenhuma consulta direta ou mudança de escopo no cliente.
const raiz=document.querySelector('[data-painel-graficos]');
if(raiz){
 let dados;try{dados=JSON.parse(raiz.dataset.painelDados);}catch{dados={};}
 const analise=dados.analise||{}, graficos=new Map();
 const paginaAvaliacoes={inicio:0,quantidade:6};
 const navAvaliacoes=raiz.querySelector('[data-avaliacoes-paginacao]');
 const percentual=v=>v==null?'Sem percentual':Number(v).toLocaleString('pt-BR',{maximumFractionDigits:1})+'%';
 const cor=nome=>getComputedStyle(raiz).getPropertyValue(nome).trim();
 const cores=()=>[cor('--serie-1'),cor('--serie-2'),cor('--serie-3'),cor('--serie-4'),cor('--serie-5')];
 function vazio(elemento,mensagem){elemento.textContent=mensagem;elemento.classList.add('tu1r3-grafico-vazio');}
 function recortarAvaliacoes(elemento){
  const todos=analise.por_avaliacao||[];
  paginaAvaliacoes.quantidade=Math.max(1,Math.min(6,Math.floor((elemento.clientHeight-72)/42)));
  paginaAvaliacoes.inicio=Math.min(paginaAvaliacoes.inicio,Math.max(0,todos.length-1));
  const fim=Math.min(todos.length,paginaAvaliacoes.inicio+paginaAvaliacoes.quantidade);
  if(navAvaliacoes){
   navAvaliacoes.hidden=todos.length<=paginaAvaliacoes.quantidade&&paginaAvaliacoes.inicio===0;
   navAvaliacoes.querySelector('[data-avaliacoes-status]').textContent='Avaliações '+(paginaAvaliacoes.inicio+1)+'–'+fim+' de '+todos.length;
   navAvaliacoes.querySelector('[data-avaliacoes-anterior]').disabled=paginaAvaliacoes.inicio===0;
   navAvaliacoes.querySelector('[data-avaliacoes-proxima]').disabled=fim>=todos.length;
  }
  return todos.slice(paginaAvaliacoes.inicio,fim);
 }
 function opcoes(tipo,elemento){
  const paleta=cores(),texto=cor('--muted'),borda=cor('--border-soft');
  const comum={animation:!matchMedia('(prefers-reduced-motion: reduce)').matches,color:paleta,textStyle:{fontFamily:'Figtree, sans-serif',color:texto},aria:{enabled:true,label:{description:'Painel de indicadores da turma. Os valores completos e a base de cálculo estão na alternativa textual acessível de cada gráfico.'}},tooltip:{trigger:'axis',renderMode:'richText',confine:true},grid:{left:44,right:22,top:30,bottom:54,containLabel:true}};
  if(tipo==='distribuicao'){comum.tooltip.formatter=itens=>{const i=itens[0];return i.axisValue+'\nResultados: '+i.value+'\nBase: '+(analise.distribuicao_acertos?.total??0)+' resultados';};}
  const numerico={type:'value',min:0,max:100,interval:elemento.clientWidth<600?50:20,axisLabel:{formatter:'{value}%'},splitLine:{lineStyle:{color:borda}}};
  if(tipo==='distribuicao'){
   const faixas=analise.distribuicao_acertos?.faixas||[];
   const itens=faixas.length===10?Array.from({length:5},(_,i)=>({label:i===4?'80–100%':(i*20)+'–<'+((i+1)*20)+'%',total:Number(faixas[i*2].total)+Number(faixas[i*2+1].total)})):faixas;
   if(!(analise.distribuicao_acertos?.total>0))return null;
   if(elemento.clientWidth<600)return {...comum,grid:{left:4,right:32,top:20,bottom:34,containLabel:true},xAxis:{type:'value',minInterval:1,name:'Resultados',nameLocation:'middle',nameGap:26,splitLine:{lineStyle:{color:borda}}},yAxis:{type:'category',inverse:true,data:itens.map(i=>i.label),axisLabel:{fontSize:11},axisTick:{show:false}},series:[{name:'Resultados',type:'bar',data:itens.map(i=>i.total),barMaxWidth:26,itemStyle:{borderRadius:[0,4,4,0]},label:{show:true,position:'right',formatter:p=>p.value||'',color:texto}}]};
   return {...comum,xAxis:{type:'category',data:itens.map(i=>i.label),axisLabel:{interval:0,rotate:0,fontSize:11},axisTick:{show:false}},yAxis:{type:'value',minInterval:1,name:'Resultados',splitLine:{lineStyle:{color:borda}}},series:[{name:'Resultados',type:'bar',data:itens.map(i=>i.total),barMaxWidth:36,itemStyle:{borderRadius:[5,5,0,0]},label:{show:true,position:'top',formatter:p=>p.value||'',color:texto}}]};
  }
  if(tipo==='avaliacoes'){
   const itens=recortarAvaliacoes(elemento);if(!(analise.por_avaliacao||[]).some(i=>i.n>0))return null;const primeiraOrdem=paginaAvaliacoes.inicio+1;
   return {...comum,grid:{left:4,right:48,top:25,bottom:54,containLabel:true},legend:{bottom:0,textStyle:{color:texto}},xAxis:numerico,yAxis:{type:'category',inverse:true,data:itens.map(i=>i.titulo+' · n='+i.n),axisLabel:{width:elemento.clientWidth<600?80:130,overflow:'truncate',fontSize:11,formatter:(valor,index)=>(primeiraOrdem+index)+' · '+valor},axisTick:{show:false}},series:[{name:'Média',type:'bar',barMaxWidth:17,data:itens.map(i=>i.media_pct),itemStyle:{borderRadius:[0,4,4,0]},label:{show:true,position:'right',formatter:p=>percentual(p.value),color:texto}},{name:'Mediana',type:'bar',barMaxWidth:17,data:itens.map(i=>i.mediana_pct),itemStyle:{borderRadius:[0,4,4,0]},label:{show:true,position:'right',formatter:p=>percentual(p.value),color:texto}}]};
  }
  if(tipo==='situacoes'){
   const s=dados.situacoes||{};if(!(s.total>0))return null;
   return {...comum,title:{text:String(s.total),subtext:'resultados',left:'center',top:'31%',textStyle:{color:cor('--fg'),fontSize:27,fontWeight:700},subtextStyle:{color:texto,fontSize:12}},color:[cor('--success'),paleta[2],cor('--muted')],tooltip:{trigger:'item',renderMode:'richText',confine:true,formatter:p=>p.name+': '+p.value+' ('+p.percent+'%)\nBase: '+s.total+' resultados'},legend:{bottom:0,selectedMode:false,textStyle:{color:texto},itemGap:14},series:[{type:'pie',radius:['43%','65%'],center:['50%','42%'],avoidLabelOverlap:true,label:{show:true,position:'inside',formatter:p=>p.value?p.value:'',color:'#fff',fontWeight:700},data:[{name:'Dentro do critério',value:s.dentro_criterio||0},{name:'Indicação de recuperação',value:s.indicacao_recuperacao||0},{name:'Outras situações',value:s.outros||0}]}]};
  }
  return null;
 }
 function renderizar(elemento){
  if(!elemento.clientWidth)return;
  let g=graficos.get(elemento);const config=opcoes(elemento.dataset.grafico,elemento);
  if(!config){if(g){g.dispose();graficos.delete(elemento);}vazio(elemento,elemento.dataset.grafico==='questoes'?'Sem análise de questões disponível neste recorte.':'Ainda não há resultados para este gráfico.');return;}
  if(!g){elemento.textContent='';elemento.classList.remove('tu1r3-grafico-vazio');g=init(elemento,null,{renderer:'svg'});graficos.set(elemento,g);}
  for(const eixo of ['xAxis','yAxis'])if(config[eixo]){
   const valores=Array.isArray(config[eixo])?config[eixo]:[config[eixo]];
   valores.forEach(a=>{a.axisLabel={...a.axisLabel,color:cor('--muted')};a.nameTextStyle={color:cor('--muted')};a.axisLine={lineStyle:{color:cor('--border')}};});
  }
  g.setOption(config,true);g.resize();
 }
 const elementos=[...raiz.querySelectorAll('[data-grafico]')];
 if(navAvaliacoes){
  const mostrarPagina=delta=>{paginaAvaliacoes.inicio=Math.max(0,paginaAvaliacoes.inicio+delta*paginaAvaliacoes.quantidade);const el=elementos.find(e=>e.dataset.grafico==='avaliacoes');if(el)renderizar(el);};
  navAvaliacoes.querySelector('[data-avaliacoes-anterior]').addEventListener('click',e=>{mostrarPagina(-1);if(e.currentTarget.disabled)navAvaliacoes.querySelector('[data-avaliacoes-proxima]').focus();});
  navAvaliacoes.querySelector('[data-avaliacoes-proxima]').addEventListener('click',e=>{mostrarPagina(1);if(e.currentTarget.disabled)navAvaliacoes.querySelector('[data-avaliacoes-anterior]').focus();});
 }

 const resize=new ResizeObserver(entradas=>entradas.forEach(e=>renderizar(e.target)));elementos.forEach(e=>resize.observe(e));
 const tema=new MutationObserver(()=>elementos.forEach(renderizar));tema.observe(document.documentElement,{attributes:true,attributeFilter:['data-theme','data-tema','class','style']});

 window.addEventListener('pagehide',()=>{resize.disconnect();tema.disconnect();graficos.forEach(g=>g.dispose());});
}
