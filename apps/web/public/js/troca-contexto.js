(function () {
  var botao=document.getElementById('contexto-trocar'), modal=document.getElementById('troca-contexto-modal');
  if (!botao || !modal) return;
  botao.hidden=false;
  botao.addEventListener('click',function () {
    if (typeof modal.showModal !== 'function') { window.location.href=botao.dataset.alternativa; return; }
    modal.showModal();
  });
  document.getElementById('troca-contexto-fechar').addEventListener('click',function(){modal.close();});
  modal.addEventListener('close',function(){botao.focus();});
  var ocupado=false, fechar=document.getElementById('troca-contexto-fechar'), erro=document.getElementById('troca-contexto-erro');
  modal.addEventListener('cancel',function(e){if(ocupado)e.preventDefault();});
  modal.querySelectorAll('form').forEach(function(form){form.addEventListener('submit',async function(e){
    e.preventDefault(); if(ocupado)return;
    ocupado=true; erro.hidden=true; fechar.disabled=true; modal.setAttribute('aria-busy','true');
    var botoes=modal.querySelectorAll('button[type="submit"]'); botoes.forEach(function(b){b.disabled=true;});
    var controle=new AbortController(), limite=setTimeout(function(){controle.abort();},20000);
    var origem=e.submitter, textoOrigem=origem ? origem.textContent : '';
    if(origem)origem.textContent='Entrando…';
    try {
      var r=await fetch(form.action,{method:'POST',headers:{Accept:'application/json'},body:new FormData(form),credentials:'same-origin',redirect:'error',signal:controle.signal});
      if(r.status===401 || r.status===419){location.assign('/login');return;}
      var dados=await r.json();
      if(!r.ok || !dados.url)throw new Error(dados.message || 'Não foi possível trocar o acesso. Tente novamente.');
      var destino=new URL(dados.url,location.href);
      if(destino.origin!==location.origin || destino.username || destino.password || destino.pathname!=='/app/painel' || destino.search || destino.hash || typeof dados.revisao!=='string' || !/^[A-Za-z0-9]{40}$/.test(dados.revisao))throw new Error('Não foi possível confirmar o destino. Atualize a página.');
      window.g360ContextoSelecionado?.(dados.revisao);
      window.G360EntradaVinculo?.confirmar(dados.revisao);
      location.assign(destino.href);
    } catch(e) {
      erro.textContent=(e.name==='AbortError' || e instanceof TypeError || e instanceof SyntaxError)
        ? 'Não foi possível confirmar a troca. Atualize a página antes de tentar novamente.'
        : (e.message || 'Não foi possível confirmar a troca. Atualize a página antes de tentar novamente.');
      erro.hidden=false; erro.focus();
    } finally {
      clearTimeout(limite); if(origem)origem.textContent=textoOrigem;
      ocupado=false; fechar.disabled=false; botoes.forEach(function(b){b.disabled=false;}); modal.removeAttribute('aria-busy');
    }
  });});
})();
