@php
 $imagemInicial=['avatar_id'=>session('auth_avatar_id'),'tem_foto'=>(bool)session('auth_tem_foto',false),'imagem_versao'=>session('auth_imagem_versao',1),'foto_url'=>route('perfil.foto',['v'=>session('auth_imagem_versao',1),'_ctx'=>\App\Support\ContextoWeb::id()])];
@endphp
<dialog id="imagem-usuario-modal" class="troca-contexto-modal imagem-modal" aria-labelledby="imagem-usuario-titulo" aria-describedby="imagem-usuario-orientacao">
 <div class="troca-contexto-cabecalho"><h2 id="imagem-usuario-titulo">Sua imagem de perfil</h2><button class="btn btn-secondary" id="imagem-usuario-fechar" type="button" aria-label="Fechar imagem de perfil">Fechar</button></div>
 <p id="imagem-usuario-orientacao">A imagem é da sua conta e aparece nos seus vínculos. Escolha uma opção e salve para confirmar.</p>
 <p id="imagem-usuario-erro" class="field-error" role="alert" tabindex="-1" hidden></p>
 <p id="imagem-usuario-sucesso" role="status" hidden></p>
 <div id="imagem-usuario-preview" class="imagem-modal-preview" aria-label="Prévia da imagem"></div>
 <form id="imagem-usuario-form" method="POST" action="{{ route('perfil.imagem') }}" enctype="multipart/form-data">
 @csrf
 <fieldset class="imagem-modal-tipos"><legend class="sr-only">Escolha a imagem</legend><label><input type="radio" name="tipo" value="avatar"> Avatar</label><label><input type="radio" name="tipo" value="foto"> Foto</label></fieldset>
 <div id="imagem-usuario-avatares"><x-seletor-avatar :direto="true" titulo="Escolha um avatar" :nome="session('auth_nome','Usuário')" :avatar-id="session('auth_avatar_id')" preview-id="imagem-avatar-preview" /></div>
 <div id="imagem-usuario-foto" class="field">
 <div id="imagem-usuario-origem" class="imagem-modal-acoes foto-origem"><button type="button" class="btn btn-secondary" data-camera>Tirar foto</button><button type="button" class="btn btn-secondary" data-arquivo>Escolher arquivo</button></div>
 <input hidden type="file" id="imagem-usuario-arquivo" name="foto" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif,.dng">
 <input hidden type="file" id="imagem-usuario-camera" accept="image/*" capture="user">
 <p class="field-help">A foto será ajustada e compactada antes de enviar. A câmera depende do celular e do navegador.</p>
 <x-preparo-foto id="imagem-usuario" />
 <details class="field-help"><summary>Formatos e limites</summary><p>Fotos de até 32 MB e 48 megapixels. JPG, PNG e WebP; HEIC/HEIF são convertidos. RAW/DNG exige uma prévia JPG incorporada compatível. Se não houver, escolha a versão JPG/HEIC ou use o modo padrão da câmera. O original não é enviado nem armazenado.</p></details>
 </div>
 <div class="imagem-modal-acoes imagem-modal-rodape"><button class="btn btn-secondary" id="imagem-usuario-cancelar" type="button">Cancelar</button><button class="btn btn-primary" id="imagem-usuario-salvar" type="submit" disabled>Salvar imagem</button></div>
 </form>
</dialog>
<script>
(function(){
 var modal=document.getElementById('imagem-usuario-modal'),form=document.getElementById('imagem-usuario-form'),erro=document.getElementById('imagem-usuario-erro'),sucesso=document.getElementById('imagem-usuario-sucesso');
 var fechar=document.getElementById('imagem-usuario-fechar'),cancelar=document.getElementById('imagem-usuario-cancelar'),salvar=document.getElementById('imagem-usuario-salvar'),arquivo=document.getElementById('imagem-usuario-arquivo');
 var camera=document.getElementById('imagem-usuario-camera');
 form.querySelector('[data-camera]').addEventListener('click',function(){camera.click();});
 form.querySelector('[data-arquivo]').addEventListener('click',function(){arquivo.click();});

 var preview=document.getElementById('imagem-usuario-preview'),galeria=document.getElementById('imagem-usuario-avatares'),foto=document.getElementById('imagem-usuario-foto');
 var atual=@json($imagemInicial),origem=null,ocupado=false,blob=null;
 var editor=new G360PreparoFoto({input:arquivo,camera:camera,root:document.getElementById('imagem-usuario-editor'),error:mostrarErro,change:atualizar,start:function(){cancelar.textContent='Cancelar';erro.hidden=true;sucesso.hidden=true;}});
 function renderizar(local,d,tamanho){
  var span=document.createElement('span');span.className='avatar-aluno avatar-aluno--azul avatar-aluno--'+tamanho;span.setAttribute('aria-hidden','true');
  if(d.tem_foto&&d.foto_url){var url=new URL(d.foto_url,location.href);if(url.origin!==location.origin&&url.protocol!=='blob:')throw new Error('Imagem indisponível.');var img=document.createElement('img');img.src=url.href;img.alt='';img.style.cssText='width:100%;height:100%;object-fit:cover;border-radius:inherit;';span.append(img);}
  else{var id=Number(d.avatar_id);if(id>=1&&id<=36){span.classList.add('avatar-aluno--imagem');span.style.setProperty('--avatar-x',((id-1)%6)*20+'%');span.style.setProperty('--avatar-y',Math.floor((id-1)/6)*20+'%');}var iniciais=document.createElement('span');iniciais.className='avatar-iniciais';iniciais.textContent=local.dataset.iniciais||document.querySelector('[data-imagem-propria]')?.dataset.iniciais||'?';span.append(iniciais);}
  local.replaceChildren(span);
 }
 function mostrarErro(texto){erro.textContent=texto;erro.hidden=false;}
 function tipo(){return form.querySelector('[name="tipo"]:checked')?.value||'avatar';}
 function avatar(){return form.querySelector('[name="avatar_id"]:checked')?.value||'';}
 function atualizar(){
  var ehFoto=tipo()==='foto',f=arquivo.files[0],valido=true;galeria.hidden=ehFoto;foto.hidden=!ehFoto;arquivo.disabled=!ehFoto;form.querySelectorAll('[name="avatar_id"]').forEach(function(i){i.disabled=ehFoto;});
  if(blob){URL.revokeObjectURL(blob);blob=null;}
  preview.hidden=ehFoto&&!!editor?.imagem;modal.classList.toggle('foto-em-edicao',ehFoto&&!!editor?.imagem);
  if(ehFoto&&f){valido=!!editor?.ready&&['image/jpeg','image/png','image/webp'].includes(f.type)&&f.size<=2097152;if(valido){blob=URL.createObjectURL(f);renderizar(preview,{tem_foto:true,foto_url:blob},'lg');}else renderizar(preview,atual,'lg');}
  else if(ehFoto)renderizar(preview,atual,'lg');else renderizar(preview,{avatar_id:avatar()},'lg');
  var mudou=ehFoto?!!f:(atual.tem_foto||String(atual.avatar_id||'')!==avatar());salvar.disabled=ocupado||!!editor?.busy||!mudou||!valido;
 }
 function preparar(){editor.reset();form.reset();cancelar.textContent='Cancelar';arquivo.value='';camera.value='';erro.hidden=true;sucesso.hidden=true;form.querySelector('[name="tipo"][value="'+(atual.tem_foto?'foto':'avatar')+'"]').checked=true;form.querySelectorAll('[name="avatar_id"]').forEach(function(i){i.checked=i.value===String(atual.avatar_id||'');});atualizar();}
 document.querySelectorAll('[data-abrir-imagem]').forEach(function(b){b.addEventListener('click',function(){origem=b;preparar();modal.showModal();fechar.focus();});});
 function sair(){if(!ocupado)modal.close();}fechar.addEventListener('click',sair);cancelar.addEventListener('click',sair);modal.addEventListener('cancel',function(e){if(ocupado)e.preventDefault();});modal.addEventListener('close',function(){editor.reset();if(blob){URL.revokeObjectURL(blob);blob=null;}if(origem)origem.focus();});
 form.addEventListener('change',function(){cancelar.textContent='Cancelar';erro.hidden=true;sucesso.hidden=true;atualizar();});
 form.addEventListener('submit',async function(e){
  e.preventDefault();if(ocupado||salvar.disabled)return;var dados=new FormData(form);ocupado=true;form.querySelectorAll('input,button').forEach(function(i){i.disabled=true;});salvar.disabled=true;fechar.disabled=true;cancelar.disabled=true;modal.setAttribute('aria-busy','true');erro.hidden=true;sucesso.hidden=true;
  var controle=new AbortController(),limite=setTimeout(function(){controle.abort();},20000);
  try{var r=await fetch(form.action,{method:'POST',body:dados,headers:{Accept:'application/json'},signal:controle.signal,credentials:'same-origin'});var j=await r.json();if(!r.ok){if(j.errors?.foto)throw new Error('Verifique o formato, tamanho e dimensões da foto.');throw new Error(j.message||'Não foi possível salvar a imagem.');}
   if(!j.data||!Object.hasOwn(j.data,'tem_foto'))throw new Error('Não foi possível confirmar a atualização. Atualize a página para conferir.');
   atual=j.data;document.querySelectorAll('[data-imagem-propria]').forEach(function(el){renderizar(el,atual,el.dataset.tamanho||'sm');});arquivo.value='';preparar();sucesso.textContent='Imagem de perfil atualizada.';sucesso.hidden=false;cancelar.textContent='Fechar';
  }catch(e){mostrarErro((e.name==='AbortError'||e instanceof TypeError||e instanceof SyntaxError)?'Não foi possível confirmar a atualização. Atualize a página para conferir.':e.message);erro.focus();}
  finally{clearTimeout(limite);ocupado=false;form.querySelectorAll('input,button').forEach(function(i){i.disabled=false;});fechar.disabled=false;cancelar.disabled=false;modal.removeAttribute('aria-busy');atualizar();}
 });
})();
</script>
