export const perfis={coordenador:'Coordenação',professor:'Professor',diretor:'Direção',secretaria:'Secretaria escolar'};
export const clone=value=>structuredClone(value);
export const MAX_PAINEIS=15,MAX_NIVEIS=2,HEADER_ROWS=3,MAX_ROWS=400;
export const linhasCabecalho=n=>n.headerRows??HEADER_ROWS;
const pessoal=id=>typeof id==='string'&&/^pessoal-[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/.test(id);
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
export const nodes=s=>[...s.sections,...Object.values(s.widgets)];
export const obter=(s,id)=>s.sections.find(n=>n.id===id)||s.widgets[id];
export const filhos=(s,parent)=>nodes(s).filter(n=>n.parent===parent).sort((a,b)=>a.y-b.y||a.x-b.x||a.id.localeCompare(b.id));
export function titulo(value,max){if(typeof value!=='string'||!value.trim()||[...value.trim()].length>max)throw Error('Informe um título de 1 a '+max+' caracteres.');return value.trim();}
export function profundidade(s,id){let n=obter(s,id),count=0;const seen=new Set();while(n?.parent!==null){if(!n||seen.has(n.id))throw Error('Um painel não pode ficar dentro de si ou de seus descendentes.');seen.add(n.id);n=s.sections.find(p=>p.id===n.parent);if(!n)throw Error('Painel de destino não encontrado.');count++;}return count;}
export function estrutura(s,c){
 const defs=catalogo(c);if(!s||s.schema!==3||scopeKey(s.context)!==scopeKey(c)||!Array.isArray(s.sections)||s.sections.length<1||s.sections.length>MAX_PAINEIS||!s.widgets||Object.keys(s.widgets).length!==defs.widgets.length)throw Error('Disposição incompatível com este contexto.');
 const ids=new Set();for(const n of nodes(s)){
  if(!n||ids.has(n.id))throw Error('Item repetido.');ids.add(n.id);const section=n.kind==='section';
  if(section){if(!defs.sections.some(d=>d.id===n.id)&&!pessoal(n.id))throw Error('Painel inválido.');titulo(n.originalTitle,60);if(n.headerRows!==undefined&&(!Number.isInteger(n.headerRows)||n.headerRows<3||n.headerRows>8))throw Error('Cabeçalho inválido.');}
  else if(n.kind!=='widget'||!defs.widgets.some(w=>w.id===n.id)||s.widgets[n.id]!==n)throw Error('Widget não autorizado.');
  titulo(n.title,section?60:80);if(n.parent!==null&&!s.sections.some(p=>p.id===n.parent))throw Error('Painel de destino não encontrado.');if(n.parent===n.id)throw Error('Um painel não pode conter a si próprio.');
  if(typeof n.fixo!=='boolean'||!['x','y','w','h'].every(k=>Number.isInteger(n[k])))throw Error('Geometria inválida.');const d=section?{minW:4,minH:4}:defs.widgets.find(w=>w.id===n.id);
  if(n.x<0||n.y<0||n.w<d.minW||n.w>12||n.x+n.w>12||n.h<d.minH||n.h>MAX_ROWS||n.y+n.h>MAX_ROWS)throw Error('Item fora dos limites da grade.');
  if(!section&&(typeof n.active!=='boolean'||!['vertical','horizontal'].includes(n.orientacao)))throw Error('Configuração de widget inválida.');
 }
 if(defs.widgets.some(w=>!s.widgets[w.id]))throw Error('Faltam widgets do catálogo.');for(const n of nodes(s))if(profundidade(s,n.id)+(n.kind==='section'?1:0)>MAX_NIVEIS)throw Error('Esta prévia permite dois níveis de painéis, preservando a leitura.');return s;
}
export function visivel(s,id){const n=obter(s,id);return n.kind==='widget'?n.active:filhos(s,id).some(child=>visivel(s,child.id));}
const sobrepoe=(a,b)=>a.x<b.x+b.w&&a.x+a.w>b.x&&a.y<b.y+b.h&&a.y+a.h>b.y;
function compactarGrupo(s,parent,{compact=false}={}){
 const items=filhos(s,parent).filter(n=>n.kind==='section'||n.active),placed=items.filter(n=>n.fixo);for(let i=0;i<placed.length;i++)if(placed.slice(0,i).some(p=>sobrepoe(p,placed[i])))throw Error('Itens fixados se sobrepõem.');
 for(const n of items.filter(n=>!n.fixo)){
  if(!compact&&placed.every(p=>!sobrepoe(p,n))){placed.push(n);continue;}let found=false;
  for(let y=0;y+n.h<=MAX_ROWS&&!found;y++)for(const x of [n.x,...Array.from({length:13-n.w},(_,i)=>i)])if(placed.every(p=>!sobrepoe(p,{...n,x,y}))){Object.assign(n,{x,y});placed.push(n);found=true;break;}
  if(!found)throw Error('Não há espaço suficiente nesta grade.');
 }
}
export function normalizar(state,c,{compact=false}={}){
 const s=clone(state);estrutura(s,c);const deepest=[...s.sections].sort((a,b)=>profundidade(s,b.id)-profundidade(s,a.id));
 for(const section of deepest){compactarGrupo(s,section.id,{compact});const height=linhasCabecalho(section)+Math.max(3,...filhos(s,section.id).filter(n=>n.kind==='section'||n.active).map(n=>n.y+n.h));if(section.fixo&&height>section.h)throw Error('Libere o tamanho do painel fixado antes de ampliar seu conteúdo.');section.h=Math.max(section.h,height);if(section.h>MAX_ROWS)throw Error('Painel excedeu a altura disponível.');}
 compactarGrupo(s,null,{compact});return validar(s,c);
}
export function validar(s,c){estrutura(s,c);for(const parent of [null,...s.sections.map(n=>n.id)]){const items=filhos(s,parent).filter(n=>n.kind==='section'||n.active);for(let i=0;i<items.length;i++)if(items.slice(0,i).some(n=>sobrepoe(n,items[i])))throw Error('Itens sobrepostos na mesma grade.');if(parent!==null&&items.some(n=>n.y+n.h>obter(s,parent).h-linhasCabecalho(obter(s,parent))))throw Error('O painel precisa crescer para acomodar seu conteúdo.');}return clone(s);}
export function padrao(c){
 const d=catalogo(c),s={schema:3,context:clone(c),sections:d.sections.map((n,i)=>({...n,originalTitle:n.title,kind:'section',parent:null,x:0,y:i*80,w:12,h:6,fixo:false})),widgets:{}};
 for(const section of s.sections){let x=0,y=0,row=0;for(const dfn of d.widgets.filter(w=>w.parent===section.id)){if(x+dfn.w>12){x=0;y+=row;row=0;}s.widgets[dfn.id]={id:dfn.id,kind:'widget',title:dfn.title,parent:section.id,active:true,fixo:false,x,y,w:dfn.w,h:dfn.h,orientacao:'vertical'};x+=dfn.w;row=Math.max(row,dfn.h);}}return normalizar(s,c,{compact:true});
}
export function podeMover(s,id,parent){try{const next=clone(s),n=obter(next,id);if(!n||parent!==null&&!next.sections.some(p=>p.id===parent))return false;n.parent=parent;for(const item of nodes(next))if(profundidade(next,item.id)+(item.kind==='section'?1:0)>MAX_NIVEIS)return false;return true;}catch{return false;}}
export function transferir(state,id,parent,c){if(!podeMover(state,id,parent))throw Error('Destino inválido: não mova um painel para si, seus descendentes ou além de dois níveis.');const next=clone(state),n=obter(next,id),pin=n.fixo;n.parent=parent;n.x=0;n.y=0;n.fixo=false;compactarGrupo(next,parent,{compact:true});n.fixo=pin;return normalizar(next,c);}
export function reordenar(state,parent,ids,c){
 const next=clone(state),items=filhos(next,parent);if(ids.length!==items.length||new Set(ids).size!==ids.length||ids.some(id=>!items.some(n=>n.id===id)))throw Error('Ordem de itens inválida.');
 const placed=items.filter(n=>n.fixo);for(const id of ids){const n=obter(next,id);if(n.fixo||n.kind==='widget'&&!n.active)continue;let found=false;for(let y=0;y+n.h<=MAX_ROWS&&!found;y++)for(let x=0;x+n.w<=12;x++)if(placed.every(p=>!sobrepoe(p,{...n,x,y}))){Object.assign(n,{x,y});placed.push(n);found=true;break;}if(!found)throw Error('Não há espaço para essa ordem.');}
 return normalizar(next,c);
}
export function adicionarPainel(state,name,id,parent,c){if(!pessoal(id)||obter(state,id))throw Error('Identificador de painel inválido.');if(state.sections.length>=MAX_PAINEIS)throw Error('Esta prévia permite até '+MAX_PAINEIS+' painéis pessoais.');const next=clone(state),title=titulo(name,60);next.sections.push({id,title,originalTitle:title,kind:'section',parent:null,x:0,y:0,w:12,h:6,fixo:false});return transferir(next,id,parent,c);}
export function removerPainel(state,id,c){
 if(state.sections.length<=1)throw Error('Mantenha ao menos um painel configurado. Painéis vazios não aparecem na visualização.');const next=clone(state),n=next.sections.find(v=>v.id===id);if(!n)throw Error('Painel não encontrado.');let floor=Math.max(0,...filhos(next,n.parent).filter(v=>v.id!==id).map(v=>v.y+v.h));for(const child of filhos(next,id)){child.parent=n.parent;child.x=0;child.y=floor;floor+=child.h;}next.sections=next.sections.filter(v=>v.id!==id);return normalizar(next,c);
}
export function ativar(state,id,active,c){const next=clone(state),n=next.widgets[id];if(!n)throw Error('Widget não autorizado.');n.active=active;if(active){n.x=0;n.y=Math.max(0,...filhos(next,n.parent).filter(v=>v.id!==id&&(v.kind==='section'||v.active)).map(v=>v.y+v.h));}return normalizar(next,c);}
export function caminho(s,id){const n=obter(s,id);return n.parent===null?n.title:caminho(s,n.parent)+' / '+n.title;}
export function migrar(raw,c){
 if(raw?.schema===3)return validar(raw,c);if(![1,2].includes(raw?.schema))throw Error('Versão local desconhecida.');const old=clone(raw),defs=catalogo(c);
 if(!Array.isArray(old.sections)||old.schema===2&&!Array.isArray(old.unassigned)||old.sections.some(s=>!Array.isArray(s.widgets)))throw Error('Composição local anterior inválida.');
 const membership=[...old.sections.flatMap(s=>s.widgets),...(old.unassigned||[])];if(membership.length!==defs.widgets.length||new Set(membership).size!==membership.length||membership.some(id=>!defs.widgets.some(w=>w.id===id)))throw Error('Referências locais anteriores inválidas.');
 const s={schema:3,context:old.context,sections:old.sections.slice().reverse().map((n,i)=>({id:n.id,title:n.title,originalTitle:n.originalTitle||defs.sections.find(d=>d.id===n.id)?.title||n.title,kind:'section',parent:null,x:0,y:i*80,w:12,h:6,fixo:false})),widgets:{}};
 for(const [id,w]of Object.entries(old.widgets)){const parent=old.sections.find(n=>n.widgets.includes(id))?.id??null;s.widgets[id]={...w,id,kind:'widget',parent};}return normalizar(s,c,{compact:true});
}
export function ler(storage,c){const raw=storage.getItem(scopeKey(c));if(!raw)return {state:padrao(c),revision:0,origin:'perfil'};const record=JSON.parse(raw);if(!Number.isInteger(record.revision)||record.revision<1)throw Error('Preferência local inválida.');return {state:migrar(record.state,c),revision:record.revision,origin:'usuario'};}
export function salvar(storage,c,state,expected){const current=ler(storage,c);if(current.revision!==expected){const error=Error('Esta disposição mudou em outra aba. Seu rascunho foi preservado.');error.code=409;throw error;}const clean=validar(state,c);storage.setItem(scopeKey(c),JSON.stringify({revision:expected+1,state:clean}));return expected+1;}
