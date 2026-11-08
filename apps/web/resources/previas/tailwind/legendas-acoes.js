// Legendas complementares: nomes acessíveis continuam presentes nos botões.
export function iniciarLegendasAcoes() {
  const legenda=document.createElement('div');
  legenda.id='legenda-acao';legenda.className='legenda-acao';
  legenda.setAttribute('role','tooltip');legenda.setAttribute('popover','manual');
  document.body.append(legenda);
  let alvo,tarefa;
  function fechar(){
    clearTimeout(tarefa);
    if(legenda.matches(':popover-open'))legenda.hidePopover();
    alvo?.removeAttribute('aria-describedby');alvo=undefined;
  }
  function mostrar(botao){
    fechar();alvo=botao;legenda.textContent=botao.dataset.legenda;
    botao.setAttribute('aria-describedby',legenda.id);legenda.showPopover();
    const r=botao.getBoundingClientRect(),d=legenda.getBoundingClientRect();
    legenda.style.left=Math.max(8,Math.min(r.left,innerWidth-d.width-8))+'px';
    legenda.style.top=(r.top-d.height-8>=8?r.top-d.height-8:r.bottom+8)+'px';
  }
  function agendarFechamento(){
    clearTimeout(tarefa);
    tarefa=setTimeout(()=>{if(!legenda.matches(':hover')&&alvo!==document.activeElement)fechar();},100);
  }
  document.querySelectorAll('[data-legenda]').forEach(botao=>{
    botao.addEventListener('pointerenter',evento=>{if(evento.pointerType!=='touch')mostrar(botao);});
    botao.addEventListener('pointerleave',agendarFechamento);
    botao.addEventListener('focus',()=>mostrar(botao));
    botao.addEventListener('blur',agendarFechamento);
    botao.addEventListener('click',fechar);
  });
  legenda.addEventListener('pointerenter',()=>clearTimeout(tarefa));
  legenda.addEventListener('pointerleave',agendarFechamento);
  document.addEventListener('keydown',evento=>{if(evento.key==='Escape')fechar();});
  window.addEventListener('resize',fechar);
  window.addEventListener('scroll',fechar,{capture:true,passive:true});
}
