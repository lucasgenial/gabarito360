// Estrutura autenticada: navegação visual separada da conta e da seleção de vínculo.
(() => {
  const raiz=document.documentElement, lateral=document.getElementById('menu-recolher');
  const folha=document.getElementById('nav-folha'),mais=document.querySelector('.nav-inferior-mais');
  const desktop=matchMedia('(min-width:768px)');
  function atualizarMenu(){
    const aberto=!raiz.classList.contains('menu-recolhido');
    if(lateral){lateral.setAttribute('aria-expanded',String(aberto));lateral.setAttribute('aria-label',aberto?'Recolher menu principal':'Expandir menu principal');lateral.title=lateral.getAttribute('aria-label');}
  }
  lateral?.addEventListener('click',()=>{
    const compacto=raiz.classList.toggle('menu-recolhido');
    try{localStorage.setItem('g360-menu',compacto?'recolhido':'aberto');}catch(_){}
    atualizarMenu();requestAnimationFrame(()=>window.dispatchEvent(new Event('resize')));
  });
  atualizarMenu();
  if(folha&&mais){
    mais.addEventListener('click',()=>{folha.showModal();mais.setAttribute('aria-expanded','true');folha.querySelector('[data-fechar]').focus();});
    folha.querySelectorAll('[data-fechar]').forEach(b=>b.addEventListener('click',()=>folha.close()));
    folha.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>folha.close()));
    folha.addEventListener('close',()=>{mais.setAttribute('aria-expanded','false');if(!desktop.matches)mais.focus();});
    folha.addEventListener('click',e=>{if(e.target!==folha)return;const r=folha.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)folha.close();});
    desktop.addEventListener('change',()=>{if(folha.open){folha.close();if(desktop.matches)lateral?.focus();}});
  }
  window.addEventListener('beforeprint',()=>document.querySelectorAll('details').forEach(d=>{d.open=true;}));
})();
