'use strict';
document.querySelectorAll('[data-catalogo-localizacao]').forEach(box => {
    const uf=box.querySelector('[data-uf]'), select=box.querySelector('[data-municipio]'), busca=box.querySelector('[data-busca]'), status=box.querySelector('[data-status]'), mais=box.querySelector('[data-proximos]'), pesquisa=box.querySelector('[data-pesquisa-municipio]');
    const fonte=box.querySelector('[data-fonte-codigo]'), codigo=box.querySelector('[data-numero-codigo]');
    if(fonte && codigo){const atualizar=()=>{codigo.disabled=!fonte.value;if(!fonte.value)codigo.value='';};fonte.addEventListener('change',atualizar);atualizar();}
    let sequencia=0, pagina=1;
    async function carregar(page=1,codigo='') {
        const seq=++sequencia; select.disabled=true;mais.hidden=true;
        if(page===1)select.replaceChildren(new Option('Selecione o município',''));
        if(pesquisa)pesquisa.hidden=!uf.value;
        if(!uf.value){select.replaceChildren(new Option('Selecione a UF primeiro',''));status.textContent='Selecione uma UF.';return;}
        status.textContent='Carregando municípios…';
        const recurso=JSON.parse(box.dataset.url),dados={uf:uf.value,page:page};
        if(codigo)dados.codigo_ibge=codigo;else if(busca.value.trim())dados.busca=busca.value.trim();
        try {
            const r=await G360Requisicao(recurso,{body:JSON.stringify(dados),headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});if(!r.ok)throw new Error('indisponivel');const d=await r.json();if(seq!==sequencia)return;
            d.itens.forEach(m=>select.add(new Option(m.nome,m.codigo_ibge)));select.disabled=false;
            if(codigo && d.itens.some(m=>m.codigo_ibge===codigo))select.value=codigo;
            pagina=d.paginacao.atual;mais.hidden=pagina>=d.paginacao.ultima;
            status.textContent=d.itens.length?`Página ${pagina} de ${d.paginacao.ultima}.`:'Nenhum município encontrado neste filtro.';
            if(select.getAttribute?.('aria-invalid')==='true')select.focus();
        }catch(e){if(seq!==sequencia)return;status.textContent='Não foi possível carregar os municípios. Use Localizar para tentar novamente.';select.disabled=true;}
    }
    uf.addEventListener('change',()=>{busca.value='';carregar();});
    box.querySelector('[data-localizar]').addEventListener('click',()=>carregar());mais.addEventListener('click',()=>carregar(pagina+1));
    busca.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();carregar();}});
    carregar(1,select.dataset.selecionado||'');
});
const primeiroErro=document.querySelector?.('[aria-invalid="true"]');
if(primeiroErro){let ancestral=primeiroErro.parentElement;while(ancestral){if(ancestral.tagName==='DETAILS')ancestral.open=true;ancestral=ancestral.parentElement;}primeiroErro.focus();}
else document.querySelector?.('[data-resumo-erros]')?.focus();
document.querySelectorAll('[data-catalogo-autoridade]').forEach(form=>{
    const escola=form.querySelector('[data-escola]'), versao=form.querySelector('[data-versao]');
    escola.addEventListener('change',()=>{versao.value=escola.selectedOptions[0]?.dataset.versao||'0';});
});
