/* Apenas coleções explicitamente integrais; não modifica URLs, formulários ou seleção. */
(function () {
  'use strict';
  var compararTexto = new Intl.Collator('pt-BR', { numeric: true, sensitivity: 'base' });
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-tabela][data-colecao-completa="true"]').forEach(function (grupo) {
      var tabela = grupo.querySelector('table.tabela-padrao');
      if (!tabela || tabela.tBodies.length !== 1 || tabela.querySelector('input[type="checkbox"], input[type="radio"], td[rowspan]')) return;
      var corpo = tabela.tBodies[0];
      var registros = Array.from(corpo.rows);
      if (!registros.length || registros.some(function (r) { return Array.from(r.cells).some(function (c) { return c.colSpan !== 1; }); })) return;
      var ordem = grupo.querySelector('[data-tabela-ordem]');
      var tamanho = grupo.querySelector('[data-tabela-tamanho]');
      var anterior = grupo.querySelector('[data-tabela-anterior]');
      var proxima = grupo.querySelector('[data-tabela-proxima]');
      var pagina = 1;
      var ordenados = registros.slice();
      var colunas = [];
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
      function desenhar() {
        var limite = tamanho.value === 'todos' ? ordenados.length : Number(tamanho.value);
        var paginas = Math.max(1, Math.ceil(ordenados.length / limite));
        pagina = Math.min(pagina, paginas);
        var inicio = (pagina - 1) * limite;
        ordenados.forEach(function (tr, i) { tr.hidden = i < inicio || i >= inicio + limite; });
        grupo.querySelector('[data-tabela-total]').textContent = (inicio + 1) + '–' + Math.min(inicio + limite, ordenados.length) + ' de ' + ordenados.length + ' registros carregados';
        grupo.querySelector('[data-tabela-pagina]').textContent = pagina + ' de ' + paginas;
        anterior.disabled = pagina === 1;
        proxima.disabled = pagina === paginas;
        // Se a página seguinte desabilitar o botão em foco, manter um alvo útil no mesmo navegador.
        if (document.activeElement === proxima && proxima.disabled && !anterior.disabled) anterior.focus();
        else if (document.activeElement === anterior && anterior.disabled && !proxima.disabled) proxima.focus();
      }
      ordem.addEventListener('change', function () {
        var partes = ordem.value.split(':');
        var coluna = Number(partes[0]);
        ordenados = registros.slice();
        Array.from(tabela.tHead.rows[0].cells).forEach(function (th) { th.removeAttribute('aria-sort'); });
        if (colunas.includes(coluna) && ['asc', 'desc'].includes(partes[1])) {
          ordenados.sort(function (a, b) {
            return compararTexto.compare(a.cells[coluna].dataset.valorOrdem ?? a.cells[coluna].textContent.trim(), b.cells[coluna].dataset.valorOrdem ?? b.cells[coluna].textContent.trim()) * (partes[1] === 'desc' ? -1 : 1) || registros.indexOf(a) - registros.indexOf(b);
          });
          tabela.tHead.rows[0].cells[coluna].setAttribute('aria-sort', partes[1] === 'asc' ? 'ascending' : 'descending');
        }
        ordenados.forEach(function (tr) { corpo.appendChild(tr); });
        pagina = 1; desenhar();
      });
      tamanho.addEventListener('change', function () { pagina = 1; desenhar(); });
      anterior.addEventListener('click', function () { pagina--; desenhar(); });
      proxima.addEventListener('click', function () { pagina++; desenhar(); });
      grupo.querySelector('[data-tabela-controles]').hidden = false;
      grupo.querySelector('[data-tabela-rodape]').hidden = false;
      desenhar();
    });
  });
})();
