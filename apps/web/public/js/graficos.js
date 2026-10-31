/* Gráficos do Gabarito360 sobre o ECharts. Cada <div data-grafico> traz os dados em JSON (docs/27). */
(function () {
  var graficos = [];

  function token(nome) {
    return getComputedStyle(document.documentElement).getPropertyValue(nome).trim();
  }

  function fonte() {
    return getComputedStyle(document.body).fontFamily;
  }

  // Números no formato brasileiro: 3,2 e 45%
  function texto(d, v) {
    if (v === null || v === undefined || v === '-') return '—';
    if (d.formato === 'nota') return Number(v).toFixed(1).replace('.', ',');
    if (d.formato === 'pct') return v + '%';
    return String(v);
  }

  function dica(d, itens) {
    var titulo = (d.prefixo || '') + itens[0].axisValue;
    return itens.reduce(function (html, i) {
      var v = i.value && typeof i.value === 'object' ? i.value.value : i.value;
      return html + '<br>' + i.marker + (d.series.length > 1 ? i.seriesName + ': ' : '') + '<b>' + texto(d, v) + '</b>';
    }, '<b>' + titulo + '</b>');
  }

  function opcoes(d) {
    var tinta = token('--fg-2'), apagado = token('--muted'), linha = token('--border-soft');
    var paleta = [1, 2, 3, 4, 5].map(function (n) { return token('--serie-' + n); });
    var varias = d.series.length > 1;
    return {
      animation: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
      animationDuration: 300,
      color: paleta,
      textStyle: { fontFamily: fonte(), color: tinta },
      grid: { left: 8, right: 8, top: varias ? 36 : 20, bottom: 8, containLabel: true },
      legend: varias ? { top: 0, left: 0, textStyle: { color: tinta }, itemWidth: 12, itemHeight: 12 } : { show: false },
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow', shadowStyle: { color: linha, opacity: 0.5 } },
        backgroundColor: token('--surface'),
        borderColor: token('--border'),
        textStyle: { color: token('--fg'), fontFamily: fonte() },
        confine: true,
        formatter: function (itens) { return dica(d, itens); }
      },
      xAxis: {
        type: 'category',
        data: d.categorias,
        axisTick: { show: false },
        axisLine: { lineStyle: { color: token('--border') } },
        axisLabel: { color: apagado, fontSize: d.intervalo === 0 ? 11 : 12, interval: d.intervalo === undefined ? 'auto' : d.intervalo, hideOverlap: d.intervalo !== 0 }
      },
      yAxis: {
        type: 'value',
        max: d.maximo === undefined ? null : d.maximo,
        minInterval: d.inteiros ? 1 : 0,
        axisLabel: { color: apagado, fontSize: 12, formatter: d.formato === 'pct' ? '{value}%' : '{value}' },
        splitLine: { lineStyle: { color: linha } }
      },
      series: d.series.map(function (s) {
        return {
          name: s.nome,
          type: 'bar',
          barMaxWidth: 40,
          barGap: '10%',
          itemStyle: { borderRadius: [4, 4, 0, 0] },
          label: {
            show: !!d.rotulos, position: 'top', color: tinta, fontSize: 12, fontWeight: 600,
            formatter: function (p) { return texto(d, p.value); }
          },
          data: s.valores.map(function (v, i) {
            var cor = s.cores && s.cores[i];
            return cor ? { value: v, itemStyle: { color: token(cor) } } : v;
          })
        };
      })
    };
  }

  // viewBox: permite que o CSS de impressão escale o desenho inteiro para a folha
  function ajustarEscala(g) {
    var svg = g.el.querySelector('svg');
    if (svg) svg.setAttribute('viewBox', '0 0 ' + g.instancia.getWidth() + ' ' + g.instancia.getHeight());
  }

  function desenhar(g) {
    g.instancia.setOption(opcoes(g.dados), true);
    ajustarEscala(g);
  }

  function iniciar() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-grafico]'), function (el) {
      var g = { el: el, dados: JSON.parse(el.getAttribute('data-grafico')), instancia: echarts.init(el, null, { renderer: 'svg' }) };
      graficos.push(g);
      desenhar(g);
    });
    if (!graficos.length) return;

    if (window.ResizeObserver) {
      var aoRedimensionar = new ResizeObserver(function () { graficos.forEach(function (g) { g.instancia.resize(); ajustarEscala(g); }); });
      graficos.forEach(function (g) { aoRedimensionar.observe(g.el); });
    }
    // Troca de tema: as cores vêm dos tokens do CSS
    new MutationObserver(function () { graficos.forEach(desenhar); })
      .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    // O SVG escala para a folha pelo CSS: redesenhar aqui cortava o gráfico em A4
    window.addEventListener('beforeprint', function () {
      graficos.forEach(function (g) { g.instancia.setOption({ animation: false }); });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
  else iniciar();
})();
