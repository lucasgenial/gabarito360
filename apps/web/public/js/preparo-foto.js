/* Preparação local de retratos: o original nunca é enviado por este editor. */
(function () {
 'use strict';
 var base = new URL('.', document.currentScript.src);
 var LIMITE_BYTES = 32 * 1024 * 1024, LIMITE_PIXELS = 48000000;
 function canvas(w,h) {var c=document.createElement('canvas');c.width=w;c.height=h;return c;}
 function blob(c,t,q) {return new Promise(function(resolve,reject){c.toBlob(function(b){b?resolve(b):reject(new Error('Não foi possível preparar esta foto.'));},t,q);});}
 function jpegDimensoes(b) {
  var v=new DataView(b),p=2;
  while(p+9<v.byteLength){if(v.getUint8(p++)!==255)break;var m=v.getUint8(p++);if(m===217||m===218)break;if(m===216||m===1)continue;var n=v.getUint16(p);if(n<2||p+n>v.byteLength)break;
   if([192,193,194,195,197,198,199,201,202,203,205,206,207].includes(m))return [v.getUint16(p+5),v.getUint16(p+3)];p+=n;}
 }
 async function identificar(f) {
  var b=await f.slice(0,1048576).arrayBuffer(),a=new Uint8Array(b),v=new DataView(b),tipo='',dim;
  if(a[0]===255&&a[1]===216){tipo='nativo';dim=jpegDimensoes(b);if(!dim)dim=jpegDimensoes(await f.arrayBuffer());}
  else if(v.byteLength>=24&&v.getUint32(0)===0x89504e47&&v.getUint32(4)===0x0d0a1a0a){tipo='nativo';dim=[v.getUint32(16),v.getUint32(20)];}
  else if(a.length>=30&&String.fromCharCode(...a.slice(0,4))==='RIFF'&&String.fromCharCode(...a.slice(8,12))==='WEBP'){
   tipo='nativo';var chunk=String.fromCharCode(...a.slice(12,16));
   if(chunk==='VP8X')dim=[1+a[24]+(a[25]<<8)+(a[26]<<16),1+a[27]+(a[28]<<8)+(a[29]<<16)];
   else if(chunk==='VP8 ')dim=[v.getUint16(26,true)&16383,v.getUint16(28,true)&16383];
   else if(chunk==='VP8L'&&a[20]===47){var bits=v.getUint32(21,true);dim=[(bits&16383)+1,((bits>>>14)&16383)+1];}
  }
  else if(a.length>=12&&String.fromCharCode(...a.slice(4,8))==='ftyp')tipo='heic';
  else if(a.length>=8&&((a[0]===73&&a[1]===73&&a[2]===42)||(a[0]===77&&a[1]===77&&a[3]===42)))tipo='dng';
  if(!tipo)throw new Error('Não foi possível preparar esta foto. Escolha JPG, PNG, WebP ou uma foto compatível do celular.');
  if(tipo==='nativo'&&!dim)throw new Error('Não foi possível conferir esta foto. Escolha outro arquivo ou tire uma nova foto.');
  if(dim&&(!dim[0]||!dim[1]||dim[0]*dim[1]>LIMITE_PIXELS))throw new Error('Esta foto tem resolução muito alta. Tire outra foto com até 48 megapixels.');
  return tipo;
 }
 async function abrir(f) {
  if(typeof createImageBitmap==='function')return createImageBitmap(f,{imageOrientation:'from-image'});
  return new Promise(function(resolve,reject){var u=URL.createObjectURL(f),i=new Image();i.onload=function(){URL.revokeObjectURL(u);resolve(i);};i.onerror=function(){URL.revokeObjectURL(u);reject(new Error('Não foi possível ler esta foto.'));};i.src=u;});
 }
 class PreparoFoto {
  constructor(o) {
   this.o=o;this.root=o.root;this.input=o.input;this.camera=o.camera;this.host=this.root.querySelector('[data-foto-cropper]');this.seq=0;this.busy=false;this.ready=false;this.imagem=null;this.timer=null;this.worker=null;this.cropper=null;this.escalaBase=1;this.redefinindo=false;
   this.status=this.root.querySelector('[data-foto-status]');this.ajustes=this.root.querySelector('[data-foto-ajustes]');this.abortar=this.root.querySelector('[data-foto-abortar]');this.rotacao=0;this.rotacaoInput=this.root.querySelector('[data-foto-rotacao]');this.anguloInput=this.root.querySelector('[data-foto-angulo]');
   [this.input,this.camera].forEach(el=>el.addEventListener('change',e=>{e.stopPropagation();var f=el.files[0];if(f)this.preparar(f);else if(this.ready&&this.preparado){this.reporArquivo();this.notificar();}}));
   this.root.addEventListener('change',e=>e.stopPropagation());
   this.rotacaoInput.addEventListener('input',()=>this.inclinar(Number(this.rotacaoInput.value)));
   this.trocar=this.root.querySelector('[data-foto-trocar]');this.trocar.addEventListener('click',()=>{var d=this.root.closest('dialog'),aberto=d.classList.toggle('foto-trocar');this.trocar.setAttribute('aria-expanded',String(aberto));});
   this.abortar.addEventListener('click',()=>this.reset());
   this.host.addEventListener('wheel',e=>{if(e.ctrlKey||e.metaKey)e.stopPropagation();},{capture:true});
   this.host.addEventListener('keydown',e=>{if(e.ctrlKey||e.metaKey)return;var d={ArrowLeft:[-8,0],ArrowRight:[8,0],ArrowUp:[0,-8],ArrowDown:[0,8]}[e.key],i=this.cropper?.getCropperImage();if(d&&i){e.preventDefault();i.$move(d[0],d[1]);}else if(i&&['+','=','-'].includes(e.key)){e.preventDefault();i.$zoom(e.key==='-'?-0.08:0.08);}});
   this.largura=0;this.resize=new ResizeObserver(()=>this.redimensionar());this.resize.observe(this.host);
  }
  redimensionar(){
   var w=this.host.clientWidth;if(!w)return;if(!this.cropper||!this.carregado||!this.largura){this.largura=w;return;}if(Math.abs(w-this.largura)<0.5)return;
   var i=this.cropper.getCropperImage(),m=i.$getTransform(),r=w/this.largura;this.redefinindo=true;this.escalaBase*=r;
   i.$setTransform(m[0]*r,m[1]*r,m[2]*r,m[3]*r,m[4]*r+(r-1)*this.imagem.width/2,m[5]*r+(r-1)*this.imagem.height/2);
   this.redefinindo=false;this.largura=w;this.ajustarLimites();this.desenhar();
  }
  atualizarGraus(){var valor=new Intl.NumberFormat('pt-BR',{maximumFractionDigits:1}).format(this.rotacao);this.rotacaoInput.value=String(this.rotacao);this.anguloInput.textContent=valor+'°';this.rotacaoInput.setAttribute('aria-valuetext',valor+' graus');}
  inclinar(valor){
   if(!this.cropper||!this.carregado||!Number.isFinite(valor))return;var novo=Math.max(-180,Math.min(180,Math.round(valor*2)/2)),delta=novo-this.rotacao,i=this.cropper.getCropperImage(),r=i.getBoundingClientRect(),c=this.cropper.getCropperCanvas().getBoundingClientRect();
   i.rotatable=true;i.$rotate(delta+'deg',c.left+c.width/2-r.left,c.top+c.height/2-r.top);i.rotatable=false;this.rotacao=novo;this.atualizarGraus();
  }
  ajustarLimites(){
   if(!this.cropper)return;var i=this.cropper.getCropperImage(),m=i.$getTransform(),c=this.cropper.getCropperCanvas(),n=G360FotoGeometria.ajustar(m,this.imagem.width,this.imagem.height,c.clientWidth,c.clientHeight);
   if(n.some((v,k)=>Math.abs(v-m[k])>0.00001)){this.redefinindo=true;i.$setTransform(n);this.redefinindo=false;}
  }
  async montar(n){
   this.cropper=new Cropper.Cropper(this.imagem,{container:this.host,template:'<cropper-canvas scale-step="0.08"><cropper-image initial-fit="cover" scalable translatable></cropper-image><cropper-handle action="move" plain></cropper-handle></cropper-canvas>'});
   var i=this.cropper.getCropperImage();i.setAttribute('alt','Foto para enquadrar');await i.$ready();if(n!==this.seq)return;
   i.$center('cover');this.largura=this.host.clientWidth;this.rotacao=0;this.carregado=true;this.ajustes.inert=false;this.atualizarGraus();var instancia=this.cropper;
   i.addEventListener('transform',e=>{
    if(this.redefinindo)return;var m=e.detail.matrix,c=this.cropper.getCropperCanvas();if(!m.every(Number.isFinite)){e.preventDefault();return;}
    var correto=G360FotoGeometria.ajustar(m,this.imagem.width,this.imagem.height,c.clientWidth,c.clientHeight);
    if(correto.some((v,k)=>Math.abs(v-m[k])>0.00001)){e.preventDefault();this.redefinindo=true;i.$setTransform(correto);this.redefinindo=false;}
    queueMicrotask(()=>{if(this.cropper===instancia&&this.carregado){this.ajustarLimites();this.desenhar();}});
   });
   this.desenhar();
  }
  notificar(){this.o.change();}
  reset() {
   this.seq++;clearTimeout(this.timer);if(this.cancelarConversao)this.cancelarConversao();if(this.worker){this.worker.terminate();this.worker=null;}this.host.replaceChildren();this.root.closest('dialog').classList.remove('foto-trocar');this.trocar.setAttribute('aria-expanded','false');this.cropper=null;this.carregado=false;this.largura=0;this.preparado=null;this.imagem=null;this.ready=false;this.busy=false;this.input.value='';this.camera.value='';this.root.hidden=true;this.ajustes.hidden=true;this.ajustes.inert=false;this.abortar.hidden=true;this.notificar();
  }
  async converter(f,tipo) {
   return new Promise((resolve,reject)=>{
    var endereco=new URL('preparo-foto-codecs.js',base);endereco.searchParams.set('v',this.root.dataset.codecVersion||'1');
    var w=new Worker(endereco),encerrado=false;this.worker=w;
    var cancelar=()=>fim(new DOMException('Preparação cancelada.','AbortError'));
    var fim=(erro,resultado)=>{if(encerrado)return;encerrado=true;clearTimeout(limite);w.terminate();if(this.worker===w)this.worker=null;if(this.cancelarConversao===cancelar)this.cancelarConversao=null;erro?reject(erro):resolve(resultado);};
    var limite=setTimeout(()=>fim(new Error('Esta foto demorou para preparar. Escolha outra foto ou use a câmera em modo padrão.')),25000);this.cancelarConversao=cancelar;
    w.onmessage=e=>fim(e.data.erro?new Error(e.data.erro):null,e.data.blob);
    w.onerror=()=>fim(new Error('Não foi possível preparar esta foto. Escolha outro arquivo ou tire uma nova foto.'));
    w.postMessage({file:f,tipo});
   });
  }
  async preparar(f) {
   if(this.o.start)this.o.start();
   this.reset();var n=this.seq;this.root.hidden=false;this.busy=true;this.status.textContent='Preparando foto…';this.abortar.hidden=false;this.notificar();
   try{
    if(f.size>LIMITE_BYTES)throw new Error('Esta foto é muito grande. Escolha uma foto com até 32 MB ou tire outra no modo padrão.');
    var tipo=await identificar(f);if(n!==this.seq)return;
    var fonte=f;
    if(tipo!=='nativo')fonte=await this.converter(f,tipo);
    if(n!==this.seq)return;
    var bitmap=await abrir(fonte),w=bitmap.width||bitmap.naturalWidth,h=bitmap.height||bitmap.naturalHeight;
    try{if(n!==this.seq)return;if(!w||!h||w*h>LIMITE_PIXELS)throw new Error('Esta foto tem resolução muito alta. Tire outra foto com até 48 megapixels.');var k=Math.min(1,1600/Math.max(w,h));this.imagem=canvas(Math.round(w*k),Math.round(h*k));this.imagem.getContext('2d').drawImage(bitmap,0,0,this.imagem.width,this.imagem.height);}finally{if(bitmap.close)bitmap.close();}
    this.ajustes.inert=true;this.ajustes.hidden=false;this.abortar.hidden=true;await this.montar(n);
   }catch(e){if(n===this.seq){this.reset();this.o.error(['InvalidStateError','EncodingError','RangeError','TypeError'].includes(e.name)?'Não foi possível preparar esta foto. Escolha outro arquivo ou tire uma nova foto.':(e.message||'Não foi possível preparar esta foto.'));}}
  }
  desenhar() {
   if(!this.cropper||!this.carregado)return;this.ready=false;this.busy=true;this.input.value='';this.notificar();
   clearTimeout(this.timer);var n=++this.seq;this.timer=setTimeout(()=>this.compactar(n),180);
  }
  async compactar(n) {
   try {
    var saida=await this.cropper.getCropperCanvas().$toCanvas({width:384,height:384,beforeDraw:function(ctx,c){ctx.fillStyle='#fff';ctx.fillRect(0,0,c.width,c.height);}});if(n!==this.seq)return;var b;
    for(var q of [0.82,0.72,0.60,0.45]){b=await blob(saida,'image/webp',q);if(b.type!=='image/webp')b=await blob(saida,'image/jpeg',q);if(n!==this.seq)return;if(b.size<=98304)break;}
    if(b.size>131072)throw new Error('Não foi possível compactar esta foto. Escolha outra imagem.');
    if(!['image/webp','image/png','image/jpeg'].includes(b.type))throw new Error('Este navegador não conseguiu preparar a imagem.');
    this.preparado=new File([b],b.type==='image/webp'?'perfil.webp':'perfil.jpg',{type:b.type});this.reporArquivo();this.busy=false;this.ready=true;this.status.textContent='Foto pronta para ajustar.';this.notificar();
   }catch(e){if(n===this.seq){this.busy=false;this.ready=false;this.o.error(e.message);this.notificar();}}
  }
  reporArquivo(){var dt=new DataTransfer();dt.items.add(this.preparado);this.input.files=dt.files;}
 }
 window.G360PreparoFoto=PreparoFoto;
})();
