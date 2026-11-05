@php
 $rascunho=!isset($aluno);
 $imagemInicial=['avatar_id'=>$aluno['avatar_id']??old('avatar_id'),'tem_foto'=>(bool)($aluno['tem_foto']??false),'imagem_versao'=>$aluno['imagem_versao']??1,'foto_url'=>$rascunho?null:route('alunos.foto',['id'=>$aluno['id'],'v'=>$aluno['imagem_versao']??1])];
@endphp
<dialog id="imagem-aluno-modal" class="troca-contexto-modal imagem-modal" aria-labelledby="imagem-aluno-titulo" aria-describedby="imagem-aluno-orientacao">
 <div class="troca-contexto-cabecalho"><h2 id="imagem-aluno-titulo">{{ $rascunho ? 'Imagem do novo aluno' : 'Imagem de '.$aluno['nome'] }}</h2><button class="btn btn-secondary" id="imagem-aluno-fechar" type="button" aria-label="Fechar imagem do aluno">Fechar</button></div>
 <p id="imagem-aluno-orientacao">{{ $rascunho ? 'Escolha uma imagem. Ela será salva ao cadastrar o aluno; cancelar o cadastro descarta a seleção.' : 'Altere somente a imagem deste aluno. Os dados do cadastro e o histórico permanecem preservados.' }}</p>
 <p id="imagem-aluno-erro" class="field-error" role="alert" tabindex="-1" hidden></p>
 <p id="imagem-aluno-sucesso" role="status" hidden></p>
 <div id="imagem-aluno-preview" class="imagem-modal-preview" aria-label="Prévia da imagem"></div>
 <form id="imagem-aluno-form" method="POST" action="{{ $rascunho ? '#' : route('alunos.imagem', $aluno['id']) }}" enctype="multipart/form-data">
 @csrf
 <fieldset class="imagem-modal-tipos"><legend class="sr-only">Escolha a imagem</legend><label><input type="radio" name="tipo" value="avatar"> Avatar</label><label><input type="radio" name="tipo" value="foto"> Foto</label></fieldset>
 <div id="imagem-aluno-avatares"><x-seletor-avatar :direto="true" titulo="Escolha um avatar" :nome="$aluno['nome']??old('nome','Novo aluno')" :avatar-id="$imagemInicial['avatar_id']" preview-id="imagem-aluno-avatar-preview" /></div>
 <div id="imagem-aluno-foto" class="field">
 <div id="imagem-aluno-origem" class="imagem-modal-acoes foto-origem"><button type="button" class="btn btn-secondary" data-camera>Tirar foto</button><button type="button" class="btn btn-secondary" data-arquivo>Escolher arquivo</button></div>
 <input hidden type="file" id="imagem-aluno-arquivo" name="foto" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif,.dng">
 <input hidden type="file" id="imagem-aluno-camera" accept="image/*" capture="user">
 <p class="field-help">A foto será ajustada e compactada antes de enviar. A câmera depende do celular e do navegador.</p>
 <x-preparo-foto id="imagem-aluno" />
 <details class="field-help"><summary>Formatos e limites</summary><p>Fotos de até 32 MB e 48 megapixels. JPG, PNG e WebP; HEIC/HEIF são convertidos. RAW/DNG exige uma prévia JPG incorporada compatível. Se não houver, escolha a versão JPG/HEIC ou use o modo padrão da câmera. O original não é enviado nem armazenado.</p></details>
 </div>
 <div class="imagem-modal-acoes imagem-modal-rodape"><button class="btn btn-secondary" id="imagem-aluno-cancelar" type="button">Cancelar</button><button class="btn btn-primary" id="imagem-aluno-salvar" type="submit" disabled>{{ $rascunho ? 'Usar esta imagem' : 'Salvar imagem' }}</button></div>
 </form>
</dialog>
<script>
(function(){
 var modal=document.getElementById('imagem-aluno-modal'),form=document.getElementById('imagem-aluno-form'),erro=document.getElementById('imagem-aluno-erro'),sucesso=document.getElementById('imagem-aluno-sucesso');
 var fechar=document.getElementById('imagem-aluno-fechar'),cancelar=document.getElementById('imagem-aluno-cancelar'),salvar=document.getElementById('imagem-aluno-salvar'),arquivo=document.getElementById('imagem-aluno-arquivo');
 var camera=document.getElementById('imagem-aluno-camera');
 form.querySelector('[data-camera]').addEventListener('click',function(){camera.click();});
 form.querySelector('[data-arquivo]').addEventListener('click',function(){arquivo.click();});

 var preview=document.getElementById('imagem-aluno-preview'),galeria=document.getElementById('imagem-aluno-avatares'),foto=document.getElementById('imagem-aluno-foto');
 var atual=@json($imagemInicial),rascunho=@json($rascunho),origem=null,ocupado=false,blob=null,arquivoPreparado=null;
 var cadastro=document.getElementById('cadastro-aluno');
 var editor=new G360PreparoFoto({input:arquivo,camera:camera,root:document.getElementById('imagem-aluno-editor'),error:mostrarErro,change:atualizar,start:function(){cancelar.textContent='Cancelar';erro.hidden=true;sucesso.hidden=true;}});
 function renderizar(local,d,tamanho){
  var span=document.createElement('span');span.className='avatar-aluno avatar-aluno--azul avatar-aluno--'+tamanho;span.setAttribute('aria-hidden','true');
  if(d.tem_foto&&d.foto_url){var url=new URL(d.foto_url,location.href);if(url.origin!==location.origin&&url.protocol!=='blob:')throw new Error('Imagem indisponível.');var img=document.createElement('img');img.src=url.href;img.alt='';img.style.cssText='width:100%;height:100%;object-fit:cover;border-radius:inherit;';span.append(img);}
  else{var id=Number(d.avatar_id);if(id>=1&&id<=36){span.classList.add('avatar-aluno--imagem');span.style.setProperty('--avatar-x',((id-1)%6)*20+'%');span.style.setProperty('--avatar-y',Math.floor((id-1)/6)*20+'%');}var iniciais=document.createElement('span');iniciais.className='avatar-iniciais';iniciais.textContent=local.dataset.iniciais||document.querySelector('[data-imagem-aluno]')?.dataset.iniciais||'?';span.append(iniciais);}
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
 function preparar(){editor.reset();form.reset();cancelar.textContent='Cancelar';arquivo.value='';camera.value='';if(rascunho&&arquivoPreparado)editor.preparar(arquivoPreparado);erro.hidden=true;sucesso.hidden=true;form.querySelector('[name="tipo"][value="'+(atual.tem_foto?'foto':'avatar')+'"]').checked=true;form.querySelectorAll('[name="avatar_id"]').forEach(function(i){i.checked=i.value===String(atual.avatar_id||'');});atualizar();}
 document.querySelectorAll('[data-abrir-imagem-aluno]').forEach(function(b){b.addEventListener('click',function(){origem=b;preparar();modal.showModal();fechar.focus();});});
 function sair(){if(!ocupado)modal.close();}fechar.addEventListener('click',sair);cancelar.addEventListener('click',sair);modal.addEventListener('cancel',function(e){if(ocupado)e.preventDefault();});modal.addEventListener('close',function(){editor.reset();if(blob){URL.revokeObjectURL(blob);blob=null;}if(origem)origem.focus();});
 form.addEventListener('change',function(){cancelar.textContent='Cancelar';erro.hidden=true;sucesso.hidden=true;atualizar();});
 form.addEventListener('submit',async function(e){
  e.preventDefault();if(ocupado||salvar.disabled)return;
  if(rascunho){
   arquivoPreparado=tipo()==='foto'?arquivo.files[0]:null;
   var dt=new DataTransfer();if(arquivoPreparado)dt.items.add(arquivoPreparado);cadastro.querySelector('[name="foto"]').files=dt.files;
   cadastro.querySelector('[name="imagem_preparada"]').value=arquivoPreparado?'foto':'avatar';
   cadastro.querySelector('[name="avatar_id"]').value=arquivoPreparado?'':avatar();
   if(atual.foto_url?.startsWith('blob:'))URL.revokeObjectURL(atual.foto_url);
   atual={avatar_id:avatar(),tem_foto:!!arquivoPreparado,foto_url:arquivoPreparado?URL.createObjectURL(arquivoPreparado):null};
   document.querySelectorAll('[data-imagem-aluno]').forEach(function(el){renderizar(el,atual,el.dataset.tamanho||'lg');});
   document.getElementById('imagem-aluno-rascunho').textContent='Imagem preparada. Será salva ao cadastrar o aluno.';
   modal.close();return;
  }
  var dados=new FormData(form);ocupado=true;form.querySelectorAll('input,button').forEach(function(i){i.disabled=true;});salvar.disabled=true;fechar.disabled=true;cancelar.disabled=true;modal.setAttribute('aria-busy','true');erro.hidden=true;sucesso.hidden=true;
  var controle=new AbortController(),limite=setTimeout(function(){controle.abort();},20000);
  try{var r=await fetch(form.action,{method:'POST',body:dados,headers:{Accept:'application/json'},signal:controle.signal,credentials:'same-origin'});var j=await r.json();if(!r.ok){if(j.errors?.foto)throw new Error('Verifique o formato, tamanho e dimensões da foto.');throw new Error(j.message||'Não foi possível salvar a imagem.');}
   if(!j.data||!Object.hasOwn(j.data,'tem_foto'))throw new Error('Não foi possível confirmar a atualização. Atualize a página para conferir.');
   atual=j.data;document.querySelectorAll('[data-imagem-aluno]').forEach(function(el){renderizar(el,atual,el.dataset.tamanho||'sm');});arquivo.value='';preparar();sucesso.textContent='Imagem do aluno atualizada.';sucesso.hidden=false;cancelar.textContent='Fechar';
  }catch(e){mostrarErro((e.name==='AbortError'||e instanceof TypeError||e instanceof SyntaxError)?'Não foi possível confirmar a atualização. Atualize a página para conferir.':e.message);erro.focus();}
  finally{clearTimeout(limite);ocupado=false;form.querySelectorAll('input,button').forEach(function(i){i.disabled=false;});fechar.disabled=false;cancelar.disabled=false;modal.removeAttribute('aria-busy');atualizar();}
 });
})();
</script>
