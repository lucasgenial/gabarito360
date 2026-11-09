/* Gráficos JSON sobre o ECharts. Os gráficos específicos da turma mantêm seu renderer próprio. */
(function () {
  'use strict';
  if (window.G360Graficos) { window.G360Graficos.iniciar(); return; }
  var graficos = [], quadro = 0;
  function token(nome, el) { return getComputedStyle(el || document.documentElement).getPropertyValue(nome).trim(); }
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
    if (d.numeradores && d.numeradores[itens[0].dataIndex] !== undefined) linhas.push((d.rotulo_numerador || 'Quantidade') + ': ' + d.numeradores[itens[0].dataIndex]);
    if (d.bases && d.bases[itens[0].dataIndex] !== undefined) linhas.push('Base: ' + d.bases[itens[0].dataIndex] + ' ' + (d.unidade_base || 'registros'));
    return linhas.join('\n');
  }
  function opcoes(g) {
    var localToken = function(nome) { return token(nome, g.el); };
    var d = g.dados, tinta = localToken('--fg-2') || localToken('--fg'), apagado = localToken('--muted'), linha = localToken('--border-soft');
    var varias = d.series.length > 1, widget = g.el.closest('[data-widget-id]'), orientacao = widget && widget.dataset.orientacao;
    var horizontal = orientacao === 'horizontal' || (orientacao !== 'vertical' && (!!d.horizontal || (g.el.clientWidth < 420 && d.categorias.length > 2)));
    var nomesPorWidget = {
      'cadastros-turno':['Turno','Turmas'], 'comparacao-turmas':['Turma','Acertos (%)'],
      'cobertura-disciplinas':['Disciplina','Aplicações (%)'], 'periodos-escolares':['Período escolar','Acertos (%)'],
      'habilidades':['Habilidade','Acertos (%)'], 'reavaliacoes':['Grupo','Acertos (%)']
    };
    var nomes = nomesPorWidget[widget && widget.dataset.widgetId] || [d.eixo_categoria || 'Categoria',d.eixo_valor || (d.formato === 'pct' ? 'Percentual (%)' : d.formato === 'nota' ? 'Nota' : 'Quantidade')];
    var valor = { type: 'value', interval: d.formato === 'pct' && g.el.clientWidth < 420 ? 50 : undefined, max: d.maximo === undefined ? null : d.maximo, minInterval: d.inteiros ? 1 : 0,
      axisLabel: { color: apagado, fontSize: 11, formatter: d.formato === 'pct' ? '{value}%' : '{value}' }, splitLine: { lineStyle: { color: linha } } };
    var categorias = { type: 'category', data: d.categorias, inverse: horizontal,
      axisTick: { show: false }, axisLine: { lineStyle: { color: localToken('--border') } },
      axisLabel: { color: apagado, fontSize: 11, formatter: function(v,i){ return d.rotulos_curtos && d.rotulos_curtos[i] !== undefined ? d.rotulos_curtos[i] : v; }, width: horizontal ? (g.el.clientWidth < 420 ? 86 : 140) : Math.max(45,Math.floor((g.el.clientWidth-32)/Math.max(1,d.categorias.length))-12),
        overflow: 'truncate', interval: horizontal ? 0 : (d.intervalo === undefined ? 'auto' : d.intervalo), hideOverlap: !horizontal && d.intervalo !== 0 } };
    valor.name = nomes[1]; valor.nameLocation = 'middle'; valor.nameGap = horizontal ? 28 : 38; valor.nameRotate = horizontal ? 0 : 90;
    var medida = document.createElement('canvas').getContext('2d'); medida.font = '11px ' + fonte();
    var larguraCategorias = Math.max.apply(null, d.categorias.map(function(v,i){return medida.measureText(String(d.rotulos_curtos && d.rotulos_curtos[i] !== undefined ? d.rotulos_curtos[i] : v)).width;}).concat([0]));
    categorias.name = nomes[0]; categorias.nameLocation = 'middle'; categorias.nameGap = horizontal ? Math.min(g.el.clientWidth < 420 ? 86 : 140,larguraCategorias) + 20 : 32; categorias.nameRotate = horizontal ? 90 : 0;
    [valor,categorias].forEach(function(e){ e.nameTextStyle = {color: tinta,fontSize:11,fontFamily:fonte()}; });
    return {
      animation: !window.matchMedia('(prefers-reduced-motion: reduce)').matches, animationDuration: 250,
      color: [1, 2, 3, 4, 5].map(function (n) { return localToken('--painel-serie-' + n) || localToken('--serie-' + n); }),
      textStyle: { fontFamily: fonte(), color: tinta },
      aria: { enabled: true, decal: { show: varias }, label: { description: g.descricao + '. Os valores completos estão na alternativa textual acessível.' } },
      grid: { left: 40, right: horizontal ? 42 : 12, top: 24, bottom: varias ? 78 : 48, containLabel: true },
      legend: varias ? { bottom: 0, left: 0, type: 'plain', textStyle: { color: tinta }, itemWidth: 12, itemHeight: 12 } : { show: false },
      tooltip: { trigger: 'axis', renderMode: 'richText', confine: true, axisPointer: { type: 'shadow' },
        backgroundColor: localToken('--surface'), borderColor: localToken('--border'), textStyle: { color: localToken('--fg'), fontFamily: fonte() },
        formatter: function (itens) { return dica(d, Array.isArray(itens) ? itens : [itens]); } },
      xAxis: horizontal ? valor : categorias, yAxis: horizontal ? categorias : valor,
      series: d.series.map(function (s) {
        return { name: s.nome, type: 'bar', barMaxWidth: varias ? 22 : 36, barGap: '15%', labelLayout: { hideOverlap: true },
          itemStyle: { borderRadius: horizontal ? [0, 4, 4, 0] : [4, 4, 0, 0] },
          label: { show: !!d.rotulos, position: horizontal ? 'right' : 'top', color: tinta, fontSize: 11, fontWeight: 600, padding: [0, 3], backgroundColor: 'transparent',
            formatter: function (p) { return texto(d, p.value); } },
          data: s.valores.map(function (v, i) { var cor = s.cores && s.cores[i]; return cor ? { value: v, itemStyle: { color: localToken(cor) } } : v; }) };
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
        if (!g.el.isConnected) { if(g.el.closest('[data-editor-estacionado="true"]'))return true;if (g.instancia) g.instancia.dispose(); if (resize) resize.unobserve(g.el); return false; }
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
      if(!el.dataset.graficoDescricaoBase)el.dataset.graficoDescricaoBase=(el.getAttribute('aria-label') || 'Gráfico').replace(/[.\s]+$/g, '');
      var g = { el: el, dados: d, descricao: el.dataset.graficoDescricaoBase, instancia: null, reconstruir: true }; graficos.push(g);
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
  document.addEventListener('g360:painel-redimensionado', function () { agendar(true); });
  document.addEventListener('g360:painel-orientacao', function () { agendar(true); });
  window.addEventListener('resize', function () { agendar(false); });
  window.addEventListener('beforeprint', function () { graficos.forEach(function (g) { if (g.instancia) g.instancia.setOption({ animation: false }); }); });
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
})();
