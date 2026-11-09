export const perfis = {coordenador:'Coordenação',professor:'Professor',diretor:'Direção',secretaria:'Secretaria escolar'};
export const clone = value => structuredClone(value);
const gerente = role => ['coordenador','diretor'].includes(role);
const widget = (id,title,type,section,extra={}) => ({id,title,type,section,w:6,h:7,minW:4,minH:7,...extra});
export function catalogo(context){
 if(!Object.hasOwn(perfis,context.perfil)||!['turmas','turma'].includes(context.painel)||!['ana','bruno'].includes(context.usuario)||!['escola-a','escola-b'].includes(context.vinculo))throw Error('Contexto de demonstração inválido.');
 const pedagogo=context.perfil!=='secretaria';
 const list=context.painel==='turmas';
 let sections=list?[{id:'cadastros',title:'Turmas'},{id:'analises',title:'Análises entre turmas'},{id:'acompanhamento',title:'Acompanhamento pedagógico'}]:[{id:'resultados',title:'Resultados da turma'},{id:'acompanhamento',title:'Acompanhamento da turma'}];
 let widgets=list?[
  widget('lista','Turmas','lista','cadastros',{w:12,h:11,minH:8,minW:6}),
  widget('turnos','Turmas por turno','turnos','analises'),
  widget('comparativo','Resultados entre turmas','comparativo','analises'),
  widget('cobertura','Cobertura por disciplina','cobertura','analises'),
  widget('participacao','Participação nas aplicações','participacao','acompanhamento'),
  widget('agenda','Agenda de avaliações','agenda','acompanhamento'),
  widget('devolutivas','Devolutivas','devolutivas','acompanhamento')
 ]:[
  widget('destaques','Destaques da turma','destaques','resultados',{h:11,minH:11}),
  widget('atencao','Alunos que precisam de atenção','atencao','resultados',{h:11,minH:11}),
  widget('avaliacoes','Resultados por avaliação','avaliacoes','resultados'),
  widget('periodos','Resultados por período escolar','periodos','resultados'),
  widget('agenda','Agenda da turma','agenda','resultados'),
  widget('distribuicao','Distribuição dos acertos','distribuicao','resultados'),
  widget('metas','Metas de acompanhamento','metas','acompanhamento'),
  widget('devolutivas','Devolutivas','devolutivas','acompanhamento'),
  widget('habilidades','Habilidades em acompanhamento','habilidades','acompanhamento'),
  widget('reavaliacoes','Resultados das reavaliações','reavaliacoes','acompanhamento')
 ];
 if(!pedagogo){widgets=list?widgets.filter(w=>['lista','turnos'].includes(w.id)):[widget('matriculas','Matrículas da turma','matriculas','resultados')];sections=sections.filter(s=>widgets.some(w=>w.section===s.id));}
 if(!gerente(context.perfil))widgets=widgets.filter(w=>w.id!=='metas');
 sections=sections.filter(s=>widgets.some(w=>w.section===s.id));
 return {sections,widgets};
}
export const scopeKey=c=>'g360:previa-editor:v2:'+JSON.stringify([c.usuario,c.perfil,c.vinculo,c.painel,c.painel==='turma'?c.turma:null]);
export function padrao(context){
 const c=catalogo(context),widgets={};
 for(const s of c.sections){let x=0,y=0,row=0;for(const w of c.widgets.filter(w=>w.section===s.id)){if(x+w.w>12){x=0;y+=row;row=0;}widgets[w.id]={title:w.title,active:true,fixo:false,x,y,w:w.w,h:w.h,orientacao:'vertical'};x+=w.w;row=Math.max(row,w.h);}}
 return {schema:1,context:clone(context),sections:c.sections.map(s=>({...s,widgets:c.widgets.filter(w=>w.section===s.id).map(w=>w.id)})).reverse(),widgets};
}
export function titulo(value,max){if(typeof value!=='string'||!value.trim()||[...value.trim()].length>max)throw Error('Informe um título de 1 a '+max+' caracteres.');return value.trim();}
export function validar(state,context){
 const c=catalogo(context);
 if(!state||state.schema!==1||scopeKey(state.context)!==scopeKey(context)||state.sections?.length!==c.sections.length||Object.keys(state.widgets||{}).length!==c.widgets.length)throw Error('Disposição incompatível com este contexto.');
 const seen=new Set(),groups=new Set();
 for(const s of state.sections){if(!c.sections.some(v=>v.id===s.id)||groups.has(s.id)||!Array.isArray(s.widgets))throw Error('Seção inválida.');groups.add(s.id);titulo(s.title,60);
  for(const id of s.widgets){const def=c.widgets.find(v=>v.id===id),w=state.widgets[id];if(!def||seen.has(id)||!w)throw Error('Widget desconhecido ou repetido.');seen.add(id);titulo(w.title,80);
   if(typeof w.active!=='boolean'||typeof w.fixo!=='boolean'||!['vertical','horizontal'].includes(w.orientacao)||!['x','y','w','h'].every(k=>Number.isInteger(w[k]))||w.x<0||w.y<0||w.x+w.w>12||w.y+w.h>200||w.w<def.minW||w.w>12||w.h<def.minH||w.h>30)throw Error('Configuração de widget inválida.');
   for(const peer of s.widgets.slice(0,s.widgets.indexOf(id))){const p=state.widgets[peer];if(w.active&&p.active&&w.x<p.x+p.w&&w.x+w.w>p.x&&w.y<p.y+p.h&&w.y+w.h>p.y)throw Error('Widgets sobrepostos.');}
  }
 }
 if(seen.size!==c.widgets.length)throw Error('Faltam widgets autorizados.');return clone(state);
}
export function transferir(state,id,sectionId,index){
 const next=clone(state),target=next.sections.find(s=>s.id===sectionId),source=next.sections.find(s=>s.widgets.includes(id));if(!source||!target)throw Error('Transferência inválida.');
 source.widgets=source.widgets.filter(v=>v!==id);target.widgets.splice(Math.max(0,Math.min(index??target.widgets.length,target.widgets.length)),0,id);
 if(source.id!==target.id){const w=next.widgets[id];w.x=0;w.y=Math.max(0,...target.widgets.filter(v=>v!==id&&next.widgets[v].active).map(v=>next.widgets[v].y+next.widgets[v].h));}
 return next;
}
export function ativar(state,id,active){const next=clone(state),w=next.widgets[id];if(!w)throw Error('Widget não autorizado.');w.active=active;if(active){const s=next.sections.find(s=>s.widgets.includes(id));w.x=0;w.y=Math.max(0,...s.widgets.filter(v=>v!==id&&next.widgets[v].active).map(v=>next.widgets[v].y+next.widgets[v].h));}return next;}
export function moverSecao(state,id,delta){const next=clone(state),from=next.sections.findIndex(s=>s.id===id),to=from+delta;if(from<0||to<0||to>=next.sections.length)return next;next.sections.splice(to,0,next.sections.splice(from,1)[0]);return next;}
export function ler(storage,context){const raw=storage.getItem(scopeKey(context));if(!raw)return {state:padrao(context),revision:0,origin:'perfil'};const record=JSON.parse(raw);if(!Number.isInteger(record.revision)||record.revision<1)throw Error('Preferência local inválida.');return {state:validar(record.state,context),revision:record.revision,origin:'usuario'};}
export function salvar(storage,context,state,expected){const current=ler(storage,context);if(current.revision!==expected){const error=Error('Esta disposição mudou em outra aba. Seu rascunho foi preservado.');error.code=409;throw error;}const clean=validar(state,context),record={revision:expected+1,state:clean};storage.setItem(scopeKey(context),JSON.stringify(record));return record.revision;}
