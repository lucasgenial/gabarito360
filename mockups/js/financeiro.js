/* Mockup funcional da experiência financeira limitada (FN-068 subfase 2.0; FN-081 subfase 0.1).
   Dados fictícios. Estado só na memória desta página: nada vai ao servidor, ao navegador
   (sem localStorage) nem a uma API. Não há cobrança, carteira, saldo calculado nem integração real.
   Todo número exibido "vem" do aplicativo financeiro fictício; o mockup não calcula saldo. */
(function () {
  'use strict';

  var params = new URLSearchParams(location.search);
  var noQuadro = params.get('quadro') === '1';

  /* ---------- Dados fictícios ---------- */
  var ESCOLA = 'Colégio Estadual Monte Verde';
  var NUCLEO = 'Núcleo Regional Sul';
  var PERFIS = {
    'prof-pessoal': { nome: 'Marina Teixeira', iniciais: 'MT', perfil: 'Professor', espacoRotulo: 'Meu espaço', espaco: 'Espaço pessoal',
      contexto: 'Professor (espaço pessoal)', tipo: 'pessoal', veAluno: true, corrige: true },
    'prof-escola': { nome: 'Marina Teixeira', iniciais: 'MT', perfil: 'Professor', espacoRotulo: 'Minha escola', espaco: ESCOLA,
      contexto: 'Escola · ' + ESCOLA, tipo: 'professor-instituicao', veAluno: true, corrige: true },
    'resp-escola': { nome: 'Rafael Prado', iniciais: 'RP', perfil: 'Diretor escolar', espacoRotulo: 'Minha escola', espaco: ESCOLA,
      contexto: 'Escola · ' + ESCOLA, tipo: 'responsavel', nivel: 'escola', veAluno: true, corrige: true },
    'resp-nucleo': { nome: 'Helena Duarte', iniciais: 'HD', perfil: 'Diretor do núcleo', espacoRotulo: 'Meu núcleo', espaco: NUCLEO,
      contexto: 'Núcleo · ' + NUCLEO, tipo: 'responsavel', nivel: 'nucleo', veAluno: false, corrige: false },
    'financeiro': { nome: 'Equipe Financeiro', iniciais: 'EF', perfil: 'Financeiro (contexto interno)', espacoRotulo: 'Contexto', espaco: 'Contexto interno',
      contexto: 'Contexto interno · todos os clientes', tipo: 'financeiro', veAluno: false, corrige: false }
  };

  /* Movimentos recebidos do aplicativo financeiro (fictícios). qtd > 0 entra, qtd < 0 sai, 0 sem efeito. */
  var MOVIMENTOS = [
    { id: 'm1', data: '12/11/2026 09:12', periodo: 'mes', mov: 'Consumo', qtd: -1, prof: 'Marina Teixeira', aluno: 'Ana Beatriz Souza', aplic: 'Avaliação 2 — Matemática · 9º A (11/11/2026)', ref: 'op-ESC-7f3a', fonte: 'Franquia do ciclo' },
    { id: 'm2', data: '12/11/2026 09:10', periodo: 'mes', mov: 'Reenvio sem cobrança', qtd: 0, prof: 'Marina Teixeira', aluno: 'Bruno Lima', aplic: 'Avaliação 2 — Matemática · 9º A (11/11/2026)', ref: 'op-ESC-51c0', fonte: 'Mesma operação; nenhum consumo novo' },
    { id: 'm3', data: '12/11/2026 09:02', periodo: 'mes', mov: 'Liberação', qtd: 1, prof: 'Marina Teixeira', aluno: 'Carlos Eduardo Nunes', aplic: 'Avaliação 2 — Matemática · 9º A (11/11/2026)', ref: 'op-ESC-90de', fonte: 'Imagem inutilizável; reserva liberada' },
    { id: 'm4', data: '12/11/2026 09:01', periodo: 'mes', mov: 'Reserva', qtd: -1, prof: 'Marina Teixeira', aluno: 'Carlos Eduardo Nunes', aplic: 'Avaliação 2 — Matemática · 9º A (11/11/2026)', ref: 'op-ESC-90de', fonte: 'Franquia do ciclo' },
    { id: 'm5', data: '11/11/2026 16:40', periodo: 'mes', mov: 'Consumo', qtd: -1, prof: 'João Pires', aluno: 'Daniela Rocha', aplic: 'Simulado 1 — Português · 8º B (10/11/2026)', ref: 'op-ESC-12aa', fonte: 'Saldo da instituição' },
    { id: 'm6', data: '11/11/2026 16:38', periodo: 'mes', mov: 'Consumo', qtd: -1, prof: 'Luíza Campos', aluno: 'Eduardo Prado', aplic: 'Avaliação 1 — Ciências · 7º A (10/11/2026)', ref: 'op-ESC-3b77', fonte: 'Saldo da instituição' },
    { id: 'm7', data: '01/11/2026 00:00', periodo: 'mes', mov: 'Concessão do ciclo', qtd: 2000, prof: '', aluno: '', aplic: '', ref: 'ciclo-2026-11', fonte: 'Franquia do plano (valor fictício)' },
    { id: 'm8', data: '28/10/2026 10:15', periodo: 'anterior', mov: 'Crédito comprado', qtd: 500, prof: '', aluno: '', aplic: '', ref: 'apf-compra-0042', fonte: 'Registrado pelo aplicativo financeiro' },
    { id: 'm9', data: '20/10/2026 14:05', periodo: 'anterior', mov: 'Consumo', qtd: -1, prof: 'Marina Teixeira', aluno: 'Fernanda Alves', aplic: 'Avaliação 1 — Matemática · 9º A (20/10/2026)', ref: 'op-ESC-0c41', fonte: 'Franquia do ciclo' }
  ];
  var PESSOAL = [
    { id: 'p1', data: '12/11/2026 08:30', periodo: 'mes', mov: 'Consumo', qtd: -1, prof: 'Marina Teixeira', aluno: 'Gabriel Souza', aplic: 'Revisão — Matemática · Reforço (12/11/2026)', ref: 'op-PES-a1b2', fonte: 'Créditos por contribuição validada' },
    { id: 'p2', data: '05/11/2026 18:00', periodo: 'mes', mov: 'Créditos por contribuição validada', qtd: 10, prof: '', aluno: '', aplic: '', ref: 'apf-recomp-0007', fonte: 'Concedido pelo aplicativo financeiro após a curadoria' },
    { id: 'p3', data: '01/11/2026 00:00', periodo: 'mes', mov: 'Concessão do ciclo', qtd: 30, prof: '', aluno: '', aplic: '', ref: 'ciclo-2026-11', fonte: 'Franquia do plano (valor fictício)' }
  ];
  var TOTAIS = [
    { id: 't1', cliente: ESCOLA, consumos: 1284, liberacoes: 22, reenvios: 61 },
    { id: 't2', cliente: 'Escola Municipal Rio Doce', consumos: 806, liberacoes: 14, reenvios: 30 },
    { id: 't3', cliente: NUCLEO + ' (escolas sem alocação própria)', consumos: 227, liberacoes: 5, reenvios: 9 }
  ];
  var LIMITES = [
    { prof: 'Marina Teixeira', limite: 120, usado: 87 },
    { prof: 'João Pires', limite: 80, usado: 80 },
    { prof: 'Luíza Campos', limite: 100, usado: 92 },
    { prof: 'Paulo Neri', limite: null, usado: 15 },
    { prof: 'Sofia Ramos', limite: 60, usado: 12 }
  ];
  var ALOCACOES = [
    { escola: ESCOLA, alocada: 2000, usada: 1200 },
    { escola: 'Escola Municipal Rio Doce', alocada: 1500, usada: 806 },
    { escola: 'Escola Estadual Vale Azul', alocada: 500, usada: 0 }
  ];

  /* ---------- Estado (só memória) ---------- */
  var estado = {
    tela: 'visao', perfil: 'prof-escola', cenario: 'sucesso', simulacao: false,
    filtros: { periodo: 'mes', movimento: '', professor: '' },
    atualizado: '12/11/2026 09:41', enviado: false, aviso: ''
  };
  var temporizador = null;

  /* ---------- Utilidades ---------- */
  function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
  function num(n) { return Number(n).toLocaleString('pt-BR'); }
  function $(id) { return document.getElementById(id); }
  function p() { return PERFIS[estado.perfil]; }
  var pendencias = [];
  function pend(texto) { if (pendencias.indexOf(texto) < 0) pendencias.push(texto); return ''; }
  function selo(texto) { return '<span class="fi-pendente"><span class="fi-pendente-rotulo">Pendente de decisão:</span> ' + esc(texto) + '</span>'; }
  function blocoPendencias() {
    if (!pendencias.length) return '';
    return '<details class="fi-pendencias"><summary>Pendências de decisão nesta tela (' + pendencias.length + ')</summary><p class="page-sub">Nota do mockup: estes pontos não estão decididos. A tela real só os mostra depois da decisão.</p><ul class="fi-pendentes">' + pendencias.map(function (x) { return '<li>' + selo(x) + '</li>'; }).join('') + '</ul></details>';
  }
  function aviso(tipo, html, alerta) { return '<div class="g-aviso ' + tipo + '" role="' + (alerta ? 'alert' : 'status') + '">' + html + '</div>'; }
  function botao(texto, classe, dados) { return '<button type="button" class="btn ' + classe + '" ' + (dados || '') + '>' + esc(texto) + '</button>'; }
  function indicador(rotulo, valor, detalhe, tipo) {
    return '<div class="g-indicador ' + (tipo || '') + '"><div class="g-indicador-rotulo">' + esc(rotulo) + '</div><div class="g-indicador-valor">' + esc(valor) + '</div>' + (detalhe ? '<div class="g-indicador-detalhe">' + esc(detalhe) + '</div>' : '') + '</div>';
  }
  function carregando(rotulo) {
    return '<div class="g360-carregando g360-carregando-linhas" role="status" aria-live="polite" aria-busy="true"><div class="g360-carga-marca"><span>' + esc(rotulo) + '</span></div><div class="g360-skeleton" aria-hidden="true"><i></i><i></i><i></i></div></div>';
  }
  function vazio(titulo, texto) { return '<div class="g-vazio"><b>' + esc(titulo) + '</b>' + esc(texto || '') + '</div>'; }
  function estadoTela(codigo, titulo, msg, acoes) {
    return '<div class="fi-estado"><section class="estado-conteudo"><p class="estado-codigo">' + esc(codigo) + '</p><h2 class="fi-estado-titulo">' + esc(titulo) + '</h2>' + (msg ? '<p class="estado-mensagem">' + esc(msg) + '</p>' : '') + (acoes ? '<div class="estado-acoes">' + acoes + '</div>' : '') + '</section></div>';
  }
  function origem() { return estado.simulacao ? 'Origem: simulação (sem valor comercial)' : 'Origem: aplicativo financeiro'; }
  function agora() {
    var d = new Date(); function z(n) { return (n < 10 ? '0' : '') + n; }
    return z(d.getDate()) + '/' + z(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + z(d.getHours()) + ':' + z(d.getMinutes());
  }

  /* ---------- Abas da área Créditos ---------- */
  function abas() {
    var a = [['visao', 'Visão'], ['extrato', 'Histórico']];
    if (p().tipo === 'responsavel') a.push(['limites', p().nivel === 'nucleo' ? 'Franquia das escolas' : 'Limites por professor']);
    return a;
  }
  function desenharAbas() {
    var nav = $('fi-abas'), lista = abas();
    nav.hidden = estado.cenario === 'negado';
    nav.innerHTML = lista.map(function (x) {
      var sel = x[0] === estado.tela;
      return '<a id="fi-aba-' + x[0] + '" href="#' + x[0] + '" role="tab" aria-controls="fi-painel" aria-selected="' + sel + '" tabindex="' + (sel ? '0' : '-1') + '" data-aba="' + x[0] + '">' + esc(x[1]) + '</a>';
    }).join('');
    $('fi-painel').setAttribute('aria-labelledby', lista.some(function (x) { return x[0] === estado.tela; }) && !nav.hidden ? 'fi-aba-' + estado.tela : 'fi-titulo');
  }

  /* ---------- Cabeçalho da página, avisos e ações ---------- */
  function desenharTopo() {
    var pf = p();
    $('fi-subtitulo').textContent = pf.contexto + ' · dados recebidos do aplicativo financeiro';
    var acoes = '';
    if (estado.cenario !== 'negado') acoes += botao('Atualizar', 'btn-secondary', 'data-acao="atualizar"');
    if ((pf.tipo === 'responsavel' || pf.tipo === 'pessoal') && estado.cenario !== 'negado') acoes += botao('Abrir compra no aplicativo financeiro', 'btn-primary', 'data-encaminhar="comprar"');
    $('fi-acoes-pagina').innerHTML = acoes;

    var h = '';
    if (estado.simulacao) h += aviso('info', '<b>Simulação.</b> Estes dados vêm do simulador de integração da homologação. Eles não têm valor comercial e nunca viram cobrança.');
    if (estado.aviso) h += aviso('info', esc(estado.aviso));
    if (estado.cenario === 'sem-saldo') h += avisoSemSaldo();
    if (estado.cenario === 'limite') h += avisoLimite();
    if (estado.cenario === 'indisponivel') h += aviso('erro', '<b>Não foi possível consultar o aplicativo financeiro.</b> Nenhum saldo é mostrado até a próxima consulta. Os envios ao modo servidor ficam recusados. A leitura no aparelho e a revisão manual continuam. <div class="fi-acoes">' + botao('Tentar novamente', 'btn-secondary', 'data-acao="atualizar"') + '</div>', true);
    $('fi-avisos-topo').innerHTML = h;
  }
  function avisoSemSaldo() {
    var t = p().tipo;
    if (t === 'professor-instituicao') return aviso('atencao', '<b>A escola está sem créditos para o modo servidor.</b> Os envios ao servidor ficam recusados. Peça ao responsável financeiro da sua escola. A leitura no aparelho continua.');
    if (t === 'financeiro') return aviso('atencao', '<b>1 cliente está sem créditos.</b> O Financeiro só acompanha. A compra é feita pelo responsável, no aplicativo financeiro.');
    return aviso('atencao', '<b>Sem créditos neste contexto.</b> Os envios ao modo servidor ficam recusados. A leitura no aparelho continua. A compra é feita no aplicativo financeiro.');
  }
  function avisoLimite() {
    var t = p().tipo;
    if (t === 'professor-instituicao') return aviso('atencao', '<b>Você atingiu seu limite mensal de créditos.</b> Seus envios ao modo servidor ficam recusados. Fale com o responsável financeiro da escola.' + pend('quando o limite renova (fim do ciclo)'));
    if (t === 'responsavel') return aviso('atencao', '<b>' + (p().nivel === 'nucleo' ? '1 escola atingiu a franquia alocada.' : '1 professor atingiu o limite mensal.') + '</b> O envio dele ao modo servidor fica recusado, mesmo com saldo na instituição. <div class="fi-acoes">' + botao(p().nivel === 'nucleo' ? 'Ver franquia das escolas' : 'Ver limites', 'btn-secondary', 'data-aba="limites"') + '</div>');
    if (t === 'pessoal') return aviso('info', 'O espaço pessoal não tem limite mensal por professor. Nada muda neste contexto.');
    return aviso('atencao', '<b>2 clientes têm professores no limite mensal.</b> O Financeiro só acompanha os totais.');
  }

  /* ---------- Tela 1: Visão ---------- */
  function indicadoresSucesso(zero) {
    var pf = p(), at = estado.atualizado;
    var ult = indicador('Última atualização', at, origem());
    var semSaldo = estado.cenario === 'sem-saldo';
    if (pf.tipo === 'pessoal') return [
      indicador('Saldo do espaço pessoal', zero || semSaldo ? '0 créditos' : '42 créditos', 'Inclui créditos por contribuição validada', semSaldo ? 'perigo' : 'destaque'),
      indicador('Franquia do ciclo', zero || semSaldo ? '0 restantes de 30' : '18 restantes de 30', 'Renova em 01/12/2026 (valor fictício)'), ult];
    if (pf.tipo === 'professor-instituicao') {
      var lim = estado.cenario === 'limite';
      return [
        indicador('Seu limite mensal', '120 créditos', 'Definido pelo responsável financeiro'),
        indicador('Usado por você no mês', zero ? '0' : lim ? '120' : '87', 'Só os seus envios nesta escola'),
        indicador('Restante no seu limite', zero ? '120' : lim ? '0' : '33', lim ? 'Limite atingido' : 'Pode acabar antes se a escola ficar sem saldo', lim ? 'perigo' : 'destaque'), ult];
    }
    if (pf.tipo === 'responsavel' && pf.nivel === 'escola') return [
      indicador('Saldo da escola', zero ? '0 créditos' : semSaldo ? '0 créditos' : '1.240 créditos', 'Créditos comprados e concedidos', semSaldo ? 'perigo' : 'destaque'),
      indicador('Franquia do ciclo', zero || semSaldo ? '0 restantes de 2.000' : '800 restantes de 2.000', 'Alocada pelo núcleo; renova em 01/12/2026 (valor fictício)'),
      indicador('Professores com limite mensal', '4 de 5', '1 sem limite definido'), ult];
    if (pf.tipo === 'responsavel') return [
      indicador('Saldo do núcleo', zero || semSaldo ? '0 créditos' : '5.400 créditos', 'Não cobre as escolas sem alocação', semSaldo ? 'perigo' : 'destaque'),
      indicador('Franquia do ciclo do núcleo', zero || semSaldo ? '0 restantes de 10.000' : '3.100 restantes de 10.000', 'Renova em 01/12/2026 (valor fictício)'),
      indicador('Franquia alocada às escolas', '4.000', '3 escolas'), ult];
    return [
      indicador('Consumos no mês', zero ? '0' : '2.317', 'Todos os clientes, sem nomes de alunos'),
      indicador('Clientes com consumo', zero ? '0' : '3', 'Contexto interno'),
      indicador('Liberações sem cobrança', zero ? '0' : '41', 'Falhas técnicas e imagens inutilizáveis'), ult];
  }
  function visao() {
    var pf = p(), c = estado.cenario;
    if (c === 'negado') return (pend('quem vê a área de créditos em cada contexto'), estadoTela('Acesso negado', 'Seu perfil não mostra créditos neste contexto.', 'Troque o vínculo se você tem outro contexto.', botao('Trocar vínculo', 'btn-secondary', 'data-acao="trocar"')));
    var h = '';
    if (c === 'carregando') return carregando('Consultando o aplicativo financeiro…');
    if (c === 'indisponivel') {
      h += '<div class="g-indicadores">' + ['Saldo', 'Franquia do ciclo', 'Última consulta válida'].map(function (r) { return '<div class="g-indicador fi-valor-indisponivel"><div class="g-indicador-rotulo">' + esc(r) + '</div><div class="g-indicador-valor">Indisponível</div><div class="g-indicador-detalhe">Sem resposta do aplicativo financeiro</div></div>'; }).join('') + '</div>';
      return h;
    }
    h += '<div class="g-indicadores">' + indicadoresSucesso(c === 'vazio').join('') + '</div>';
    h += '<p class="fi-origem">' + (pf.tipo === 'financeiro' ? 'O Financeiro vê só totais. Nomes de alunos e aplicações não aparecem.' : 'O Gabarito360 só mostra estes valores. Quem decide saldo, franquia e limite é o aplicativo financeiro.') + '</p>';
    if (pf.tipo === 'professor-instituicao') { pend('quem vê o saldo institucional'); h += aviso('info', 'O saldo da escola aparece só para quem tem permissão.'); }

    /* Consumo recente */
    h += '<section class="card card-pad fi-secao" aria-labelledby="fi-recente"><h2 id="fi-recente" class="fi-h2">' + (pf.tipo === 'financeiro' ? 'Totais por cliente' : 'Movimentos recentes') + '</h2>';
    if (c === 'vazio') h += vazio('Sem movimentos neste ciclo.', ' Os envios ao modo servidor aparecem aqui.');
    else if (pf.tipo === 'financeiro') h += '<ul class="fi-lista-curta">' + TOTAIS.map(function (t) { return '<li><b>' + esc(t.cliente) + '</b><span>' + num(t.consumos) + ' consumos no mês</span></li>'; }).join('') + '</ul>';
    else h += '<ul class="fi-lista-curta">' + linhasVisiveis().slice(0, 3).map(function (m) { return '<li><span><b>' + esc(m.mov) + '</b> · ' + esc(m.data) + '</span><span class="' + (m.qtd > 0 ? 'fi-qtd-mais' : 'fi-qtd-menos') + '">' + qtdTexto(m.qtd) + '</span></li>'; }).join('') + '</ul>';
    h += '<div class="fi-acoes">' + botao('Ver histórico', 'btn-secondary', 'data-aba="extrato"') + '</div></section>';

    /* Ações financeiras: só encaminhamento */
    if (pf.tipo === 'responsavel' || pf.tipo === 'pessoal') {
      pend('lugar da ação: painel externo, navegação integrada ou tela limitada'); pend('formato da compra avulsa');
      h += '<section class="card card-pad fi-secao" aria-labelledby="fi-acoes-fin"><h2 id="fi-acoes-fin" class="fi-h2">Ações financeiras</h2>'
        + '<p class="page-sub fi-sem-topo">Compra de créditos' + (pf.tipo === 'responsavel' ? ', limites por professor e alocação de franquia' : '') + ' são feitas no aplicativo financeiro, com o seu acesso de lá. Use o botão do topo da página.</p></section>';
    } else if (pf.tipo === 'professor-instituicao') {
      pend('quem é o responsável financeiro em cada contexto');
      h += '<section class="card card-pad fi-secao" aria-labelledby="fi-mais"><h2 id="fi-mais" class="fi-h2">Precisa de mais créditos?</h2><p class="page-sub fi-sem-topo">Fale com o responsável financeiro da escola. Ele compra créditos para a escola e ajusta o seu limite mensal.</p></section>';
    }
    return h;
  }

  /* ---------- Tela 2: Histórico ---------- */
  function qtdTexto(q) { return q > 0 ? '+' + num(q) : q < 0 ? '−' + num(-q) : '0'; }
  function linhasBase() {
    var pf = p();
    if (pf.tipo === 'pessoal') return PESSOAL;
    if (pf.tipo === 'professor-instituicao') return MOVIMENTOS.filter(function (m) { return m.prof === 'Marina Teixeira'; });
    return MOVIMENTOS;
  }
  function linhasVisiveis() {
    var f = estado.filtros;
    return linhasBase().filter(function (m) {
      return (!f.periodo || m.periodo === f.periodo) && (!f.movimento || m.mov === f.movimento) && (!f.professor || m.prof === f.professor);
    });
  }
  function textoFiltros() {
    var f = estado.filtros, partes = [];
    partes.push('período: ' + (f.periodo === 'anterior' ? 'mês anterior' : f.periodo === 'mes' ? 'este mês' : 'todos'));
    if (f.movimento) partes.push('movimento: ' + f.movimento);
    if (f.professor) partes.push('professor: ' + f.professor);
    return 'Dados filtrados: ' + partes.join('; ') + '.';
  }
  function tabela(titulo, cab, linhas, total) {
    return '<div class="tabela-conjunto tabela-integrada" data-tabela data-modo="lista"><div class="tabela-area"><table class="table tabela-padrao"><caption class="sr-only">' + esc(titulo) + '</caption><thead><tr>'
      + cab.map(function (c) { return '<th scope="col"' + (c === 'Ações' ? ' class="tabela-coluna-acoes"' : '') + '>' + esc(c) + '</th>'; }).join('') + '</tr></thead><tbody>' + linhas.map(function (r) { return r.replace(/(<td[^>]*>)([\s\S]*?)(<\/td>)/g, '$1<div class="tabela-valor">$2</div>$3'); }).join('') + '</tbody></table></div>'
      + '<div class="tabela-rodape"><p role="status" aria-live="polite">' + esc(total) + '</p></div></div>';
  }
  function extrato() {
    var pf = p(), c = estado.cenario;
    if (c === 'negado') return estadoTela('Acesso negado', 'Seu perfil não mostra o histórico de créditos neste contexto.', null, botao('Trocar vínculo', 'btn-secondary', 'data-acao="trocar"'));
    var cab = '<div class="fi-cabecalho-lista"><h2 class="fi-h2">' + (pf.tipo === 'financeiro' ? 'Totais por cliente' : 'Histórico de créditos') + '</h2>'
      + (pf.tipo === 'financeiro' || c === 'indisponivel' || c === 'carregando' ? '' : botao('Filtrar', 'btn-secondary', 'data-acao="filtros" aria-haspopup="dialog"')) + '</div>';
    var h = '<section class="card card-pad tabela-lista-principal tabela-principal-integrada">' + cab;
    if (c === 'carregando') return h + carregando('Carregando o histórico…') + '</section>';
    if (c === 'indisponivel') return h + aviso('erro', 'Não foi possível carregar o histórico. Nenhum dado antigo é mostrado como atual. ' + '<div class="fi-acoes">' + botao('Tentar novamente', 'btn-secondary', 'data-acao="atualizar"') + '</div>', true) + '</section>';
    if (pf.tipo === 'financeiro') {
      if (c === 'vazio') return h + vazio('Sem consumos neste mês.', '') + '</section>';
      var lt = TOTAIS.map(function (t) {
        return '<tr><td class="tabela-principal" data-rotulo="Cliente">' + esc(t.cliente) + '</td><td data-rotulo="Consumos no mês">' + num(t.consumos) + '</td><td data-rotulo="Liberações">' + num(t.liberacoes) + '</td><td data-rotulo="Reenvios sem cobrança">' + num(t.reenvios) + '</td><td class="tabela-acoes" data-rotulo="Ações">' + botao('Ver detalhe', 'btn-secondary btn-sm', 'data-detalhe-total="' + t.id + '"') + '</td></tr>';
      });
      return h + tabela('Totais por cliente', ['Cliente', 'Consumos no mês', 'Liberações', 'Reenvios sem cobrança', 'Ações'], lt, TOTAIS.length + ' clientes. Sem nomes de alunos.') + '</section>';
    }
    h += '<p class="page-sub fi-filtros-aplicados">' + esc(textoFiltros()) + '</p>';
    var linhas = c === 'vazio' ? [] : linhasVisiveis();
    if (!linhas.length) {
      return h + (c === 'vazio' ? vazio('Sem movimentos neste período.', ' Os envios ao modo servidor aparecem aqui.') : vazio('Nenhum movimento corresponde aos filtros.', '') + '<div class="fi-acoes fi-centro">' + botao('Limpar filtros', 'btn-secondary', 'data-acao="limpar-filtros"') + '</div>') + '</section>';
    }
    var comProf = pf.tipo === 'responsavel';
    var lr = linhas.map(function (m) {
      return '<tr><td class="tabela-principal" data-rotulo="Movimento">' + esc(m.mov) + '</td><td data-rotulo="Data">' + esc(m.data) + '</td><td data-rotulo="Quantidade"><span class="' + (m.qtd > 0 ? 'fi-qtd-mais' : 'fi-qtd-menos') + '">' + qtdTexto(m.qtd) + '</span></td>'
        + (comProf ? '<td data-rotulo="Professor">' + esc(m.prof || '—') + '</td>' : '') + '<td data-rotulo="Origem">' + esc(m.fonte) + '</td>'
        + '<td class="tabela-acoes" data-rotulo="Ações">' + botao('Ver detalhe', 'btn-secondary btn-sm', 'data-detalhe="' + m.id + '" aria-label="Ver detalhe: ' + esc(m.mov) + ' de ' + esc(m.data) + '"') + '</td></tr>';
    });
    var colunas = ['Movimento', 'Data', 'Quantidade'].concat(comProf ? ['Professor'] : []).concat(['Origem', 'Ações']);
    return h + tabela('Histórico de créditos', colunas, lr, linhas.length + ' movimento(s). Aluno e aplicação aparecem no detalhe.') + '</section>';
  }

  /* ---------- Tela 3: Limites por professor / Franquia das escolas ---------- */
  function situacao(l) {
    if (l.limite == null) { pend('regra para professor sem limite definido'); return '<span class="badge badge-muted">Sem limite definido</span>'; }
    if (l.usado >= l.limite) return '<span class="badge badge-danger">Limite atingido</span>';
    if (l.usado / l.limite >= 0.9) return '<span class="badge badge-warn">Perto do limite</span>';
    return '<span class="badge badge-success">Dentro do limite</span>';
  }
  function limites() {
    var pf = p(), c = estado.cenario;
    if (pf.tipo !== 'responsavel' || c === 'negado') { pend('quem pode ver e alterar limites'); return estadoTela('Acesso negado', 'Seu perfil não gerencia limites neste contexto.', 'Os limites mensais por professor são do responsável financeiro institucional.', botao('Voltar à visão', 'btn-secondary', 'data-aba="visao"')); }
    var nucleo = pf.nivel === 'nucleo';
    pend('quem altera ' + (nucleo ? 'alocações' : 'limites')); pend('fim do ciclo do limite');
    var h = '<section class="card card-pad tabela-lista-principal tabela-principal-integrada"><h2 class="fi-h2">' + (nucleo ? 'Franquia alocada às escolas' : 'Limites mensais por professor') + '</h2>'
      + '<p class="page-sub fi-sem-topo">' + (nucleo ? 'A escola só consome a franquia do núcleo por alocação explícita. Alocar não transfere créditos.' : 'O limite governa o consumo do saldo da escola. Ele não cria saldo pessoal nem transfere créditos ao professor.') + ' O ajuste é feito no aplicativo financeiro.</p>';
    if (c === 'carregando') return h + carregando('Carregando ' + (nucleo ? 'as alocações' : 'os limites') + '…') + '</section>';
    if (c === 'indisponivel') return h + aviso('erro', 'Não foi possível carregar ' + (nucleo ? 'as alocações' : 'os limites') + '. Nenhum valor antigo é mostrado como atual.<div class="fi-acoes">' + botao('Tentar novamente', 'btn-secondary', 'data-acao="atualizar"') + '</div>', true) + '</section>';
    if (c === 'vazio') return h + vazio(nucleo ? 'Nenhuma alocação neste ciclo.' : 'Nenhum limite definido.', nucleo ? ' As escolas não consomem a franquia do núcleo sem alocação.' : ' Os limites aparecem aqui depois de definidos no aplicativo financeiro.') + '</section>';
    if (nucleo) {
      var la = ALOCACOES.map(function (a, i) {
        return '<tr><td class="tabela-principal" data-rotulo="Escola">' + esc(a.escola) + '</td><td data-rotulo="Alocada no ciclo">' + num(a.alocada) + '</td><td data-rotulo="Usada">' + num(c === 'limite' && i === 0 ? a.alocada : a.usada) + '</td><td class="tabela-acoes" data-rotulo="Ações">' + botao('Abrir alocação no aplicativo financeiro', 'btn-secondary btn-sm', 'data-encaminhar="alocacao" data-alvo="' + esc(a.escola) + '" aria-label="Abrir alocação de ' + esc(a.escola) + ' no aplicativo financeiro"') + '</td></tr>';
      });
      return h + tabela('Franquia alocada às escolas', ['Escola', 'Alocada no ciclo', 'Usada', 'Ações'], la, ALOCACOES.length + ' escolas. Valores fictícios.') + '</section>';
    }
    var ll = LIMITES.map(function (l) {
      return '<tr><td class="tabela-principal" data-rotulo="Professor">' + esc(l.prof) + '</td><td data-rotulo="Limite mensal">' + (l.limite == null ? '—' : num(l.limite)) + '</td><td data-rotulo="Usado no mês">' + num(l.usado) + '</td><td data-rotulo="Situação">' + situacao(l) + '</td><td class="tabela-acoes" data-rotulo="Ações">' + botao('Abrir limite no aplicativo financeiro', 'btn-secondary btn-sm', 'data-encaminhar="limite" data-alvo="' + esc(l.prof) + '" aria-label="Abrir limite de ' + esc(l.prof) + ' no aplicativo financeiro"') + '</td></tr>';
    });
    return h + tabela('Limites mensais por professor', ['Professor', 'Limite mensal', 'Usado no mês', 'Situação', 'Ações'], ll, LIMITES.length + ' professores. Valores fictícios.') + '</section>';
  }

  /* ---------- Tela 4: Leitor360, envio ao modo servidor ---------- */
  function leitorPagina() {
    var pf = p();
    var sec = document.querySelector('.tela-mk[data-tela="leitor"]');
    var card = sec.querySelector('.card');
    var bloqueio = sec.querySelector('[data-fi-bloqueio]');
    if (!pf.corrige) {
      card.hidden = true;
      if (!bloqueio) { bloqueio = document.createElement('div'); bloqueio.setAttribute('data-fi-bloqueio', ''); sec.appendChild(bloqueio); }
      bloqueio.innerHTML = estadoTela('Acesso negado', 'Este perfil não corrige provas.', 'O modo servidor é usado por quem corrige. ' + (pf.tipo === 'financeiro' ? 'O Financeiro só acompanha os totais.' : 'O núcleo acompanha o consumo das escolas.'), botao('Ver créditos', 'btn-secondary', 'data-ir="visao"'));
      return;
    }
    card.hidden = false; if (bloqueio) bloqueio.remove();
    $('fi-leitor-resultado').innerHTML = estado.enviado ? aviso('sucesso', '<b>12 folhas entraram na fila.</b> Posição 3. Você recebe um aviso ao terminar.') : '';
  }
  function modalEnvio(forcar) {
    var pf = p(), c = forcar || estado.cenario;
    if (c === 'carregando') {
      abrirModal('Usar a correção no servidor', carregando('Consultando seus créditos no aplicativo financeiro…'), [['Cancelar', 'btn-secondary']]);
      clearTimeout(temporizador); temporizador = setTimeout(function () { if (modalAberto()) modalEnvio('sucesso'); }, 1200);
      return;
    }
    var recusa = function (titulo, texto, extra, botoes) {
      abrirModal(titulo, aviso('atencao', '<b>' + esc(texto) + '</b> Nada foi enviado nem cobrado.', true) + '<p>A leitura no aparelho e a revisão manual continuam disponíveis.</p>' + (extra || ''), botoes);
    };
    if (c === 'sem-saldo') return recusa('Envio recusado', 'Sem créditos para este envio.', '<p>' + (pf.tipo === 'professor-instituicao' ? 'Peça ao responsável financeiro da sua escola.' : 'A compra de créditos é feita no aplicativo financeiro.') + '</p>', [['Fechar', 'btn-secondary'], ['Ver créditos', 'btn-primary', 'visao']]);
    if (c === 'limite' && pf.tipo === 'professor-instituicao') return recusa('Envio recusado', 'Você atingiu seu limite mensal de créditos.', '<p>Fale com o responsável financeiro da escola. O saldo da escola não aparece aqui.</p><ul class="fi-pendentes"><li>' + selo('quando o limite renova (fim do ciclo)') + '</li></ul>', [['Fechar', 'btn-secondary'], ['Ver meu limite', 'btn-primary', 'visao']]);
    if (c === 'limite' && pf.tipo === 'responsavel') return recusa('Envio recusado', 'A escola atingiu o limite de consumo da instituição.', '<p>O limite é definido no aplicativo financeiro.</p>', [['Fechar', 'btn-secondary'], ['Ver créditos', 'btn-primary', 'visao']]);
    if (c === 'negado') return recusa('Envio recusado', 'Você não tem permissão para enviar neste contexto.', '', [['Fechar', 'btn-secondary'], ['Trocar vínculo', 'btn-primary', '#trocar']]);
    if (c === 'indisponivel') return recusa('Envio não feito', 'Não foi possível confirmar seus créditos agora. Tente de novo em instantes.', '', [['Fechar', 'btn-secondary'], ['Tentar novamente', 'btn-primary', '#tentar-envio']]);
    /* Autorizado (sucesso, vazio e limite no espaço pessoal) */
    var saldo = pf.tipo === 'pessoal' ? 'Saldo do espaço pessoal: 42 créditos.' : pf.tipo === 'professor-instituicao' ? 'Seu limite mensal: 33 créditos restantes.' : 'Saldo da escola: 1.240 créditos.';
    var corpo = (estado.simulacao ? aviso('info', '<b>Simulação.</b> A autorização vem do simulador da homologação. Nada vira cobrança.') : '')
      + '<dl class="fi-dados"><dt>Contexto de cobrança</dt><dd>' + esc(pf.contexto) + '</dd><dt>Folhas</dt><dd>12 alunos nesta aplicação</dd><dt>Créditos</dt><dd>Até 12: 1 por aluno nesta aplicação (valor de teste). ' + esc(saldo) + '</dd></dl>'
      + '<p class="fi-modal-aviso">Reenviar o mesmo aluno nesta aplicação não cobra de novo. Folha que falhar não é cobrada.</p>'
      + '<p>A correção entra numa fila. Você recebe um aviso ao terminar.</p>'
      + (c === 'limite' && pf.tipo === 'pessoal' ? '<p class="page-sub">O espaço pessoal não tem limite mensal por professor.</p>' : '');
    abrirModal('Usar a correção no servidor', corpo, [['Cancelar', 'btn-secondary'], ['Enviar ao servidor', 'btn-primary', '#confirmar-envio']]);
  }

  /* ---------- Detalhes e encaminhamentos ---------- */
  function detalhe(id) {
    var m = linhasBase().filter(function (x) { return x.id === id; })[0]; if (!m) return;
    var pf = p(), temProva = !!m.aluno;
    var dadoAluno = !temProva ? '<span class="fi-restrito">Não se aplica</span>' : pf.veAluno ? esc(m.aluno) : '<span class="fi-restrito">Dado restrito</span>';
    var dadoAplic = !temProva ? '<span class="fi-restrito">Não se aplica</span>' : pf.veAluno ? esc(m.aplic) : '<span class="fi-restrito">Dado restrito</span>';
    var corpo = '<dl class="fi-dados">'
      + '<dt>Movimento</dt><dd>' + esc(m.mov) + '</dd><dt>Data e hora</dt><dd>' + esc(m.data) + '</dd><dt>Quantidade</dt><dd>' + qtdTexto(m.qtd) + '</dd>'
      + '<dt>Contexto</dt><dd>' + esc(pf.contexto) + '</dd>' + (m.prof ? '<dt>Professor</dt><dd>' + esc(m.prof) + '</dd>' : '')
      + '<dt>Aluno</dt><dd>' + dadoAluno + '</dd><dt>Aplicação</dt><dd>' + dadoAplic + '</dd>'
      + '<dt>Referência da operação</dt><dd><code>' + esc(m.ref) + '</code></dd><dt>Origem do valor</dt><dd>' + esc(m.fonte) + '</dd><dt>Origem do dado</dt><dd>' + esc(origem()) + '</dd></dl>'
      + (temProva && !pf.veAluno ? '<p class="page-sub">Aluno e aplicação aparecem só para quem tem acesso à prova.</p>' : '')
      + (m.mov === 'Reenvio sem cobrança' ? aviso('info', 'O reenvio usou a mesma operação do aluno nesta aplicação. Nenhum crédito novo foi consumido.') : '');
    abrirModal('Detalhe do movimento', corpo, [['Fechar', 'btn-primary']]);
  }
  function detalheTotal(id) {
    var t = TOTAIS.filter(function (x) { return x.id === id; })[0]; if (!t) return;
    abrirModal('Totais do cliente', '<dl class="fi-dados"><dt>Cliente</dt><dd>' + esc(t.cliente) + '</dd><dt>Consumos no mês</dt><dd>' + num(t.consumos) + '</dd><dt>Liberações sem cobrança</dt><dd>' + num(t.liberacoes) + '</dd><dt>Reenvios sem cobrança</dt><dd>' + num(t.reenvios) + '</dd><dt>Alunos e aplicações</dt><dd><span class="fi-restrito">Dado restrito ao contexto pedagógico</span></dd><dt>Origem do dado</dt><dd>' + esc(origem()) + '</dd></dl>', [['Fechar', 'btn-primary']]);
  }
  function encaminhar(tipo, alvo) {
    var o = {
      comprar: ['Abrir compra no aplicativo financeiro', 'A compra de créditos para ' + (p().tipo === 'pessoal' ? 'o seu espaço pessoal' : 'a instituição') + ' é feita no aplicativo financeiro.', ['formato da compra avulsa', 'arrependimento e reembolso (revisão jurídica)']],
      limite: ['Abrir limite no aplicativo financeiro', 'O limite mensal de ' + alvo + ' é ajustado no aplicativo financeiro. O limite não transfere créditos ao professor.', ['quem pode alterar limites', 'fim do ciclo do limite']],
      alocacao: ['Abrir alocação no aplicativo financeiro', 'A alocação de franquia para ' + alvo + ' é ajustada no aplicativo financeiro. Alocar não transfere créditos.', ['quem pode alterar alocações']]
    }[tipo];
    var corpo = '<p>' + esc(o[1]) + '</p><p>Lá, o aplicativo financeiro confere a sua identidade e as suas permissões. O Gabarito360 não grava saldo, limite nem compra.</p>'
      + '<ul class="fi-pendentes"><li>' + selo('lugar da ação: painel externo, navegação integrada ou tela limitada') + '</li>' + o[2].map(function (x) { return '<li>' + selo(x) + '</li>'; }).join('') + '</ul>';
    abrirModal(o[0], corpo, [['Cancelar', 'btn-secondary'], ['Abrir aplicativo financeiro', 'btn-primary', '#encaminhado']]);
  }
  function filtros() {
    var pf = p(), f = estado.filtros;
    var movs = linhasBase().map(function (m) { return m.mov; }).filter(function (v, i, a) { return a.indexOf(v) === i; });
    var profs = MOVIMENTOS.map(function (m) { return m.prof; }).filter(function (v, i, a) { return v && a.indexOf(v) === i; });
    function op(v, t, s) { return '<option value="' + esc(v) + '"' + (s === v ? ' selected' : '') + '>' + esc(t) + '</option>'; }
    var corpo = '<div class="fi-campos">'
      + '<div class="field"><label for="fi-f-periodo">Período</label><select class="select" id="fi-f-periodo">' + op('mes', 'Este mês', f.periodo) + op('anterior', 'Mês anterior', f.periodo) + op('', 'Todos', f.periodo) + '</select></div>'
      + '<div class="field"><label for="fi-f-mov">Movimento</label><select class="select" id="fi-f-mov">' + op('', 'Todos', f.movimento) + movs.map(function (m) { return op(m, m, f.movimento); }).join('') + '</select></div>'
      + (pf.tipo === 'responsavel' ? '<div class="field"><label for="fi-f-prof">Professor</label><select class="select" id="fi-f-prof">' + op('', 'Todos', f.professor) + profs.map(function (m) { return op(m, m, f.professor); }).join('') + '</select></div>' : '')
      + '</div>';
    abrirModal('Filtrar histórico', corpo, [['Limpar campos', 'btn-ghost', '#limpar-campos'], ['Cancelar', 'btn-secondary'], ['Aplicar filtros', 'btn-primary', '#aplicar-filtros']]);
  }

  /* ---------- Modal da aplicação (foco preso, Esc fecha, foco volta ao gatilho) ---------- */
  var ultimoFoco = null;
  function modalAberto() { return $('mk-modal').classList.contains('open'); }
  function abrirModal(titulo, corpo, botoes) {
    var fundo = $('mk-modal');
    if (!modalAberto()) ultimoFoco = document.activeElement;
    $('mk-modal-titulo').textContent = titulo;
    $('mk-modal-corpo').innerHTML = corpo;
    var pe = $('mk-modal-pe'); pe.textContent = '';
    botoes.forEach(function (b) {
      var x = document.createElement('button'); x.type = 'button'; x.className = 'btn ' + b[1]; x.textContent = b[0];
      x.addEventListener('click', function () { acaoModal(b[2]); });
      pe.appendChild(x);
    });
    fundo.classList.add('open'); fundo.setAttribute('aria-hidden', 'false');
    var alvo = $('mk-modal-corpo').querySelector('select,input') || pe.querySelector('.btn-primary') || pe.querySelector('button');
    if (alvo) alvo.focus();
  }
  function fecharModal() {
    var fundo = $('mk-modal'); if (!modalAberto()) return;
    clearTimeout(temporizador);
    fundo.classList.remove('open'); fundo.setAttribute('aria-hidden', 'true');
    if (ultimoFoco && ultimoFoco.isConnected) ultimoFoco.focus();
    else focarDestino();
  }
  function focarDestino() {
    var alvo = estado.focar && document.querySelector('.tela-mk.ativa ' + estado.focar);
    estado.focar = null;
    (alvo || $('fi-titulo')).focus({ preventScroll: true });
  }
  function acaoModal(cod) {
    if (!cod) { fecharModal(); return; }
    if (cod.charAt(0) !== '#') { fecharModal(); irPara(cod); return; }
    var a = cod.slice(1);
    if (a === 'aplicar-filtros') {
      estado.filtros.periodo = $('fi-f-periodo').value;
      estado.filtros.movimento = $('fi-f-mov').value;
      estado.filtros.professor = $('fi-f-prof') ? $('fi-f-prof').value : '';
      estado.focar = '[data-acao="filtros"]'; render(); fecharModal(); return;
    }
    if (a === 'limpar-campos') { $('fi-f-periodo').value = 'mes'; $('fi-f-mov').value = ''; if ($('fi-f-prof')) $('fi-f-prof').value = ''; return; }
    if (a === 'confirmar-envio') {
      fecharModal();
      $('fi-leitor-resultado').innerHTML = estado.enviado
        ? aviso('info', '<b>Estas folhas já estão na fila.</b> O reenvio do mesmo aluno nesta aplicação não cobra de novo.')
        : aviso('sucesso', '<b>12 folhas entraram na fila.</b> Posição 3. Você recebe um aviso ao terminar.');
      estado.enviado = true; return;
    }
    if (a === 'tentar-envio') { modalEnvio(); return; }
    if (a === 'trocar') { fecharModal(); trocarVinculo(); return; }
    if (a === 'encaminhado') { estado.aviso = 'No protótipo, nada foi aberto. A ação real acontece no aplicativo financeiro.'; render(); fecharModal(); return; }
    fecharModal();
  }
  function trocarVinculo() { estado.aviso = 'No protótipo, a troca de vínculo usa o seletor "Perfil e contexto" da barra do mockup.'; if (estado.tela === 'leitor') irPara('visao'); else render(); }
  function prenderFoco(ev) {
    var caixa = document.querySelector('#mk-modal .modal');
    var itens = Array.prototype.filter.call(caixa.querySelectorAll('button:not([disabled]),[href],input,select,textarea,[tabindex="0"]'), function (n) { return n.getClientRects().length; });
    if (!itens.length) return;
    var primeiro = itens[0], ultimo = itens[itens.length - 1];
    if (ev.shiftKey && document.activeElement === primeiro) { ev.preventDefault(); ultimo.focus(); }
    else if (!ev.shiftKey && document.activeElement === ultimo) { ev.preventDefault(); primeiro.focus(); }
  }

  /* ---------- Renderização ---------- */
  /* Itens do menu por perfil (Acesso::menu()): o núcleo não corrige; o Financeiro interno só vê Painel e Créditos. */
  var OCULTOS = { 'resp-nucleo': ['Corrigir'], 'financeiro': ['Provas', 'Corrigir', 'Turmas', 'Relatórios'] };
  function atualizarCasca() {
    var pf = p(), ocultos = OCULTOS[estado.perfil] || [];
    document.querySelectorAll('.lateral-app-itens a, .nav-mobile-app a, #nav-folha nav a').forEach(function (a) {
      var rotulo = (a.getAttribute('title') || a.textContent).trim();
      a.hidden = ocultos.some(function (o) { return rotulo.indexOf(o) === 0; });
    });
    document.querySelectorAll('[data-espaco-rotulo]').forEach(function (n) { n.textContent = pf.espacoRotulo; });
    document.querySelectorAll('[data-espaco-nome]').forEach(function (n) { n.textContent = pf.espaco; });
    document.querySelectorAll('[data-espaco-perfil]').forEach(function (n) { n.textContent = pf.perfil; });
    var nome = document.querySelector('[data-conta-nome]'); if (nome) nome.textContent = pf.nome;
    var perf = document.querySelector('[data-conta-perfil]'); if (perf) perf.textContent = pf.perfil;
    var ini = document.querySelector('.avatar-conta-app .avatar-iniciais'); if (ini) ini.textContent = pf.iniciais;
    var legado = document.querySelector('[data-conta-legado]'); if (legado) legado.hidden = pf.tipo === 'financeiro';
    var menu = estado.tela === 'leitor' ? 'leitor' : 'creditos';
    document.querySelectorAll('[data-menu]').forEach(function (a) {
      var ativo = a.getAttribute('data-menu') === menu;
      a.classList.toggle('active', ativo);
      if (ativo) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
    });
    $('fi-trilha').textContent = estado.tela === 'leitor' ? 'Correção da prova' : 'Créditos';
    document.title = (estado.tela === 'leitor' ? 'Correção da prova' : 'Créditos') + ' — mockup FN-068 e FN-081 — Gabarito360';
  }
  function render() {
    atualizarCasca();
    var credito = estado.tela !== 'leitor';
    document.querySelectorAll('.tela-mk').forEach(function (s) { s.classList.toggle('ativa', s.getAttribute('data-tela') === (credito ? 'creditos' : 'leitor')); });
    if (credito) {
      pendencias = [];
      desenharAbas(); desenharTopo();
      var corpo = estado.tela === 'extrato' ? extrato() : estado.tela === 'limites' ? limites() : visao();
      $('fi-painel').innerHTML = blocoPendencias() + corpo;
    } else leitorPagina();
    ['mk-tela', 'mk-perfil', 'mk-cenario'].forEach(function (id, i) { var s = $(id); if (s) s.value = [estado.tela, estado.perfil, estado.cenario][i]; });
    var sim = $('mk-simulacao'); if (sim) sim.setAttribute('aria-pressed', String(estado.simulacao));
    if (!noQuadro) {
      var u = new URL(location.href);
      u.searchParams.set('tela', estado.tela); u.searchParams.set('perfil', estado.perfil); u.searchParams.set('cenario', estado.cenario);
      if (estado.simulacao) u.searchParams.set('sim', '1'); else u.searchParams.delete('sim');
      history.replaceState(null, '', u);
    }
    atualizarQuadro();
  }
  function irPara(tela) {
    var antes = estado.tela === 'leitor';
    estado.tela = ['visao', 'extrato', 'limites', 'leitor'].indexOf(tela) >= 0 ? tela : 'visao';
    if (estado.tela === 'limites' && p().tipo !== 'responsavel') { estado.tela = 'visao'; estado.aviso = 'Seu perfil não gerencia limites neste contexto.'; }
    render();
    if (antes !== (estado.tela === 'leitor')) window.scrollTo(0, 0);
  }
  function atualizar() {
    var anterior = estado.cenario === 'indisponivel' || estado.cenario === 'carregando' ? 'sucesso' : estado.cenario;
    estado.cenario = 'carregando'; estado.aviso = ''; render();
    clearTimeout(temporizador);
    temporizador = setTimeout(function () { estado.cenario = anterior; estado.atualizado = agora(); estado.focar = '[data-acao="atualizar"]'; render(); focarDestino(); }, 900);
  }

  /* ---------- Tema (igual a app.js: data-theme no <html> e ícone no botão #theme-toggle) ---------- */
  var LUA = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  var SOL = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>';
  function temaAtual() { return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'; }
  function aplicarTema(tema) {
    document.documentElement.setAttribute('data-theme', tema);
    var btn = $('theme-toggle');
    btn.innerHTML = tema === 'dark' ? SOL : LUA;
    btn.title = tema === 'dark' ? 'Mudar para tema claro' : 'Mudar para tema escuro';
    btn.setAttribute('aria-label', btn.title);
    var mk = $('mk-tema'); if (mk) mk.setAttribute('aria-pressed', String(tema === 'dark'));
    atualizarQuadro();
  }

  /* ---------- Seletor de aparelho: iframe na largura exata, escalado para caber ---------- */
  function aparelho() { var s = $('mk-aparelho'); return s ? s.value : '0'; }
  function atualizarQuadro() {
    if (noQuadro || !$('mk-quadro')) return;
    var quadro = $('mk-quadro'), app = $('mk-app'), saida = $('mk-escala');
    var v = aparelho(), moldura = v !== '0';
    quadro.classList.toggle('mk-oculto', !moldura); app.classList.toggle('mk-oculto', moldura);
    if (!moldura) { saida.textContent = ''; return; }
    var d = v.split('x').map(Number), w = d[0], h = d[1], iframe = $('mk-iframe');
    var src = 'financeiro.html?quadro=1&tela=' + estado.tela + '&perfil=' + estado.perfil + '&cenario=' + estado.cenario + '&tema=' + temaAtual() + (estado.simulacao ? '&sim=1' : '');
    if (iframe.getAttribute('data-src') !== src) { iframe.setAttribute('data-src', src); iframe.src = src; }
    var escala = Math.min(1, Math.max(200, quadro.clientWidth - 34) / w);
    iframe.style.width = w + 'px'; iframe.style.height = h + 'px';
    iframe.style.transform = escala < 1 ? 'scale(' + escala.toFixed(4) + ')' : '';
    quadro.style.height = Math.ceil(h * escala + 34) + 'px';
    saida.textContent = w + ' × ' + h + ' px · visualização ' + (escala < 1 ? 'reduzida a ' + Math.round(escala * 100) + '%' : 'em 100%');
  }

  /* ---------- Lateral recolhível (layout-preferencias.js): escolha só na memória ---------- */
  var escolhaMenu = null;
  function aplicarMenu() {
    var raiz = document.documentElement;
    raiz.classList.toggle('menu-recolhido', escolhaMenu ? escolhaMenu === 'recolhido' : !window.matchMedia('(min-width:1280px)').matches);
    var b = $('menu-recolher'), aberto = !raiz.classList.contains('menu-recolhido');
    b.setAttribute('aria-expanded', String(aberto));
    b.setAttribute('aria-label', aberto ? 'Recolher menu principal' : 'Expandir menu principal'); b.title = b.getAttribute('aria-label');
  }

  /* ---------- Início ---------- */
  document.addEventListener('DOMContentLoaded', function () {
    if (noQuadro) { $('mk-barra').remove(); $('mk-quadro').remove(); }
    if (PERFIS[params.get('perfil')]) estado.perfil = params.get('perfil');
    if (['sucesso', 'carregando', 'vazio', 'sem-saldo', 'limite', 'negado', 'indisponivel'].indexOf(params.get('cenario')) >= 0) estado.cenario = params.get('cenario');
    estado.simulacao = params.get('sim') === '1';
    var ap = params.get('aparelho'); if (ap && $('mk-aparelho')) $('mk-aparelho').value = ap;
    aplicarMenu();
    aplicarTema(params.get('tema') === 'dark' ? 'dark' : 'light');
    irPara(params.get('tela') || 'visao');

    $('menu-recolher').addEventListener('click', function () {
      escolhaMenu = document.documentElement.classList.contains('menu-recolhido') ? 'aberto' : 'recolhido'; aplicarMenu();
    });
    window.matchMedia('(min-width:1280px)').addEventListener('change', aplicarMenu);
    $('theme-toggle').addEventListener('click', function () { aplicarTema(temaAtual() === 'dark' ? 'light' : 'dark'); });

    /* Menu da conta */
    var conta = $('user-menu-trigger'), menuConta = $('user-menu');
    conta.addEventListener('click', function () { var abrir = menuConta.hidden; menuConta.hidden = !abrir; conta.setAttribute('aria-expanded', String(abrir)); });
    document.addEventListener('click', function (e) { if (!menuConta.hidden && !e.target.closest('.conta-container-app')) { menuConta.hidden = true; conta.setAttribute('aria-expanded', 'false'); } });

    /* Folha "Mais" do celular (layout-interacoes.js) */
    var folha = $('nav-folha'), mais = document.querySelector('.nav-inferior-mais');
    mais.addEventListener('click', function () { folha.showModal(); mais.setAttribute('aria-expanded', 'true'); folha.querySelector('[data-fechar]').focus(); });
    folha.querySelector('[data-fechar]').addEventListener('click', function () { folha.close(); });
    folha.addEventListener('close', function () { mais.setAttribute('aria-expanded', 'false'); });
    folha.addEventListener('click', function (e) {
      if (e.target !== folha) return;
      var r = folha.getBoundingClientRect();
      if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) folha.close();
    });

    /* Barra do mockup */
    if ($('mk-tela')) {
      $('mk-tela').addEventListener('change', function (e) { irPara(e.target.value); });
      $('mk-perfil').addEventListener('change', function (e) { estado.perfil = e.target.value; estado.filtros = { periodo: 'mes', movimento: '', professor: '' }; estado.aviso = ''; estado.enviado = false; render(); });
      $('mk-cenario').addEventListener('change', function (e) { clearTimeout(temporizador); estado.cenario = e.target.value; estado.aviso = ''; render(); });
      $('mk-simulacao').addEventListener('click', function () { estado.simulacao = !estado.simulacao; render(); });
      $('mk-aparelho').addEventListener('change', function () { var u = new URL(location.href); u.searchParams.set('aparelho', aparelho()); history.replaceState(null, '', u); atualizarQuadro(); });
      $('mk-tema').addEventListener('click', function () { aplicarTema(temaAtual() === 'dark' ? 'light' : 'dark'); });
      window.addEventListener('resize', atualizarQuadro);
    }

    /* Delegação de cliques da tela */
    document.addEventListener('click', function (ev) {
      var t = ev.target;
      var ir = t.closest('[data-ir]'); if (ir) { ev.preventDefault(); if (folha.open) folha.close(); if (!menuConta.hidden) { menuConta.hidden = true; conta.setAttribute('aria-expanded', 'false'); } fecharModal(); irPara(ir.getAttribute('data-ir')); return; }
      var aba = t.closest('[data-aba]'); if (aba) { ev.preventDefault(); irPara(aba.getAttribute('data-aba')); var nova = $('fi-aba-' + estado.tela); if (nova) nova.focus(); return; }
      var enc = t.closest('[data-encaminhar]'); if (enc) { encaminhar(enc.getAttribute('data-encaminhar'), enc.getAttribute('data-alvo') || ''); return; }
      var det = t.closest('[data-detalhe]'); if (det) { detalhe(det.getAttribute('data-detalhe')); return; }
      var dt = t.closest('[data-detalhe-total]'); if (dt) { detalheTotal(dt.getAttribute('data-detalhe-total')); return; }
      var ac = t.closest('[data-acao]');
      if (ac) {
        var a = ac.getAttribute('data-acao');
        if (a === 'atualizar') atualizar();
        else if (a === 'filtros') filtros();
        else if (a === 'limpar-filtros') { estado.filtros = { periodo: 'mes', movimento: '', professor: '' }; render(); }
        else if (a === 'enviar-servidor') modalEnvio();
        else if (a === 'trocar') trocarVinculo();
        else if (a === 'revisar-manual') $('fi-leitor-resultado').innerHTML = aviso('info', 'No protótipo, a revisão manual não abre. Ela continua disponível sem créditos.');
        return;
      }
      if (t.closest('[data-fechar]') && t.closest('#mk-modal')) fecharModal();
      if (t.id === 'mk-modal') fecharModal();
    });

    /* Teclado: Esc fecha, Tab preso no modal, setas nas abas */
    document.addEventListener('keydown', function (ev) {
      if (modalAberto()) {
        if (ev.key === 'Escape') { ev.preventDefault(); fecharModal(); }
        else if (ev.key === 'Tab') prenderFoco(ev);
        return;
      }
      if (ev.key === 'Escape' && !menuConta.hidden) { menuConta.hidden = true; conta.setAttribute('aria-expanded', 'false'); conta.focus(); return; }
      var aba = ev.target.closest && ev.target.closest('#fi-abas [role="tab"]');
      if (aba && (ev.key === 'ArrowRight' || ev.key === 'ArrowLeft' || ev.key === 'Home' || ev.key === 'End')) {
        ev.preventDefault();
        var lista = abas().map(function (x) { return x[0]; }), i = lista.indexOf(estado.tela);
        i = ev.key === 'Home' ? 0 : ev.key === 'End' ? lista.length - 1 : (i + (ev.key === 'ArrowRight' ? 1 : -1) + lista.length) % lista.length;
        irPara(lista[i]); $('fi-aba-' + estado.tela).focus();
      }
    });
  });
})();
