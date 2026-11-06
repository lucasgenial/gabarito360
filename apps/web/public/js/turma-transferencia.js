/* Transferência: um diálogo, duas etapas e nenhuma escrita antes da confirmação. */
(function () {
  'use strict';
  const modal = document.querySelector('[data-transferencia-modal]');
  const corpo = modal?.querySelector('[data-transferencia-corpo]');
  let acionador = null, pendente = false, carregamento = null;
  const rascunhos = new WeakMap();
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
  const revisao = () => document.querySelector('meta[name="g360-revisao"]')?.content || '';
  function fechar() { if (!pendente) { carregamento?.abort(); modal?.close(); } }
  modal?.querySelector('[data-transferencia-fechar]').addEventListener('click', fechar);
  modal?.addEventListener('cancel', e => { if (pendente) e.preventDefault(); });
  modal?.addEventListener('close', () => {
    carregamento?.abort();const f=corpo.querySelector('form');
    if(acionador&&f)rascunhos.set(acionador,{turma_id:f.elements.namedItem('turma_id').value,motivo:f.elements.namedItem('motivo').value});
    acionador?.focus();
  });
  corpo?.addEventListener('click',e=>{if(e.target.closest('[data-transferencia-cancelar]')){e.preventDefault();fechar();}});
  function mensagem(texto) { const p = document.createElement('p');p.setAttribute('role','status');p.textContent = texto;corpo.replaceChildren(p); }
  function painelAtual(alvo) {
    const a=document.createElement('a');a.className='btn btn-secondary';a.href='/app/painel';a.dataset.navegacaoIniciarAtual='';
    const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.setAttribute('viewBox','0 0 24 24');svg.setAttribute('width','20');svg.setAttribute('height','20');svg.setAttribute('aria-hidden','true');svg.setAttribute('fill','none');svg.setAttribute('stroke','currentColor');svg.setAttribute('stroke-width','2');
    const path=document.createElementNS('http://www.w3.org/2000/svg','path');path.setAttribute('d','M4 20V4h16v16zM8 16V9m4 7v-4m4 4V7');svg.append(path);a.append(svg,document.createTextNode(' Abrir painel atual'));alvo.append(a);
  }
  document.addEventListener('click', async e => {
    const a = e.target.closest('[data-transferencia-abrir]');
    if (!a || !modal || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey) return;
    e.preventDefault();e.stopPropagation();acionador = a;mensagem('Carregando turmas disponíveis…');modal.showModal();
    const atual = new AbortController();carregamento = atual;const timer = setTimeout(() => atual.abort(), 25000);
    try {
      const d = window.G360Navegacao.descritor(a.dataset.recurso);
      const body = new FormData();body.set('_token',csrf());body.set('_revisao',revisao());body.set('_recurso',d.referencia);
      const r = await fetch(d.url,{method:'POST',body,credentials:'same-origin',cache:'no-store',redirect:'error',signal:atual.signal,headers:{'Accept':'text/html','X-G360-Modal':'transferencia','X-G360-Hidratar':'1','X-Requested-With':'XMLHttpRequest'}});
      if ([401,419].includes(r.status)) { location.assign('/login');return; }
      if(r.status===409){const erro=new Error('Seu acesso mudou em outra aba. Esta operação não foi executada.');erro.acessoAlterado=true;throw erro;}
      if (!r.ok || !r.headers.get('Content-Type')?.includes('text/html')) throw new Error('Não foi possível consultar as turmas. Feche e abra novamente.');
      const html = new DOMParser().parseFromString(await r.text(),'text/html');
      const conteudo = html.querySelector('[data-transferencia-conteudo]');
      if (!conteudo) throw new Error('O acesso mudou. Atualize a página antes de continuar.');
      if(!modal.open||carregamento!==atual)return;
      corpo.replaceChildren(document.importNode(conteudo,true));const form=corpo.querySelector('[data-turma-transferencia]');
      const salvo=rascunhos.get(a);if(form&&salvo){for(const[n,v]of Object.entries(salvo))form.elements.namedItem(n).value=v;}
      iniciar(form);
      corpo.querySelector('select, [data-transferencia-cancelar]')?.focus();
    } catch (erro) { if(modal.open&&carregamento===atual){mensagem(erro.name==='AbortError'?'A consulta demorou mais que o esperado. Feche e abra novamente.':erro.message);if(erro.acessoAlterado)painelAtual(corpo);} }
    finally { clearTimeout(timer); }
  },true);
  function iniciar(form) {
    if (!form || form.dataset.transferenciaPronta) return;
    form.dataset.transferenciaPronta='1';let revisado=false;
    const campos=form.querySelector('[data-transferencia-campos]'), resumo=form.querySelector('[data-transferencia-revisao]');
    const voltar=form.querySelector('[data-transferencia-voltar]'), verbo=form.querySelector('[data-transferencia-verbo]');
    const erro=form.querySelector('[data-transferencia-erro]');const submit=form.querySelector('button[type="submit"]');
    function etapa(revisao) { revisado=revisao;campos.hidden=revisao;resumo.hidden=!revisao;voltar.hidden=!revisao;verbo.textContent=revisao?'Confirmar e salvar':'Revisar transferência';if(revisao){resumo.tabIndex=-1;resumo.focus();} }
    voltar.addEventListener('click',()=>{etapa(false);form.elements.namedItem('turma_id').focus();});
    form.querySelector('[data-transferencia-cancelar]')?.addEventListener('click',e=>{if(modal?.open){e.preventDefault();fechar();}});
    form.addEventListener('submit',async e=>{
      e.preventDefault();if(pendente)return;
      if(!revisado){if(!form.reportValidity())return;form.querySelector('[data-transferencia-destino]').textContent=form.elements.namedItem('turma_id').selectedOptions[0].textContent;form.querySelector('[data-transferencia-motivo]').textContent=form.elements.namedItem('motivo').value;etapa(true);return;}
      form.querySelectorAll('[aria-invalid]').forEach(c=>{c.removeAttribute('aria-invalid');c.removeAttribute('aria-describedby');});form.querySelectorAll('[data-transferencia-erro-campo]').forEach(c=>c.remove());
      pendente=true;submit.disabled=true;voltar.disabled=true;form.setAttribute('aria-busy','true');erro.hidden=true;
      const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),30000);let incerto=true;
      try {
        const r=await fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin',cache:'no-store',redirect:'error',signal:controller.signal,headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        if([401,419].includes(r.status)){location.assign('/login');return;}
        if(!r.headers.get('Content-Type')?.includes('application/json')){incerto=true;throw new Error('Não foi possível confirmar. Confira a turma atual antes de reenviar.');}
        const dados=await r.json();
        if(!r.ok){
          incerto=!!dados.resultado_incerto || ![400,403,404,422,429].includes(r.status);
          if(r.status===422){etapa(false);let primeiro=null;for(const[n,m]of Object.entries(dados.errors||{})){const c=form.elements.namedItem(n);if(!c||!c.closest('.field'))continue;const aviso=document.createElement('p');aviso.className='tu1-erro';aviso.dataset.transferenciaErroCampo='';aviso.id='transferencia-erro-'+n;aviso.textContent=Array.isArray(m)?m.join(' '):String(m);c.setAttribute('aria-invalid','true');c.setAttribute('aria-describedby',aviso.id);c.closest('.field').append(aviso);primeiro??=c;}if(primeiro){primeiro.focus();return;}}
          const falha=new Error(Object.values(dados.errors||{}).flat().join(' ')||dados.message||'Não foi possível transferir.');falha.acessoAlterado=!!dados.acesso_alterado;throw falha;}
        window.G360Navegacao.abrir(dados.destino);
      } catch(falha){if(falha.name==='AbortError'||falha instanceof TypeError)incerto=true;etapa(false);erro.textContent=incerto?'Não foi possível confirmar o resultado. Feche esta janela e confira a turma atual antes de iniciar outra transferência.':falha.message;if(falha.acessoAlterado)painelAtual(erro);erro.hidden=false;erro.tabIndex=-1;erro.focus();}
      finally {clearTimeout(timer);pendente=false;submit.disabled=incerto;voltar.disabled=false;form.removeAttribute('aria-busy');}
    });
  }
  document.querySelectorAll('[data-turma-transferencia]').forEach(iniciar);
})();
