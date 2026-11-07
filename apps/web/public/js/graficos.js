/* Gráficos JSON sobre o ECharts. Os gráficos específicos da turma mantêm seu renderer próprio. */
(function () {
  'use strict';
  if (window.G360Graficos) { window.G360Graficos.iniciar(); return; }
  var graficos = [], quadro = 0;
  function token(nome) { return getComputedStyle(document.documentElement).getPropertyValue(nome).trim(); }
  function fonte() { return getComputedStyle(document.body).fontFamily; }
  function texto(d, v) {
    if (v === null || v === undefined || v === '-') return '—';
    var n = Number(v);
    if (!Number.isFinite(n)) return String(v);
    return n.toLocaleString('pt-BR', { maximumFractionDigits: d.formato === 'nota' ? 1 : 2, minimumFractionDigits: d.formato === 'nota' ? 1 : 0 }) + (d.formato === 'pct' ? '%' : '');
  }
  function dica(d, itens) {
    if (!itens.length) return '';
    var linhas = [String(d.prefixo || '') + String(itens[0].axisValue)];
    itens.forEach(function (i) {
      var v = i.value && typeof i.value === 'object' ? i.value.value : i.value;
      linhas.push((d.series.length > 1 ? i.seriesName + ': ' : '') + texto(d, v));
    });
    if (d.bases && d.bases[itens[0].dataIndex] !== undefined) linhas.push('Base: ' + d.bases[itens[0].dataIndex] + ' ' + (d.unidade_base || 'registros'));
    return linhas.join('\n');
  }
  function opcoes(g) {
    var d = g.dados, tinta = token('--fg-2') || token('--fg'), apagado = token('--muted'), linha = token('--border-soft');
    var varias = d.series.length > 1, horizontal = !!d.horizontal || (g.el.clientWidth < 420 && d.categorias.length > 2);
    var valor = { type: 'value', interval: d.formato === 'pct' && g.el.clientWidth < 420 ? 50 : undefined, max: d.maximo === undefined ? null : d.maximo, minInterval: d.inteiros ? 1 : 0,
      axisLabel: { color: apagado, fontSize: 11, formatter: d.formato === 'pct' ? '{value}%' : '{value}' }, splitLine: { lineStyle: { color: linha } } };
    var categorias = { type: 'category', data: d.categorias, inverse: horizontal,
      axisTick: { show: false }, axisLine: { lineStyle: { color: token('--border') } },
      axisLabel: { color: apagado, fontSize: 11, formatter: function(v,i){ return d.rotulos_curtos && d.rotulos_curtos[i] !== undefined ? d.rotulos_curtos[i] : v; }, width: horizontal ? (g.el.clientWidth < 420 ? 86 : 140) : undefined,
        overflow: horizontal ? 'truncate' : undefined, interval: horizontal ? 0 : (d.intervalo === undefined ? 'auto' : d.intervalo), hideOverlap: !horizontal && d.intervalo !== 0 } };
    return {
      animation: !window.matchMedia('(prefers-reduced-motion: reduce)').matches, animationDuration: 250,
      color: [1, 2, 3, 4, 5].map(function (n) { return token('--serie-' + n); }),
      textStyle: { fontFamily: fonte(), color: tinta },
      aria: { enabled: true, decal: { show: varias }, label: { description: g.descricao + '. Os valores completos estão na alternativa textual acessível.' } },
      grid: { left: 4, right: horizontal ? 42 : 8, top: 16, bottom: varias ? 48 : 8, containLabel: true },
      legend: varias ? { bottom: 0, left: 0, type: 'plain', textStyle: { color: tinta }, itemWidth: 12, itemHeight: 12 } : { show: false },
      tooltip: { trigger: 'axis', renderMode: 'richText', confine: true, axisPointer: { type: 'shadow' },
        backgroundColor: token('--surface'), borderColor: token('--border'), textStyle: { color: token('--fg'), fontFamily: fonte() },
        formatter: function (itens) { return dica(d, Array.isArray(itens) ? itens : [itens]); } },
      xAxis: horizontal ? valor : categorias, yAxis: horizontal ? categorias : valor,
      series: d.series.map(function (s) {
        return { name: s.nome, type: 'bar', barMaxWidth: varias ? 22 : 36, barGap: '15%',
          itemStyle: { borderRadius: horizontal ? [0, 4, 4, 0] : [4, 4, 0, 0] },
          label: { show: !!d.rotulos, position: horizontal ? 'right' : 'top', color: tinta, fontSize: 11, fontWeight: 600,
            formatter: function (p) { return texto(d, p.value); } },
          data: s.valores.map(function (v, i) { var cor = s.cores && s.cores[i]; return cor ? { value: v, itemStyle: { color: token(cor) } } : v; }) };
      })
    };
  }
  function ajustarEscala(g) {
    var svg = g.el.querySelector('svg');
    if (svg && g.instancia) svg.setAttribute('viewBox', '0 0 ' + g.instancia.getWidth() + ' ' + g.instancia.getHeight());
  }
  function desenhar(g, completo) {
    if (!g.el.isConnected || !g.el.clientWidth || !g.el.clientHeight || !window.echarts) return;
    if (!g.instancia) { g.instancia = window.echarts.getInstanceByDom(g.el) || window.echarts.init(g.el, null, { renderer: 'svg' }); completo = true; }
    var estreito = g.el.clientWidth < 420;
    if (completo || estreito !== g.estreito) { g.instancia.setOption(opcoes(g), true); g.estreito = estreito; }
    g.instancia.resize(); ajustarEscala(g);
  }
  function agendar(completo) {
    if (completo) graficos.forEach(function (g) { g.reconstruir = true; });
    if (quadro) return;
    quadro = requestAnimationFrame(function () {
      quadro = 0;
      graficos = graficos.filter(function (g) {
        if (!g.el.isConnected) { if (g.instancia) g.instancia.dispose(); if (resize) resize.unobserve(g.el); return false; }
        desenhar(g, g.reconstruir); g.reconstruir = false; return true;
      });
    });
  }
  var resize = window.ResizeObserver ? new ResizeObserver(function () { agendar(false); }) : null;
  function iniciar() {
    if (!window.echarts) return;
    document.querySelectorAll('.grafico[data-grafico]').forEach(function (el) {
      if (graficos.some(function (g) { return g.el === el; })) return;
      var d;
      try { d = JSON.parse(el.getAttribute('data-grafico')); } catch (_) { return; }
      if (!d || !Array.isArray(d.categorias) || !Array.isArray(d.series) || !d.series.every(function (s) { return Array.isArray(s.valores); })) return;
      var g = { el: el, dados: d, descricao: (el.getAttribute('aria-label') || 'Gráfico').replace(/[.\s]+$/g, ''), instancia: null, reconstruir: true }; graficos.push(g);
      if (resize) resize.observe(el);
    });
    agendar(false);
  }
  window.G360Graficos = { iniciar: iniciar };
  new MutationObserver(function () { agendar(true); }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'data-tema'] });
  // Abrir uma aba, mudar disposição ou inserir conteúdo torna elegível um gráfico antes oculto.
  new MutationObserver(function (mudancas) {
    if (mudancas.some(function (m) { return m.type === 'childList' && Array.from(m.addedNodes).some(function (n) { return n.nodeType === 1 && (n.matches('.grafico[data-grafico]') || n.querySelector('.grafico[data-grafico]')); }); })) iniciar();
    if (mudancas.some(function (m) { return m.type === 'attributes' && m.attributeName === 'hidden'; })) agendar(false);
  }).observe(document.documentElement, { subtree: true, childList: true, attributes: true, attributeFilter: ['hidden'] });
  document.addEventListener('g360:painel-redimensionado', function () { agendar(false); });
  window.addEventListener('resize', function () { agendar(false); });
  window.addEventListener('beforeprint', function () { graficos.forEach(function (g) { if (g.instancia) g.instancia.setOption({ animation: false }); }); });
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
})();
