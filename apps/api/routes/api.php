<?php

use App\Http\Controllers\Api\AuthController;
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
    });

});
