var correcao_acompanhamentoConfig = JSON.parse(document.getElementById('correcao-acompanhamento-config').textContent);
document.getElementById('foto-img')?.addEventListener('error',function(){
  if(this.getAttribute('src')) { this.style.display='none';document.getElementById('foto-erro').style.display='block'; }
});

var STATUS_URL = correcao_acompanhamentoConfig.status;
window.g360RecursosCartoes = window.g360RecursosCartoes || new Map();
var LISTA_URL = correcao_acompanhamentoConfig.cartoes;
var PROVA_ID = correcao_acompanhamentoConfig.provaId;
var PODE_EXCLUIR = correcao_acompanhamentoConfig.podeExcluir;
var NOTA_MAX = correcao_acompanhamentoConfig.notaMax;

// ---------- Alunos corrigidos ----------
var corrDados = [], corrAssinatura = '';
var miniaturasCartoes = criarMiniaturasCartoes(document.getElementById('corr-lista'), verFoto);
function escH(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
function escA(t) { return escH(t).replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function fmtNota(n) { return Number(n).toFixed(1).replace('.', ','); }
var cargaCartoes=G360Carga(document.getElementById('corrigidos'));
function carregarCorrigidos(silenciosa=false) {
  return cargaCartoes.executar(()=>G360BuscarJSON(LISTA_URL),function(d){
    if(!Array.isArray(d.cartoes))throw new Error('Lista indisponível');
    var lista=d.cartoes,assin=JSON.stringify(lista);document.getElementById('corr-busca').disabled=false;
    if(assin===corrAssinatura)return;corrAssinatura=assin;corrDados=lista;desenharCorrigidos();
    if(lista.some(c=>!c.aluno_id))carregarAlunos();
  },{silenciosa});
}
function desenharCorrigidos() {
  corrDados.forEach(c=>{if(c._recursos)g360RecursosCartoes.set(Number(c.id),c._recursos);});
  var termo = (document.getElementById('corr-busca').value || '').toLowerCase();
  var itens = corrDados.filter(function (c) {
    return !termo || (c.aluno_nome || 'aluno não identificado').toLowerCase().indexOf(termo) >= 0 || String(c.matricula || '').indexOf(termo) >= 0;
  });
  document.getElementById('corr-qtd').textContent = corrDados.length;
  var ul = document.getElementById('corr-lista');
  if (!itens.length) {
    ul.innerHTML = '<li style="color:var(--muted);font-size:13px;">' + (corrDados.length ? 'Nenhum aluno encontrado.' : 'Nenhum cartão recebido ainda.') + '</li>';
    miniaturasCartoes.atualizar();
    return;
  }
  ul.innerHTML = itens.map(function (c) {
    var nome = c.aluno_nome ? escH(c.aluno_nome) : '<span style="color:var(--danger)">Aluno não identificado</span>';
    var sub = [c.turma, c.matricula, c.lingua ? (c.lingua === 'ESP' ? 'Espanhol' : 'Inglês') : null].filter(Boolean).map(escH).join(' · ');
    var st = c.status === 'lido' ? '<span class="badge badge-success">lido</span>'
      : c.status === 'ambiguo' ? '<span class="badge badge-warn">' + c.ambiguas + ' p/ revisar</span>'
      : '<span class="badge badge-muted">lendo</span>';
    var nota = (c.nota_final !== null && c.nota_final !== undefined)
      ? '<div class="corr-nota ' + (Number(c.nota_final) >= NOTA_MAX * 0.6 ? 'corr-nota-aprovada' : 'corr-nota-reprovada') + '">' + fmtNota(c.nota_final) + '</div>'
        + '<div style="font-size:11px;color:var(--muted);text-align:right;">' + c.acertos + '/' + c.total_questoes + '</div>'
      : '';
    var foto = c.miniatura_url
      ? '<button type="button" class="corr-thumb corr-miniatura" data-miniatura-cartao="' + c.id + '" data-miniatura-versao="' + escA(c.tentativa_numero || c.created_at || '') + '" data-miniatura-url="' + escA(JSON.stringify(c.miniatura_url)) + '" aria-busy="true" aria-label="Carregando miniatura do cartão-resposta" disabled>'
        + '<img data-miniatura-imagem alt="" hidden>'
        + '<span class="corr-miniatura-carga" data-miniatura-carga aria-hidden="true"><span class="g360-marca-carregando" aria-hidden="true"></span><span>Aguarde…</span></span>'
        + '<span class="corr-miniatura-erro" data-miniatura-erro hidden><span class="g360-marca-carregando" aria-hidden="true"></span><span>Falha na foto</span><svg class="corr-miniatura-atualizar" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 4v7h-7M20 11a8 8 0 1 0-2 6"/></svg></span></button>'
      : '<div class="corr-thumb corr-miniatura-ausente"><span class="g360-marca-carregando" aria-hidden="true"></span><span>' + (c.tem_imagem === false ? 'Sem imagem' : 'Sem miniatura') + '</span></div>';
    var acoes = c.tem_imagem ? '<button type="button" class="btn btn-sm btn-ghost" data-cartao-acao="foto" data-cartao-id="' + c.id + '">Ver foto</button>' : '';
    if (c.aluno_id && c.nota_final !== null && c.nota_final !== undefined) {
      acoes += '<button type="button" class="btn btn-sm btn-secondary" data-cartao-acao="resultado" data-cartao-id="' + c.id + '">Acertos e erros</button>';
    }
    if (!c.aluno_id && typeof seletorAluno === 'function' && typeof ALUNOS !== 'undefined' && ALUNOS.length) acoes += seletorAluno(c.id);
    if (PODE_EXCLUIR && c.status === 'lido') acoes += '<button type="button" class="btn btn-sm btn-ghost" data-cartao-acao="revisao" data-cartao-id="' + c.id + '">Revisar leitura</button>';
    if (PODE_EXCLUIR) acoes += '<button type="button" class="btn btn-sm btn-ghost" style="color:var(--danger);" data-cartao-acao="excluir" data-cartao-id="' + c.id + '">Retirar correção</button>';
    return '<li class="corr-item">' + foto + '<div class="corr-info"><b>' + nome + '</b><div style="color:var(--muted);">' + sub + '</div>'
      + '<div style="margin-top:4px;">' + st + '</div><div class="corr-acoes">' + acoes + '</div></div><div>' + nota + '</div></li>';
  }).join('');
  miniaturasCartoes.atualizar();
}
function abrirModal(id) { document.getElementById(id).style.display = 'flex'; }
function fecharModal(id) { document.getElementById(id).style.display = 'none'; if (id === 'foto-modal') document.getElementById('foto-img').src = ''; }
function verFoto(id) {
  var c = corrDados.filter(function (x) { return x.id === id; })[0] || {};
  document.getElementById('foto-titulo').textContent = (c.aluno_nome || 'Aluno não identificado') + (c.turma ? ' · ' + c.turma : '');
  document.getElementById('foto-erro').style.display = 'none';
  document.getElementById('foto-img').style.display = '';
  window.g360ImagemContextual(document.getElementById('foto-img'), c.foto_url || c._recursos.imagem).catch(function(){document.getElementById('foto-img').style.display='none';document.getElementById('foto-erro').style.display='block';});
  abrirModal('foto-modal');
}
async function excluirCorrecao(id) {
  var c = corrDados.filter(function (x) { return x.id === id; })[0] || {};
  var retirada;try{retirada=await confirmarRetiradaCartao(c._recursos.retirada,c.aluno_nome||'Cartão-resposta');}catch(e){alert(e.message);return;}
  if(!retirada)return;
  G360Requisicao(c._recursos.excluir, { method: 'DELETE', credentials: 'same-origin',body:JSON.stringify(retirada), headers: { 'Content-Type':'application/json','Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
    .then(function (res) {
      if (!res.ok) { alert(res.j.erro || 'Não foi possível excluir.'); return; }
      corrAssinatura = ''; carregarCorrigidos(); atualizar();window.G360Toast?.sucesso('Correção retirada. Os registros anteriores foram preservados no histórico.');
    });
}
document.getElementById('corr-busca').addEventListener('input', desenharCorrigidos);
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { fecharModal('foto-modal'); } });
carregarCorrigidos();
setInterval(function () { if (!document.hidden) carregarCorrigidos(true); }, 20000);
var CSRF = correcao_acompanhamentoConfig.csrf;

var promessaAlunos;
function carregarAlunos(){
  if(promessaAlunos)return promessaAlunos;
  var estado=document.getElementById('upload-alunos-estado'),select=document.getElementById('upload-aluno');estado.textContent='Carregando alunos…';select.disabled=true;
  promessaAlunos=G360BuscarJSON(correcao_acompanhamentoConfig.alunos).then(function(d){
    ALUNOS=d.alunos.map(a=>({id:a.id,nome:a.nome,turma:a.turma_nome||''}));
    ALUNOS.forEach(a=>{var o=document.createElement('option');o.value=a.id;o.textContent=a.nome;select.append(o);});
    estado.textContent='';select.disabled=false;desenharCorrigidos();return ALUNOS;
  }).catch(function(){promessaAlunos=null;estado.textContent='Não foi possível carregar os alunos. Você pode enviar sem identificar e vincular depois. ';var b=document.createElement('button');b.type='button';b.className='btn btn-secondary btn-sm';b.textContent='Tentar novamente';b.onclick=carregarAlunos;estado.append(b);});
  return promessaAlunos;
}
function abrirUpload() { document.getElementById('modal-upload').style.display = 'flex';carregarAlunos(); }
function fecharUpload() { document.getElementById('modal-upload').style.display = 'none'; }

function tempoRelativo(iso) {
  if (!iso) return '';
  var diffMs = Date.now() - new Date(iso).getTime();
  var min = Math.floor(diffMs / 60000);
  if (min < 1) return 'agora mesmo';
  if (min < 60) return 'há ' + min + ' minuto' + (min > 1 ? 's' : '');
  var h = Math.floor(min / 60);
  return 'há ' + h + ' hora' + (h > 1 ? 's' : '');
}

function renderEstado(data) {
  if(!Number.isFinite(data.esperados)||!Number.isFinite(data.concluidos))throw new Error('Indicadores indisponíveis');
  var pct=data.percentual_concluido;
  document.getElementById('v-pct').textContent=pct===null?'—':new Intl.NumberFormat('pt-BR',{maximumFractionDigits:2}).format(pct)+'%';
  [['concluidos','concluidos'],['esperados','esperados'],['sem-cartao','sem_cartao'],['processando','em_processamento'],['revisao','alunos_revisao'],['total','cartoes_recebidos']].forEach(x=>document.getElementById('v-'+x[0]).textContent=data[x[1]]);
  document.getElementById('v-progresso-resumo').textContent=data.esperados?data.concluidos+' de '+data.esperados+' alunos com correção concluída.':'Sem alunos previstos.';
  document.getElementById('v-pendencias-tecnicas').textContent=[data.falhas?data.falhas+' aluno(s) com leitura não concluída.':'',data.nao_identificados?data.nao_identificados+' cartão(ões) sem aluno identificado.':''].filter(Boolean).join(' ');
  document.getElementById('bar').style.width=(pct??0)+'%';
  var progresso=document.querySelector('.progress-track');if(pct===null)progresso.removeAttribute('aria-valuenow');else progresso.setAttribute('aria-valuenow',pct);
  var aviso=document.getElementById('aviso-concluida');
  if(data.concluida&&!aviso){aviso=document.createElement('span');aviso.id='aviso-concluida';aviso.className='badge badge-success';aviso.textContent='Todos os alunos previstos têm resultado concluído';document.getElementById('v-progresso-resumo').append(' ',aviso);}
  if(!data.concluida)aviso?.remove();
}

function renderAmbiguidades(lista) {
  var alunos = {};
  lista.forEach(function (a) { alunos[a.cartao_id] = true; });
  var botao = document.getElementById('btn-revisar');
  document.getElementById('ambig-resumo').textContent = lista.length
    ? lista.length + ' questões de ' + Object.keys(alunos).length + ' cartão(ões) aguardam sua decisão.'
    : 'Nenhuma pendência de revisão.';
  if (botao) {
    botao.textContent = 'Revisar leituras (' + lista.length + ')';
    botao.style.display = lista.length ? '' : 'none';
  }
}

function renderAtividade(recentes) {
  var ul = document.getElementById('activity-list');
  ul.innerHTML = '';
  if (!recentes.length) {
    ul.innerHTML = '<li style="border:0;color:var(--muted);">Nenhum cartão processado ainda.</li>';
    return;
  }
  recentes.forEach(function (r) {
    var li = document.createElement('li');
    var nome = r.aluno_nome || 'Aluno não identificado';
    var nota = r.nota_final !== null ? parseFloat(r.nota_final).toFixed(1).replace('.', ',') : '—';
    li.innerHTML = '<div class="act-ico" style="background:var(--success-light);color:var(--success);">✓</div><div><b>' + escH(nome) + '</b> — nota ' + nota + '<div class="act-time">' + tempoRelativo(r.updated_at) + '</div></div>';
    ul.appendChild(li);
  });
}

var cargaProgresso=G360Carga(document.getElementById('regiao-progresso'));
document.getElementById('regiao-progresso').addEventListener('g360:carga',function(e){
  if(e.detail.estado!=='erro')return;
  if(!e.detail.anterior){document.getElementById('ambig-resumo').textContent='Revisões indisponíveis no momento. Tente novamente nos indicadores.';document.getElementById('activity-list').textContent='Atividade indisponível no momento. Tente novamente nos indicadores.';}
});
function atualizar(silenciosa=false) {
  return cargaProgresso.executar(()=>G360BuscarJSON(STATUS_URL),function(data){renderEstado(data);renderAmbiguidades(data.ambiguidades||[]);renderAtividade(data.recentes||[]);},{silenciosa});
}

atualizar();
setInterval(function () { if (!document.hidden) atualizar(true); }, 10000);


document.addEventListener('click',function(e){var b=e.target.closest('[data-cartao-acao]');if(!b)return;var id=Number(b.dataset.cartaoId),c=corrDados.find(c=>Number(c.id)===id);if(!c)return;if(b.dataset.cartaoAcao==='foto')verFoto(id);else if(b.dataset.cartaoAcao==='excluir')excluirCorrecao(id);else G360Navegacao.abrir(c._recursos[b.dataset.cartaoAcao]);});

document.addEventListener('click',function(e){var b=e.target.closest('[data-correcao-acao]');if(b)({'abrir-upload':abrirUpload,'fechar-upload':fecharUpload,'fechar-foto':function(){fecharModal('foto-modal');}})[b.dataset.correcaoAcao]?.();if(e.target.matches('[data-correcao-fundo-foto]'))fecharModal('foto-modal');});
document.addEventListener('submit',function(e){var texto=e.target.dataset.correcaoConfirmar;if(texto&&!confirm(texto))e.preventDefault();});
