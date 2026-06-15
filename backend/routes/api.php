<?php

use App\Http\Controllers\Api\V2\Agenda\AgendaController;
use App\Http\Controllers\Api\V2\Alunos\AlunoController;
use App\Http\Controllers\Api\V2\Alunos\AlunoFichaController;
use App\Http\Controllers\Api\V2\Alunos\AlunoFotoController;
use App\Http\Controllers\Api\V2\Aplicacoes\AplicacaoController;
use App\Http\Controllers\Api\V2\Atividades\AtividadeController;
use App\Http\Controllers\Api\V2\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V2\Auth\LoginController;
use App\Http\Controllers\Api\V2\Auth\LogoutController;
use App\Http\Controllers\Api\V2\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V2\Comparativos\ComparativoController;
use App\Http\Controllers\Api\V2\Correcao\CorrecaoController;
use App\Http\Controllers\Api\V2\Dashboards\DashboardController;
use App\Http\Controllers\Api\V2\Escolas\EscolaController;
use App\Http\Controllers\Api\V2\Escolas\EscolaPerfilController;
use App\Http\Controllers\Api\V2\Escolas\MembroController;
use App\Http\Controllers\Api\V2\HealthController;
use App\Http\Controllers\Api\V2\Integracoes\IntegracaoController;
use App\Http\Controllers\Api\V2\Leituras\LeituraController;
use App\Http\Controllers\Api\V2\Me\MeController;
use App\Http\Controllers\Api\V2\Me\SessaoController;
use App\Http\Controllers\Api\V2\Me\ShowFotoController;
use App\Http\Controllers\Api\V2\Me\UpdateFotoController;
use App\Http\Controllers\Api\V2\Me\UpdateMeController;
use App\Http\Controllers\Api\V2\Me\UpdatePasswordController;
use App\Http\Controllers\Api\V2\Me\UpdatePreferenciasController;
use App\Http\Controllers\Api\V2\Notificacoes\NotificacaoController;
use App\Http\Controllers\Api\V2\Nucleos\NucleoController;
use App\Http\Controllers\Api\V2\Onboarding\PerfilController;
use App\Http\Controllers\Api\V2\Onboarding\SolicitacaoController;
use App\Http\Controllers\Api\V2\Provas\GabaritoController;
use App\Http\Controllers\Api\V2\Provas\ProvaController;
use App\Http\Controllers\Api\V2\Provas\ProvaTurmaController;
use App\Http\Controllers\Api\V2\Relatorios\ExportacaoController;
use App\Http\Controllers\Api\V2\Relatorios\RelatorioController;
use App\Http\Controllers\Api\V2\Relatorios\RelatorioProvaController;
use App\Http\Controllers\Api\V2\Resultados\ResultadoController;
use App\Http\Controllers\Api\V2\Turmas\TurmaController;
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

        // Turmas (/turmas/importar antes de /turmas/{turma})
        Route::get('/turmas', [TurmaController::class, 'index'])->name('turmas.index');
        Route::post('/turmas', [TurmaController::class, 'store'])->name('turmas.store');
        Route::post('/turmas/importar', [TurmaController::class, 'importar'])->name('turmas.importar');
        Route::get('/turmas/{turma}', [TurmaController::class, 'show'])->name('turmas.show');
        Route::get('/turmas/{turma}/alunos', [TurmaController::class, 'alunos'])->name('turmas.alunos');

        // Alunos
        Route::post('/alunos', [AlunoController::class, 'store'])->name('alunos.store');
        Route::get('/alunos/{aluno}', [AlunoController::class, 'show'])->name('alunos.show');
        Route::put('/alunos/{aluno}', [AlunoController::class, 'update'])->name('alunos.update');
        Route::get('/alunos/{aluno}/avaliacoes', [AlunoController::class, 'avaliacoes'])->name('alunos.avaliacoes');
        Route::get('/alunos/{aluno}/ficha.pdf', AlunoFichaController::class)->name('alunos.ficha');
        Route::post('/alunos/{aluno}/foto', [AlunoController::class, 'foto'])->name('alunos.foto');
        Route::get('/alunos/{aluno}/foto', AlunoFotoController::class)->name('alunos.foto.show');

        // Provas (gabarito.pdf antes de gabarito)
        Route::get('/provas', [ProvaController::class, 'index'])->name('provas.index');
        Route::post('/provas', [ProvaController::class, 'store'])->name('provas.store');
        Route::get('/provas/{prova}', [ProvaController::class, 'show'])->name('provas.show');
        Route::put('/provas/{prova}', [ProvaController::class, 'update'])->name('provas.update');
        Route::post('/provas/{prova}/publicar', [ProvaController::class, 'publicar'])->name('provas.publicar');
        Route::get('/provas/{prova}/gabarito.pdf', [GabaritoController::class, 'pdf'])->name('provas.gabarito.pdf');
        Route::get('/provas/{prova}/gabarito', [GabaritoController::class, 'show'])->name('provas.gabarito.show');
        Route::put('/provas/{prova}/gabarito', [GabaritoController::class, 'update'])->name('provas.gabarito.update');
        Route::get('/provas/{prova}/turmas', [ProvaTurmaController::class, 'index'])->name('provas.turmas.index');
        Route::post('/provas/{prova}/turmas', [ProvaTurmaController::class, 'store'])->name('provas.turmas.store');
        Route::delete('/provas/{prova}/turmas/{turma}', [ProvaTurmaController::class, 'destroy'])->name('provas.turmas.destroy');

        // Aplicacoes (ciclo operacional)
        Route::get('/aplicacoes', [AplicacaoController::class, 'index'])->name('aplicacoes.index');
        Route::post('/aplicacoes', [AplicacaoController::class, 'store'])->name('aplicacoes.store');
        Route::get('/aplicacoes/{aplicacao}', [AplicacaoController::class, 'show'])->name('aplicacoes.show');
        Route::post('/aplicacoes/{aplicacao}/iniciar', [AplicacaoController::class, 'iniciar'])->name('aplicacoes.iniciar');
        Route::post('/aplicacoes/{aplicacao}/finalizar', [AplicacaoController::class, 'finalizar'])->name('aplicacoes.finalizar');
        Route::get('/aplicacoes/{aplicacao}/leituras', [LeituraController::class, 'index'])->name('aplicacoes.leituras.index');
        Route::post('/aplicacoes/{aplicacao}/leituras', [LeituraController::class, 'capturar'])->name('aplicacoes.leituras.capturar');

        // Leituras (confirmar e revisar pendencias)
        Route::post('/leituras/{leitura}/confirmar', [LeituraController::class, 'confirmar'])->name('leituras.confirmar');
        Route::post('/leituras/{leitura}/revisao', [LeituraController::class, 'revisar'])->name('leituras.revisao');

        // Correcao (progresso e pendencias por prova)
        Route::get('/correcao/{prova}', [CorrecaoController::class, 'progresso'])->name('correcao.progresso');
        Route::get('/correcao/{prova}/pendencias', [CorrecaoController::class, 'pendencias'])->name('correcao.pendencias');

        // Resultados (B6)
        Route::get('/resultados', [ResultadoController::class, 'index'])->name('resultados.index');
        Route::get('/resultados/{resultado}', [ResultadoController::class, 'show'])->name('resultados.show');

        // Dashboards (B6)
        Route::get('/dashboards/aplicacao/{aplicacao}', [DashboardController::class, 'aplicacao'])->name('dashboards.aplicacao');
        Route::get('/dashboards/prova/{prova}', [DashboardController::class, 'prova'])->name('dashboards.prova');

        // Relatórios (B6)
        Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');
        Route::post('/relatorios', [RelatorioController::class, 'store'])->name('relatorios.store');
        // Relatório de prova (dados) + exportação multi-formato — antes do wildcard {relatorio}.
        Route::get('/relatorios/prova/{prova}', [RelatorioProvaController::class, 'show'])->name('relatorios.prova');
        Route::post('/relatorios/prova/{prova}/exportar', [ExportacaoController::class, 'store'])->name('relatorios.prova.exportar');
        Route::get('/relatorios/{relatorio}', [RelatorioController::class, 'show'])->name('relatorios.show');
        Route::get('/relatorios/{relatorio}/download', [RelatorioController::class, 'download'])->name('relatorios.download');

        // Exportações (B6) — artefatos csv/pdf/xlsx de relatórios
        Route::get('/exportacoes', [ExportacaoController::class, 'index'])->name('exportacoes.index');
        Route::get('/exportacoes/{exportacao}', [ExportacaoController::class, 'show'])->name('exportacoes.show');
        Route::get('/exportacoes/{exportacao}/download', [ExportacaoController::class, 'download'])->name('exportacoes.download');

        // Comparativos (B6)
        Route::get('/comparativos/nucleo/{nucleo}', [ComparativoController::class, 'nucleo'])->name('comparativos.nucleo');

        // Snapshot de indicadores da prova (B6)
        Route::get('/dashboards/prova/{prova}/snapshot', [DashboardController::class, 'snapshot'])->name('dashboards.prova.snapshot');

        // Notificações (B7) — rotas literais antes do wildcard {notificacao}
        Route::get('/notificacoes', [NotificacaoController::class, 'index'])->name('notificacoes.index');
        Route::post('/notificacoes/ler-todas', [NotificacaoController::class, 'marcarTodas'])->name('notificacoes.ler-todas');
        Route::get('/notificacoes/preferencias', [NotificacaoController::class, 'preferencias'])->name('notificacoes.preferencias.index');
        Route::put('/notificacoes/preferencias', [NotificacaoController::class, 'atualizarPreferencias'])->name('notificacoes.preferencias.update');
        Route::post('/notificacoes/{notificacao}/ler', [NotificacaoController::class, 'marcarLida'])->name('notificacoes.ler');

        // Agenda (B7)
        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
        Route::post('/agenda', [AgendaController::class, 'store'])->name('agenda.store');
        Route::get('/agenda/{evento}', [AgendaController::class, 'show'])->name('agenda.show');
        Route::put('/agenda/{evento}', [AgendaController::class, 'update'])->name('agenda.update');
        Route::post('/agenda/{evento}/confirmar', [AgendaController::class, 'confirmar'])->name('agenda.confirmar');

        // Atividades recentes (B7) — feed dos painéis
        Route::get('/atividades-recentes', [AtividadeController::class, 'index'])->name('atividades-recentes.index');
    });
});
