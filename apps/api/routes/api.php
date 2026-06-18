<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('health', fn () => response()->json(['status' => 'ok', 'app' => 'gabarito360-api']));

    // Rotas públicas de autenticação
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
    });

    // Rotas protegidas
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',     [AuthController::class, 'me']);
            Route::put('me',     [AuthController::class, 'update']);
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
        });
    });

});
