(function () {
  'use strict';
  const filtros=document.querySelector('[data-painel-filtros]');
  const documento=document.querySelector('[data-painel-documento]');
  if(!filtros||!documento)return;
  const campos=['painel_disciplina','painel_professor','painel_inicio','painel_fim'];
  const valores=()=>JSON.stringify(campos.map(nome=>filtros.elements.namedItem(nome)?.value||''));
  const aplicados=valores();
  function conferir(){
    const pendente=valores()!==aplicados;
    documento.querySelectorAll('button[type="submit"]').forEach(b=>{b.disabled=pendente;});
    document.querySelector('[data-painel-pendente]').hidden=!pendente;
  }
  filtros.addEventListener('input',conferir);filtros.addEventListener('change',conferir);
})();
