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
    Route::post('/vinculos/convite/consultar', [\App\Http\Controllers\ConviteVinculoController::class, 'consultar'])->middleware('throttle:20,1')->name('vinculos.convite.consultar');
    Route::post('/vinculos/convite/responder', [\App\Http\Controllers\ConviteVinculoController::class, 'responder'])->middleware('throttle:10,1')->name('vinculos.convite.responder');
    Route::get('/vinculos', [\App\Http\Controllers\Auth\VinculoController::class, 'index'])->name('vinculos.index');
    Route::post('/vinculos', [\App\Http\Controllers\Auth\VinculoController::class, 'store'])->middleware('throttle:20,1')->name('vinculos.store');
    Route::get('/painel',  [DashboardController::class, 'index'])->name('painel');

    // Meu perfil (MP-024)
    Route::view('/referencia-visual', 'referencia.index')->name('referencia');
    Route::get('/trocar-senha',  [PerfilController::class, 'formTrocarSenha'])->name('trocar-senha');
    Route::put('/trocar-senha',  [PerfilController::class, 'trocarSenha'])->name('trocar-senha.store');
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
    Route::get('/cartoes/{id}/imagem',          [CorrecaoController::class, 'imagem'])->name('cartoes.imagem');
    Route::delete('/cartoes/{id}',              [CorrecaoController::class, 'excluir'])->name('cartoes.excluir');
    Route::post('/provas/{id}/cartoes',         [CorrecaoController::class, 'upload'])->name('correcao.upload');
    Route::get('/provas/{id}/cartoes/envios/{chave}', [CorrecaoController::class, 'envio'])->whereUuid('chave')->name('correcao.envio');
    Route::post('/provas/{id}/cartoes/envios/{chave}/repetir', [CorrecaoController::class, 'repetirEnvio'])->whereUuid('chave')->name('correcao.envio.repetir');
    Route::get('/provas/{id}/cartoes/pdf',      [CorrecaoController::class, 'pdf'])->name('correcao.pdf');
    Route::post('/cartoes/{id}/resolver-ambiguidade', [CorrecaoController::class, 'resolverAmbiguidade'])->name('cartoes.resolver-ambiguidade');
    Route::post('/cartoes/{id}/vincular-aluno',       [CorrecaoController::class, 'vincularAluno'])->name('cartoes.vincular-aluno');
    Route::post('/cartoes/{id}/revisar',              [CorrecaoController::class, 'revisar'])->name('cartoes.revisar');

    // Resultados e relatórios (MP-022)
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

