/* TBL1: coleções integrais; seleção é opt-in e preserva os inputs de todas as páginas. */
(function () {
  'use strict';
  var compararTexto = new Intl.Collator('pt-BR', { numeric: true, sensitivity: 'base' });
  function normalizar(texto) { return String(texto).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR'); }
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-tabela][data-colecao-completa="true"]').forEach(function (grupo) {
      var selecao = grupo.dataset.modo === 'selecao';
      var tabela = grupo.querySelector('table.tabela-padrao');
      if (!tabela || tabela.tBodies.length !== 1 || tabela.querySelector('td[rowspan]') ||
          (!selecao && tabela.querySelector('input[type="checkbox"], input[type="radio"]'))) return;
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
      Array.from(tabela.tHead.rows[0].cells).forEach(function (th, i) {
        if (th.dataset.ordenar !== 'texto') return;
        colunas.push(i);
        ['asc', 'desc'].forEach(function (direcao) {
          var opcao = document.createElement('option');
          opcao.value = i + ':' + direcao;
          opcao.textContent = th.textContent.trim() + (direcao === 'asc' ? ' (A–Z)' : ' (Z–A)');
          ordem.appendChild(opcao);
        });
      });
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
      ordem.addEventListener('change', function () {
        var partes = ordem.value.split(':'), coluna = Number(partes[0]);
        ordenados = registros.slice();
        Array.from(tabela.tHead.rows[0].cells).forEach(function (th) { th.removeAttribute('aria-sort'); });
        if (colunas.includes(coluna) && ['asc', 'desc'].includes(partes[1])) {
          ordenados.sort(function (a, b) {
            return compararTexto.compare(a.cells[coluna].dataset.valorOrdem ?? a.cells[coluna].textContent.trim(), b.cells[coluna].dataset.valorOrdem ?? b.cells[coluna].textContent.trim()) * (partes[1] === 'desc' ? -1 : 1) || indicesOriginais.get(a) - indicesOriginais.get(b);
          });
          tabela.tHead.rows[0].cells[coluna].setAttribute('aria-sort', partes[1] === 'asc' ? 'ascending' : 'descending');
        }
        ordenados.forEach(function (tr) { corpo.appendChild(tr); });
        pagina = 1; desenhar();
      });
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
      grupo.querySelector('[data-tabela-controles]').hidden = false;
      grupo.querySelector('[data-tabela-rodape]').hidden = false;
      desenhar();
    });
  });
})();
