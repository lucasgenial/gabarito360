<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EscolaController;
use App\Http\Controllers\Api\NucleoController;
use App\Http\Controllers\Api\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('health', fn () => response()->json(['status' => 'ok', 'app' => 'gabarito360-api']));

    // Rotas públicas de autenticação
    Route::prefix('auth')->group(function () {
        Route::post('login',           [AuthController::class, 'login']);
        Route::post('registro',        [AuthController::class, 'registro']);
        Route::post('esqueci-senha',   [AuthController::class, 'esqueceuSenha']);
        Route::post('redefinir-senha', [AuthController::class, 'redefinirSenha']);
    });

    // Rotas protegidas
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',     [AuthController::class, 'me']);
            Route::put('me',     [AuthController::class, 'update']);
        });

        // Escolas
        Route::prefix('escolas')->group(function () {
            Route::get('/',           [EscolaController::class, 'index']);
            Route::post('/',          [EscolaController::class, 'store'])->middleware('perfil:admin_rede');
            Route::get('{id}',        [EscolaController::class, 'show']);
            Route::put('{id}',        [EscolaController::class, 'update'])->middleware('perfil:admin_rede,dir_escolar');
            Route::post('{id}/toggle',[EscolaController::class, 'toggle'])->middleware('perfil:admin_rede');
        });

        // Núcleos
        Route::prefix('nucleos')->group(function () {
            Route::get('/',     [NucleoController::class, 'index'])->middleware('perfil:admin_rede,dir_nucleo');
            Route::post('/',    [NucleoController::class, 'store'])->middleware('perfil:admin_rede');
            Route::get('{id}',  [NucleoController::class, 'show'])->middleware('perfil:admin_rede,dir_nucleo');
            Route::put('{id}',  [NucleoController::class, 'update'])->middleware('perfil:admin_rede,dir_nucleo');
            Route::delete('{id}',[NucleoController::class, 'destroy'])->middleware('perfil:admin_rede');
        });

        // Usuários / equipe
        Route::prefix('usuarios')->group(function () {
            Route::get('/',          [UsuarioController::class, 'index'])->middleware('perfil:admin_rede,dir_escolar');
            Route::post('/',         [UsuarioController::class, 'store'])->middleware('perfil:admin_rede,dir_escolar');
            Route::get('{id}',       [UsuarioController::class, 'show'])->middleware('perfil:admin_rede,dir_escolar');
            Route::put('{id}',       [UsuarioController::class, 'update'])->middleware('perfil:admin_rede,dir_escolar');
            Route::post('{id}/toggle',[UsuarioController::class, 'toggle'])->middleware('perfil:admin_rede,dir_escolar');
        });

        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('admin',      [DashboardController::class, 'admin'])
                ->middleware('perfil:admin_rede');
            Route::get('dir-nucleo',  [DashboardController::class, 'dirNucleo'])
                ->middleware('perfil:dir_nucleo');
            Route::get('dir-escolar',  [DashboardController::class, 'dirEscolar'])
                ->middleware('perfil:dir_escolar');
            Route::get('coordenador',  [DashboardController::class, 'coordenador'])
                ->middleware('perfil:coordenador');
            Route::get('professor',    [DashboardController::class, 'professor'])
                ->middleware('perfil:professor');
            Route::get('aluno',        [DashboardController::class, 'aluno'])
                ->middleware('perfil:aluno');
        });
    });

});
