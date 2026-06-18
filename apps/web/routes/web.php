<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MembroController;
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

    // Placeholders para MPs futuros
    Route::get('/escolas',      fn () => abort(404))->name('escolas.index');
    Route::get('/turmas',       fn () => abort(404))->name('turmas.index');
    Route::get('/provas',       fn () => abort(404))->name('provas.index');
});

Route::get('/', fn () => redirect()->route('login'));
