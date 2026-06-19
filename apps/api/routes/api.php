<?php

use App\Http\Controllers\Api\AlunoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartaoController;
use App\Http\Controllers\Api\ConfiguracaoController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EscolaController;
use App\Http\Controllers\Api\GabaritoController;
use App\Http\Controllers\Api\NucleoController;
use App\Http\Controllers\Api\ProvaController;
use App\Http\Controllers\Api\RelatorioController;
use App\Http\Controllers\Api\ResultadoController;
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

        // Provas
        Route::prefix('provas')->group(function () {
            Route::get('/',             [ProvaController::class, 'index']);
            Route::post('/',            [ProvaController::class, 'store'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            Route::get('{id}',          [ProvaController::class, 'show']);
            Route::put('{id}',          [ProvaController::class, 'update'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            Route::post('{id}/publicar',[ProvaController::class, 'publicar'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            Route::delete('{id}',       [ProvaController::class, 'destroy'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            // Gabarito (MP-020)
            Route::get('{id}/gabarito',          [GabaritoController::class, 'show']);
            Route::post('{id}/gabarito',         [GabaritoController::class, 'salvar'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            Route::post('{id}/gabarito/publicar',[GabaritoController::class, 'publicar'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            // Cartões / OMR (MP-021)
            Route::get('{id}/cartoes',        [CartaoController::class, 'index']);
            Route::get('{id}/cartoes/status', [CartaoController::class, 'status']);
            Route::post('{id}/cartoes',       [CartaoController::class, 'store'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor,aplicador');
        });

        // Cartões / OMR (MP-021)
        Route::prefix('cartoes')->group(function () {
            Route::get('{id}',                       [CartaoController::class, 'show']);
            Route::put('{id}/vincular-aluno',         [CartaoController::class, 'vincularAluno'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            Route::post('{id}/resolver-ambiguidade',  [CartaoController::class, 'resolverAmbiguidade'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            Route::post('{id}/revisar',               [CartaoController::class, 'revisar'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
        });

        // Alunos
        Route::prefix('alunos')->group(function () {
            Route::get('/',            [AlunoController::class, 'index']);
            Route::post('/',           [AlunoController::class, 'store'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            Route::get('{id}',         [AlunoController::class, 'show']);
            Route::put('{id}',         [AlunoController::class, 'update'])->middleware('perfil:admin_rede,dir_escolar,coordenador,professor');
            Route::post('{id}/toggle', [AlunoController::class, 'toggle'])->middleware('perfil:admin_rede,dir_escolar,coordenador');
            // Resultado individual (MP-022)
            Route::get('{id}/resultados/{provaId}', [ResultadoController::class, 'show']);
        });

        // Relatórios (MP-022)
        Route::prefix('relatorios')->group(function () {
            Route::get('prova/{id}',                       [RelatorioController::class, 'prova']);
            Route::get('turma/{turmaId}/prova/{provaId}',   [RelatorioController::class, 'turmaProva']);
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

        // Configurações da rede (MP-024)
        Route::prefix('configuracoes')->group(function () {
            Route::get('/', [ConfiguracaoController::class, 'show'])->middleware('perfil:admin_rede');
            Route::put('/', [ConfiguracaoController::class, 'update'])->middleware('perfil:admin_rede');
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
            Route::get('secretaria',  [DashboardController::class, 'secretaria'])
                ->middleware('perfil:secretario_educacao');
            Route::get('aplicador',   [DashboardController::class, 'aplicador'])
                ->middleware('perfil:aplicador');
        });
    });

});
