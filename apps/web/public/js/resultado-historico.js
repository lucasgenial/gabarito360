window.iniciarHistoricoResultado=function(base){
  const root=document.getElementById('historico-tentativas'),qtd=document.getElementById('resultado-tentativas-total'),carga=G360Carga(root);
  function carregar(alvo,navegar=false,voltar=null){
    return carga.executar(()=>G360BuscarJSON(alvo),d=>{
      if(typeof d.html!=='string'||!Number.isFinite(d.total))throw new Error('Histórico indisponível');
      qtd.textContent=d.total+' '+(d.total===1?'leitura registrada':'leituras registradas');root.hidden=d.total<=1;
      if(d.total<=1)return;
      root.querySelector('[data-carga-conteudo]').innerHTML=d.html;iniciarHistoricoDocumento();
      if(navegar)requestAnimationFrame(()=>{const acionador=voltar?[...root.querySelectorAll('[data-historico-leitura]')].find(b=>b.dataset.historicoLeitura===voltar):null;const foco=acionador||document.getElementById('tentativa-detalhe-titulo')||document.getElementById('historico-titulo');foco?.focus({preventScroll:true});(document.getElementById('tentativa-detalhe')||foco)?.scrollIntoView({block:'start'});});
    });
  }
  root.addEventListener('click',e=>{
    const b=e.target.closest('[data-historico-recurso]');if(!b||root.getAttribute('aria-busy')==='true')return;
    e.preventDefault();carregar(JSON.parse(b.dataset.historicoRecurso),true,b.dataset.historicoVoltar||null);
  });
  carregar(base);
};

window.iniciarHistoricoDocumento=function(){
  const modal=document.getElementById('historico-documento-modal');if(!modal||modal.dataset.iniciado)return;modal.dataset.iniciado='1';
  const root=document.getElementById('historico-documento-leitor'),fechar=document.getElementById('historico-documento-fechar');
  const leitor=new LeitorCartao(root,{ajuste:'largura',controles:false});let origem,overflow;
  document.querySelectorAll('[data-historico-imagem]').forEach(b=>b.addEventListener('click',()=>{
    origem=b;overflow=document.documentElement.style.overflow;document.documentElement.style.overflow='hidden';
    document.getElementById('historico-documento-contexto').textContent=b.dataset.historicoContexto;
    modal.showModal();leitor.carregar(JSON.parse(b.dataset.historicoImagem));fechar.focus({preventScroll:true});
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
