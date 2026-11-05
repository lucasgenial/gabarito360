/* Uma promessa por região: esqueleto inicial, atualização sem apagar os dados anteriores. */
window.G360Carga=function(root){
  const conteudo=root.querySelector('[data-carga-conteudo]'),skeleton=root.querySelector('[data-carga-esqueleto]'),feedback=root.querySelector('[data-carga-feedback]');
  let pronta=false,ocupada=false,ultima=null;
  async function executar(obter,renderizar,opcoes={}){
    if(ocupada)return;ocupada=true;ultima=()=>executar(obter,renderizar);root.setAttribute('aria-busy','true');
    if(feedback.contains(document.activeElement)){if(!root.hasAttribute('tabindex'))root.tabIndex=-1;root.focus({preventScroll:true});}
    feedback.setAttribute('aria-live',opcoes.silenciosa?'off':'polite');
    skeleton.hidden=pronta;conteudo.hidden=!pronta;feedback.replaceChildren();
    if(pronta)feedback.textContent='Atualizando…';
    try{
      const d=await obter();renderizar(d);pronta=true;conteudo.hidden=false;feedback.textContent='';root.dispatchEvent(new CustomEvent('g360:carga',{detail:{estado:'pronta'}}));
    }catch(e){
      feedback.textContent=pronta?'Não foi possível atualizar. Os dados anteriores foram mantidos. ':'Não foi possível carregar este bloco. ';
      const b=document.createElement('button');b.type='button';b.className='btn btn-secondary btn-sm';b.textContent='Tentar novamente';b.onclick=ultima;feedback.append(b);
      root.dispatchEvent(new CustomEvent('g360:carga',{detail:{estado:'erro',anterior:pronta}}));
    }finally{skeleton.hidden=true;root.setAttribute('aria-busy','false');ocupada=false;}
  }
  return {executar};
};
window.G360BuscarJSON=async function(url){
  const c=new AbortController(),t=setTimeout(()=>c.abort(),25000);
  try{const r=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},signal:c.signal});if(!r.ok)throw new Error('Bloco indisponível');return await r.json();}finally{clearTimeout(t);}
};
