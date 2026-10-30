{{--
  Arquivo: apps/web/resources/views/correcao/_camera.blade.php
  Função: Correção pela câmera do celular (OMR nativo, doc 18). Abre um visor ao vivo com
          as guias dos 4 quadrados pretos do cartão; cada canto fica verde quando o
          quadrado está no lugar e, com os 4 verdes e a mão firme, a foto é tirada sozinha.
          Sem câmera ao vivo (navegador antigo/permissão negada) cai na câmera do sistema.
          Usado no acompanhamento da prova e no modo só-câmera (aplicador).
          Espera: $prova e $alunos (vazio = sem vincular; a API não devolve nota ao aplicador).
--}}
<style>
  .cam-tela { position:fixed; inset:0; z-index:1000; background:#000; display:flex; flex-direction:column; touch-action:none; }
  .cam-tela[hidden] { display:none; }
  .cam-tela canvas { flex:1; width:100%; min-height:0; display:block; }
  .cam-topo { position:absolute; top:0; left:0; right:0; display:flex; align-items:center; gap:10px; padding:12px 14px;
    background:linear-gradient(rgba(0,0,0,.65), transparent); color:#fff; font-size:15px; font-weight:600; }
  .cam-topo span { flex:1; text-shadow:0 1px 2px #000; }
  .cam-btn-redondo { width:44px; height:44px; border-radius:50%; border:0; background:rgba(255,255,255,.18); color:#fff; font-size:20px; cursor:pointer; }
  .cam-base { position:absolute; left:0; right:0; bottom:0; display:flex; align-items:center; justify-content:space-around;
    padding:16px 14px 22px; background:linear-gradient(transparent, rgba(0,0,0,.7)); color:#fff; font-size:13px; }
  .cam-disparo { width:74px; height:74px; border-radius:50%; border:5px solid #fff; background:rgba(255,255,255,.25); cursor:pointer; }
  .cam-disparo:active { background:#fff; }
  .cam-auto { display:flex; flex-direction:column; align-items:center; gap:4px; width:64px; }
  .cam-ultimo { position:absolute; left:12px; right:12px; bottom:120px; background:rgba(255,255,255,.95); color:#111; border-radius:10px;
    padding:10px 12px; font-size:14px; box-shadow:0 4px 16px rgba(0,0,0,.4); }
  .cam-ultimo:empty { display:none; }
</style>

<div class="card card-pad" id="camera" style="margin-top:16px;scroll-margin-top:90px;">
  <div style="display:flex;flex-wrap:wrap;gap:10px;">
    <button type="button" id="cam-abrir" class="btn btn-primary" style="flex:1 1 220px;justify-content:center;padding:16px;font-size:16px;">📷 Fotografar cartões</button>
    <input id="cam-input" type="file" accept="image/*" capture="environment" style="display:none;">
    <label for="lote-input" class="btn btn-secondary" style="flex:1 1 220px;justify-content:center;padding:16px;cursor:pointer;">Enviar fotos da galeria</label>
    <input id="lote-input" type="file" accept="image/*" multiple style="display:none;">
  </div>
  <p style="font-size:12px;color:var(--muted);margin-top:10px;">Folha sobre a mesa e com boa luz. Encaixe os <b>4 quadrados pretos</b> nas guias da tela: quando os 4 ficarem verdes, a foto é tirada sozinha. O aluno é identificado pelo QR do cartão.</p>
  <ul id="envios" style="list-style:none;padding:0;margin:8px 0 0;display:flex;flex-direction:column;gap:6px;"></ul>
</div>

{{-- Visor ao vivo com as guias dos cantos --}}
<div id="cam-tela" class="cam-tela" hidden>
  <canvas id="cam-canvas"></canvas>
  <video id="cam-video" playsinline muted style="display:none;"></video>
  <div class="cam-topo">
    <span id="cam-dica">Encaixe os 4 quadrados pretos nas guias</span>
    <button type="button" class="cam-btn-redondo" id="cam-fechar" aria-label="Fechar câmera">✕</button>
  </div>
  <div id="cam-ultimo" class="cam-ultimo"></div>
  <div class="cam-base">
    <button type="button" class="cam-btn-redondo" id="cam-luz" aria-label="Lanterna" style="visibility:hidden;">🔦</button>
    <button type="button" class="cam-disparo" id="cam-disparo" aria-label="Tirar foto"></button>
    <label class="cam-auto"><input type="checkbox" id="cam-auto-chk" checked style="width:20px;height:20px;"> Auto</label>
  </div>
</div>

@push('scripts')
<script>
// ---------- Correção pela câmera ----------
// URLs relativas: o site roda atrás de proxy https (Cloudflare); URL absoluta http seria bloqueada.
var UPLOAD_URL = '{{ route('correcao.upload', $prova['id'], false) }}';
var CAM_CARTOES_URL = '/cartoes';
var CAM_CSRF = '{{ csrf_token() }}';
function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
var ALUNOS = {!! json_encode(collect($alunos)->map(function ($a) { return ['id' => $a['id'], 'nome' => $a['nome'], 'turma' => $a['turma_nome'] ?? '']; })->values(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};

function paraJpeg(canvas) {
  return new Promise(function (ok) {
    canvas.toBlob(function (b) { ok(new File([b], 'cartao.jpg', { type: 'image/jpeg' })); }, 'image/jpeg', 0.88);
  });
}
function reduzirFoto(file) {
  return new Promise(function (ok) {
    if (!window.createImageBitmap) return ok(file);
    createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (img) {
      var s = Math.min(1, 2400 / Math.max(img.width, img.height));
      var c = document.createElement('canvas');
      c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      paraJpeg(c).then(ok);
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
    method: 'POST', credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CAM_CSRF },
    body: JSON.stringify({ aluno_id: parseInt(sel.value) })
  }).then(function (r) { return r.json(); }).then(function () {
    sel.parentNode.innerHTML = '<span class="badge badge-success">Vinculado a ' + esc(sel.options[sel.selectedIndex].text) + '</span>';
    if (typeof atualizar === 'function') atualizar();
  });
}
// Mensagem legível para respostas que não são JSON (sessão expirada, foto grande, servidor fora)
function erroHttp(status) {
  if (status === 419 || status === 401) return 'sua sessão expirou. Recarregue a página e entre de novo.';
  if (status === 413) return 'foto grande demais para o servidor.';
  if (status >= 500) return 'o servidor não conseguiu ler o cartão agora. Tente de novo.';
  return 'falha no envio (HTTP ' + status + ').';
}
/** Envia a foto e devolve uma promessa com o HTML do resultado (também listado em #envios). */
function enviarCartao(file) {
  var li = linhaEnvio('⏳ Lendo cartão…');
  return reduzirFoto(file).then(function (f) {
    var fd = new FormData(); fd.append('imagem', f); fd.append('_token', CAM_CSRF);
    return fetch(UPLOAD_URL, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
  }).then(function (r) {
    return r.json().then(function (j) { return { ok: r.ok, j: j }; }, function () { return { ok: false, j: { erro: erroHttp(r.status) } }; });
  }).then(function (res) {
      var d = res.j;
      if (!res.ok) { li.innerHTML = '⚠️ <b>Erro:</b> ' + esc(d.erro || 'falha no envio'); return li.innerHTML; }
      var quem = d.aluno_nome ? '<b>' + esc(d.aluno_nome) + '</b>' : '<b style="color:var(--danger)">Aluno não identificado</b>';
      var nota = d.acertos !== null && d.acertos !== undefined ? ' — <b>' + d.acertos + '/' + d.total + '</b> acertos · nota ' + Number(d.nota_final).toFixed(1).replace('.', ',') : '';
      var amb = d.ambiguas ? ' · <span style="color:var(--warning,#c27c0e)">' + d.ambiguas + ' questão(ões) para revisar</span>' : '';
      var lg = d.lingua ? ' · ' + (d.lingua === 'ESP' ? 'Espanhol' : 'Inglês') : '';
      var mov = d.prova_corrigida ? '<div style="font-size:13px;margin-top:3px;">↪ Cartão de <b>' + esc(d.prova_corrigida) + '</b>: corrigido na prova certa.</div>' : '';
      var resumo = (d.aluno_nome && !d.ambiguas ? '✅ ' : '📝 ') + quem + nota + lg + amb + mov;
      li.innerHTML = resumo + (d.aluno_nome ? '' : (ALUNOS.length ? seletorAluno(d.id) : '<div style="font-size:13px;color:var(--muted);margin-top:4px;">QR ilegível ou cartão em branco: o professor vincula o aluno depois.</div>'));
      if (typeof atualizar === 'function') atualizar();
      return resumo;
    }).catch(function (e) { li.innerHTML = '⚠️ <b>Sem conexão:</b> ' + esc(e.message) + '. Verifique a internet e tente de novo.'; return li.innerHTML; });
}
document.getElementById('cam-input').addEventListener('change', function (e) {
  Array.prototype.forEach.call(e.target.files, enviarCartao); e.target.value = '';
});
document.getElementById('lote-input').addEventListener('change', function (e) {
  var fila = Array.prototype.slice.call(e.target.files); e.target.value = '';
  (function prox() { var f = fila.shift(); if (f) enviarCartao(f).then(prox); })();
});

// ---------- Visor ao vivo com guias dos cantos ----------
(function () {
  // Geometria do cartão A4 (mm) — igual a apps/api/omr/layout.py: marcadores de 8 mm centrados a 12 mm das bordas
  var PAG_W = 210, PAG_H = 297, MARCA = 8, CENTROS = [[12, 12], [198, 12], [12, 285], [198, 285]];
  var tela = document.getElementById('cam-tela'), cv = document.getElementById('cam-canvas'), ctx = cv.getContext('2d');
  var video = document.getElementById('cam-video'), dica = document.getElementById('cam-dica');
  var ultimo = document.getElementById('cam-ultimo'), autoChk = document.getElementById('cam-auto-chk');
  var stream = null, rodando = false, folha = null, cantosOk = [false, false, false, false];
  var estaveis = 0, ocupado = false, pausaAte = 0, ultimaAnalise = 0, track = null, luz = false;
  var esperaTrocar = false; // depois de uma foto, só fotografa de novo quando o cartão sair do enquadramento

  function abrir() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      document.getElementById('cam-input').click(); // sem câmera ao vivo: câmera do sistema
      return;
    }
    navigator.mediaDevices.getUserMedia({ audio: false, video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } } })
      .then(function (s) {
        stream = s; track = s.getVideoTracks()[0];
        video.srcObject = s;
        return video.play();
      })
      .then(function () {
        tela.hidden = false; rodando = true; estaveis = 0; esperaTrocar = false; ultimo.innerHTML = '';
        document.body.style.overflow = 'hidden';
        var cap = track && track.getCapabilities ? track.getCapabilities() : {};
        document.getElementById('cam-luz').style.visibility = cap.torch ? 'visible' : 'hidden';
        requestAnimationFrame(quadro);
      })
      .catch(function () {
        fechar();
        document.getElementById('cam-input').click(); // permissão negada: câmera do sistema
      });
  }
  function fechar() {
    rodando = false; tela.hidden = true; document.body.style.overflow = '';
    if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
  }

  // Desenha o vídeo no canvas ("cover") e calcula onde a folha A4 deve ficar
  function quadro(t) {
    if (!rodando) return;
    var dpr = window.devicePixelRatio || 1, W = cv.clientWidth * dpr, H = cv.clientHeight * dpr;
    if (cv.width !== W || cv.height !== H) { cv.width = W; cv.height = H; }
    var vw = video.videoWidth, vh = video.videoHeight;
    if (vw && vh) {
      var s = Math.max(W / vw, H / vh), dw = vw * s, dh = vh * s, dx = (W - dw) / 2, dy = (H - dh) / 2;
      ctx.drawImage(video, dx, dy, dw, dh);
      // Folha: maior A4 em pé que cabe com folga para o topo e a barra inferior
      var areaTop = H * 0.09, areaH = H * 0.74, fw = Math.min(W * 0.92, areaH * PAG_W / PAG_H), fh = fw * PAG_H / PAG_W;
      folha = { x: (W - fw) / 2, y: areaTop + (areaH - fh) / 2, w: fw, h: fh, mm: fw / PAG_W, video: { s: s, dx: dx, dy: dy } };
      if (t - ultimaAnalise > 180 && !ocupado && t > pausaAte) { ultimaAnalise = t; analisar(); }
      desenharGuias();
    }
    requestAnimationFrame(quadro);
  }

  function desenharGuias() {
    var f = folha, lado = MARCA * f.mm * 1.9;
    ctx.save();
    ctx.lineWidth = Math.max(2, f.mm * 0.6);
    ctx.setLineDash([f.mm * 3, f.mm * 2]);
    ctx.strokeStyle = 'rgba(255,255,255,.55)';
    ctx.strokeRect(f.x, f.y, f.w, f.h);
    ctx.setLineDash([]);
    CENTROS.forEach(function (c, i) {
      var x = f.x + c[0] * f.mm, y = f.y + c[1] * f.mm, ok = cantosOk[i];
      ctx.strokeStyle = ok ? '#2ecc71' : '#ffcc00';
      ctx.fillStyle = ok ? 'rgba(46,204,113,.25)' : 'rgba(255,204,0,.12)';
      ctx.lineWidth = Math.max(3, f.mm * 0.9);
      ctx.fillRect(x - lado / 2, y - lado / 2, lado, lado);
      ctx.strokeRect(x - lado / 2, y - lado / 2, lado, lado);
    });
    if (ocupado) {
      ctx.fillStyle = 'rgba(0,0,0,.35)'; ctx.fillRect(0, 0, cv.width, cv.height);
    } else if (estaveis > 0 && autoChk.checked) {
      // anel de progresso da captura automática
      var cx = cv.width / 2, cy = f.y + f.h / 2, r = f.mm * 14;
      ctx.strokeStyle = '#2ecc71'; ctx.lineWidth = f.mm * 2;
      ctx.beginPath(); ctx.arc(cx, cy, r, -Math.PI / 2, -Math.PI / 2 + Math.PI * 2 * Math.min(1, estaveis / 4)); ctx.stroke();
    }
    ctx.restore();
  }

  // Um canto está "ok" se a guia contém um quadrado escuro sobre fundo claro, perto do centro
  function cantoTemMarca(cx, cy, lado) {
    var x0 = Math.round(cx - lado / 2), y0 = Math.round(cy - lado / 2), n = Math.round(lado);
    if (x0 < 0 || y0 < 0 || x0 + n > cv.width || y0 + n > cv.height || n < 8) return false;
    var px = ctx.getImageData(x0, y0, n, n).data, lum = [], i;
    for (i = 0; i < px.length; i += 4) lum.push(0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2]);
    var ord = lum.slice().sort(function (a, b) { return a - b; });
    var claro = ord[Math.floor(ord.length * 0.9)];
    if (claro < 70) return false;                 // canto todo escuro: fora da folha
    var limiar = claro * 0.5, escuros = 0, sx = 0, sy = 0;
    for (i = 0; i < lum.length; i++) {
      if (lum[i] < limiar) { escuros++; sx += i % n; sy += Math.floor(i / n); }
    }
    var frac = escuros / lum.length;
    if (frac < 0.12 || frac > 0.6) return false;  // sem quadrado (só texto) ou sombra/borda
    var dxm = sx / escuros / n - 0.5, dym = sy / escuros / n - 0.5;
    return Math.abs(dxm) < 0.25 && Math.abs(dym) < 0.25;
  }

  function analisar() {
    var f = folha, lado = MARCA * f.mm * 2.2;
    cantosOk = CENTROS.map(function (c) { return cantoTemMarca(f.x + c[0] * f.mm, f.y + c[1] * f.mm, lado); });
    var n = cantosOk.filter(Boolean).length;
    if (esperaTrocar) {
      if (n <= 1) { esperaTrocar = false; } // cartão retirado: libera o próximo
      else { estaveis = 0; dica.textContent = 'Cartão lido. Coloque o próximo.'; return; }
    }
    if (n === 4) {
      estaveis++;
      dica.textContent = autoChk.checked ? 'Perfeito! Segure firme…' : 'Tudo certo: toque no botão para fotografar';
      if (autoChk.checked && estaveis >= 4) capturar();
    } else {
      estaveis = 0;
      dica.textContent = n === 0 ? 'Encaixe os 4 quadrados pretos nas guias' : 'Ajuste: ' + n + ' de 4 cantos encaixados';
    }
  }

  // Foto em resolução total, recortada na área da folha (com margem) direto do vídeo
  function capturar() {
    if (ocupado || !folha) return;
    ocupado = true; estaveis = 0;
    if (navigator.vibrate) navigator.vibrate(60);
    var f = folha, v = f.video, m = f.w * 0.06;
    var sx = Math.max(0, (f.x - m - v.dx) / v.s), sy = Math.max(0, (f.y - m - v.dy) / v.s);
    var sw = Math.min(video.videoWidth - sx, (f.w + 2 * m) / v.s), sh = Math.min(video.videoHeight - sy, (f.h + 2 * m) / v.s);
    var esc2 = Math.min(1, 2400 / Math.max(sw, sh)), c = document.createElement('canvas');
    c.width = Math.round(sw * esc2); c.height = Math.round(sh * esc2);
    c.getContext('2d').drawImage(video, sx, sy, sw, sh, 0, 0, c.width, c.height);
    dica.textContent = 'Lendo cartão…';
    ultimo.innerHTML = '⏳ Lendo cartão…';
    paraJpeg(c).then(enviarCartao).then(function (html) {
      ultimo.innerHTML = html + '<div style="font-size:12px;color:#555;margin-top:4px;">Coloque o próximo cartão.</div>';
      ocupado = false; esperaTrocar = true; pausaAte = performance.now() + 800;
      cantosOk = [false, false, false, false];
      dica.textContent = 'Cartão lido. Coloque o próximo.';
    });
  }

  document.getElementById('cam-abrir').addEventListener('click', abrir);
  document.getElementById('cam-fechar').addEventListener('click', fechar);
  document.getElementById('cam-disparo').addEventListener('click', capturar);
  document.getElementById('cam-luz').addEventListener('click', function () {
    if (!track) return;
    luz = !luz;
    track.applyConstraints({ advanced: [{ torch: luz }] }).catch(function () {});
  });
  document.addEventListener('visibilitychange', function () { if (document.hidden && rodando) fechar(); });
})();
</script>
@endpush
