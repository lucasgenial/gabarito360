(function () {
  'use strict';
  var form = document.getElementById('catalogo-busca');
  if (!form) return;
  var uf = document.getElementById('cat-uf'), municipio = document.getElementById('cat-municipio');
  var busca = document.getElementById('cat-municipio-busca'), status = document.getElementById('cat-status');
  var anterior = document.getElementById('cat-anterior'), proximo = document.getElementById('cat-proximo');
  var ferramentas = document.getElementById('cat-ferramentas');
  var sequencia = 0, pagina = 1;
  function invalidarResultados(e) {
    if (!e.target.name || e.target.name === '_ctx') return;
    var resultados = document.getElementById('cat-resultados');
    if (resultados) {
      resultados.hidden = true;
      document.getElementById('cat-resultados-status').textContent = 'Filtros alterados. Clique em Buscar instituições para atualizar os resultados.';
    }
  }
  form.addEventListener('input', invalidarResultados);
  form.addEventListener('change', invalidarResultados);
  function limpar() { municipio.replaceChildren(new Option('Todos os municípios da UF', '')); }
  async function carregar(p) {
    var seq = ++sequencia, selecionado = municipio.value;
    pagina = p; anterior.hidden = true; proximo.hidden = true;
    if (ferramentas) ferramentas.hidden = !uf.value;
    if (!uf.value) { limpar(); municipio.disabled = true; status.textContent = 'Escolha uma UF para localizar municípios.'; return; }
    municipio.disabled = true; status.textContent = 'Carregando municípios…';
    var recurso=JSON.parse(form.dataset.municipios),dados={uf:uf.value,busca:busca.value.trim(),page:p};
    try {
      var resposta = await G360Requisicao(recurso, {body:JSON.stringify(dados),headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'});
      if (!resposta.ok) throw new Error('indisponivel');
      var dados = await resposta.json();
      if (seq !== sequencia) return;
      limpar();
      dados.itens.forEach(function (m) { municipio.add(new Option(m.nome, m.codigo_ibge)); });
      if (dados.itens.some(function (m) { return m.codigo_ibge === selecionado; })) municipio.value = selecionado;
      municipio.disabled = false;
      status.textContent = dados.paginacao.total ? 'Página ' + p + ' de ' + dados.paginacao.ultima + ' · ' + dados.paginacao.total + ' município(s)' : 'Nenhum município com este nome nesta UF.';
      anterior.hidden = p <= 1; proximo.hidden = p >= dados.paginacao.ultima;
    } catch (e) {
      if (seq !== sequencia) return;
      status.textContent = 'Não foi possível carregar os municípios. Clique em Localizar para tentar novamente.';
      municipio.disabled = false;
    }
  }
  uf.addEventListener('change', function () { limpar(); busca.value = ''; carregar(1); });
  document.getElementById('cat-carregar').addEventListener('click', function () { carregar(1); });
  busca.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); carregar(1); } });
  anterior.addEventListener('click', function () { carregar(pagina - 1); });
  proximo.addEventListener('click', function () { carregar(pagina + 1); });
  if (uf.value && !municipio.value) carregar(1);
  var invalido = form.querySelector ? form.querySelector('[aria-invalid="true"]') : null;
  if (invalido) {
    var pai = invalido.parentElement;
    while (pai && pai !== form) { if (pai.tagName === 'DETAILS') pai.open = true; pai = pai.parentElement; }
    invalido.focus();
  }
})();
