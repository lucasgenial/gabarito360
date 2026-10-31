/* Aparência do Turnstile: acompanha o tema do site e o espaço do formulário. */
window.g360Turnstile = function () {
  document.querySelectorAll('.seguranca-widget').forEach(function (elemento) {
    var widget, anterior;
    function atualizar() {
      var tamanho = elemento.clientWidth < 300 ? 'compact' : 'flexible';
      var tema = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
      var estado = tamanho + ':' + tema;
      if (estado === anterior) return;
      anterior = estado;
      if (widget !== undefined) window.turnstile.remove(widget);
      widget = window.turnstile.render(elemento, {
        sitekey: elemento.dataset.sitekey,
        action: elemento.dataset.action,
        language: 'pt-br',
        theme: tema,
        size: tamanho,
      });
    }
    atualizar();
    new ResizeObserver(atualizar).observe(elemento);
    new MutationObserver(atualizar).observe(document.documentElement, {
      attributes: true, attributeFilter: ['data-theme'],
    });
  });
};
