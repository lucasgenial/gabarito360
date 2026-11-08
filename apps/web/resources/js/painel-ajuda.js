import {posicaoAjuda} from './painel-indicadores.js';

// Popovers textuais nativos: sem HTML da API, fetch ou alteração de preferências.
const botoes=[...document.querySelectorAll('[data-painel-ajuda]')];
const pares=botoes.map(botao=>({botao,popover:document.getElementById(botao.getAttribute('popovertarget'))})).filter(p=>p.popover);
function fechar(){for(const {popover} of pares)if(popover.matches(':popover-open'))popover.hidePopover();}
function posicionar({botao,popover}){
 if(!popover.matches(':popover-open')||!botao.isConnected)return;
 let r=popover.getBoundingClientRect();
 const escalaX=r.width/popover.offsetWidth||1,escalaY=r.height/popover.offsetHeight||1;
 popover.style.maxWidth=Math.min(320,innerWidth-32)/escalaX+'px';
 popover.style.maxHeight=Math.min(420,innerHeight-32)/escalaY+'px';
 r=popover.getBoundingClientRect();
 const p=posicaoAjuda(botao.getBoundingClientRect(),r,{width:innerWidth,height:innerHeight});
 const left=parseFloat(getComputedStyle(popover).left)||0,top=parseFloat(getComputedStyle(popover).top)||0;
 popover.style.left=left+(p.left-r.left)/escalaX+'px';
 popover.style.top=top+(p.top-r.top)/escalaY+'px';
}
for(const par of pares){
 const {botao,popover}=par;
 popover.addEventListener('toggle',()=>{
  const aberto=popover.matches(':popover-open');botao.setAttribute('aria-expanded',String(aberto));
  if(aberto)posicionar(par);
 });
}
document.addEventListener('focusin',e=>{for(const {botao,popover} of pares)if(popover.matches(':popover-open')&&e.target!==botao&&!popover.contains(e.target))popover.hidePopover();});
document.addEventListener('scroll',e=>{if(!pares.some(p=>p.popover.contains(e.target)))fechar();},{capture:true,passive:true});
for(const evento of ['g360:painel-redimensionado','g360:acesso-alterado','g360:painel-orientacao'])document.addEventListener(evento,fechar);
window.addEventListener('resize',fechar);
window.addEventListener('pagehide',fechar);
