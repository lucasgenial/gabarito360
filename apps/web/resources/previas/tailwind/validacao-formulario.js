import {formValidation} from '@form-validation/core';
import {notEmpty} from '@form-validation/validator-not-empty';
import {stringLength} from '@form-validation/validator-string-length';
import {integer} from '@form-validation/validator-integer';
import {between} from '@form-validation/validator-between';

// Adaptador G360: motor real FormValidation, apresentação própria com Tailwind.
// Não registra DefaultSubmit, Remote, fetch ou persistência nesta prévia.
export function criarValidacaoTurma(container) {
  const campos={nome:{elemento:container.querySelector('#nome-turma'),erro:container.querySelector('#nome-erro')},ano:{elemento:container.querySelector('#ano'),erro:container.querySelector('#ano-erro')}};
  const falhas=new Map();
  const motor=formValidation(container,{fields:{
    nome:{validators:{notEmpty:{trim:true,message:'Informe o nome da turma.'},stringLength:{max:50,message:'Use até 50 caracteres no nome da turma.'}}},
    ano:{validators:{notEmpty:{message:'Informe o ano letivo.'},integer:{message:'Informe um ano inteiro.'},between:{min:2020,max:2099,message:'Informe um ano entre 2020 e 2099.'}}}
  }}).registerValidator('notEmpty',notEmpty).registerValidator('stringLength',stringLength).registerValidator('integer',integer).registerValidator('between',between);
  container.dataset.motorValidacao='FormValidation';
  function apresentar(nome,estado,mensagem=''){
    const campo=campos[nome];campo.elemento.dataset.validacao=estado;
    campo.elemento.setAttribute('aria-invalid',String(estado==='invalido'));
    campo.erro.textContent=mensagem;campo.erro.hidden=estado!=='invalido';
  }
  motor.on('core.field.validating',nome=>falhas.delete(nome));
  motor.on('core.validator.validated',evento=>{if(!evento.result.valid&&!falhas.has(evento.field))falhas.set(evento.field,evento.result.message);});
  motor.on('core.field.invalid',nome=>apresentar(nome,'invalido',falhas.get(nome)||'Confira este campo.'));
  motor.on('core.field.valid',nome=>apresentar(nome,'valido'));
  for(const [nome,campo] of Object.entries(campos)){
    campo.erro.setAttribute('role','alert');
    campo.elemento.addEventListener('input',()=>{motor.resetField(nome,false);apresentar(nome,'pendente');});
    campo.elemento.addEventListener('blur',()=>{if(campo.elemento.value!==''||campo.elemento.dataset.validacao==='invalido')void motor.revalidateField(nome);});
  }
  return {
    async validarTudo(){
      const resultado=await motor.validate();
      if(resultado!=='Valid'){
        const primeiro=Object.values(campos).find(c=>c.elemento.getAttribute('aria-invalid')==='true')?.elemento;
        if(primeiro)await new Promise(resolve=>requestAnimationFrame(()=>{
          primeiro.focus({preventScroll:true});
          primeiro.scrollIntoView({block:'center',behavior:'instant'});
          resolve();
        }));
      }
      return resultado==='Valid';
    },
    limparEstados(){motor.resetForm(false);falhas.clear();Object.keys(campos).forEach(nome=>apresentar(nome,'pendente'));}
  };
}
