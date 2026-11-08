/* Tabelas: ordenação nos cabeçalhos e cards, preservando nós, seleção e formulários. */
(function () {
  'use strict';
  var compararTexto = new Intl.Collator('pt-BR', { numeric: true, sensitivity: 'base' });
  function normalizar(texto) { return String(texto).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR'); }
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-tabela]').forEach(function (grupo) {
      var completa = grupo.dataset.colecaoCompleta === 'true';
      var selecao = completa && grupo.dataset.modo === 'selecao';
      var tabela = grupo.querySelector('table.tabela-padrao');
      if (!tabela || !tabela.tHead || tabela.tHead.rows.length !== 1 || tabela.tBodies.length !== 1 || tabela.querySelector('[rowspan]') ||
          Array.from(tabela.tHead.rows[0].cells).some(function (c) { return c.colSpan !== 1; })) return;
      var corpo = tabela.tBodies[0], registros = Array.from(corpo.rows);
      if (!registros.length || registros.some(function (r) { return Array.from(r.cells).some(function (c) { return c.colSpan !== 1; }); })) return;
      var ordem = grupo.querySelector('[data-tabela-ordem]'), tamanho = grupo.querySelector('[data-tabela-tamanho]');
      var anterior = grupo.querySelector('[data-tabela-anterior]'), proxima = grupo.querySelector('[data-tabela-proxima]');
      var busca = grupo.querySelector('[data-tabela-busca]'), somente = grupo.querySelector('[data-tabela-somente-selecionadas]');
      var filtrar = grupo.querySelector('[data-tabela-filtrar]'), limpar = grupo.querySelector('[data-tabela-limpar]');
      var pagina = 1, ordenados = registros.slice(), filtrados = registros.slice(), colunas = [];
      var termoAplicado = '', marcadasNoFiltro = null;
      var indicesOriginais = new Map(registros.map(function (r, i) { return [r, i]; }));
      function marcada(r) { return !!r.querySelector('input[type="checkbox"][name]:checked'); }
      var cabecalhos = Array.from(tabela.tHead.rows[0].cells), aviso = grupo.querySelector('[data-tabela-ordenacao-status]');
      function textoCelula(c) {
        if (c.dataset.valorOrdem !== undefined) return c.dataset.valorOrdem.trim();
        var principal = c.querySelector('.turma-name strong,.student-name b,.exam-title,.member-name');
        if (principal) return principal.textContent.trim();
        var copia = c.cloneNode(true);
        copia.querySelectorAll('.sr-only,[aria-hidden="true"],.av,img').forEach(function (e) { e.remove(); });
        return copia.textContent.replace(/\s+/g, ' ').trim();
      }
      function numero(texto, canonico) {
        if (canonico && /^-?\d+(?:\.\d+)?$/.test(texto)) return Number(texto);
        if (!/^-?(?:\d{1,3}(?:\.\d{3})+|\d+)(?:,\d+)?\s*(?:%|º|ª)?$/.test(texto)) return null;
        return Number(texto.replace(/[.%ºª\s]/g, '').replace(',', '.'));
      }
      function data(texto) {
        var m = texto.match(/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2}))?$/);
        if (!m) return null;
        var d = new Date(Date.UTC(+m[3], +m[2]-1, +m[1], +(m[4]||0), +(m[5]||0)));
        return d.getUTCFullYear()===+m[3] && d.getUTCMonth()===+m[2]-1 && d.getUTCDate()===+m[1] ? d.getTime() : null;
      }
      function vazio(v) { return !v || /^[—–-]$/.test(v); }
      var valores = new Map(), tipos = new Map();
      cabecalhos.forEach(function (th, i) {
        var celulas = registros.map(function (r) { return r.cells[i]; });
        // Controles de seleção e operações não são dados a ordenar.
        if (!th.textContent.trim() || th.classList.contains('tabela-coluna-acoes') || th.dataset.ordenar === 'nao' ||
            celulas.some(function (c) { return !c || c.classList.contains('tabela-acoes'); }) ||
            th.querySelector('input,select,button,a') || celulas.every(function (c) { return c.querySelector('input[type="checkbox"],input[type="radio"]') && !c.textContent.trim(); })) return;
        var rotulo = th.textContent.trim();
        var itens = celulas.map(function (c) { var texto=textoCelula(c); return {texto:texto,numero:numero(texto,c.dataset.valorOrdem!==undefined),data:data(texto)}; });
        var preenchidos = itens.filter(function (v) { return !vazio(v.texto); });
        var tipo = th.dataset.ordenar==='texto' ? 'texto' : preenchidos.length && preenchidos.every(function (v) { return v.numero!==null; }) ? 'numero' : preenchidos.length && preenchidos.every(function (v) { return v.data!==null; }) ? 'data' : 'texto';
        tipos.set(i,tipo);valores.set(i,new Map(registros.map(function (r,j) { return [r,itens[j]]; })));
        colunas.push(i);
        var botao = document.createElement('button');botao.type='button';botao.className='tabela-ordenar';
        botao.textContent=rotulo;botao.setAttribute('aria-label','Ordenar por '+rotulo+' em ordem crescente');
        th.replaceChildren(botao);
        botao.addEventListener('click',function () { ordem.value=i+':'+(th.getAttribute('aria-sort')==='ascending'?'desc':'asc');ordenar(); });
        ['asc','desc'].forEach(function (direcao) {
          var opcao=document.createElement('option');opcao.value=i+':'+direcao;
          opcao.textContent=rotulo+(direcao==='asc'?' (crescente)':' (decrescente)');ordem.appendChild(opcao);
        });
      });
      if (!colunas.length && !completa) return;
      function sincronizarCabecalhos() {
        var oculto=getComputedStyle(tabela.tHead).position==='absolute' && tabela.tHead.getBoundingClientRect().width<=1;
        cabecalhos.forEach(function (th) { var b=th.querySelector('.tabela-ordenar');if(b)b.tabIndex=oculto?-1:0; });
      }
      new ResizeObserver(sincronizarCabecalhos).observe(tabela);
      new MutationObserver(sincronizarCabecalhos).observe(grupo,{attributes:true,attributeFilter:['data-apresentacao']});
      sincronizarCabecalhos();
      function atualizarRodape(inicio, limite) {
        var quantidade = filtrados.length, paginas = Math.max(1, Math.ceil(quantidade / limite));
        if (selecao) {
          var exibidas = Math.max(0, Math.min(limite, quantidade - inicio)), escolhidas = registros.filter(marcada).length;
          grupo.querySelector('[data-tabela-total]').textContent = exibidas + (exibidas === 1 ? ' exibida de ' : ' exibidas de ') + registros.length + ' disciplinas · ' + escolhidas + (escolhidas === 1 ? ' selecionada' : ' selecionadas') +
            ((termoAplicado || marcadasNoFiltro !== null) ? ' · ' + quantidade + ' no filtro aplicado' : '');
        } else {
          grupo.querySelector('[data-tabela-total]').textContent = (inicio + 1) + '–' + Math.min(inicio + limite, quantidade) + ' de ' + quantidade + ' registros carregados';
        }
        grupo.querySelector('[data-tabela-pagina]').textContent = pagina + ' de ' + paginas;
        anterior.disabled = pagina === 1; proxima.disabled = pagina === paginas;
      }
      function desenhar() {
        filtrados = ordenados.filter(function (tr) {
          return (!termoAplicado || normalizar(tr.dataset.busca || '').includes(termoAplicado)) && (marcadasNoFiltro === null || marcadasNoFiltro.has(tr));
        });
        if (!completa) return;
        var limite = tamanho.value === 'todos' ? Math.max(1, filtrados.length) : Number(tamanho.value);
        var paginas = Math.max(1, Math.ceil(filtrados.length / limite));
        pagina = Math.min(pagina, paginas);
        var inicio = (pagina - 1) * limite;
        var visiveis = new Set(filtrados.slice(inicio, inicio + limite));
        registros.forEach(function (tr) { tr.hidden = !visiveis.has(tr); });
        atualizarRodape(inicio, limite);
        if (selecao) grupo.querySelector('[data-tabela-selecao-vazia]').hidden = filtrados.length !== 0;
        if (document.activeElement === proxima && proxima.disabled && !anterior.disabled) anterior.focus();
        else if (document.activeElement === anterior && anterior.disabled && !proxima.disabled) proxima.focus();
      }
      function ordenar() {
        var partes=ordem.value.split(':'),coluna=Number(partes[0]),direcao=partes[1],valido=colunas.includes(coluna)&&['asc','desc'].includes(direcao);
        ordenados=registros.slice();
        cabecalhos.forEach(function (th,i) {
          th.removeAttribute('aria-sort');var b=th.querySelector('.tabela-ordenar');
          if (b) b.setAttribute('aria-label','Ordenar por '+b.textContent+' em ordem '+(valido&&i===coluna&&direcao==='asc'?'decrescente':'crescente'));
        });
        if (valido) {
          var tipo=tipos.get(coluna),cache=valores.get(coluna);
          ordenados.sort(function (a,b) {
            var va=cache.get(a),vb=cache.get(b),vazioA=vazio(va.texto),vazioB=vazio(vb.texto);
            // Ausências ficam por último em ambas as direções, com empates estáveis.
            if (vazioA!==vazioB) return vazioA?1:-1;
            var delta=tipo==='texto'?compararTexto.compare(va.texto,vb.texto):va[tipo]-vb[tipo];
            return delta*(direcao==='desc'?-1:1)||indicesOriginais.get(a)-indicesOriginais.get(b);
          });
          cabecalhos[coluna].setAttribute('aria-sort',direcao==='asc'?'ascending':'descending');
        }
        ordenados.forEach(function (tr) { corpo.appendChild(tr); });
        if (aviso) {
          aviso.hidden=!valido;
          aviso.textContent=valido?(completa?'Registros carregados':'Ordenação dos registros desta página')+' · '+cabecalhos[coluna].textContent+' · '+(direcao==='asc'?'crescente':'decrescente')+'.':'';
        }
        pagina=1;desenhar();
      }
      ordem.addEventListener('change',ordenar);
      if (completa) {
      tamanho.addEventListener('change', function () { pagina = 1; desenhar(); });
      anterior.addEventListener('click', function () { pagina--; desenhar(); });
      proxima.addEventListener('click', function () { pagina++; desenhar(); });
      if (selecao) {
        function aplicarFiltro() {
          termoAplicado = normalizar(busca.value.trim());
          // Fotografia na aplicação explícita: desmarcar não esconde a linha em foco.
          marcadasNoFiltro = somente.checked ? new Set(registros.filter(marcada)) : null;
          grupo.querySelector('[data-tabela-selecao-alterada]').hidden = true;
          pagina = 1; desenhar();
        }
        filtrar.addEventListener('click', aplicarFiltro);
        busca.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); aplicarFiltro(); } });
        limpar.addEventListener('click', function () { busca.value = ''; somente.checked = false; aplicarFiltro(); busca.focus(); });
        corpo.addEventListener('change', function (e) {
          if (e.target.matches('input[type="checkbox"][name]')) {
            var limite = tamanho.value === 'todos' ? Math.max(1, filtrados.length) : Number(tamanho.value);
            atualizarRodape((pagina - 1) * limite, limite);
            grupo.querySelector('[data-tabela-selecao-alterada]').hidden = marcadasNoFiltro === null || !registros.some(function (r) { return marcada(r) !== marcadasNoFiltro.has(r); });
          }
        });
        grupo.querySelector('[data-tabela-filtros-selecao]').hidden = false;
        var resumo = grupo.closest('fieldset')?.querySelector('[data-tabela-resumo-inicial]');
        if (resumo) resumo.hidden = true;
      }
      grupo.querySelector('[data-tabela-rodape]').hidden = false;
      }
      grupo.querySelector('[data-tabela-controles]').hidden = false;
      desenhar();
    });
  });
})();
