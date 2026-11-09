/* Abas locais: a referência UR4 e a revisão de acesso pertencem à entrada do histórico. */
(function () {
  'use strict';
  const nav = document.querySelector('[data-turma-abas]');
  if (!nav) return;
  const abas = [...nav.querySelectorAll('[data-turma-aba]')];
  const paineis = new Map([...document.querySelectorAll('[data-turma-painel]')].map(p => [p.dataset.turmaPainel, p]));
  const valida = valor => abas.some(a => a.dataset.turmaAba === valor);
  function secaoHash() {
    const chave = location.hash.slice(1);
    if (chave === 'professores') return 'acesso';
    return valida(chave) ? chave : null;
  }
  // F4-B: a aba adiada busca seu fragmento uma única vez, na primeira abertura; erro oferece nova tentativa.
  const iniciadas = new WeakSet();
  function carregarAdiada(painel) {
    const root = painel?.querySelector('[data-turma-secao-adiada]');
    if (!root || iniciadas.has(root) || typeof window.G360Carga !== 'function') return;
    iniciadas.add(root);
    // Um atributo inválido cai no estado de erro do bloco, sem interromper as demais abas.
    window.G360Carga(root).executar(() => window.G360BuscarJSON(JSON.parse(root.dataset.turmaSecaoUrl)), d => {
      if (typeof d?.html !== 'string') throw new Error('Seção indisponível');
      root.querySelector('[data-carga-conteudo]').innerHTML = d.html;
      document.dispatchEvent(new CustomEvent('g360:turma-secao', {detail: {secao: painel.dataset.turmaPainel}}));
      // Link legado #professores: rola até o título quando ele chega com o fragmento.
      if (location.hash === '#professores') requestAnimationFrame(() => document.getElementById('professores')?.scrollIntoView({block: 'start'}));
    });
  }
  function selecionar(chave, foco = false, registrar = true) {
    if (!valida(chave)) chave = abas[0]?.dataset.turmaAba;
    abas.forEach(a => {
      const ativa = a.dataset.turmaAba === chave;
      a.setAttribute('aria-selected', String(ativa));
      a.tabIndex = ativa ? 0 : -1;
      if (ativa) {
        if (foco) a.focus({preventScroll: true});
        // Mover somente a faixa das abas, sem rolar o documento até o cabeçalho.
        const r=a.getBoundingClientRect(),n=nav.getBoundingClientRect();
        if(r.left<n.left)nav.scrollLeft-=n.left-r.left;
        else if(r.right>n.right)nav.scrollLeft+=r.right-n.right;
      }
    });
    paineis.forEach((p, k) => { p.hidden = k !== chave; });
    carregarAdiada(paineis.get(chave));
    if (registrar) {
      try { history.replaceState(history.state, '', location.pathname + location.search + '#' + chave); }
      catch (_) { /* A navegação local continua disponível sem armazenamento. */ }
    }
  }
  nav.setAttribute('role', 'tablist');
  abas.forEach(a => {
    const p=paineis.get(a.dataset.turmaAba);
    a.setAttribute('role', 'tab');a.setAttribute('aria-controls',p.id);
    p.setAttribute('role','tabpanel');p.setAttribute('aria-labelledby',a.id);p.tabIndex=0;
    a.addEventListener('click',e=>{e.preventDefault();selecionar(a.dataset.turmaAba,true);});
    a.addEventListener('keydown',e=>{
      const i=abas.indexOf(a);
      const destino={ArrowRight:(i+1)%abas.length,ArrowLeft:(i-1+abas.length)%abas.length,Home:0,End:abas.length-1}[e.key];
      if(destino!==undefined){e.preventDefault();selecionar(abas[destino].dataset.turmaAba,true);}
      else if(e.key===' '||e.key==='Enter'){e.preventDefault();selecionar(a.dataset.turmaAba,true);}
    });
  });
  selecionar(secaoHash() || nav.dataset.secaoInicial);
  const painelAtivo=[...paineis.values()].find(p=>!p.hidden);
  const erro=painelAtivo?.querySelector('[aria-invalid="true"]');
  if(erro){
    const focar=()=>requestAnimationFrame(()=>{if(!painelAtivo.hidden)erro.focus();});
    if(document.readyState==='complete')focar();
    else window.addEventListener('load',focar,{once:true});
  }
  window.addEventListener('hashchange',()=>selecionar(secaoHash()||nav.dataset.secaoInicial,false,false));
  window.addEventListener('resize',()=>selecionar(abas.find(a=>a.getAttribute('aria-selected')==='true')?.dataset.turmaAba,false,false));
})();
