import {render} from 'solid-js/web';
import {createComponent} from 'solid-js';
import toast,{Toaster} from 'solid-toast';

// Ilha Solid pequena: Blade continua responsável pelas páginas e formulários.
const raiz=document.getElementById('g360-notificacoes');
if(raiz){
  render(()=>createComponent(Toaster,{position:'top-right',gutter:12,containerClassName:'g360-toaster',containerStyle:{top:'80px',right:'16px',left:'16px',bottom:'auto','z-index':1100}}),raiz);
  const ativos=new Map();let seq=0;
  const titulos={sucesso:'Concluído',erro:'Não foi possível concluir',info:'Informação',carregando:'Em andamento'};
  function suspender(n,motivo,sim){
    if(ativos.get(n.id)!==n)return;
    if(sim){if(!n.pausas.size&&n.inicio){n.restante-=Date.now()-n.inicio;n.inicio=0;}n.pausas.add(motivo);clearTimeout(n.timer);}
    else{n.pausas.delete(motivo);if(!n.pausas.size&&Number.isFinite(n.restante)){n.inicio=Date.now();clearTimeout(n.timer);n.timer=setTimeout(()=>fechar(n.id),Math.max(0,n.restante));}}
  }
  function fechar(id){const n=ativos.get(id);if(!n)return;clearTimeout(n.timer);if(n.elemento?.contains(document.activeElement)&&n.origem?.isConnected)n.origem.focus({preventScroll:true});n.inline?.remove();toast.dismiss(id);ativos.delete(id);}
  function mostrar(tipo,mensagem,opcoes={}){
    const id=opcoes.id||'g360-'+(++seq);const anterior=ativos.get(id);if(anterior){clearTimeout(anterior.timer);anterior.inline?.remove();ativos.delete(id);}
    while(ativos.size>=3){const antigo=[...ativos.values()].find(n=>!n.elemento?.contains(document.activeElement));fechar(antigo.id);}
    const duracao=opcoes.duracao??(tipo==='sucesso'?5000:tipo==='info'?8000:Infinity);
    const n={id,restante:duracao,pausas:new Set(),inicio:0,timer:null,origem:anterior?.origem||document.activeElement};ativos.set(id,n);
    const conteudo=document.createElement('div');conteudo.className='g360-toast-conteudo';conteudo.dataset.g360Toast=id;
    n.elemento=conteudo;
    const texto=document.createElement('div'),titulo=document.createElement('strong'),p=document.createElement('p');
    titulo.textContent=opcoes.titulo||titulos[tipo];p.textContent=String(mensagem);texto.append(titulo,p);
    const fecharBotao=document.createElement('button');fecharBotao.type='button';fecharBotao.className='g360-toast-fechar';fecharBotao.setAttribute('aria-label','Fechar notificação');fecharBotao.textContent='×';fecharBotao.onclick=()=>fechar(id);conteudo.append(texto,fecharBotao);
    conteudo.addEventListener('keydown',e=>{if(e.key==='Escape'){e.preventDefault();e.stopPropagation();fechar(id);}});
    const dialogo=document.querySelector('dialog[open]');
    if(dialogo){conteudo.classList.add('g360-toast-inline');conteudo.setAttribute('role',tipo==='erro'?'alert':'status');dialogo.append(conteudo);n.inline=conteudo;}
    else{
      const fn={sucesso:toast.success,erro:toast.error,carregando:toast.loading,info:toast}[tipo]||toast;
      fn(conteudo,{id,duration:Infinity,unmountDelay:0,className:'g360-toast g360-toast-'+tipo,
        ariaProps:{role:tipo==='erro'?'alert':'status','aria-live':tipo==='erro'?'assertive':'polite'},
        iconTheme:{primary:tipo==='erro'?'var(--danger)':tipo==='sucesso'?'var(--success)':'var(--accent)',secondary:'var(--surface)'},
        style:{background:'var(--surface)',color:'var(--fg)',border:'1px solid var(--border-soft)','border-radius':'var(--radius-md)','box-shadow':'var(--shadow-md)',padding:'12px','max-width':'360px'}});
    }
    conteudo.addEventListener('mouseenter',()=>suspender(n,'hover',true));conteudo.addEventListener('mouseleave',()=>suspender(n,'hover',false));
    conteudo.addEventListener('focusin',()=>suspender(n,'foco',true));conteudo.addEventListener('focusout',e=>{if(!conteudo.contains(e.relatedTarget))suspender(n,'foco',false);});
    if(document.hidden)suspender(n,'oculta',true);suspender(n,'inicial',false);return id;
  }
  document.addEventListener('visibilitychange',()=>ativos.forEach(n=>suspender(n,'oculta',document.hidden)));
  window.G360Toast={sucesso:(m,o)=>mostrar('sucesso',m,o),erro:(m,o)=>mostrar('erro',m,o),info:(m,o)=>mostrar('info',m,o),carregando:(m,o)=>mostrar('carregando',m,o),fechar};
  document.addEventListener('g360:toast',e=>{const d=e.detail||{};mostrar(['sucesso','erro','info','carregando'].includes(d.tipo)?d.tipo:'info',d.mensagem||'',d);});
  const dados=document.getElementById('g360-notificacoes-iniciais');
  if(dados){
    for(const d of JSON.parse(dados.textContent)){
      // Mantém avisos contextuais de campo/conflito; substitui somente flashes genéricos.
      document.querySelectorAll('[data-g360-flash="'+d.tipo+'"]').forEach(el=>{el.hidden=true;el.setAttribute('aria-live','off');el.removeAttribute('role');});
      mostrar(d.tipo,d.mensagem);
    }
  }
}
