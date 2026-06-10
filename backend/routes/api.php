<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\NucleoController;
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
    });
});
