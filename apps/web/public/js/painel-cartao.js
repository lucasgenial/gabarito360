/* Somente documento. Não altera respostas, nota ou foto; preserva foco e rolagem ao fechar. */
window.criarPainelCartao=function(url,opcoes={}){
  var desktop=matchMedia('(min-width:1100px)'),modal=document.getElementById('doc-modal'),painel=document.getElementById('doc-painel');
  var root=document.getElementById('doc-leitor'),botao=document.getElementById('doc-abrir'),fechar=document.getElementById('doc-fechar');
  var leitor=new LeitorCartao(root,opcoes),overflow='',origem=null,pendente=false,aberto=false,estacao=painel.closest('.documento-estacao');
  botao?.setAttribute('aria-expanded','false');
  function dimensionar(){
    if(!desktop.matches||!painel.offsetWidth)return;
    if(opcoes.cardsIguais){painel.style.height=estacao.querySelector('.res-questoes').offsetHeight+'px';return;}
    var r=painel.getBoundingClientRect(),escala=r.width/painel.offsetWidth,f=document.querySelector('footer[aria-label="Versão do software"]');
    var limite=f?Math.min(innerHeight,f.getBoundingClientRect().top):innerHeight;
    painel.style.height=Math.max(260,(limite-r.top-16)/escala)+'px';
  }
  function ajustar(){if(pendente)return;pendente=true;requestAnimationFrame(function(){pendente=false;dimensionar();});}
  function sincronizar(){
    if(desktop.matches&&modal.open){aberto=true;modal.close();}
    painel.hidden=!desktop.matches||!aberto;estacao.classList.toggle('documento-aberto',desktop.matches&&aberto);
    botao?.setAttribute('aria-expanded',String(desktop.matches?aberto:modal.open));botao?.setAttribute('aria-controls',desktop.matches?'doc-painel':'doc-modal');
    if(desktop.matches)botao?.removeAttribute('aria-haspopup');else botao?.setAttribute('aria-haspopup','dialog');
    if(botao&&opcoes.rotulo){botao.setAttribute('aria-label',opcoes.rotulo);botao.setAttribute('title',desktop.matches&&aberto?'Ocultar cartão resposta':'Cartão resposta');}
    else if(botao)botao.textContent=desktop.matches&&aberto?'Ocultar cartão-resposta':'Visualizar cartão-resposta';
    document.getElementById(desktop.matches?'doc-desktop':'doc-mobile').append(root);dimensionar();
    if((desktop.matches&&aberto)||modal.open)leitor.carregar(url);else leitor.limpar();
  }
  botao?.addEventListener('click',function(){
    if(desktop.matches){aberto=!aberto;sincronizar();return;}
    if(modal.open)return;origem=document.activeElement;overflow=document.documentElement.style.overflow;document.documentElement.style.overflow='hidden';
    modal.showModal();sincronizar();fechar.focus({preventScroll:true});
  });
  fechar.addEventListener('click',function(){modal.close();});
  modal.addEventListener('close',function(){document.documentElement.style.overflow=overflow;if(!desktop.matches){leitor.limpar();botao?.setAttribute('aria-expanded','false');}if(origem?.isConnected)origem.focus({preventScroll:true});});
  modal.addEventListener('keydown',function(e){
    if(e.key!=='Tab')return;
    var xs=Array.from(modal.querySelectorAll('button:not([disabled]),[tabindex="0"]')).filter(function(el){return el.getClientRects().length;});
    var p=xs[0],u=xs[xs.length-1];if(e.shiftKey&&document.activeElement===p){e.preventDefault();u.focus();}else if(!e.shiftKey&&document.activeElement===u){e.preventDefault();p.focus();}
  });
  desktop.addEventListener('change',sincronizar);window.addEventListener('resize',ajustar);window.addEventListener('scroll',ajustar,{passive:true});sincronizar();
  if(opcoes.cardsIguais)new ResizeObserver(ajustar).observe(estacao.querySelector('.res-questoes'));
};
