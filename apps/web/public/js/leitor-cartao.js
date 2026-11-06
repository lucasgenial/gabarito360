/* Leitor controlado: raster→PDF só em memória. Nenhum arquivo salvo é modificado. */
(function () {
  'use strict';
  let dependencias;
  function bibliotecas() {
    if (!dependencias) dependencias = Promise.all([
      import('/js/libs/pdfjs-6.5.438/pdf.min.js'),
      window.PDFLib ? Promise.resolve(window.PDFLib) : new Promise((resolve,reject) => {
        const s=document.createElement('script');s.src='/js/libs/pdf-lib-1.17.1/pdf-lib.min.js';
        s.onload=()=>resolve(window.PDFLib);s.onerror=()=>{s.remove();reject(new Error('Biblioteca indisponível'));};document.head.append(s);
      })
    ]).then(([pdfjs,lib])=>{pdfjs.GlobalWorkerOptions.workerSrc='/js/libs/pdfjs-6.5.438/pdf.worker.min.js';return {pdfjs,lib};})
      .catch(e=>{dependencias=null;throw e;});
    return dependencias;
  }
  async function converter(blob,lib) {
    if (blob.type.split(';')[0]==='application/pdf') return new Uint8Array(await blob.arrayBuffer());
    if (!blob.type.startsWith('image/')) throw new Error('Documento inválido');
    const url=URL.createObjectURL(blob),imagem=new Image();
    try {
      imagem.src=url;await imagem.decode();
      const w=imagem.naturalWidth,h=imagem.naturalHeight;
      if (!w||!h||w*h>48000000) throw new Error('Imagem indisponível');
      const fator=Math.min(1,4096/Math.max(w,h));
      const c=document.createElement('canvas');c.width=Math.round(w*fator);c.height=Math.round(h*fator);
      const g=c.getContext('2d');g.fillStyle='#fff';g.fillRect(0,0,c.width,c.height);g.drawImage(imagem,0,0,c.width,c.height);
      const jpg=await new Promise((resolve,reject)=>c.toBlob(b=>b?resolve(b):reject(new Error('Conversão indisponível')),'image/jpeg',.95));
      c.width=c.height=0;
      const doc=await lib.PDFDocument.create(),foto=await doc.embedJpg(await jpg.arrayBuffer());
      const largura=595.28,altura=largura*h/w,pagina=doc.addPage([largura,altura]);
      pagina.drawImage(foto,{x:0,y:0,width:largura,height:altura});return doc.save();
    } finally {imagem.removeAttribute('src');URL.revokeObjectURL(url);}
  }
  window.LeitorCartao=class {
    constructor(root,opcoes={}) {
      this.root=root;this.sequencia=0;this.url=null;this.chaveDocumento=null;this.pdf=null;this.pagina=1;this.zoom=1;
      this.controles=opcoes.controles!==false;this.largura=opcoes.ajuste==='largura';
      root.classList.add('rev-leitor');
      root.innerHTML='<div class="rev-leitor-ferramentas" aria-label="Controles do PDF">'
        +(this.controles?'<button type="button" class="btn btn-ghost" data-menos aria-label="Diminuir PDF" disabled>−</button>'
        +'<output data-zoom aria-label="Ampliação do PDF">Ajustado</output>'
        +'<button type="button" class="btn btn-ghost" data-mais aria-label="Ampliar PDF" disabled>+</button>'
        +'<button type="button" class="btn btn-ghost" data-ajustar disabled>Ajustar à página</button>':'')
        +'<span data-paginas hidden><button type="button" class="btn btn-ghost" data-anterior aria-label="Página anterior">‹</button><output data-pagina></output><button type="button" class="btn btn-ghost" data-proxima aria-label="Próxima página">›</button></span></div>'
        +'<div class="rev-leitor-area" tabindex="0" role="region" aria-label="Leitor PDF do cartão-resposta">'
        +'<p class="rev-modal-estado" data-estado role="status">Selecione um cartão para visualizar.</p>'
        +'<div class="rev-modal-estado" data-erro role="alert" hidden>Não foi possível carregar o cartão em PDF. <button type="button" class="btn btn-secondary" data-repetir>Tentar novamente</button></div>'
        +'<div class="rev-pdf-pagina rev-imagem-restrita" data-folha hidden><canvas role="img" aria-label="Cartão-resposta completo" draggable="false"></canvas></div></div>';
      this.area=root.querySelector('.rev-leitor-area');this.canvas=root.querySelector('canvas');this.folha=root.querySelector('[data-folha]');
      this.estado=root.querySelector('[data-estado]');this.erro=root.querySelector('[data-erro]');
      this.ferramentas=root.querySelector('.rev-leitor-ferramentas');this.ferramentas.hidden=!this.controles;
      if(this.controles){root.querySelector('[data-mais]').onclick=()=>this.ampliar(1.25);
      root.querySelector('[data-menos]').onclick=()=>this.ampliar(.8);
      root.querySelector('[data-ajustar]').onclick=()=>{this.zoom=1;this.renderizar();};}
      root.querySelector('[data-anterior]').onclick=()=>{if(this.pagina>1){this.pagina--;this.renderizar();}};
      root.querySelector('[data-proxima]').onclick=()=>{if(this.pdf&&this.pagina<this.pdf.numPages){this.pagina++;this.renderizar();}};
      root.querySelector('[data-repetir]').onclick=()=>this.carregar(this.url,true);
      ['contextmenu','dragstart','copy','cut'].forEach(tipo=>this.folha.addEventListener(tipo,e=>e.preventDefault()));
      root.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&['p','s','c','x'].includes(e.key.toLowerCase())){e.preventDefault();e.stopPropagation();}});
      this.area.addEventListener('wheel',e=>{if(this.controles&&e.ctrlKey&&this.pdf){e.preventDefault();this.ampliar(e.deltaY<0?1.1:1/1.1);}},{passive:false});
      this.observer=new ResizeObserver(()=>{if(this.pdf&&this.area.clientWidth&&this.area.clientHeight)this.renderizar();});this.observer.observe(this.area);
    }
    botoes(ativo) {this.root.querySelectorAll('[data-mais],[data-menos],[data-ajustar]').forEach(b=>b.disabled=!ativo);}
    limpar() {
      this.sequencia++;this.abort?.abort();this.tarefa?.cancel();
      if(this.carga)this.carga.destroy().catch(()=>{});else this.pdf?.destroy().catch(()=>{});
      this.pdf=null;this.carga=null;this.url=null;this.chaveDocumento=null;this.folha.hidden=true;this.canvas.width=this.canvas.height=0;
      this.erro.hidden=true;this.estado.hidden=false;this.estado.textContent='Selecione um cartão para visualizar.';this.botoes(false);
      this.root.querySelector('[data-paginas]').hidden=true;this.area.setAttribute('aria-busy','false');
      this.ferramentas.hidden=!this.controles;
    }
    async carregar(url,forcar=false) {
      const chave=typeof url==='object'&&url!==null?JSON.stringify([url.url,url.referencia,url.metodo]):url;
      if(chave===this.chaveDocumento&&!forcar)return;
      const repetindo=this.erro.contains(document.activeElement);
      this.limpar();if(!url)return;this.url=url;this.chaveDocumento=chave;
      const seq=this.sequencia;this.abort=new AbortController();
      this.estado.innerHTML='<div class="g360-carregando g360-carregando-pagina"><div class="g360-carga-marca"><span class="g360-marca-carregando" aria-hidden="true"></span><span>Carregando cartão em PDF…</span></div><div class="g360-skeleton" aria-hidden="true"><i></i><i></i><i></i></div></div>';this.area.setAttribute('aria-busy','true');
      if(repetindo)this.area.focus({preventScroll:true});
      try {
        const timeout=setTimeout(()=>this.abort?.abort(),25000);
        let blob;
        try {const r=await G360Requisicao(url,{credentials:'same-origin',cache:'no-store',signal:this.abort.signal});if(!r.ok)throw new Error('Cartão indisponível');blob=await r.blob();}
        finally {clearTimeout(timeout);}
        if(seq!==this.sequencia)return;
        const {pdfjs,lib}=await bibliotecas(),bytes=await converter(blob,lib);
        if(seq!==this.sequencia)return;
        const carga=pdfjs.getDocument({data:bytes,isEvalSupported:false,enableXfa:false,useWasm:false});this.carga=carga;
        const pdf=await carga.promise;
        if(seq!==this.sequencia){await pdf.destroy();return;}
        this.pdf=pdf;this.pagina=1;this.zoom=1;await this.renderizar();
      } catch(e) {if(seq!==this.sequencia)return;this.folha.hidden=true;this.estado.hidden=true;this.erro.hidden=false;this.botoes(false);}
      finally {if(seq===this.sequencia)this.area.setAttribute('aria-busy','false');}
    }
    ampliar(fator){if(!this.pdf||!this.controles)return;this.zoom=Math.max(.5,Math.min(5,this.zoom*fator));this.renderizar();}
    async renderizar() {
      if(!this.pdf||!this.area.clientWidth||!this.area.clientHeight)return;
      const seq=this.sequencia,rodada=(this.rodada||0)+1;this.rodada=rodada;const anterior=this.tarefa;anterior?.cancel();
      try {
        if(anterior)await anterior.promise.catch(()=>{});
        if(seq!==this.sequencia||rodada!==this.rodada||!this.pdf)return;
        const page=await this.pdf.getPage(this.pagina);if(seq!==this.sequencia||rodada!==this.rodada)return;
        const base=page.getViewport({scale:1}),largura=(this.area.clientWidth-32)/base.width,fit=this.largura?largura:Math.min(largura,(this.area.clientHeight-32)/base.height);
        const v=page.getViewport({scale:Math.max(.05,fit)*this.zoom});
        const dpr=Math.min(window.devicePixelRatio||1,2,Math.sqrt(8000000/(v.width*v.height)));
        this.canvas.width=Math.ceil(v.width*dpr);this.canvas.height=Math.ceil(v.height*dpr);
        this.canvas.style.width=v.width+'px';this.canvas.style.height=v.height+'px';
        this.canvas.setAttribute('aria-label','Cartão-resposta completo — página '+this.pagina+' de '+this.pdf.numPages);
        this.tarefa=page.render({canvasContext:this.canvas.getContext('2d'),viewport:v,transform:[dpr,0,0,dpr,0,0],annotationMode:0});
        await this.tarefa.promise;if(seq!==this.sequencia||rodada!==this.rodada)return;
        this.estado.hidden=true;this.erro.hidden=true;this.folha.hidden=false;this.botoes(true);
        if(this.controles)this.root.querySelector('[data-zoom]').textContent=this.zoom===1?'Ajustado':Math.round(this.zoom*100)+'%';
        this.ferramentas.hidden=!this.controles&&this.pdf.numPages===1;
        this.root.querySelector('[data-paginas]').hidden=this.pdf.numPages===1;
        this.root.querySelector('[data-pagina]').textContent=this.pagina+' / '+this.pdf.numPages;
        this.root.querySelector('[data-anterior]').disabled=this.pagina===1;this.root.querySelector('[data-proxima]').disabled=this.pagina===this.pdf.numPages;
        this.area.scrollTop=this.area.scrollLeft=0;
      }catch(e){if(e.name==='RenderingCancelledException'||seq!==this.sequencia)return;this.folha.hidden=true;this.estado.hidden=true;this.erro.hidden=false;this.botoes(false);}
    }
  };
})();
