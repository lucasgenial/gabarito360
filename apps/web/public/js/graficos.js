/* Gráficos do Gabarito360 sobre o ECharts. Cada <div data-grafico> traz os dados em JSON (docs/27). */
(function () {
  var graficos = [];

  function token(nome) {
    return getComputedStyle(document.documentElement).getPropertyValue(nome).trim();
  }

  function fonte() {
    return getComputedStyle(document.body).fontFamily;
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
        confine: true
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
        axisLabel: { color: apagado, fontSize: 12 },
        splitLine: { lineStyle: { color: linha } }
      },
      series: d.series.map(function (s) {
        return {
          name: s.nome,
          type: 'bar',
          barMaxWidth: 40,
          barGap: '10%',
          itemStyle: { borderRadius: [4, 4, 0, 0] },
          label: { show: !!d.rotulos, position: 'top', color: tinta, fontSize: 12, fontWeight: 600 },
          data: s.valores.map(function (v, i) {
            var cor = s.cores && s.cores[i];
            return cor ? { value: v, itemStyle: { color: token(cor) } } : v;
          })
        };
      })
    };
  }

  function desenhar(g) {
    g.instancia.setOption(opcoes(g.dados), true);
  }

  function iniciar() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-grafico]'), function (el) {
      var g = { el: el, dados: JSON.parse(el.getAttribute('data-grafico')), instancia: echarts.init(el, null, { renderer: 'svg' }) };
      graficos.push(g);
      desenhar(g);
    });
    if (!graficos.length) return;

    if (window.ResizeObserver) {
      var aoRedimensionar = new ResizeObserver(function () { graficos.forEach(function (g) { g.instancia.resize(); }); });
      graficos.forEach(function (g) { aoRedimensionar.observe(g.el); });
    }
    // Troca de tema: as cores vêm dos tokens do CSS
    new MutationObserver(function () { graficos.forEach(desenhar); })
      .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    // A4 sem animação e na largura da folha impressa
    window.addEventListener('beforeprint', function () {
      graficos.forEach(function (g) {
        g.instancia.setOption({ animation: false });
        g.instancia.resize({ width: g.el.clientWidth < 680 ? g.el.clientWidth : 680, animation: { duration: 0 } });
      });
    });
    window.addEventListener('afterprint', function () {
      graficos.forEach(function (g) { g.instancia.resize({ width: 'auto' }); });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
  else iniciar();
})();
