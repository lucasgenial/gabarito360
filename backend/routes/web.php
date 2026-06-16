<?php

use App\Http\Controllers\Web\Admin\EscolaController;
use App\Http\Controllers\Web\Admin\NucleoController;
use App\Http\Controllers\Web\Admin\OrganizationPanelController;
use App\Http\Controllers\Web\Admin\ProvaTurmaController;
use App\Http\Controllers\Web\Admin\UsuarioController;
use App\Http\Controllers\Web\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\Portal\AcademicController;
use App\Http\Controllers\Web\Portal\AccountController;
use App\Http\Controllers\Web\Portal\AssessmentController;
use App\Http\Controllers\Web\Portal\DashboardController;
use App\Http\Controllers\Web\Portal\OperationController;
use App\Http\Controllers\Web\Portal\OrganizationController;
use App\Http\Controllers\Web\Portal\ReportController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/painel');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('admin.login.create');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('admin.login.store');
});

Route::middleware(['auth', EnsureUserIsActive::class])
    ->name('portal.')
    ->group(function () {
        Route::get('/painel', DashboardController::class)->name('dashboard');
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/perfil', [AccountController::class, 'profile'])->name('profile');
        Route::patch('/perfil', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::get('/configuracoes', [AccountController::class, 'settings'])->name('settings');
        Route::patch('/configuracoes/preferencias', [AccountController::class, 'updatePreferences'])->name('settings.preferences');
        Route::patch('/configuracoes/senha', [AccountController::class, 'updatePassword'])->name('settings.password');

        Route::get('/escolas', [OrganizationController::class, 'index'])->name('schools.index');
        Route::post('/escolas', [OrganizationController::class, 'store'])->name('schools.store');
        Route::get('/escolas/{escola}', [OrganizationController::class, 'show'])->name('schools.show');
        Route::patch('/escolas/{escola}', [OrganizationController::class, 'update'])->name('schools.update');
        Route::post('/escolas/{escola}/reativar', [OrganizationController::class, 'reactivate'])->name('schools.reactivate');
        Route::get('/escolas/{escola}/equipe', [OrganizationController::class, 'team'])->name('schools.team');
        Route::get('/escolas/{escola}/equipe/novo', [OrganizationController::class, 'createMember'])->name('schools.team.create');
        Route::get('/escolas/{escola}/equipe/{usuario}/editar', [OrganizationController::class, 'editMember'])->name('schools.team.edit');

        Route::get('/turmas', [AcademicController::class, 'index'])->name('classes.index');
        Route::post('/turmas', [AcademicController::class, 'storeClass'])->name('classes.store');
        Route::get('/turmas/{turma}', [AcademicController::class, 'showClass'])->name('classes.show');
        Route::get('/turmas/{turma}/alunos/novo', [AcademicController::class, 'createStudent'])->name('students.create');
        Route::post('/turmas/{turma}/alunos', [AcademicController::class, 'storeStudent'])->name('students.store');
        Route::post('/alunos/importacoes', [AcademicController::class, 'importStudents'])->name('students.import');
        Route::get('/alunos/{aluno}', [AcademicController::class, 'showStudent'])->name('students.show');
        Route::get('/alunos/{aluno}/editar', [AcademicController::class, 'editStudent'])->name('students.edit');
        Route::patch('/alunos/{aluno}', [AcademicController::class, 'updateStudent'])->name('students.update');

        Route::get('/provas', [AssessmentController::class, 'index'])->name('exams.index');
        Route::get('/provas/nova', [AssessmentController::class, 'create'])->name('exams.create');
        Route::post('/provas', [AssessmentController::class, 'store'])->name('exams.store');
        Route::get('/provas/{prova}', [AssessmentController::class, 'show'])->name('exams.show');
        Route::get('/provas/{prova}/gabarito', [AssessmentController::class, 'answerKey'])->name('exams.answer-key');

        Route::get('/correcoes', [OperationController::class, 'index'])->name('operations.index');
        Route::get('/aplicacoes/{aplicacao}/correcao', [OperationController::class, 'show'])->name('operations.show');
        Route::post('/aplicacoes/{aplicacao}/iniciar', [OperationController::class, 'start'])->name('operations.start');
        Route::post('/aplicacoes/{aplicacao}/finalizar', [OperationController::class, 'finish'])->name('operations.finish');
        Route::post('/leituras/{leitura}/revisar', [OperationController::class, 'review'])->name('operations.readings.review');
        Route::post('/leituras/{leitura}/confirmar', [OperationController::class, 'confirm'])->name('operations.readings.confirm');
        Route::post('/aplicacoes/{aplicacao}/relatorio.csv', [OperationController::class, 'exportCsv'])->name('operations.report.csv');

        Route::get('/resultados/{resultado}', [ReportController::class, 'result'])->name('results.show');
        Route::get('/provas/{prova}/relatorio', [ReportController::class, 'exam'])->name('reports.exam');
        Route::get('/turmas/{turma}/provas/{prova}/relatorio', [ReportController::class, 'classExam'])->name('reports.class-exam');
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

        Route::get('/provas', [ProvaTurmaController::class, 'index'])->name('provas.index');
        Route::post('/provas/{prova}/turmas', [ProvaTurmaController::class, 'store'])->name('provas.turmas.store');
        Route::delete('/provas/{prova}/turmas/{turma}', [ProvaTurmaController::class, 'destroy'])->name('provas.turmas.destroy');
    });
