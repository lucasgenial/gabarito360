<?php

use App\Http\Controllers\Api\AlunoController;
use App\Http\Controllers\Api\AplicadorTurmaController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\EscolaController;
use App\Http\Controllers\Api\GabaritoController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ModeloCartaoController;
use App\Http\Controllers\Api\NucleoController;
use App\Http\Controllers\Api\PerfilController;
use App\Http\Controllers\Api\ProvaController;
use App\Http\Controllers\Api\ProvaTurmaController;
use App\Http\Controllers\Api\StudentImportController;
use App\Http\Controllers\Api\TurmaController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Middleware\EnsureMobileDeviceIsActive;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/health', HealthController::class)->name('health');

    Route::post('/auth/login', LoginController::class)
        ->middleware('throttle:login')
        ->name('auth.login');

    Route::middleware([
        'auth:sanctum',
        EnsureUserIsActive::class,
        EnsureMobileDeviceIsActive::class,
    ])->group(function () {
        Route::post('/auth/logout', LogoutController::class)->name('auth.logout');
        Route::get('/me', MeController::class)->name('me');

        Route::get('/nucleos', [NucleoController::class, 'index'])->name('nucleos.index');
        Route::post('/nucleos', [NucleoController::class, 'store'])->name('nucleos.store');
        Route::get('/nucleos/{nucleo}', [NucleoController::class, 'show'])->name('nucleos.show');
        Route::patch('/nucleos/{nucleo}', [NucleoController::class, 'update'])->name('nucleos.update');
        Route::delete('/nucleos/{nucleo}', [NucleoController::class, 'destroy'])->name('nucleos.destroy');

        Route::get('/escolas', [EscolaController::class, 'index'])->name('escolas.index');
        Route::post('/escolas', [EscolaController::class, 'store'])->name('escolas.store');
        Route::get('/escolas/{escola}', [EscolaController::class, 'show'])->name('escolas.show');
        Route::patch('/escolas/{escola}', [EscolaController::class, 'update'])->name('escolas.update');
        Route::delete('/escolas/{escola}', [EscolaController::class, 'destroy'])->name('escolas.destroy');

        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show'])->name('usuarios.show');
        Route::patch('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::post('/usuarios/{usuario}/perfis', [UsuarioController::class, 'assignProfile'])->name('usuarios.perfis.store');
        Route::delete('/usuarios/{usuario}/perfis/{vinculo}', [UsuarioController::class, 'revokeProfile'])->name('usuarios.perfis.destroy');
        Route::post('/usuarios/{usuario}/inativar', [UsuarioController::class, 'inactivate'])->name('usuarios.inactivate');

        Route::get('/perfis', [PerfilController::class, 'index'])->name('perfis.index');

        Route::post('/alunos/importacoes', [StudentImportController::class, 'store'])->name('alunos.importacoes.store');
        Route::get('/alunos/importacoes/{importacao}', [StudentImportController::class, 'show'])->name('alunos.importacoes.show');
        Route::post('/alunos/importacoes/{importacao}/confirmar', [StudentImportController::class, 'confirm'])->name('alunos.importacoes.confirm');
        Route::get('/alunos', [AlunoController::class, 'index'])->name('alunos.index');
        Route::post('/alunos', [AlunoController::class, 'store'])->name('alunos.store');
        Route::get('/alunos/{aluno}', [AlunoController::class, 'show'])->name('alunos.show');
        Route::patch('/alunos/{aluno}', [AlunoController::class, 'update'])->name('alunos.update');
        Route::delete('/alunos/{aluno}', [AlunoController::class, 'destroy'])->name('alunos.destroy');

        Route::get('/turmas', [TurmaController::class, 'index'])->name('turmas.index');
        Route::post('/turmas', [TurmaController::class, 'store'])->name('turmas.store');
        Route::get('/turmas/{turma}', [TurmaController::class, 'show'])->name('turmas.show');
        Route::patch('/turmas/{turma}', [TurmaController::class, 'update'])->name('turmas.update');
        Route::delete('/turmas/{turma}', [TurmaController::class, 'destroy'])->name('turmas.destroy');
        Route::get('/turmas/{turma}/matriculas', [TurmaController::class, 'matriculas'])->name('turmas.matriculas.index');
        Route::post('/turmas/{turma}/matriculas', [TurmaController::class, 'storeMatricula'])->name('turmas.matriculas.store');
        Route::patch('/turmas/{turma}/matriculas/{matricula}', [TurmaController::class, 'closeMatricula'])->name('turmas.matriculas.update');
        Route::get('/turmas/{turma}/aplicadores', [AplicadorTurmaController::class, 'index'])->name('turmas.aplicadores.index');
        Route::post('/turmas/{turma}/aplicadores', [AplicadorTurmaController::class, 'store'])->name('turmas.aplicadores.store');
        Route::delete('/turmas/{turma}/aplicadores/{vinculo}', [AplicadorTurmaController::class, 'destroy'])->name('turmas.aplicadores.destroy');

        Route::get('/modelos-cartao', [ModeloCartaoController::class, 'index'])->name('modelos-cartao.index');
        Route::post('/modelos-cartao', [ModeloCartaoController::class, 'store'])->name('modelos-cartao.store');
        Route::get('/modelos-cartao/{modelo}', [ModeloCartaoController::class, 'show'])->name('modelos-cartao.show');
        Route::patch('/modelos-cartao/{modelo}', [ModeloCartaoController::class, 'update'])->name('modelos-cartao.update');
        Route::post('/modelos-cartao/{modelo}/homologar', [ModeloCartaoController::class, 'approve'])->name('modelos-cartao.approve');
        Route::delete('/modelos-cartao/{modelo}', [ModeloCartaoController::class, 'destroy'])->name('modelos-cartao.destroy');

        Route::get('/provas', [ProvaController::class, 'index'])->name('provas.index');
        Route::post('/provas', [ProvaController::class, 'store'])->name('provas.store');
        Route::get('/provas/{prova}', [ProvaController::class, 'show'])->name('provas.show');
        Route::patch('/provas/{prova}', [ProvaController::class, 'update'])->name('provas.update');
        Route::post('/provas/{prova}/publicar', [ProvaController::class, 'publish'])->name('provas.publish');
        Route::get('/provas/{prova}/turmas', [ProvaTurmaController::class, 'index'])->name('provas.turmas.index');
        Route::post('/provas/{prova}/turmas', [ProvaTurmaController::class, 'store'])->name('provas.turmas.store');
        Route::delete('/provas/{prova}/turmas/{turma}', [ProvaTurmaController::class, 'destroy'])->name('provas.turmas.destroy');
        Route::get('/provas/{prova}/questoes', [ProvaController::class, 'questions'])->name('provas.questoes.index');
        Route::post('/provas/{prova}/questoes', [ProvaController::class, 'storeQuestion'])->name('provas.questoes.store');
        Route::get('/provas/{prova}/questoes/{questao}', [ProvaController::class, 'showQuestion'])->name('provas.questoes.show');
        Route::patch('/provas/{prova}/questoes/{questao}', [ProvaController::class, 'updateQuestion'])->name('provas.questoes.update');
        Route::get('/provas/{prova}/gabaritos', [GabaritoController::class, 'index'])->name('provas.gabaritos.index');
        Route::post('/provas/{prova}/gabaritos', [GabaritoController::class, 'store'])->name('provas.gabaritos.store');
        Route::get('/provas/{prova}/gabaritos/{gabarito}', [GabaritoController::class, 'show'])->name('provas.gabaritos.show');
        Route::get('/provas/{prova}/gabaritos/{gabarito}/validacao', [GabaritoController::class, 'validation'])->name('provas.gabaritos.validation');
        Route::get('/provas/{prova}/gabaritos/{gabarito}/respostas', [GabaritoController::class, 'responses'])->name('provas.gabaritos.respostas.index');
        Route::put('/provas/{prova}/gabaritos/{gabarito}/respostas/{questao}', [GabaritoController::class, 'upsertResponse'])->name('provas.gabaritos.respostas.upsert');
    });
});
