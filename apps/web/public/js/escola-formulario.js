var escola_formularioConfig = JSON.parse(document.getElementById('escola-formulario-config').textContent);

(function () {
  var $ = function (id) { return document.getElementById(id); };
  var aviso = function (tipo, html) { $('censo-aviso').innerHTML = html ? '<div class="aviso ' + tipo + '">' + html + '</div>' : ''; };
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };

  // Vínculo: escola sede só para anexo/unidade vinculada; sedes do núcleo escolhido
  function atualizarSede() {
    if (!$('tipo')) return;
    var sede = $('tipo').value !== 'sede';
    $('campo-sede').hidden = !sede;
    $('escola_sede_id').required = sede;
    var nucleo = $('nucleo_id').value;
    Array.prototype.forEach.call($('escola_sede_id').options, function (o) {
      if (o.value) o.hidden = !!nucleo && o.dataset.nucleo !== nucleo;
    });
  }
  if ($('tipo')) {
    $('tipo').addEventListener('change', atualizarSede);
    $('nucleo_id').addEventListener('change', atualizarSede);
    // Escolher a sede leva o núcleo junto
    $('escola_sede_id').addEventListener('change', function () {
      var o = this.selectedOptions[0];
      if (o && o.dataset.nucleo) { $('nucleo_id').value = o.dataset.nucleo; }
    });
    atualizarSede();
  }

  if (!$('btn-inep')) return;

  function preencher(d) {
    var e = d.escola;
    ['nome', 'tipo_rede', 'situacao_funcionamento', 'localizacao', 'orgao_regional', 'logradouro', 'numero', 'complemento',
     'bairro', 'cep', 'cidade', 'uf', 'co_municipio', 'telefone', 'latitude', 'longitude'].forEach(function (c) {
      if ($(c) && e[c] != null && e[c] !== '') $(c).value = e[c];
    });
    var nucleo = '';
    if (d.nucleo_sugerido) { nucleo = d.nucleo_sugerido.id; }
    else if (e.orgao_regional) {
      var op = Array.prototype.find.call($('nucleo_id').options, function (o) { return o.dataset.orgao === e.orgao_regional; });
      if (op) nucleo = op.value;
    }
    if (nucleo) { $('nucleo_id').value = nucleo; atualizarSede(); }
    var msg = '<b>Dados do Censo Escolar ' + esc(e.ano_censo) + ' preenchidos.</b> Confira e salve.';
    // Anexo no Censo ("ANEXO - …"): vínculo de anexo e a sede, se ela já estiver cadastrada
    if (e.tipo === 'anexo' && $('tipo')) {
      $('tipo').value = 'anexo';
      if (d.sede_sugerida && d.sede_sugerida.id) {
        $('escola_sede_id').value = d.sede_sugerida.id;
        if (d.sede_sugerida.nucleo_id) $('nucleo_id').value = d.sede_sugerida.nucleo_id;
        msg += '<br>No Censo esta escola é <b>anexo</b> de ' + esc(d.sede_sugerida.nome) + ' — vínculo já marcado.';
      } else if (d.sede_sugerida) {
        msg += '<br>No Censo esta escola é <b>anexo</b> de ' + esc(d.sede_sugerida.nome) + ' (INEP ' + esc(d.sede_sugerida.inep) + '), que ainda não está cadastrada. Cadastre a sede primeiro.';
      }
      atualizarSede();
    }
    if (e.orgao_regional) msg += ' Órgão regional (NTE) no Censo: <b>' + esc(e.orgao_regional) + '</b>' + (nucleo ? '' : ' — escolha o núcleo.');
    if (e.situacao_funcionamento && e.situacao_funcionamento !== 'ativa') msg += '<br>Atenção: no Censo a escola está <b>' + esc(e.situacao_funcionamento) + '</b>.';
    if (d.ja_cadastrada && String(d.ja_cadastrada.id) !== String(escola_formularioConfig.id??'')) {
      aviso('erro', 'Esta escola já está cadastrada no Gabarito360: <button type="button" class="btn btn-ghost" data-censo-escola>' + esc(d.ja_cadastrada.nome) + '</button>.');
      $('censo-aviso').querySelector('[data-censo-escola]').addEventListener('click',()=>G360Navegacao.abrir(d.ja_cadastrada._recursos.abrir));
    } else {
      aviso('ok', msg);
    }
  }

  function buscarInep() {
    var inep = $('inep').value.replace(/\D/g, '');
    $('inep').value = inep;
    if (inep.length !== 8) { aviso('erro', 'O código INEP tem 8 dígitos.'); return; }
    aviso('info', 'Buscando no Censo Escolar…');
    G360Requisicao(escola_formularioConfig.buscar, { body:JSON.stringify({inep:inep}), headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (x) { if (x.ok) preencher(x.j.data); else aviso('erro', esc(x.j.message || 'Não encontrado.')); })
      .catch(function () { aviso('erro', 'Não foi possível consultar o Censo agora. Preencha os dados à mão.'); });
  }
  $('btn-inep').addEventListener('click', buscarInep);
  $('inep').addEventListener('keydown', function (ev) { if (ev.key === 'Enter') { ev.preventDefault(); buscarInep(); } });
  $('inep').addEventListener('input', function () { if (this.value.replace(/\D/g, '').length === 8) buscarInep(); });

  function buscarNome() {
    var q = $('censo-nome').value.trim(), ul = $('censo-res');
    if (q.length < 3) { ul.hidden = false; ul.innerHTML = '<li style="padding:10px 12px;">Digite pelo menos 3 letras.</li>'; return; }
    ul.hidden = false; ul.innerHTML = '<li style="padding:10px 12px;">Procurando…</li>';
    G360Requisicao(escola_formularioConfig.pesquisar, { body:JSON.stringify({busca:q}), headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        var l = (j.data && j.data.escolas) || [];
        ul.innerHTML = l.length ? l.map(function (s) {
          return '<li><button type="button" data-inep="' + esc(s.inep) + '"><b>' + esc(s.nome) + '</b><small>INEP ' + esc(s.inep) + ' · ' + esc(s.municipio) + ' · ' + esc(s.esfera || '') + (s.situacao && s.situacao !== 'ativa' ? ' · ' + esc(s.situacao) : '') + '</small></button></li>';
        }).join('') : '<li style="padding:10px 12px;">Nenhuma escola encontrada com esse nome.</li>';
      })
      .catch(function () { ul.innerHTML = '<li style="padding:10px 12px;">Não foi possível consultar agora.</li>'; });
  }
  $('btn-nome').addEventListener('click', buscarNome);
  $('censo-nome').addEventListener('keydown', function (ev) { if (ev.key === 'Enter') { ev.preventDefault(); buscarNome(); } });
  $('censo-res').addEventListener('click', function (ev) {
    var b = ev.target.closest('button[data-inep]');
    if (!b) return;
    $('inep').value = b.dataset.inep;
    this.hidden = true;
    buscarInep();
    $('inep').scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
})();
