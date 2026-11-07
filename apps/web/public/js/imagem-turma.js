var imagem_turmaConfig = JSON.parse(document.getElementById('imagem-turma-config').textContent);

(function(){
 var modal=document.getElementById('imagem-turma-modal'),form=document.getElementById('imagem-turma-form'),erro=document.getElementById('imagem-turma-erro'),sucesso=document.getElementById('imagem-turma-sucesso');
 var fechar=document.getElementById('imagem-turma-fechar'),cancelar=document.getElementById('imagem-turma-cancelar'),salvar=document.getElementById('imagem-turma-salvar'),arquivo=document.getElementById('imagem-turma-arquivo');
 var camera=document.getElementById('imagem-turma-camera');
 form.querySelector('[data-camera]').addEventListener('click',function(){camera.click();});
 form.querySelector('[data-arquivo]').addEventListener('click',function(){arquivo.click();});

 var preview=document.getElementById('imagem-turma-preview'),galeria=document.getElementById('imagem-turma-avatares'),foto=document.getElementById('imagem-turma-foto');
 var atual=imagem_turmaConfig.inicial,origem=null,ocupado=false,blob=null;
 var editor=new G360PreparoFoto({input:arquivo,camera:camera,root:document.getElementById('imagem-turma-editor'),error:mostrarErro,change:atualizar,start:function(){cancelar.textContent='Cancelar';erro.hidden=true;sucesso.hidden=true;}});
 function renderizar(local,d,tamanho){
  var span=document.createElement('span');span.className='avatar-aluno avatar-aluno--azul avatar-aluno--'+tamanho;span.setAttribute('aria-hidden','true');
  if(d.tem_foto&&d.foto_url){
   var img=document.createElement('img');img.alt='';img.style.cssText='width:100%;height:100%;object-fit:cover;border-radius:inherit;';
   if(typeof d.foto_url==='string'&&d.foto_url.startsWith('blob:'))img.src=d.foto_url;
   else if(d.foto_url&&typeof d.foto_url==='object'){
    img.dataset.imagemAvatar='';img.dataset.imagemContextual=JSON.stringify(d.foto_url);img.style.display='none';
   }else throw new Error('Imagem indisponível.');
   span.append(img);
   if(img.hasAttribute('data-imagem-avatar')){var fallback=document.createElement('span');fallback.className='avatar-iniciais';fallback.textContent=local.dataset.iniciais||document.querySelector('[data-imagem-turma]')?.dataset.iniciais||'?';span.append(fallback);}
  }
  else{var id=Number(d.avatar_id);if(id>=1&&id<=36){span.classList.add('avatar-aluno--imagem','avatar-catalogo-turmas');var brasao=document.createElement('span');brasao.className='avatar-turma-brasao';span.append(brasao);span.style.setProperty('--avatar-x',((id-1)%6)*20+'%');span.style.setProperty('--avatar-y',Math.floor((id-1)/6)*20+'%');}var iniciais=document.createElement('span');iniciais.className='avatar-iniciais';iniciais.textContent=local.dataset.iniciais||document.querySelector('[data-imagem-turma]')?.dataset.iniciais||'?';span.append(iniciais);}
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
 document.querySelectorAll('[data-abrir-imagem-turma]').forEach(function(b){b.addEventListener('click',function(){origem=b;document.querySelectorAll('[data-imagem-turma-confirmacao]').forEach(function(el){el.hidden=true;});preparar();modal.showModal();fechar.focus();});});
 function sair(){if(!ocupado)modal.close();}fechar.addEventListener('click',sair);cancelar.addEventListener('click',sair);modal.addEventListener('cancel',function(e){if(ocupado)e.preventDefault();});modal.addEventListener('close',function(){editor.reset();if(blob){URL.revokeObjectURL(blob);blob=null;}if(origem)origem.focus();});
 form.addEventListener('change',function(){cancelar.textContent='Cancelar';erro.hidden=true;sucesso.hidden=true;atualizar();});
 form.addEventListener('submit',async function(e){
  e.preventDefault();if(ocupado||salvar.disabled)return;var dados=new FormData(form);dados.delete('_recurso');ocupado=true;form.querySelectorAll('input,button').forEach(function(i){i.disabled=true;});salvar.disabled=true;fechar.disabled=true;cancelar.disabled=true;modal.setAttribute('aria-busy','true');erro.hidden=true;sucesso.hidden=true;
  var controle=new AbortController(),limite=setTimeout(function(){controle.abort();},20000);
  try{var r=await G360Requisicao(imagem_turmaConfig.acao,{method:'POST',body:dados,headers:{Accept:'application/json'},signal:controle.signal,credentials:'same-origin'});var j=await r.json();if(!r.ok){if(j.errors?.foto)throw new Error('Verifique o formato, tamanho e dimensões da foto.');throw new Error(j.message||'Não foi possível salvar a imagem.');}
   if(!j.data||!Object.hasOwn(j.data,'tem_foto'))throw new Error('Não foi possível confirmar a atualização. Atualize a página para conferir.');
   atual=j.data;document.querySelectorAll('[data-imagem-turma]').forEach(function(el){if(String(el.dataset.imagemTurma)!==String(imagem_turmaConfig.turma))return;renderizar(el,atual,el.dataset.tamanho||'sm');});document.querySelectorAll('[data-imagem-turma-confirmacao]').forEach(function(el){el.textContent='Imagem salva.';el.hidden=false;});arquivo.value='';preparar();sucesso.textContent='Imagem da turma atualizada.';sucesso.hidden=false;cancelar.textContent='Fechar';
  }catch(e){mostrarErro((e.name==='AbortError'||e instanceof TypeError||e instanceof SyntaxError)?'Não foi possível confirmar a atualização. Atualize a página para conferir.':e.message);erro.focus();}
  finally{clearTimeout(limite);ocupado=false;form.querySelectorAll('input,button').forEach(function(i){i.disabled=false;});fechar.disabled=false;cancelar.disabled=false;modal.removeAttribute('aria-busy');atualizar();}
 });
})();
