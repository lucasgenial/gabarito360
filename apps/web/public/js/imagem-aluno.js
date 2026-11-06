var imagem_alunoConfig = JSON.parse(document.getElementById('imagem-aluno-config').textContent);

(function(){
 var modal=document.getElementById('imagem-aluno-modal'),form=document.getElementById('imagem-aluno-form'),erro=document.getElementById('imagem-aluno-erro'),sucesso=document.getElementById('imagem-aluno-sucesso');
 var fechar=document.getElementById('imagem-aluno-fechar'),cancelar=document.getElementById('imagem-aluno-cancelar'),salvar=document.getElementById('imagem-aluno-salvar'),arquivo=document.getElementById('imagem-aluno-arquivo');
 var camera=document.getElementById('imagem-aluno-camera');
 form.querySelector('[data-camera]').addEventListener('click',function(){camera.click();});
 form.querySelector('[data-arquivo]').addEventListener('click',function(){arquivo.click();});

 var preview=document.getElementById('imagem-aluno-preview'),galeria=document.getElementById('imagem-aluno-avatares'),foto=document.getElementById('imagem-aluno-foto');
 var atual=imagem_alunoConfig.inicial,rascunho=imagem_alunoConfig.rascunho,origem=null,ocupado=false,blob=null,arquivoPreparado=null;
 var cadastro=document.getElementById('cadastro-aluno');
 var editor=new G360PreparoFoto({input:arquivo,camera:camera,root:document.getElementById('imagem-aluno-editor'),error:mostrarErro,change:atualizar,start:function(){cancelar.textContent='Cancelar';erro.hidden=true;sucesso.hidden=true;}});
 function renderizar(local,d,tamanho){
  var span=document.createElement('span');span.className='avatar-aluno avatar-aluno--azul avatar-aluno--'+tamanho;span.setAttribute('aria-hidden','true');
  if(d.tem_foto&&d.foto_url){var img=document.createElement('img');if(typeof d.foto_url==='string'&&d.foto_url.startsWith('blob:'))img.src=d.foto_url;else if(d.foto_url&&typeof d.foto_url==='object')img.dataset.imagemContextual=JSON.stringify(d.foto_url);else throw new Error('Imagem indisponível.');img.alt='';img.style.cssText='width:100%;height:100%;object-fit:cover;border-radius:inherit;';span.append(img);}
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
   if(typeof atual.foto_url==='string'&&atual.foto_url.startsWith('blob:'))URL.revokeObjectURL(atual.foto_url);
   atual={avatar_id:avatar(),tem_foto:!!arquivoPreparado,foto_url:arquivoPreparado?URL.createObjectURL(arquivoPreparado):null};
   document.querySelectorAll('[data-imagem-aluno]').forEach(function(el){renderizar(el,atual,el.dataset.tamanho||'lg');});
   document.getElementById('imagem-aluno-rascunho').textContent='Imagem preparada. Será salva ao cadastrar o aluno.';
   modal.close();return;
  }
  var dados=new FormData(form);dados.delete('_recurso');ocupado=true;form.querySelectorAll('input,button').forEach(function(i){i.disabled=true;});salvar.disabled=true;fechar.disabled=true;cancelar.disabled=true;modal.setAttribute('aria-busy','true');erro.hidden=true;sucesso.hidden=true;
  var controle=new AbortController(),limite=setTimeout(function(){controle.abort();},20000);
  try{var r=await G360Requisicao(imagem_alunoConfig.acao,{method:'POST',body:dados,headers:{Accept:'application/json'},signal:controle.signal,credentials:'same-origin'});var j=await r.json();if(!r.ok){if(j.errors?.foto)throw new Error('Verifique o formato, tamanho e dimensões da foto.');throw new Error(j.message||'Não foi possível salvar a imagem.');}
   if(!j.data||!Object.hasOwn(j.data,'tem_foto'))throw new Error('Não foi possível confirmar a atualização. Atualize a página para conferir.');
   atual=j.data;document.querySelectorAll('[data-imagem-aluno]').forEach(function(el){renderizar(el,atual,el.dataset.tamanho||'sm');});arquivo.value='';preparar();sucesso.textContent='Imagem do aluno atualizada.';sucesso.hidden=false;cancelar.textContent='Fechar';
  }catch(e){mostrarErro((e.name==='AbortError'||e instanceof TypeError||e instanceof SyntaxError)?'Não foi possível confirmar a atualização. Atualize a página para conferir.':e.message);erro.focus();}
  finally{clearTimeout(limite);ocupado=false;form.querySelectorAll('input,button').forEach(function(i){i.disabled=false;});fechar.disabled=false;cancelar.disabled=false;modal.removeAttribute('aria-busy');atualizar();}
 });
})();
