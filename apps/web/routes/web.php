<?php

use App\Http\Controllers\AlunoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\CorrecaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EscolaController;
use App\Http\Controllers\GabaritoController;
use App\Http\Controllers\MembroController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProvaController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\ResultadoController;
use App\Http\Controllers\TurmaController;
use Illuminate\Support\Facades\Route;

// Saúde
Route::get('/health', fn () => response()->json(['status' => 'ok', 'app' => 'gabarito360-web']));

// Autenticação pública
Route::middleware('guest')->group(function () {
    Route::get('/login',            [LoginController::class, 'index'])->name('login');
    Route::post('/login',           [LoginController::class, 'store'])->name('login.store');
    Route::post('/cadastro',        [LoginController::class, 'cadastro'])->name('cadastro.store');
    Route::get('/esqueci-senha',    [LoginController::class, 'showEsqueciSenha'])->name('esqueci-senha');
    Route::post('/esqueci-senha',   [LoginController::class, 'postEsqueciSenha'])->name('esqueci-senha.store');
    Route::get('/redefinir-senha',  [LoginController::class, 'showRedefinirSenha'])->name('redefinir-senha');
    Route::post('/redefinir-senha', [LoginController::class, 'postRedefinirSenha'])->name('redefinir-senha.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Rotas autenticadas
Route::middleware('autenticado')->group(function () {
    Route::get('/painel',  [DashboardController::class, 'index'])->name('painel');

    // Meu perfil (MP-024)
    Route::get('/perfil',        [PerfilController::class, 'show'])->name('perfil');
    Route::put('/perfil',        [PerfilController::class, 'update'])->name('perfil.update');
    Route::put('/perfil/senha',  [PerfilController::class, 'senha'])->name('perfil.senha');

    // Configurações da rede (MP-024)
    Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->name('configuracoes.index');
    Route::put('/configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');

    // Membros da equipe (MP-013)
    Route::get('/membros',             [MembroController::class, 'index'])->name('membros.index');
    Route::get('/membros/novo',        [MembroController::class, 'create'])->name('membros.create');
    Route::post('/membros',            [MembroController::class, 'store'])->name('membros.store');
    Route::get('/membros/{id}/editar', [MembroController::class, 'edit'])->name('membros.edit');
    Route::put('/membros/{id}',        [MembroController::class, 'update'])->name('membros.update');
    Route::post('/membros/{id}/toggle',[MembroController::class, 'toggle'])->name('membros.toggle');

    // Escolas (MP-015)
    Route::get('/escolas',             [EscolaController::class, 'index'])->name('escolas.index');
    Route::post('/escolas',            [EscolaController::class, 'store'])->name('escolas.store');
    Route::get('/escolas/{id}',        [EscolaController::class, 'show'])->name('escolas.show');
    Route::put('/escolas/{id}',        [EscolaController::class, 'update'])->name('escolas.update');
    Route::post('/escolas/{id}/toggle',[EscolaController::class, 'toggle'])->name('escolas.toggle');

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
    Route::get('/provas/{id}/acompanhar',       [CorrecaoController::class, 'show'])->name('correcao.show');
    Route::get('/provas/{id}/acompanhar/status',[CorrecaoController::class, 'status'])->name('correcao.status');
    Route::post('/provas/{id}/cartoes',         [CorrecaoController::class, 'upload'])->name('correcao.upload');
    Route::post('/cartoes/{id}/resolver-ambiguidade', [CorrecaoController::class, 'resolverAmbiguidade'])->name('cartoes.resolver-ambiguidade');
    Route::post('/cartoes/{id}/revisar',              [CorrecaoController::class, 'revisar'])->name('cartoes.revisar');

    // Resultados e relatórios (MP-022)
    Route::get('/resultados/{alunoId}/{provaId}', [ResultadoController::class, 'show'])->name('resultados.show');
    Route::get('/relatorios/prova/{id}', [RelatorioController::class, 'prova'])->name('relatorios.prova');
    Route::get('/relatorios/turma/{turmaId}/prova/{provaId}', [RelatorioController::class, 'turmaProva'])->name('relatorios.turmaProva');
});

Route::get('/', fn () => redirect()->route('login'));
