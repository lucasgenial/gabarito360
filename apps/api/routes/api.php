<?php

use App\Http\Controllers\Api\AlunoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EscolaController;
use App\Http\Controllers\Api\NucleoController;
use App\Http\Controllers\Api\TurmaController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VinculoProfessorController;
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

        // Alunos
        Route::prefix('alunos')->group(function () {
            Route::get('/',            [AlunoController::class, 'index']);
            Route::post('/',           [AlunoController::class, 'store'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            Route::get('{id}',         [AlunoController::class, 'show']);
            Route::put('{id}',         [AlunoController::class, 'update'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            Route::post('{id}/toggle', [AlunoController::class, 'toggle'])->middleware('perfil:admin_rede,dir_escolar,coordenador');
        });

        // Turmas
        Route::prefix('turmas')->group(function () {
            Route::get('/',            [TurmaController::class, 'index']);
            Route::post('/',           [TurmaController::class, 'store'])->middleware('perfil:admin_rede,dir_escolar,coordenador');
            Route::get('{id}',         [TurmaController::class, 'show']);
            Route::put('{id}',         [TurmaController::class, 'update'])->middleware('perfil:admin_rede,dir_escolar,coordenador');
            Route::post('{id}/toggle', [TurmaController::class, 'toggle'])->middleware('perfil:admin_rede,dir_escolar');
            // Vínculos professor ↔ turma (MP-018)
            Route::get('{id}/professores',                [VinculoProfessorController::class, 'index']);
            Route::post('{id}/professores',               [VinculoProfessorController::class, 'store'])->middleware('perfil:admin_rede,dir_escolar,coordenador');
            Route::delete('{id}/professores/{usuarioId}', [VinculoProfessorController::class, 'destroy'])->middleware('perfil:admin_rede,dir_escolar,coordenador');
        });

        // Turmas do professor
        Route::get('usuarios/{id}/turmas', [VinculoProfessorController::class, 'turmasDoProfessor'])
            ->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');

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
