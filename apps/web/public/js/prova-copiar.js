var prova_copiarConfig = JSON.parse(document.getElementById('prova-copiar-config').textContent);

(function () {
  var modal = document.getElementById('cp1-modal'), formulario = document.getElementById('cp1-formulario');
  if (!modal || !formulario) return;
  var tentativa = prova_copiarConfig.tentativa, tituloAnterior = prova_copiarConfig.titulo, chaveAnterior = prova_copiarConfig.chave;
  function abrir(botao, recuperar) {
    var recurso=JSON.parse(botao.dataset.recurso||botao.dataset.acao);
    formulario.action=recurso.url;formulario.method='POST';formulario.dataset.recurso=JSON.stringify(recurso);
    var campo=formulario.querySelector('[name="_recurso"]');if(!campo){campo=document.createElement('input');campo.type='hidden';campo.name='_recurso';formulario.append(campo);}campo.value=recurso.referencia;
    document.getElementById('cp1-modal-origem').textContent = botao.dataset.titulo;
    document.getElementById('cp1-origem-id').value = botao.dataset.origem;
    document.getElementById('cp1-chave').value = recuperar && chaveAnterior ? chaveAnterior : botao.dataset.chave;
    document.getElementById('cp1-novo-titulo').value = recuperar && tituloAnterior !== null ? tituloAnterior : Array.from(botao.dataset.titulo).slice(0,240).join('') + ' — cópia';
    modal.showModal();
  }
  document.querySelectorAll('[data-copiar-prova]').forEach(function (botao) {
    botao.addEventListener('click', function () { abrir(botao, String(tentativa) === botao.dataset.origem); });
    if (tentativa !== null && String(tentativa) === botao.dataset.origem) abrir(botao,true);
  });
  document.getElementById('cp1-cancelar').addEventListener('click',function () { modal.close(); });
})();
