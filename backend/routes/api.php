<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\EscolaController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\NucleoController;
use App\Http\Controllers\Api\PerfilController;
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

        Route::get('/turmas', [TurmaController::class, 'index'])->name('turmas.index');
        Route::post('/turmas', [TurmaController::class, 'store'])->name('turmas.store');
        Route::get('/turmas/{turma}', [TurmaController::class, 'show'])->name('turmas.show');
        Route::patch('/turmas/{turma}', [TurmaController::class, 'update'])->name('turmas.update');
        Route::delete('/turmas/{turma}', [TurmaController::class, 'destroy'])->name('turmas.destroy');
        Route::get('/turmas/{turma}/matriculas', [TurmaController::class, 'matriculas'])->name('turmas.matriculas.index');
        Route::post('/turmas/{turma}/matriculas', [TurmaController::class, 'storeMatricula'])->name('turmas.matriculas.store');
        Route::patch('/turmas/{turma}/matriculas/{matricula}', [TurmaController::class, 'closeMatricula'])->name('turmas.matriculas.update');
    });
});
