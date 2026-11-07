/* O diálogo mantém um rascunho; somente Aplicar modifica o recorte do painel. */
(function () {
  'use strict';
  const modal = document.querySelector('[data-painel-modal]');
  const form = document.querySelector('[data-painel-filtros]');
  const abrir = document.querySelector('[data-painel-abrir]');
  if (!modal || !form || !abrir) return;
  function restaurar() {
    form.reset();
    ['painel_disciplina','painel_professor','painel_inicio','painel_fim'].forEach(n=>{form.elements.namedItem(n).value=form.elements.namedItem('painel_anterior['+n+']')?.value||'';});
    validarPeriodo();
  }
  abrir.addEventListener('click', () => { restaurar(); modal.showModal(); });
  modal.querySelectorAll('[data-painel-cancelar]').forEach(b => b.addEventListener('click', () => modal.close()));
  modal.addEventListener('close', () => { restaurar(); abrir.focus(); });
  modal.querySelector('[data-painel-limpar]')?.addEventListener('click', () => {
    ['painel_disciplina','painel_professor','painel_inicio','painel_fim'].forEach(n => { form.elements.namedItem(n).value = ''; });
    validarPeriodo();form.querySelector('select')?.focus();
  });
  const inicio = form.elements.namedItem('painel_inicio'), fim = form.elements.namedItem('painel_fim');
  function validarPeriodo() { fim.min = inicio.value; }
  inicio.addEventListener('change', validarPeriodo);
  abrir.addEventListener('click', validarPeriodo);
  if(Number(modal.dataset.painelErros)>0){validarPeriodo();modal.showModal();modal.querySelector('[aria-invalid="true"]')?.focus();}
})();

/* O modo é um campo real: navegação UR4 não depende do botão submitter. */
(function(){
 'use strict';
 const modal=document.querySelector('[data-documento-modal]');if(!modal)return;
 const form=modal.querySelector('form');let origem;
 document.querySelectorAll('[data-documento-abrir]').forEach(botao=>botao.addEventListener('click',()=>{
  origem=botao;form.reset();const imprimir=botao.dataset.documentoAbrir==='imprimir';
  form.elements.namedItem('baixar').value=imprimir?'0':'1';
  modal.querySelector('[data-documento-titulo]').textContent=imprimir?'Imprimir painel da turma':'Gerar PDF do painel da turma';
  modal.querySelector('[data-documento-confirmar]').textContent=imprimir?'Abrir para impressão':'Gerar PDF';modal.showModal();
 }));
 modal.querySelectorAll('[data-documento-fechar]').forEach(b=>b.addEventListener('click',()=>modal.close()));
 modal.addEventListener('close',()=>origem?.focus());
})();

(function(){
 const raiz=document.querySelector('[data-painel-alunos]');if(!raiz)return;
 raiz.querySelectorAll('[data-alunos-visao]').forEach(b=>b.addEventListener('click',()=>{
  raiz.querySelectorAll('[data-alunos-visao]').forEach(i=>i.setAttribute('aria-pressed',String(i===b)));
  raiz.querySelectorAll('[data-alunos-lista]').forEach(s=>s.hidden=s.dataset.alunosLista!==b.dataset.alunosVisao);
 }));
 raiz.querySelectorAll('[data-alunos-expandir]').forEach(b=>b.addEventListener('click',()=>{
  const aberto=b.getAttribute('aria-expanded')==='true';b.setAttribute('aria-expanded',String(!aberto));
  b.closest('section').querySelectorAll('[data-alunos-item]').forEach((e,i)=>e.hidden=aberto&&i>=5);
  if(!b.dataset.rotulo)b.dataset.rotulo=b.textContent;b.textContent=aberto?b.dataset.rotulo:'Mostrar cinco';
 }));
})();
