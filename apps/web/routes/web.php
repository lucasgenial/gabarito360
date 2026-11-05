<?php

use App\Http\Controllers\AlunoController;
use App\Http\Controllers\AssinaturaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\CorrecaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EscolaController;
use App\Http\Controllers\GabaritoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\MembroController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PlanoController;
use App\Http\Controllers\ProvaController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\ResultadoController;
use App\Http\Controllers\TurmaController;
use Illuminate\Support\Facades\Route;

// Saúde
Route::get('/health', fn () => response()->json(['status' => 'ok', 'app' => 'gabarito360-web']));

// Landing page (rota raiz)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Página pública de planos (pré-login)
Route::get('/planos', [PlanoController::class, 'index'])->name('planos.index');

// Fale Conosco (planos comerciais) — público
Route::get('/fale-conosco',  [LeadController::class, 'index'])->name('leads.index');
Route::post('/fale-conosco', [LeadController::class, 'store'])->middleware('throttle:5,1')->name('leads.store');
Route::get('/fale-conosco/obrigado', [LeadController::class, 'sucesso'])->name('leads.sucesso');

// Contratação assistida: URLs antigas do checkout levam ao Fale Conosco.
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// Retorno de checkout do Mercado Pago (público, sem auth)
Route::get('/checkout/sucesso',  [CheckoutController::class, 'sucesso'])->name('checkout.sucesso');
Route::get('/checkout/falha',    [CheckoutController::class, 'falha'])->name('checkout.falha');
Route::get('/checkout/pendente', [CheckoutController::class, 'pendente'])->name('checkout.pendente');

// Autenticação pública
Route::middleware('guest')->group(function () {
    Route::get('/login',            [LoginController::class, 'index'])->name('login');
    Route::post('/login',           [LoginController::class, 'store'])->name('login.store');
    Route::get('/esqueci-senha',    [LoginController::class, 'showEsqueciSenha'])->name('esqueci-senha');
    Route::post('/esqueci-senha',   [LoginController::class, 'postEsqueciSenha'])->name('esqueci-senha.store');
    Route::get('/convite',          [LoginController::class, 'showConvite'])->name('convite');
    Route::post('/convite/recusar', [LoginController::class, 'recusarConvite'])->name('convite.recusar');
    Route::get('/redefinir-senha',  [LoginController::class, 'showRedefinirSenha'])->name('redefinir-senha');
    Route::post('/redefinir-senha', [LoginController::class, 'postRedefinirSenha'])->name('redefinir-senha.store');
});

Route::get('/confirmar-email', [PerfilController::class, 'confirmarEmail'])->name('email.confirmar');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::get('/vinculos/convite', [\App\Http\Controllers\ConviteVinculoController::class, 'show'])->name('vinculos.convite');

// Rotas autenticadas
Route::middleware('autenticado')->group(function () {
    $dc=\App\Http\Controllers\DisciplinaController::class;
    Route::get('disciplinas',[$dc,'index'])->name('disciplinas.index');
    $ac=\App\Http\Controllers\AreaConhecimentoController::class;
    Route::get('areas-conhecimento',[$ac,'index'])->name('areas.index');
    Route::get('areas-conhecimento/nova',[$ac,'create'])->name('areas.create');
    Route::post('areas-conhecimento',[$ac,'store'])->name('areas.store');
    Route::get('areas-conhecimento/{id}',[$ac,'show'])->whereNumber('id')->name('areas.show');
    Route::get('areas-conhecimento/{id}/editar',[$ac,'edit'])->whereNumber('id')->name('areas.edit');
    Route::put('areas-conhecimento/{id}',[$ac,'update'])->whereNumber('id')->name('areas.update');
    Route::post('areas-conhecimento/{id}/vinculos',[$ac,'vincular'])->whereNumber('id')->name('areas.vincular');
    Route::post('areas-conhecimento/{id}/desvincular',[$ac,'desvincular'])->whereNumber('id')->name('areas.desvincular');
    Route::get('disciplinas/nova',[$dc,'create'])->name('disciplinas.create');
    Route::post('disciplinas',[$dc,'store'])->name('disciplinas.store');
    Route::get('disciplinas/{id}',[$dc,'show'])->whereNumber('id')->name('disciplinas.show');
    Route::get('disciplinas/{id}/editar',[$dc,'edit'])->whereNumber('id')->name('disciplinas.edit');
    Route::put('disciplinas/{id}',[$dc,'update'])->whereNumber('id')->name('disciplinas.update');
    Route::post('disciplinas/{id}/professores',[$dc,'indicar'])->whereNumber('id')->name('disciplinas.indicar');
    Route::post('turmas/{turma}/disciplinas',[$dc,'adicionar'])->whereNumber('turma')->name('disciplinas.adicionar');
    Route::get('turmas/{turma}/disciplinas/{componente}',[$dc,'componente'])->whereNumber('turma')->whereNumber('componente')->name('disciplinas.componente');
    Route::post('turmas/{turma}/disciplinas/{componente}/atribuicoes',[$dc,'atribuir'])->whereNumber('turma')->whereNumber('componente')->name('disciplinas.atribuir');
    Route::post('turmas/{turma}/disciplinas/{componente}/situacao',[$dc,'situacao'])->whereNumber('turma')->whereNumber('componente')->name('disciplinas.situacao');
    Route::post('turmas/{turma}/disciplinas/{componente}/atribuicoes/{atribuicao}/encerrar',[$dc,'encerrar'])->whereNumber('turma')->whereNumber('componente')->whereNumber('atribuicao')->name('disciplinas.encerrar');
    $atendimento=\App\Http\Controllers\CatalogoAtendimentoController::class;
    Route::prefix('instituicoes/solicitacoes')->name('catalogo-pedidos.')->group(function()use($atendimento){
        Route::get('',[$atendimento,'index'])->name('index');Route::get('nova',[$atendimento,'novo'])->name('novo');Route::post('',[$atendimento,'enviar'])->name('enviar');
        Route::get('municipios',[$atendimento,'municipios'])->name('municipios');Route::get('{id}',[$atendimento,'show'])->whereUuid('id')->name('show');Route::post('{id}/acoes',[$atendimento,'enviar'])->whereUuid('id')->name('acao');
    });
    Route::prefix('catalogo/atendimento')->name('catalogo-atendimento.')->group(function()use($atendimento){
        Route::get('',[$atendimento,'index'])->name('index');Route::get('novo',[$atendimento,'novo'])->name('novo');Route::post('instituicoes',[$atendimento,'enviar'])->name('cadastrar');
        Route::get('buscar',[$atendimento,'buscar'])->name('buscar');Route::get('municipios',[$atendimento,'municipios'])->name('municipios');
        Route::get('instituicoes/{unidade}',[$atendimento,'editar'])->whereUuid('unidade')->name('editar');Route::post('instituicoes/{unidade}',[$atendimento,'gravar'])->whereUuid('unidade')->name('gravar');
        Route::get('autoridades/{unidade}',[$atendimento,'autoridades'])->whereUuid('unidade')->name('autoridades');Route::post('autoridades',[$atendimento,'enviar'])->name('autorizar');
        Route::get('{id}',[$atendimento,'show'])->whereUuid('id')->name('show');Route::post('{id}/acoes',[$atendimento,'enviar'])->whereUuid('id')->name('acao');
    });
    Route::prefix('catalogo/gestao')->name('catalogo-gestao.')->group(function()use($atendimento){
        Route::get('',[$atendimento,'index'])->name('index');Route::get('municipios',[$atendimento,'municipios'])->name('municipios');
        Route::get('instituicoes/{unidade}',[$atendimento,'editar'])->whereUuid('unidade')->name('editar');Route::post('instituicoes/{unidade}',[$atendimento,'gravar'])->whereUuid('unidade')->name('gravar');
    });
    $instituicoes=\App\Http\Controllers\InstituicoesPessoaisController::class;
    Route::get('/minhas-instituicoes',[$instituicoes,'index'])->name('instituicoes.index');
    Route::get('/minhas-instituicoes/buscar',[$instituicoes,'buscar'])->name('instituicoes.buscar');
    Route::get('/minhas-instituicoes/municipios',[$instituicoes,'municipios'])->name('instituicoes.municipios');
    Route::get('/minhas-instituicoes/conferir/{unidade}',[$instituicoes,'conferir'])->whereUuid('unidade')->name('instituicoes.conferir');
    Route::post('/minhas-instituicoes',[$instituicoes,'selecionar'])->name('instituicoes.selecionar');
    Route::post('/minhas-instituicoes/{id}/arquivar',[$instituicoes,'arquivar'])->whereUuid('id')->name('instituicoes.arquivar');
    Route::get('/copias/operacao/{id}', [\App\Http\Controllers\OperacaoCopiaController::class,'show'])->whereUuid('id')->name('copias.operacao');
    Route::post('/copias/operacao/{id}/solicitar', [\App\Http\Controllers\OperacaoCopiaController::class,'solicitar'])->whereUuid('id')->name('copias.solicitar');
    Route::post('/copias/operacao/{id}/revogar', [\App\Http\Controllers\OperacaoCopiaController::class,'revogar'])->whereUuid('id')->name('copias.revogar');
    Route::get('/copias/operacao/{id}/acervo', [\App\Http\Controllers\OperacaoCopiaController::class,'acervo'])->whereUuid('id')->name('copias.acervo');
    Route::get('/copias', [\App\Http\Controllers\CopiaController::class,'index'])->name('copias.index');
    Route::get('/copias/receber-cadastro', [\App\Http\Controllers\DecisaoCadastroCopiaController::class,'receber'])->name('copias.cadastro.receber');
    Route::post('/copias/receber-cadastro', [\App\Http\Controllers\DecisaoCadastroCopiaController::class,'emitir'])->name('copias.cadastro.emitir');
    Route::post('/copias/cadastros/preparar', [\App\Http\Controllers\DecisaoCadastroCopiaController::class,'preparar'])->name('copias.cadastro.preparar');
    Route::post('/copias/cadastros/lotes', [\App\Http\Controllers\DecisaoCadastroCopiaController::class,'criar'])->name('copias.cadastro.criar');
    Route::post('/copias/cadastros/abrir', [\App\Http\Controllers\DecisaoCadastroCopiaController::class,'abrir'])->name('copias.cadastro.abrir');
    Route::get('/copias/cadastros/lotes/{id}', [\App\Http\Controllers\DecisaoCadastroCopiaController::class,'show'])->whereUuid('id')->name('copias.cadastro.decisao');
    Route::post('/copias/cadastros/lotes/{id}/evidencia', [\App\Http\Controllers\DecisaoCadastroCopiaController::class,'evidencia'])->whereUuid('id')->name('copias.cadastro.evidencia');
    Route::post('/copias/cadastros/lotes/{id}/aprovar', [\App\Http\Controllers\DecisaoCadastroCopiaController::class,'aprovar'])->whereUuid('id')->name('copias.cadastro.aprovar');
    Route::get('/copias/receber-resultados', [\App\Http\Controllers\DecisaoResultadosCopiaController::class,'receber'])->name('copias.resultado.receber');
    Route::post('/copias/receber-resultados', [\App\Http\Controllers\DecisaoResultadosCopiaController::class,'emitir'])->name('copias.resultado.emitir');
    Route::post('/copias/resultados/preparar', [\App\Http\Controllers\DecisaoResultadosCopiaController::class,'preparar'])->name('copias.resultado.preparar');
    Route::post('/copias/resultados/lotes', [\App\Http\Controllers\DecisaoResultadosCopiaController::class,'criar'])->name('copias.resultado.criar');
    Route::post('/copias/resultados/abrir', [\App\Http\Controllers\DecisaoResultadosCopiaController::class,'abrir'])->name('copias.resultado.abrir');
    Route::get('/copias/resultados/lotes/{id}', [\App\Http\Controllers\DecisaoResultadosCopiaController::class,'show'])->whereUuid('id')->name('copias.resultado.decisao');
    Route::post('/copias/resultados/lotes/{id}/evidencia', [\App\Http\Controllers\DecisaoResultadosCopiaController::class,'evidencia'])->whereUuid('id')->name('copias.resultado.evidencia');
    Route::post('/copias/resultados/lotes/{id}/aprovar', [\App\Http\Controllers\DecisaoResultadosCopiaController::class,'aprovar'])->whereUuid('id')->name('copias.resultado.aprovar');
    Route::get('/copias/resultados', [\App\Http\Controllers\SelecaoResultadosCopiaController::class,'index'])->name('copias.resultados');
    Route::post('/copias/resultados/previa', [\App\Http\Controllers\SelecaoResultadosCopiaController::class,'previa'])->name('copias.resultados.previa');
    Route::get('/copias/cadastros', [\App\Http\Controllers\SelecaoCadastroCopiaController::class,'index'])->name('copias.cadastros');
    Route::post('/copias/cadastros/previa', [\App\Http\Controllers\SelecaoCadastroCopiaController::class,'previa'])->name('copias.cadastros.previa');
    Route::get('/copias/materiais', [\App\Http\Controllers\SelecaoMaterialCopiaController::class,'index'])->name('copias.materiais');
    Route::post('/copias/materiais/previa', [\App\Http\Controllers\SelecaoMaterialCopiaController::class,'previa'])->name('copias.materiais.previa');
    Route::get('/copias/receber-material', [\App\Http\Controllers\DecisaoMaterialCopiaController::class,'receber'])->name('copias.material.receber');
    Route::post('/copias/receber-material', [\App\Http\Controllers\DecisaoMaterialCopiaController::class,'emitir'])->name('copias.material.emitir');
    Route::post('/copias/materiais/preparar', [\App\Http\Controllers\DecisaoMaterialCopiaController::class,'preparar'])->name('copias.material.preparar');
    Route::post('/copias/materiais/lotes', [\App\Http\Controllers\DecisaoMaterialCopiaController::class,'criar'])->name('copias.material.criar');
    Route::post('/copias/materiais/abrir', [\App\Http\Controllers\DecisaoMaterialCopiaController::class,'abrir'])->name('copias.material.abrir');
    Route::get('/copias/materiais/lotes/{id}', [\App\Http\Controllers\DecisaoMaterialCopiaController::class,'show'])->whereUuid('id')->name('copias.material.decisao');
    Route::post('/copias/materiais/lotes/{id}/revisar', [\App\Http\Controllers\DecisaoMaterialCopiaController::class,'revisar'])->whereUuid('id')->name('copias.material.revisar');
    Route::post('/copias/materiais/lotes/{id}/aprovar', [\App\Http\Controllers\DecisaoMaterialCopiaController::class,'aprovar'])->whereUuid('id')->name('copias.material.aprovar');
    Route::post('/vinculos/convite/consultar', [\App\Http\Controllers\ConviteVinculoController::class, 'consultar'])->middleware('throttle:20,1')->name('vinculos.convite.consultar');
    Route::post('/vinculos/convite/responder', [\App\Http\Controllers\ConviteVinculoController::class, 'responder'])->middleware('throttle:10,1')->name('vinculos.convite.responder');
    Route::get('/vinculos', [\App\Http\Controllers\Auth\VinculoController::class, 'index'])->name('vinculos.index');
    Route::post('/vinculos/interno', [\App\Http\Controllers\Auth\VinculoController::class, 'interno'])->middleware('throttle:20,1')->name('vinculos.interno');
    Route::post('/vinculos', [\App\Http\Controllers\Auth\VinculoController::class, 'store'])->middleware('throttle:20,1')->name('vinculos.store');
    Route::get('/painel',  [DashboardController::class, 'index'])->name('painel');
    Route::get('/clientes', [\App\Http\Controllers\ClienteController::class,'index'])->name('clientes.index');
    Route::get('/exportacoes-titulares', [\App\Http\Controllers\ExportacaoTitularController::class,'index'])->name('pedidos.exportacoes');
    Route::post('/exportacoes-titulares/{acao}', [\App\Http\Controllers\ExportacaoTitularController::class,'enviar'])->whereIn('acao',['conceder','revogar','gerar'])->middleware('throttle:10,1')->name('pedidos.exportacoes.enviar');
    Route::get('/exportacoes-titulares/{id}/arquivo', [\App\Http\Controllers\ExportacaoTitularController::class,'arquivo'])->whereUuid('id')->name('pedidos.exportacoes.arquivo');
    Route::get('/pedidos-titulares', [\App\Http\Controllers\PedidoTitularController::class,'index'])->name('pedidos.index');
    Route::get('/pedidos-titulares/novo', [\App\Http\Controllers\PedidoTitularController::class,'create'])->name('pedidos.create');
    Route::post('/pedidos-titulares', [\App\Http\Controllers\PedidoTitularController::class,'store'])->middleware('throttle:10,1')->name('pedidos.store');
    Route::get('/pedidos-titulares/{id}', [\App\Http\Controllers\PedidoTitularController::class,'show'])->whereUuid('id')->name('pedidos.show');
    Route::post('/pedidos-titulares/{id}/{acao}', [\App\Http\Controllers\PedidoTitularController::class,'alterar'])->whereUuid('id')->whereIn('acao',['triagem','verificacoes','decisao','sujeito'])->middleware('throttle:10,1')->name('pedidos.alterar');
    Route::get('/clientes/novo', [\App\Http\Controllers\ClienteController::class,'create'])->name('clientes.create');
    Route::post('/clientes', [\App\Http\Controllers\ClienteController::class,'store'])->name('clientes.store');
    Route::get('/clientes/{id}', [\App\Http\Controllers\ClienteController::class,'show'])->whereNumber('id')->name('clientes.show');
    Route::put('/clientes/{id}', [\App\Http\Controllers\ClienteController::class,'update'])->whereNumber('id')->name('clientes.update');
    Route::get('/acessos-internos', [\App\Http\Controllers\AcessoInternoController::class,'index'])->name('internos.index');
    Route::post('/acessos-internos', [\App\Http\Controllers\AcessoInternoController::class,'store'])->name('internos.store');
    Route::put('/acessos-internos/{id}', [\App\Http\Controllers\AcessoInternoController::class,'update'])->whereNumber('id')->name('internos.update');

    Route::get('/configuracoes/usuarios',[\App\Http\Controllers\ExcecaoFuncionalController::class,'index'])->name('funcoes.index');
    Route::post('/configuracoes/usuarios/consultar',[\App\Http\Controllers\ExcecaoFuncionalController::class,'consultar'])->name('funcoes.consultar');
    Route::post('/configuracoes/usuarios',[\App\Http\Controllers\ExcecaoFuncionalController::class,'salvar'])->name('funcoes.salvar');
    Route::post('/membros/{id}/encerrar-vinculo',[\App\Http\Controllers\MembroController::class,'encerrar'])->name('membros.encerrar');
    // Meu perfil (MP-024)
    Route::view('/referencia-visual', 'referencia.index')->name('referencia');
    Route::get('/trocar-senha',  [PerfilController::class, 'formTrocarSenha'])->name('trocar-senha');
    Route::put('/trocar-senha',  [PerfilController::class, 'trocarSenha'])->name('trocar-senha.store');
    Route::post('/perfil/imagem',[PerfilController::class,'imagem'])->name('perfil.imagem');
    Route::get('/perfil/foto',[PerfilController::class,'foto'])->name('perfil.foto');
    Route::get('/usuarios/{id}/foto',[PerfilController::class,'fotoUsuario'])->whereNumber('id')->name('usuarios.foto');
    Route::get('/perfil',        [PerfilController::class, 'show'])->name('perfil');
    Route::put('/perfil',        [PerfilController::class, 'update'])->name('perfil.update');
    Route::put('/perfil/senha',  [PerfilController::class, 'senha'])->name('perfil.senha');
    Route::post('/perfil/email', [PerfilController::class, 'email'])->name('perfil.email');

    // Configurações da rede (MP-024)
    Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->name('configuracoes.index');
    Route::put('/configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');

    // Membros da equipe (MP-013)
    Route::get('/membros',             [MembroController::class, 'index'])->name('membros.index');
    Route::get('/membros/convidar-vinculo', [\App\Http\Controllers\ConviteVinculoController::class, 'create'])->name('membros.convite-vinculo');
    Route::post('/membros/convidar-vinculo', [\App\Http\Controllers\ConviteVinculoController::class, 'store'])->name('membros.convite-vinculo.store');
    Route::get('/membros/novo',        [MembroController::class, 'create'])->name('membros.create');
    Route::get('/membros/perfis',      [MembroController::class, 'perfis'])->name('membros.perfis');
    Route::get('/membros/{id}/permissoes', [MembroController::class, 'permissoes'])->name('membros.permissoes');
    Route::put('/membros/{id}/permissoes', [MembroController::class, 'salvarPermissoes'])->name('membros.permissoes.salvar');
    Route::post('/membros',            [MembroController::class, 'store'])->name('membros.store');
    Route::get('/membros/{id}/editar', [MembroController::class, 'edit'])->name('membros.edit');
    Route::put('/membros/{id}',        [MembroController::class, 'update'])->name('membros.update');
    Route::post('/membros/{id}/toggle',[MembroController::class, 'toggle'])->name('membros.toggle');
    Route::post('/membros/{id}/convite',[MembroController::class, 'convite'])->name('membros.convite');
    Route::post('/membros/{id}/link-senha',[MembroController::class, 'linkSenha'])->name('membros.link-senha');

    // Escolas (MP-015)
    Route::get('/escolas',             [EscolaController::class, 'index'])->name('escolas.index');
    Route::post('/escolas',            [EscolaController::class, 'store'])->name('escolas.store');
    Route::get('/escolas/nova',        [EscolaController::class, 'create'])->name('escolas.create');
    Route::get('/escolas/censo/buscar',    [EscolaController::class, 'censoBuscar'])->name('escolas.censo.buscar');
    Route::get('/escolas/censo/pesquisar', [EscolaController::class, 'censoPesquisar'])->name('escolas.censo.pesquisar');
    Route::get('/escolas/{id}/editar', [EscolaController::class, 'edit'])->whereNumber('id')->name('escolas.edit');
    Route::get('/escolas/{id}',        [EscolaController::class, 'show'])->name('escolas.show');
    Route::put('/escolas/{id}',        [EscolaController::class, 'update'])->name('escolas.update');
    Route::post('/escolas/{id}/toggle',[EscolaController::class, 'toggle'])->name('escolas.toggle');
    Route::get('/escolas/{id}/logo',   [EscolaController::class, 'logo'])->name('escolas.logo');
    Route::get('/redes/{id}/logo',     [EscolaController::class, 'logoRede'])->name('redes.logo');
    Route::post('/redes/{id}/logo',    [EscolaController::class, 'enviarLogoRede'])->name('redes.logo.enviar');
    Route::post('/escolas/{id}/logo',  [EscolaController::class, 'enviarLogo'])->name('escolas.logo.enviar');

    // Alunos (MP-017)
    Route::post('/alunos/{id}/imagem', [AlunoController::class, 'imagem'])->whereNumber('id')->name('alunos.imagem');
    Route::get('/alunos/{id}/foto', [AlunoController::class, 'foto'])->whereNumber('id')->name('alunos.foto');
    Route::get('/alunos',              [AlunoController::class, 'index'])->name('alunos.index');
    Route::get('/alunos/novo',         [AlunoController::class, 'create'])->name('alunos.create');
    Route::post('/alunos',             [AlunoController::class, 'store'])->name('alunos.store');
    Route::get('/alunos/{id}',         [AlunoController::class, 'show'])->name('alunos.show');
    Route::put('/alunos/{id}',         [AlunoController::class, 'update'])->name('alunos.update');
    Route::post('/alunos/{id}/toggle', [AlunoController::class, 'toggle'])->name('alunos.toggle');

    // Turmas (MP-016) + Vínculo professor (MP-018)
    Route::get('/turmas',                              [TurmaController::class, 'index'])->name('turmas.index');
    Route::post('/turmas',                             [TurmaController::class, 'store'])->name('turmas.store');
    Route::get('/turmas/{id}',                         [TurmaController::class, 'show'])->name('turmas.show');
    Route::put('/turmas/{id}',                         [TurmaController::class, 'update'])->name('turmas.update');
    Route::post('/turmas/{id}/toggle',                 [TurmaController::class, 'toggle'])->name('turmas.toggle');
    Route::post('/turmas/{id}/professores',            [TurmaController::class, 'vincularProfessor'])->name('turmas.professores.store');
    Route::delete('/turmas/{id}/professores/{uid}',    [TurmaController::class, 'desvincularProfessor'])->name('turmas.professores.destroy');

    // Provas (MP-019)
    Route::get('/provas',             [ProvaController::class, 'index'])->name('provas.index');
    Route::get('/provas/nova',        [ProvaController::class, 'create'])->name('provas.create');
    Route::post('/provas/preparar', [\App\Http\Controllers\CurriculoProvaController::class,'preparar'])->name('provas.preparar');
    Route::get('/provas/{id}/curriculo', [\App\Http\Controllers\CurriculoProvaController::class,'editar'])->name('provas.curriculo.edit');
    Route::post('/provas/{id}/curriculo/preparar', [\App\Http\Controllers\CurriculoProvaController::class,'preparar'])->name('provas.curriculo.preparar');
    Route::post('/provas/{id}/curriculo', [\App\Http\Controllers\CurriculoProvaController::class,'salvar'])->name('provas.curriculo.salvar');
    Route::post('/provas/{id}/copiar', [ProvaController::class, 'copiar'])->name('provas.copiar.store');
    Route::post('/provas',            [ProvaController::class, 'store'])->name('provas.store');
    Route::get('/provas/{id}',        [ProvaController::class, 'show'])->name('provas.show');
    Route::put('/provas/{id}',        [ProvaController::class, 'update'])->name('provas.update');
    Route::post('/provas/{id}/publicar', [ProvaController::class, 'publicar'])->name('provas.publicar');
    Route::delete('/provas/{id}',     [ProvaController::class, 'destroy'])->name('provas.destroy');

    // Gabarito (MP-020)
    Route::get('/provas/{id}/gabarito',           [GabaritoController::class, 'show'])->name('provas.gabarito.show');
    Route::get('/provas/{id}/gabarito/editar',    [GabaritoController::class, 'edit'])->name('provas.gabarito.edit');
    Route::post('/provas/{id}/gabarito',          [GabaritoController::class, 'salvar'])->name('provas.gabarito.salvar');
    Route::post('/provas/{id}/gabarito/publicar', [GabaritoController::class, 'publicar'])->name('provas.gabarito.publicar');

    // Acompanhamento de correção / OMR (MP-021)
    Route::get('/corrigir',                     [CorrecaoController::class, 'index'])->name('correcao.index');
    Route::get('/turmas/{id}/acompanhar',       [CorrecaoController::class, 'turma'])->name('correcao.turma');
    Route::get('/provas/{id}/acompanhar',       [CorrecaoController::class, 'show'])->name('correcao.show');
    Route::get('/provas/{id}/acompanhar/status',[CorrecaoController::class, 'status'])->name('correcao.status');
    Route::get('/provas/{id}/acompanhar/cartoes',[CorrecaoController::class, 'cartoes'])->name('correcao.cartoes');
    Route::get('/provas/{id}/revisao',          [CorrecaoController::class, 'revisao'])->name('correcao.revisao');
    Route::post('/provas/{id}/correcao/fechar', [CorrecaoController::class, 'fechar'])->name('correcao.fechar');
    Route::post('/provas/{id}/correcao/reabrir',[CorrecaoController::class, 'reabrir'])->name('correcao.reabrir');
    Route::get('/provas/{id}/tentativas', [\App\Http\Controllers\TentativaCartaoController::class,'index'])->name('correcao.tentativas');
    Route::get('/tentativas/{id}', [\App\Http\Controllers\TentativaCartaoController::class,'show'])->whereUuid('id')->name('correcao.tentativa');
    Route::get('/tentativas/{id}/imagem', [\App\Http\Controllers\TentativaCartaoController::class,'imagem'])->whereUuid('id')->name('correcao.tentativa.imagem');
    Route::post('/tentativas/{id}/aplicar', [\App\Http\Controllers\TentativaCartaoController::class,'aplicar'])->whereUuid('id')->name('correcao.tentativa.aplicar');
    Route::post('/tentativas/{id}/descartar', [\App\Http\Controllers\TentativaCartaoController::class,'descartar'])->whereUuid('id')->name('correcao.tentativa.descartar');
    Route::get('/cartoes/{id}/imagem',          [CorrecaoController::class, 'imagem'])->name('cartoes.imagem');
    Route::get('/cartoes/{id}/retirada', [CorrecaoController::class,'prepararRetirada'])->name('cartoes.retirada');
    Route::delete('/cartoes/{id}',              [CorrecaoController::class, 'excluir'])->name('cartoes.excluir');
    Route::post('/provas/{id}/cartoes',         [CorrecaoController::class, 'upload'])->name('correcao.upload');
    Route::get('/provas/{id}/cartoes/envios/{chave}', [CorrecaoController::class, 'envio'])->whereUuid('chave')->name('correcao.envio');
    Route::post('/provas/{id}/cartoes/envios/{chave}/repetir', [CorrecaoController::class, 'repetirEnvio'])->whereUuid('chave')->name('correcao.envio.repetir');
    Route::get('/provas/{id}/cartoes/pdf',      [CorrecaoController::class, 'pdf'])->name('correcao.pdf');
    Route::post('/cartoes/{id}/resolver-ambiguidade', [CorrecaoController::class, 'resolverAmbiguidade'])->name('cartoes.resolver-ambiguidade');
    Route::post('/cartoes/{id}/vincular-aluno',       [CorrecaoController::class, 'vincularAluno'])->name('cartoes.vincular-aluno');
    Route::post('/cartoes/{id}/revisar',              [CorrecaoController::class, 'revisar'])->name('cartoes.revisar');

    // Resultados e relatórios (MP-022)
    Route::get('/provas/{provaId}/resultados/pdf', [ResultadoController::class, 'lote'])->name('resultados.lote');
    Route::get('/resultados/{alunoId}/{provaId}', [ResultadoController::class, 'show'])->name('resultados.show');
    Route::get('/resultados/{alunoId}/{provaId}/pdf', [ResultadoController::class, 'pdf'])->name('resultados.pdf');
    Route::get('/relatorios/prova/{id}', [RelatorioController::class, 'prova'])->name('relatorios.prova');
    Route::get('/relatorios/turma/{turmaId}/prova/{provaId}', [RelatorioController::class, 'turmaProva'])->name('relatorios.turmaProva');
    Route::get('/relatorios/turma/{turmaId}', [RelatorioController::class, 'turma'])->name('relatorios.turma');
    Route::get('/relatorios/aluno/{alunoId}', [RelatorioController::class, 'aluno'])->name('relatorios.aluno');
    Route::get('/relatorios/escola/{id?}', [RelatorioController::class, 'escola'])->name('relatorios.escola');
    Route::get('/relatorios/nucleo/{id?}', [RelatorioController::class, 'nucleo'])->name('relatorios.nucleo');
    Route::get('/relatorios/rede/{id?}',   [RelatorioController::class, 'rede'])->name('relatorios.rede');

    // Assinatura e Cobrança (MP-027)
    Route::get('/assinatura',           [AssinaturaController::class, 'index'])->name('assinatura.index');
    Route::post('/assinatura/cancelar', [AssinaturaController::class, 'cancelar'])->name('assinatura.cancelar');

});

