<?php

use App\Http\Controllers\Web\Admin\EscolaController;
use App\Http\Controllers\Web\Admin\NucleoController;
use App\Http\Controllers\Web\Admin\OrganizationPanelController;
use App\Http\Controllers\Web\Admin\UsuarioController;
use App\Http\Controllers\Web\Auth\AuthenticatedSessionController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('admin.login.store');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', EnsureUserIsActive::class])
    ->group(function () {
        Route::get('/', OrganizationPanelController::class)->name('index');
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/nucleos', [NucleoController::class, 'index'])->name('nucleos.index');
        Route::post('/nucleos', [NucleoController::class, 'store'])->name('nucleos.store');
        Route::get('/nucleos/{nucleo}/editar', [NucleoController::class, 'edit'])->name('nucleos.edit');
        Route::patch('/nucleos/{nucleo}', [NucleoController::class, 'update'])->name('nucleos.update');
        Route::delete('/nucleos/{nucleo}', [NucleoController::class, 'destroy'])->name('nucleos.destroy');

        Route::get('/escolas', [EscolaController::class, 'index'])->name('escolas.index');
        Route::post('/escolas', [EscolaController::class, 'store'])->name('escolas.store');
        Route::get('/escolas/{escola}/editar', [EscolaController::class, 'edit'])->name('escolas.edit');
        Route::patch('/escolas/{escola}', [EscolaController::class, 'update'])->name('escolas.update');
        Route::delete('/escolas/{escola}', [EscolaController::class, 'destroy'])->name('escolas.destroy');

        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::patch('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::post('/usuarios/{usuario}/perfis', [UsuarioController::class, 'assignProfile'])->name('usuarios.perfis.store');
        Route::delete('/usuarios/{usuario}/perfis/{vinculo}', [UsuarioController::class, 'revokeProfile'])->name('usuarios.perfis.destroy');
        Route::post('/usuarios/{usuario}/inativar', [UsuarioController::class, 'inactivate'])->name('usuarios.inactivate');
    });
