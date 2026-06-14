<?php

use App\Http\Controllers\Api\V2\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V2\Auth\LoginController;
use App\Http\Controllers\Api\V2\Auth\LogoutController;
use App\Http\Controllers\Api\V2\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V2\Escolas\EscolaController;
use App\Http\Controllers\Api\V2\Escolas\EscolaPerfilController;
use App\Http\Controllers\Api\V2\Escolas\MembroController;
use App\Http\Controllers\Api\V2\HealthController;
use App\Http\Controllers\Api\V2\Integracoes\IntegracaoController;
use App\Http\Controllers\Api\V2\Me\MeController;
use App\Http\Controllers\Api\V2\Me\SessaoController;
use App\Http\Controllers\Api\V2\Me\ShowFotoController;
use App\Http\Controllers\Api\V2\Me\UpdateFotoController;
use App\Http\Controllers\Api\V2\Me\UpdateMeController;
use App\Http\Controllers\Api\V2\Me\UpdatePasswordController;
use App\Http\Controllers\Api\V2\Me\UpdatePreferenciasController;
use App\Http\Controllers\Api\V2\Nucleos\NucleoController;
use App\Http\Controllers\Api\V2\Onboarding\PerfilController;
use App\Http\Controllers\Api\V2\Onboarding\SolicitacaoController;
use App\Http\Middleware\Api\V2\EnsureIdempotency;
use App\Http\Middleware\Api\V2\EnsureOrganizationalScope;
use App\Http\Middleware\EnsureMobileDeviceIsActive;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::prefix('v2')->name('api.v2.')->group(function () {
    Route::get('/health', HealthController::class)->name('health');

    // Públicas (sem autenticação)
    Route::post('/auth/login', LoginController::class)
        ->middleware('throttle:login')
        ->name('auth.login');
    Route::post('/auth/forgot-password', ForgotPasswordController::class)->name('auth.forgot-password');
    Route::post('/auth/reset-password', ResetPasswordController::class)->name('auth.reset-password');

    Route::get('/onboarding/perfis', PerfilController::class)->name('onboarding.perfis');
    Route::middleware(EnsureIdempotency::class)->group(function () {
        Route::post('/onboarding', SolicitacaoController::class)->name('onboarding.solicitar');
    });

    // Autenticadas
    Route::middleware([
        'auth:sanctum',
        EnsureUserIsActive::class,
        EnsureMobileDeviceIsActive::class,
        EnsureOrganizationalScope::class,
        EnsureIdempotency::class,
    ])->group(function () {
        Route::post('/auth/logout', LogoutController::class)->name('auth.logout');

        Route::get('/me', MeController::class)->name('me.show');
        Route::put('/me', UpdateMeController::class)->name('me.update');
        Route::put('/me/senha', UpdatePasswordController::class)->name('me.senha');
        Route::post('/me/foto', UpdateFotoController::class)->name('me.foto');
        Route::get('/me/foto', ShowFotoController::class)->name('me.foto.show');
        Route::patch('/me/preferencias', UpdatePreferenciasController::class)->name('me.preferencias');
        Route::get('/me/sessoes', [SessaoController::class, 'index'])->name('me.sessoes.index');
        Route::delete('/me/sessoes/{sessao}', [SessaoController::class, 'destroy'])->name('me.sessoes.destroy');

        // Núcleos (admin global)
        Route::get('/nucleos', [NucleoController::class, 'index'])->name('nucleos.index');
        Route::post('/nucleos', [NucleoController::class, 'store'])->name('nucleos.store');
        Route::get('/nucleos/{nucleo}', [NucleoController::class, 'show'])->name('nucleos.show');
        Route::put('/nucleos/{nucleo}', [NucleoController::class, 'update'])->name('nucleos.update');
        Route::post('/nucleos/{nucleo}/reativar', [NucleoController::class, 'reactivate'])->name('nucleos.reactivate');

        // Escolas
        Route::get('/escolas', [EscolaController::class, 'index'])->name('escolas.index');
        Route::post('/escolas', [EscolaController::class, 'store'])->name('escolas.store');
        Route::get('/escolas/{escola}', [EscolaController::class, 'show'])->name('escolas.show');
        Route::put('/escolas/{escola}', [EscolaController::class, 'update'])->name('escolas.update');
        Route::post('/escolas/{escola}/reativar', [EscolaController::class, 'reactivate'])->name('escolas.reactivate');
        Route::get('/escolas/{escola}/indicadores', [EscolaController::class, 'indicadores'])->name('escolas.indicadores');

        // Equipe (perfis e membros por escola)
        Route::get('/escolas/{escola}/perfis', [EscolaPerfilController::class, 'index'])->name('escolas.perfis.index');
        Route::put('/escolas/{escola}/perfis/{perfil}/permissoes', [EscolaPerfilController::class, 'updatePermissoes'])->name('escolas.perfis.permissoes');
        Route::get('/escolas/{escola}/membros', [MembroController::class, 'index'])->name('escolas.membros.index');
        Route::post('/escolas/{escola}/membros', [MembroController::class, 'store'])->name('escolas.membros.store');
        Route::get('/escolas/{escola}/membros/{membro}', [MembroController::class, 'show'])->name('escolas.membros.show');
        Route::put('/escolas/{escola}/membros/{membro}', [MembroController::class, 'update'])->name('escolas.membros.update');
        Route::post('/escolas/{escola}/membros/{membro}/suspender', [MembroController::class, 'suspend'])->name('escolas.membros.suspend');

        // Integrações (admin global)
        Route::get('/integracoes', [IntegracaoController::class, 'index'])->name('integracoes.index');
        Route::post('/integracoes', [IntegracaoController::class, 'store'])->name('integracoes.store');
        Route::delete('/integracoes/{integracao}', [IntegracaoController::class, 'destroy'])->name('integracoes.destroy');
        Route::post('/integracoes/{integracao}/testar', [IntegracaoController::class, 'testar'])->name('integracoes.testar');
    });
});
