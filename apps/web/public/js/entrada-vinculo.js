/* A entrada de tela cheia pertence apenas à aba que confirmou a seleção. */
(function(root){
  'use strict';
  root.G360EntradaVinculo={confirmar(revisao){
    try { if(typeof revisao==='string' && /^[A-Za-z0-9]{40}$/.test(revisao))sessionStorage.setItem('g360-entrada-vinculo',revisao); } catch(_){}
  }};
  const pagina=document.querySelector('[data-selecao-acesso]');
  if(!pagina)return;
  let ocupada=false;
  pagina.querySelectorAll('form.vinculo-opcao').forEach(form=>form.addEventListener('submit',async e=>{
    e.preventDefault();if(ocupada)return;ocupada=true;
    const botoes=pagina.querySelectorAll('button[type="submit"]');botoes.forEach(b=>b.disabled=true);
    const erro=pagina.querySelector('[data-entrada-erro]');erro.hidden=true;
    const controle=new AbortController(),limite=setTimeout(()=>controle.abort(),20000);
    try {
      const r=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'},credentials:'same-origin',redirect:'error',signal:controle.signal});
      if(r.status===401 || r.status===419){location.assign('/login');return;}
      const dados=await r.json();
      if(!r.ok)throw new Error('Seleção não confirmada');
      const destino=new URL(dados.url,location.origin);
      if(destino.origin!==location.origin || destino.username || destino.password || destino.pathname!=='/app/painel' || destino.search || destino.hash || typeof dados.revisao!=='string' || !/^[A-Za-z0-9]{40}$/.test(dados.revisao))throw new Error('Destino não confirmado');
      root.G360EntradaVinculo.confirmar(dados.revisao);
      location.assign(destino.href);
    } catch(_) {
      erro.textContent='Não foi possível confirmar a entrada. Atualize esta página para conferir seu acesso antes de tentar novamente.';
      erro.hidden=false;erro.focus();
    } finally {clearTimeout(limite);ocupada=false;botoes.forEach(b=>b.disabled=false);}
  }));
})(window);
