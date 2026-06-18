<?php

use App\Http\Controllers\AlunoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EscolaController;
use App\Http\Controllers\MembroController;
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
    Route::get('/perfil',  fn () => view('layouts.app'))->name('perfil');

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

    // Placeholders para MPs futuros
    Route::get('/provas',       fn () => abort(404))->name('provas.index');
});

Route::get('/', fn () => redirect()->route('login'));
