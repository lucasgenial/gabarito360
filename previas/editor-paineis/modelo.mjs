import * as core from '../../apps/web/resources/js/editor-paineis/modelo.mjs';
import {clone,MAX_PAINEIS,MAX_NIVEIS,HEADER_ROWS,MAX_ROWS,linhasCabecalho,nodes,obter,filhos,titulo,profundidade,visivel,podeMover,caminho} from '../../apps/web/resources/js/editor-paineis/modelo.mjs';
export {clone,MAX_PAINEIS,MAX_NIVEIS,HEADER_ROWS,MAX_ROWS,linhasCabecalho,nodes,obter,filhos,titulo,profundidade,visivel,podeMover,caminho};
export const perfis={coordenador:'Coordenação',professor:'Professor',diretor:'Direção',secretaria:'Secretaria escolar'};
const widget=(id,title,type,parent,extra={})=>({id,title,type,parent,w:6,h:7,minW:4,minH:7,...extra});
export function catalogo(c){
 if(!Object.hasOwn(perfis,c.perfil)||!['turmas','turma'].includes(c.painel)||!['ana','bruno'].includes(c.usuario)||!['escola-a','escola-b'].includes(c.vinculo))throw Error('Contexto de demonstração inválido.');
 const list=c.painel==='turmas',pedagogico=c.perfil!=='secretaria';
 let sections=list?[{id:'cadastros',title:'Turmas'},{id:'analises',title:'Análises entre turmas'},{id:'acompanhamento',title:'Acompanhamento pedagógico'}]:[{id:'resultados',title:'Resultados da turma'},{id:'acompanhamento',title:'Acompanhamento da turma'},{id:'planejamento',title:'Planejamento da turma'}];
 let widgets=list?[
  widget('lista','Turmas','lista','cadastros',{w:12,h:11,minH:8,minW:6}),widget('turnos','Turmas por turno','turnos','analises'),widget('comparativo','Resultados entre turmas','comparativo','analises'),widget('cobertura','Cobertura por disciplina','cobertura','analises'),widget('participacao','Participação nas aplicações','participacao','acompanhamento'),widget('agenda','Agenda de avaliações','agenda','acompanhamento'),widget('devolutivas','Devolutivas','devolutivas','acompanhamento')
 ]:[
  widget('destaques','Destaques da turma','destaques','resultados',{h:11,minH:11}),widget('atencao','Alunos que precisam de atenção','atencao','resultados',{h:11,minH:11}),widget('avaliacoes','Resultados por avaliação','avaliacoes','resultados'),widget('periodos','Resultados por período escolar','periodos','resultados'),widget('distribuicao','Distribuição dos acertos','distribuicao','resultados'),widget('agenda','Agenda da turma','agenda','planejamento'),widget('metas','Metas de acompanhamento','metas','planejamento'),widget('devolutivas','Devolutivas','devolutivas','acompanhamento'),widget('habilidades','Habilidades em acompanhamento','habilidades','acompanhamento'),widget('reavaliacoes','Resultados das reavaliações','reavaliacoes','acompanhamento')
 ];
 if(!pedagogico){widgets=list?widgets.filter(w=>['lista','turnos'].includes(w.id)):[widget('matriculas','Matrículas da turma','matriculas','resultados')];sections=sections.map((s,i)=>({...s,title:(list?['Turmas','Organização cadastral','Acompanhamento']:['Matrículas','Organização','Acompanhamento'])[i]}));}
 if(!['coordenador','diretor'].includes(c.perfil))widgets=widgets.filter(w=>w.id!=='metas');return {sections,widgets};
}
export const scopeKey=c=>'g360:previa-editor:v2:'+JSON.stringify([c.usuario,c.perfil,c.vinculo,c.painel,c.painel==='turma'?c.turma:null]);
const contexto=c=>({...c,catalogo:catalogo(c)});
export const estrutura=(s,c)=>core.estrutura(s,contexto(c));
export const normalizar=(s,c,opts)=>core.normalizar(s,contexto(c),opts);
export const validar=(s,c)=>core.validar(s,contexto(c));
export const padrao=(c)=>core.padrao(contexto(c));
export const transferir=(s,id,parent,c)=>core.transferir(s,id,parent,contexto(c));
export const reordenar=(s,parent,ids,c)=>core.reordenar(s,parent,ids,contexto(c));
export const adicionarPainel=(s,name,id,parent,c)=>core.adicionarPainel(s,name,id,parent,contexto(c));
export const removerPainel=(s,id,c)=>core.removerPainel(s,id,contexto(c));
export const ativar=(s,id,active,c)=>core.ativar(s,id,active,contexto(c));
export function migrar(raw,c){
 if(raw?.schema===3)return validar(raw,c);if(![1,2].includes(raw?.schema))throw Error('Versão local desconhecida.');const old=clone(raw),defs=catalogo(c);
 if(!Array.isArray(old.sections)||old.schema===2&&!Array.isArray(old.unassigned)||old.sections.some(s=>!Array.isArray(s.widgets)))throw Error('Composição local anterior inválida.');
 const membership=[...old.sections.flatMap(s=>s.widgets),...(old.unassigned||[])];if(membership.length!==defs.widgets.length||new Set(membership).size!==membership.length||membership.some(id=>!defs.widgets.some(w=>w.id===id)))throw Error('Referências locais anteriores inválidas.');
 const s={schema:3,context:old.context,sections:old.sections.slice().reverse().map((n,i)=>({id:n.id,title:n.title,originalTitle:n.originalTitle||defs.sections.find(d=>d.id===n.id)?.title||n.title,kind:'section',parent:null,x:0,y:i*80,w:12,h:6,fixo:false})),widgets:{}};
 for(const [id,w]of Object.entries(old.widgets)){const parent=old.sections.find(n=>n.widgets.includes(id))?.id??null;s.widgets[id]={...w,id,kind:'widget',parent};}return normalizar(s,c,{compact:true});
}
export function ler(storage,c){const raw=storage.getItem(scopeKey(c));if(!raw)return {state:padrao(c),revision:0,origin:'perfil'};const record=JSON.parse(raw);if(!Number.isInteger(record.revision)||record.revision<1)throw Error('Preferência local inválida.');return {state:migrar(record.state,c),revision:record.revision,origin:'usuario'};}
export function salvar(storage,c,state,expected){const current=ler(storage,c);if(current.revision!==expected){const error=Error('Esta disposição mudou em outra aba. Seu rascunho foi preservado.');error.code=409;throw error;}const clean=validar(state,c);storage.setItem(scopeKey(c),JSON.stringify({revision:expected+1,state:clean}));return expected+1;}
