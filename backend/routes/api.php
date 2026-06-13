<?php

use App\Http\Controllers\Api\V2\HealthController;
use App\Http\Middleware\Api\V2\EnsureIdempotency;
use App\Http\Middleware\Api\V2\EnsureOrganizationalScope;
use App\Http\Middleware\EnsureMobileDeviceIsActive;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::prefix('v2')->name('api.v2.')->group(function () {
    Route::get('/health', HealthController::class)->name('health');

    Route::middleware([
        'auth:sanctum',
        EnsureUserIsActive::class,
        EnsureMobileDeviceIsActive::class,
        EnsureOrganizationalScope::class,
        EnsureIdempotency::class,
    ])->group(function () {
        // B1-B9: recursos autenticados de /api/v2 sao adicionados aqui,
        // recurso a recurso, conforme docs/v2/21-plano-backend.md.
    });
});
