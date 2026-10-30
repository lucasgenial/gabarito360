{{--
  Arquivo: apps/web/resources/views/correcao/_camera.blade.php
  Função: Correção pela câmera do celular (OMR nativo, doc 18): fotografa/envia cartões
          e mostra aluno e nota de cada envio. Usado no acompanhamento da prova e no
          modo só-câmera (aplicador). Espera: $prova e $alunos (vazio = sem vincular;
          a API não devolve nota ao aplicador).
--}}
{{-- Correção pela câmera do celular (OMR nativo) --}}
<div class="card card-pad" id="camera" style="margin-top:16px;scroll-margin-top:90px;">
  <div style="display:flex;flex-wrap:wrap;gap:10px;">
    <label for="cam-input" class="btn btn-primary" style="flex:1 1 220px;justify-content:center;padding:16px;font-size:16px;cursor:pointer;">📷 Fotografar cartão</label>
    <input id="cam-input" type="file" accept="image/*" capture="environment" style="display:none;">
    <label for="lote-input" class="btn btn-secondary" style="flex:1 1 220px;justify-content:center;padding:16px;cursor:pointer;">Enviar várias fotos</label>
    <input id="lote-input" type="file" accept="image/*" multiple style="display:none;">
  </div>
  <p style="font-size:12px;color:var(--muted);margin-top:10px;">Folha sobre a mesa, boa luz, os <b>4 quadrados pretos</b> dos cantos dentro da foto. O aluno é identificado pelo QR do cartão.</p>
  <ul id="envios" style="list-style:none;padding:0;margin:8px 0 0;display:flex;flex-direction:column;gap:6px;"></ul>
</div>

@push('scripts')
<script>
// ---------- Correção pela câmera ----------
var UPLOAD_URL = '{{ route('correcao.upload', $prova['id']) }}';
var CAM_CARTOES_URL = '{{ url('cartoes') }}';
var CAM_CSRF = '{{ csrf_token() }}';
function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
var ALUNOS = {!! json_encode(collect($alunos)->map(function ($a) { return ['id' => $a['id'], 'nome' => $a['nome'], 'turma' => $a['turma_nome'] ?? '']; })->values(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
function reduzirFoto(file) {
  return new Promise(function (ok) {
    if (!window.createImageBitmap) return ok(file);
    createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (img) {
      var s = Math.min(1, 2400 / Math.max(img.width, img.height));
      var c = document.createElement('canvas');
      c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      c.toBlob(function (b) { ok(b ? new File([b], 'cartao.jpg', { type: 'image/jpeg' }) : file); }, 'image/jpeg', 0.9);
    }).catch(function () { ok(file); });
  });
}
function linhaEnvio(txt) {
  var li = document.createElement('li');
  li.style.cssText = 'padding:10px 12px;border:1px solid var(--border-soft);border-radius:8px;font-size:14px;';
  li.innerHTML = txt;
  document.getElementById('envios').prepend(li);
  return li;
}
function seletorAluno(cartaoId) {
  var opts = ALUNOS.map(function (a) { return '<option value="' + a.id + '">' + esc(a.turma) + ' · ' + esc(a.nome) + '</option>'; }).join('');
  return '<div style="margin-top:6px;display:flex;gap:6px;"><select class="select" id="vinc-' + cartaoId + '" style="flex:1;"><option value="">Escolha o aluno…</option>' + opts + '</select>' +
    '<button class="btn btn-sm btn-primary" onclick="vincular(' + cartaoId + ')">Vincular</button></div>';
}
function vincular(cartaoId) {
  var sel = document.getElementById('vinc-' + cartaoId);
  if (!sel.value) return;
  fetch(CAM_CARTOES_URL + '/' + cartaoId + '/vincular-aluno', {
    method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CAM_CSRF },
    body: JSON.stringify({ aluno_id: parseInt(sel.value) })
  }).then(function (r) { return r.json(); }).then(function () {
    sel.parentNode.innerHTML = '<span class="badge badge-success">Vinculado a ' + esc(sel.options[sel.selectedIndex].text) + '</span>';
    if (typeof atualizar === 'function') atualizar();
  });
}
function enviarCartao(file) {
  var li = linhaEnvio('⏳ Lendo cartão…');
  return reduzirFoto(file).then(function (f) {
    var fd = new FormData(); fd.append('imagem', f); fd.append('_token', CAM_CSRF);
    return fetch(UPLOAD_URL, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
  }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
    .then(function (res) {
      var d = res.j;
      if (!res.ok) { li.innerHTML = '⚠️ <b>Erro:</b> ' + esc(d.erro || 'falha no envio'); return; }
      var quem = d.aluno_nome ? '<b>' + esc(d.aluno_nome) + '</b>' : '<b style="color:var(--danger)">Aluno não identificado</b>';
      var nota = d.acertos !== null && d.acertos !== undefined ? ' — <b>' + d.acertos + '/' + d.total + '</b> acertos · nota ' + Number(d.nota_final).toFixed(1).replace('.', ',') : '';
      var amb = d.ambiguas ? ' · <span style="color:var(--warning,#c27c0e)">' + d.ambiguas + ' questão(ões) para revisar abaixo</span>' : '';
      var lg = d.lingua ? ' · ' + (d.lingua === 'ESP' ? 'Espanhol' : 'Inglês') : '';
      li.innerHTML = (d.aluno_nome && !d.ambiguas ? '✅ ' : '📝 ') + quem + nota + lg + amb + (d.aluno_nome ? '' : (ALUNOS.length ? seletorAluno(d.id) : '<div style="font-size:13px;color:var(--muted);margin-top:4px;">QR ilegível ou cartão em branco: o professor vincula o aluno depois.</div>'));
      if (typeof atualizar === 'function') atualizar();
    }).catch(function (e) { li.innerHTML = '⚠️ <b>Erro de rede:</b> ' + e.message; });
}
document.getElementById('cam-input').addEventListener('change', function (e) {
  Array.prototype.forEach.call(e.target.files, enviarCartao); e.target.value = '';
});
document.getElementById('lote-input').addEventListener('change', function (e) {
  var fila = Array.prototype.slice.call(e.target.files); e.target.value = '';
  (function prox() { var f = fila.shift(); if (f) enviarCartao(f).then(prox); })();
});

</script>
@endpush
