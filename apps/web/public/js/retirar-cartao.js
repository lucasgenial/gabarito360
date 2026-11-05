window.confirmarRetiradaCartao=async function(id,nome){
  if(confirmarRetiradaCartao.ocupada)return null;confirmarRetiradaCartao.ocupada=true;
  try{
  var r=await fetch('/cartoes/'+id+'/retirada',{credentials:'same-origin',headers:{Accept:'application/json'}}),d=await r.json();
  if(!r.ok)throw new Error(d.erro||'Não foi possível conferir a correção atual.');
  var modal=document.getElementById('retirar-modal'),form=document.getElementById('retirar-form'),origem=document.activeElement;
  form.reset();document.getElementById('retirar-aluno').textContent=nome||'Cartão-resposta';
  document.getElementById('retirar-nota').textContent=d.nota_atual===null?'Sem nota concluída.':'Nota vigente: '+Number(d.nota_atual).toFixed(1).replace('.',',');
  return await new Promise(function(resolve){
    var resposta=null;
    function encerrar(){modal.removeEventListener('close',encerrar);form.onsubmit=null;if(origem?.isConnected)origem.focus({preventScroll:true});resolve(resposta);}
    form.onsubmit=function(e){e.preventDefault();if(!form.reportValidity())return;resposta={motivo:document.getElementById('retirar-motivo').value,versao:d.versao_correcao};modal.close();};
    document.getElementById('retirar-cancelar').onclick=function(){modal.close();};modal.addEventListener('close',encerrar);modal.showModal();document.getElementById('retirar-cancelar').focus();
  });
  }finally{confirmarRetiradaCartao.ocupada=false;}
};
