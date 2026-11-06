(function(){
  'use strict';
  const erro=document.querySelector('[data-formulario-validado] [aria-invalid="true"]');
  if(!erro)return;
  const focar=()=>requestAnimationFrame(()=>erro.focus());
  if(document.readyState==='complete')focar();else window.addEventListener('load',focar,{once:true});
})();
