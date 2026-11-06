/* Navegação do BFF: endereços fixos e referência por entrada do histórico. */
(function (root) {
  'use strict';
  const CHAVE = 'g360-ur4-pendente';
  const revisao = () => document.querySelector('meta[name="g360-revisao"]')?.content || '';
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
  function descritor(valor) {
    const d = typeof valor === 'string' ? JSON.parse(valor) : valor;
    if (!d || typeof d.url !== 'string' || typeof d.pagina !== 'string' || !/^[a-f0-9]{48}$/.test(d.referencia)) throw new Error('Seleção indisponível.');
    for (const caminho of [d.url,d.pagina]) {
      const u = new URL(caminho,location.origin);
      if (u.origin !== location.origin || u.username || u.password || !u.pathname.startsWith('/app/') || u.search || u.hash) throw new Error('Endereço de navegação inválido.');
    }
    return d;
  }
  function campo(form,nome,valor) {
    if (valor === undefined || valor === null) return;
    if (Array.isArray(valor)) { valor.forEach(v=>campo(form,nome+'[]',v)); return; }
    const input=document.createElement('input');input.type='hidden';input.name=nome;input.value=String(valor);form.append(input);
  }
  function formulario(d,opcoes) {
    const form=document.createElement('form');form.method='POST';form.action=d.url;
    if (opcoes.novaAba) form.target='_blank';
    campo(form,'_token',csrf());campo(form,'_revisao',opcoes.revisao||revisao());campo(form,'_recurso',d.referencia);
    Object.entries(opcoes.dados||{}).forEach(([k,v])=>{ if (!['_token','_revisao','_recurso','_method'].includes(k)) campo(form,k,v); });
    document.body.append(form);form.submit();form.remove();
  }
  function abrir(valor,opcoes={}) {
    const d=descritor(valor);
    if (opcoes.novaAba || Object.keys(opcoes.dados||{}).length || d.metodo!=='GET') { formulario(d,opcoes);return; }
    const estado={ur4:{destino:d,revisao:revisao()}};
    try { sessionStorage.removeItem(CHAVE); } catch (_) {}
    history.pushState(estado,'',d.pagina);
    location.reload(); // GET real: atualizar/voltar nunca repete o POST da operação.
  }
  root.G360Navegacao={abrir,descritor};
  function erro(caixa,mensagem,repetir,acesso=false) {
    caixa.replaceChildren();caixa.setAttribute('aria-busy','false');caixa.setAttribute('role','alert');caixa.tabIndex=-1;
    const h=document.createElement('h1');h.textContent='Não foi possível abrir esta tela';
    const p=document.createElement('p');p.textContent=mensagem;caixa.append(h,p);
    const acoes=document.createElement('div');acoes.className='contexto-aviso-acoes';caixa.append(acoes);
    if (acesso) {
      const a=document.createElement('a');a.href='/app/vinculos';a.className='btn btn-primary';a.textContent='Selecionar vínculo';acoes.append(a);caixa.focus();return;
    }
    if (repetir) { const b=document.createElement('button');b.className='btn btn-primary';b.textContent='Tentar novamente';b.addEventListener('click',()=>location.reload());acoes.append(b); }
    const lista=document.querySelector('[data-navegacao-estrutura]')?.dataset.lista;
    if (!repetir && lista && lista!=='/app/painel') { const l=document.createElement('a');l.href=lista;l.className='btn btn-primary';l.textContent='Ir para a lista';acoes.append(l); }
    const a=document.createElement('a');a.href='/app/painel';a.className='btn btn-secondary';a.textContent='Voltar ao painel';acoes.append(a);caixa.focus();
  }
  async function hidratar(shell) {
    let estado=history.state?.ur4;
    try {
      const pendente=JSON.parse(sessionStorage.getItem(CHAVE)||'null');
      sessionStorage.removeItem(CHAVE);
      if (!estado && pendente && pendente.destino?.pagina===location.pathname) { estado=pendente;history.replaceState({ur4:estado},'',location.pathname); }
    } catch (_) { /* Histórico da entrada ainda funciona quando o armazenamento da aba é bloqueado. */ }
    if (!estado && shell.dataset.exigeSelecao==='1') { erro(shell.querySelector('[data-navegacao-status]'),'Abra este registro pela lista para continuar.',false);return; }
    const body=new FormData();body.set('_token',csrf());body.set('_revisao',estado?.revisao||revisao());
    let url=shell.dataset.consulta;
    try { if (estado) { const d=descritor(estado.destino);if(d.pagina!==location.pathname)throw new Error('Seleção inválida.');body.set('_recurso',d.referencia);url=d.url; } }
    catch (_) { erro(shell.querySelector('[data-navegacao-status]'),'Abra este registro pela lista para continuar.',false);return; }
    const abort=new AbortController(),timer=setTimeout(()=>abort.abort(),30000);
    try {
      const r=await fetch(url,{method:'POST',body,credentials:'same-origin',cache:'no-store',redirect:'error',signal:abort.signal,headers:{'Accept':'text/html','X-G360-Hidratar':'1','X-Requested-With':'XMLHttpRequest'}});
      const tipo=r.headers.get('Content-Type')||'';
      // Recuperar somente a leitura inicial; nunca reaplicar formulários ou arquivos.
      if (r.status===401 || r.status===419) { location.assign('/login');return; }
      if (!tipo.toLowerCase().includes('text/html')) {
        let dados=null;
        if (tipo.toLowerCase().includes('application/json')) { try { dados=await r.json(); } catch (_) {} }
        if (r.status===409 && dados?.acesso_alterado) {
          erro(shell.querySelector('[data-navegacao-status]'),'Seu acesso ativo mudou. Esta tela não foi aberta. Selecione o vínculo para continuar.',false,true);return;
        }
        if (r.status===409 && dados?.selecionar_vinculo) { location.assign('/app/vinculos');return; }
        erro(shell.querySelector('[data-navegacao-status]'),'Confira seu acesso e abra o registro novamente pela lista.',r.status>=500);return;
      }
      if (r.status>=500) { erro(shell.querySelector('[data-navegacao-status]'),'O serviço está temporariamente indisponível. Tente novamente.',true);return; }
      const html=await r.text();
      if (!/<html[\s>]/i.test(html)) throw new Error('Resposta incompatível.');
      document.open();document.write(html);document.close();
    } catch (_) { erro(shell.querySelector('[data-navegacao-status]'),'Não foi possível carregar as informações. Tente novamente.',true); }
    finally { clearTimeout(timer); }
  }
  function iniciar() {
    const ponte=document.querySelector('[data-navegacao-redirecionar]');
    if (ponte) {
      try { const d=descritor(ponte.dataset.recurso);sessionStorage.setItem(CHAVE,JSON.stringify({destino:d,revisao:revisao()}));location.replace(d.pagina); }
      catch (_) { erro(ponte.querySelector('section'),'Abra o registro novamente pela lista para continuar.',false); }
      return;
    }
    const shell=document.querySelector('[data-navegacao-estrutura]');
    if (shell) { hidratar(shell);return; }
    const renderizada=document.querySelector('meta[name="g360-pagina-renderizada"]');
    if (renderizada) {
      try { history.replaceState({ur4:{destino:descritor(renderizada.content),revisao:revisao()}},'',location.pathname); }
      catch (_) { /* Não escolher outro alvo se o estado desta entrada não puder ser registrado. */ }
    }
    document.addEventListener('click',e=>{
      const a=e.target.closest('a[data-recurso]');if(!a||e.defaultPrevented||e.button!==0)return;
      e.preventDefault();abrir(a.dataset.recurso,{novaAba:e.ctrlKey||e.metaKey||e.shiftKey||a.target==='_blank'});
    });
    document.addEventListener('submit',e=>{
      const form=e.target;if(!form.matches('form[data-navegacao-consulta]')||e.defaultPrevented)return;
      e.preventDefault();const dados={};for(const [k,v] of new FormData(form)){if(v instanceof File)continue;if(k.endsWith('[]'))(dados[k.slice(0,-2)]??=[]).push(v);else dados[k]=v;}
      abrir(form.dataset.recurso,{dados,novaAba:form.target==='_blank'});
    });
    root.addEventListener('popstate',()=>location.reload());
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',iniciar,{once:true});else iniciar();
})(window);
