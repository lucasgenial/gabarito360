window.iniciarHistoricoResultado=function(base){
  const root=document.getElementById('historico-tentativas'),qtd=document.getElementById('resultado-tentativas-total'),carga=G360Carga(root),caminho=location.pathname;
  function carregar(alvo,navegar=false,voltar=null){
    return carga.executar(()=>G360BuscarJSON(base+alvo.search),d=>{
      if(typeof d.html!=='string'||!Number.isFinite(d.total))throw new Error('Histórico indisponível');
      qtd.textContent=d.total+' '+(d.total===1?'leitura registrada':'leituras registradas');
      root.hidden=d.total<=1;
      if(d.total<=1)return;
      root.querySelector('[data-carga-conteudo]').innerHTML=d.html;
      iniciarHistoricoDocumento();if(navegar)history.pushState(null,'',alvo.href);
      if(navegar||alvo.hash==='#tentativa-detalhe')requestAnimationFrame(()=>{
        const links=[...root.querySelectorAll('a[href]')];
        const retorno=voltar?links.find(a=>new URL(a.href).searchParams.get('tentativa')===voltar):null;
        const foco=retorno||document.getElementById(alvo.searchParams.has('tentativa')?'tentativa-detalhe-titulo':'historico-titulo');
        foco?.focus({preventScroll:true});(retorno||document.getElementById(alvo.searchParams.has('tentativa')?'tentativa-detalhe':'historico-titulo'))?.scrollIntoView({block:'start'});
      });
    });
  }
  root.addEventListener('click',e=>{
    const a=e.target.closest('a[href]');if(!a||e.button!==0||e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)return;
    const alvo=new URL(a.href);if(alvo.origin!==location.origin||alvo.pathname!==caminho)return;
    e.preventDefault();if(root.getAttribute('aria-busy')==='true')return;carregar(alvo,true,a.dataset.historicoVoltar);
  });
  addEventListener('popstate',()=>{if(location.pathname===caminho)carregar(new URL(location.href));});
  carregar(new URL(location.href));
};

window.iniciarHistoricoDocumento=function(){
  const modal=document.getElementById('historico-documento-modal');if(!modal||modal.dataset.iniciado)return;modal.dataset.iniciado='1';
  const root=document.getElementById('historico-documento-leitor'),fechar=document.getElementById('historico-documento-fechar');
  const leitor=new LeitorCartao(root,{ajuste:'largura',controles:false});let origem,overflow;
  document.querySelectorAll('[data-historico-imagem]').forEach(b=>b.addEventListener('click',()=>{
    origem=b;overflow=document.documentElement.style.overflow;document.documentElement.style.overflow='hidden';
    document.getElementById('historico-documento-contexto').textContent=b.dataset.historicoContexto;
    modal.showModal();leitor.carregar(b.dataset.historicoImagem);fechar.focus({preventScroll:true});
  }));
  fechar.addEventListener('click',()=>modal.close());
  modal.addEventListener('close',()=>{leitor.limpar();document.documentElement.style.overflow=overflow;origem?.focus({preventScroll:true});});
  modal.addEventListener('keydown',e=>{
    if(e.key!=='Tab')return;
    const xs=[...modal.querySelectorAll('button:not([disabled]),[tabindex="0"]')].filter(x=>x.getClientRects().length),primeiro=xs[0],ultimo=xs.at(-1);
    if(e.shiftKey&&document.activeElement===primeiro){e.preventDefault();ultimo.focus();}
    else if(!e.shiftKey&&document.activeElement===ultimo){e.preventDefault();primeiro.focus();}
  });
};
