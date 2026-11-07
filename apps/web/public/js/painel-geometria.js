/* Grade manual pura: nenhuma operação compacta, ordena ou desloca vizinhos.
 * Layout: [{id,section,x,y,w,h}]. Catálogo autorizado: [{id,section,minW,maxW,minH,maxH,defaultW?,defaultH?}].
 * Aplicar resultado SOMENTE com ok=true; falhas devolvem o layout de entrada sem alterações.
 * Responsividade e persistência ficam no consumidor; este motor nunca adapta ou grava desktop.
 */
(function (root, factory) {
  'use strict';
  var api = factory();
  if (typeof module === 'object' && module.exports) module.exports = api;
  else root.G360PainelGeometria = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  'use strict';
  var COLUMNS = 12, MAX_ROWS = 512, MAX_ITEMS = 256;
  var fields = ['id', 'section', 'x', 'y', 'w', 'h'];
  function identifier(value) { return typeof value === 'string' && /^[a-zA-Z0-9][a-zA-Z0-9_.:-]{0,79}$/.test(value); }
  function integer(value, min, max) { return Number.isSafeInteger(value) && value >= min && value <= max; }
  function record(value) { return value !== null && typeof value === 'object' && !Array.isArray(value); }
  function copy(layout) { return Array.isArray(layout) ? layout.map(function (item) { return record(item) ? Object.assign({}, item) : item; }) : layout; }
  function result(ok, layout, error, changed) { return { ok: ok, layout: copy(layout), error: error || null, changed: changed || [] }; }
  function configuration(catalog, options) {
    options = options === undefined ? {} : options;
    if (!record(options) || !Array.isArray(catalog) || catalog.length > MAX_ITEMS) return null;
    var maxRows = options.maxRows === undefined ? 200 : options.maxRows;
    if (!integer(maxRows, 1, MAX_ROWS)) return null;
    var sectionRows = options.sectionRows === undefined ? {} : options.sectionRows;
    if (!record(sectionRows)) return null;
    if (Object.keys(sectionRows).some(function (id) { return !identifier(id) || !integer(sectionRows[id], 1, maxRows); })) return null;
    var widgets = new Map();
    for (var item of catalog) {
      if (!record(item) || !identifier(item.id) || !identifier(item.section) || widgets.has(item.id)) return null;
      var rows = Object.prototype.hasOwnProperty.call(sectionRows, item.section) ? sectionRows[item.section] : maxRows;
      if (!integer(item.minW, 1, COLUMNS) || !integer(item.maxW, item.minW, COLUMNS)
          || !integer(item.minH, 1, rows) || !integer(item.maxH, item.minH, rows)) return null;
      var defaultW = item.defaultW === undefined ? item.minW : item.defaultW;
      var defaultH = item.defaultH === undefined ? item.minH : item.defaultH;
      if (!integer(defaultW, item.minW, item.maxW) || !integer(defaultH, item.minH, item.maxH)) return null;
      widgets.set(item.id, Object.assign({}, item, { rows: rows, defaultW: defaultW, defaultH: defaultH }));
    }
    return widgets;
  }
  function overlaps(a, b) {
    return a.section === b.section && a.x < b.x + b.w && b.x < a.x + a.w && a.y < b.y + b.h && b.y < a.y + a.h;
  }
  function errorFor(layout, widgets) {
    if (!widgets) return 'invalid_catalog';
    if (!Array.isArray(layout) || layout.length > MAX_ITEMS) return 'invalid_layout';
    var ids = new Set();
    for (var item of layout) {
      if (!record(item) || Object.keys(item).some(function (key) { return !fields.includes(key); })
          || !identifier(item.id) || !identifier(item.section)) return 'invalid_item';
      var widget = widgets.get(item.id);
      if (!widget) return 'unauthorized_widget';
      if (ids.has(item.id)) return 'duplicate_widget';
      ids.add(item.id);
      if (item.section !== widget.section) return 'cross_section';
      if (!integer(item.w, widget.minW, widget.maxW) || !integer(item.h, widget.minH, widget.maxH)
          || !integer(item.x, 0, COLUMNS - item.w) || !integer(item.y, 0, widget.rows - item.h)) return 'out_of_bounds';
    }
    for (var i = 0; i < layout.length; i++) for (var j = i + 1; j < layout.length; j++) {
      if (overlaps(layout[i], layout[j])) return 'collision';
    }
    return null;
  }
  function validate(layout, catalog, options) {
    var error = errorFor(layout, configuration(catalog, options));
    return result(!error, layout, error);
  }
  function update(layout, catalog, options, change) {
    var widgets = configuration(catalog, options), error = errorFor(layout, widgets);
    if (error) return result(false, layout, error);
    var next = copy(layout), changed = change(next, widgets);
    if (typeof changed === 'string') return result(false, layout, changed);
    error = errorFor(next, widgets);
    return error ? result(false, layout, error) : result(true, next, null, changed);
  }
  /** targetId explícito troca as âncoras; x/y devem ser a âncora atual do alvo. */
  function move(layout, catalog, id, x, y, targetId, options) {
    return update(layout, catalog, options, function (next) {
      var source = next.find(function (item) { return item.id === id; });
      if (!source) return 'unknown_widget';
      if (!integer(x, 0, COLUMNS - 1) || !integer(y, 0, MAX_ROWS - 1)) return 'out_of_bounds';
      if (targetId !== undefined && targetId !== null) {
        var target = next.find(function (item) { return item.id === targetId; });
        if (!target || target.id === source.id) return 'invalid_target';
        if (target.section !== source.section) return 'cross_section';
        if (x !== target.x || y !== target.y) return 'invalid_target';
        var previousX = source.x, previousY = source.y;
        source.x = target.x; source.y = target.y;
        target.x = previousX; target.y = previousY;
        return [source.id, target.id];
      }
      if (source.x === x && source.y === y) return [];
      source.x = x; source.y = y;
      return [source.id];
    });
  }
  function resize(layout, catalog, id, w, h, options) {
    return update(layout, catalog, options, function (next) {
      var source = next.find(function (item) { return item.id === id; });
      if (!source) return 'unknown_widget';
      if (source.w === w && source.h === h) return [];
      source.w = w; source.h = h;
      return [id];
    });
  }
  function insert(layout, catalog, id, options) {
    return update(layout, catalog, options, function (next, widgets) {
      var widget = widgets.get(id);
      if (!widget) return 'unauthorized_widget';
      if (next.some(function (item) { return item.id === id; })) return 'duplicate_widget';
      var candidate = { id: id, section: widget.section, x: 0, y: 0, w: widget.defaultW, h: widget.defaultH };
      for (var y = 0; y <= widget.rows - candidate.h; y++) for (var x = 0; x <= COLUMNS - candidate.w; x++) {
        candidate.x = x; candidate.y = y;
        if (!next.some(function (other) { return overlaps(candidate, other); })) {
          next.push(candidate);
          return [id];
        }
      }
      return 'no_space';
    });
  }
  /** Revogar remove apenas os IDs ausentes do catálogo; buracos são preservados. */
  function removeUnauthorized(layout, catalog, options) {
    var widgets = configuration(catalog, options);
    if (!widgets) return result(false, layout, 'invalid_catalog');
    if (!Array.isArray(layout) || layout.length > MAX_ITEMS
        || layout.some(function (item) { return !record(item) || !identifier(item.id); })) return result(false, layout, 'invalid_layout');
    var next = layout.filter(function (item) { return widgets.has(item.id); });
    var error = errorFor(next, widgets);
    if (error) return result(false, layout, error);
    return result(true, next, null, layout.filter(function (item) { return !widgets.has(item.id); }).map(function (item) { return item.id; }));
  }
  /** Salvo inválido falha inteiro. O chamador decide restaurar defaults explicitamente. */
  function reconcile(saved, catalog, defaults, options) {
    var initial = saved === null || saved === undefined;
    var candidate = initial ? validate(defaults, catalog, options) : removeUnauthorized(saved, catalog, options);
    if (!candidate.ok) return result(false, initial ? defaults : saved, candidate.error);
    var next = candidate.layout, changed = candidate.changed.slice();
    for (var widget of catalog) {
      if (next.some(function (item) { return item.id === widget.id; })) continue;
      var addition = insert(next, catalog, widget.id, options);
      if (!addition.ok) return result(false, initial ? defaults : saved, addition.error);
      next = addition.layout; changed.push(widget.id);
    }
    return result(true, next, null, changed);
  }
  return Object.freeze({ columns: COLUMNS, validate: validate, move: move, resize: resize,
    insert: insert, removeUnauthorized: removeUnauthorized, reconcile: reconcile });
});
