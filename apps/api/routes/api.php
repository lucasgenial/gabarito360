<?php

use App\Http\Controllers\Api\AlunoController;
use App\Http\Controllers\Api\AssinaturaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartaoController;
use App\Http\Controllers\Api\CensoController;
use App\Http\Controllers\Api\ConfiguracaoController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EscolaController;
use App\Http\Controllers\Api\GabaritoController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\NucleoController;
use App\Http\Controllers\Api\PlanoController;
use App\Http\Controllers\Api\ProvaController;
use App\Http\Controllers\Api\RelatorioController;
use App\Http\Controllers\Api\ResultadoController;
use App\Http\Controllers\Api\TurmaController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\AcompanhamentoTurmaController;
use App\Http\Controllers\Api\RelatorioConsolidadoController;
use App\Http\Controllers\Api\VinculoProfessorController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ConviteVinculoController;
use App\Services\Omr\ImagensCartao;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('health', fn () => response()->json(['status' => 'ok', 'app' => 'gabarito360-api']));

    // Webhook Mercado Pago (sem autenticação Sanctum — verificado por assinatura HMAC)
    Route::post('webhooks/mercadopago', [WebhookController::class, 'mercadoPago']);

    // Planos (público)
    Route::get('planos', [PlanoController::class, 'index']);

    // Fale Conosco (público)
    Route::post('leads', [LeadController::class, 'store'])->middleware('throttle:5,1');

    // Foto do cartão por link temporário assinado: o navegador busca direto, sem passar pela web
    Route::get('cartoes/{id}/arquivo/{tipo}', [CartaoController::class, 'arquivo'])
        ->whereNumber('id')->whereIn('tipo', ImagensCartao::TIPOS)
        ->middleware('signed:relative')->name('cartoes.arquivo');

    // Rotas públicas de autenticação
    Route::prefix('auth')->group(function () {
        Route::post('login',           [AuthController::class, 'login']);
        Route::post('esqueci-senha',   [AuthController::class, 'esqueceuSenha'])->middleware('throttle:5,1');
        Route::post('redefinir-senha', [AuthController::class, 'redefinirSenha'])->middleware('throttle:10,1');
        Route::post('confirmar-email', [AuthController::class, 'confirmarEmail'])->middleware('throttle:10,1');
        Route::post('convite',         [AuthController::class, 'consultarConvite'])->middleware('throttle:20,1');
        Route::post('convite/recusar', [AuthController::class, 'recusarConvite'])->middleware('throttle:10,1');
    });

    // Rotas protegidas
    Route::middleware(['auth:sanctum', 'contexto_vinculo', 'senha_trocada'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('convites-vinculo/{id}/consultar', [ConviteVinculoController::class, 'consultar'])->whereNumber('id')->middleware('throttle:20,1');
            Route::post('convites-vinculo/{id}/responder', [ConviteVinculoController::class, 'responder'])->whereNumber('id')->middleware('throttle:10,1');
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',     [AuthController::class, 'me']);
            Route::post('email', [AuthController::class, 'trocarEmail'])->middleware('throttle:5,1');
            Route::put('me',     [AuthController::class, 'update']);
        });

        // Provas
        Route::prefix('provas')->group(function () {
            Route::get('/',             [ProvaController::class, 'index'])->middleware('permissao:prova.ver');
            Route::post('/',            [ProvaController::class, 'store'])->middleware('permissao:prova.criar'); // doc 03: diretor não cria prova
            Route::get('{id}',          [ProvaController::class, 'show'])->middleware('permissao:prova.ver');
            Route::put('{id}',          [ProvaController::class, 'update'])->middleware('permissao:prova.editar');
            Route::post('{id}/publicar',[ProvaController::class, 'publicar'])->middleware('permissao:prova.editar');
            Route::delete('{id}',       [ProvaController::class, 'destroy'])->middleware('permissao:prova.editar');
            // Gabarito (MP-020)
            Route::get('{id}/gabarito',          [GabaritoController::class, 'show'])->middleware('permissao:gabarito.ver');
            Route::post('{id}/gabarito',         [GabaritoController::class, 'salvar'])->middleware('permissao:gabarito.editar');
            Route::post('{id}/gabarito/publicar',[GabaritoController::class, 'publicar'])->middleware('permissao:gabarito.editar');
            // Cartões / OMR (MP-021)
            Route::get('{id}/cartoes',        [CartaoController::class, 'index'])->middleware('permissao:correcao.acompanhar,cartao.enviar');
            Route::get('{id}/cartoes/status', [CartaoController::class, 'status'])->middleware('permissao:correcao.acompanhar,cartao.enviar');
            Route::get('{id}/cartoes/pdf',    [CartaoController::class, 'pdf'])->middleware('permissao:cartao.imprimir');
            Route::get('{id}/revisao',           [CartaoController::class, 'revisao'])->middleware('permissao:correcao.acompanhar');
            Route::post('{id}/correcao/fechar',  [CartaoController::class, 'fecharCorrecao'])->middleware('permissao:correcao.fechar');
            Route::post('{id}/correcao/reabrir', [CartaoController::class, 'reabrirCorrecao'])->middleware('permissao:correcao.reabrir');
            Route::post('{id}/cartoes',       [CartaoController::class, 'store'])->middleware('permissao:cartao.enviar');
            Route::get('{id}/cartoes/envios/{chave}', [CartaoController::class, 'envio'])->whereUuid('chave')->middleware('permissao:cartao.enviar');
            Route::post('{id}/cartoes/envios/{chave}/repetir', [CartaoController::class, 'repetirEnvio'])->whereUuid('chave')->middleware('permissao:cartao.enviar');
        });

        // Cartões / OMR (MP-021)
        Route::prefix('cartoes')->group(function () {
            Route::get('{id}',                       [CartaoController::class, 'show'])->middleware('permissao:correcao.acompanhar');
            Route::get('{id}/imagem',                [CartaoController::class, 'imagem'])->middleware('permissao:correcao.acompanhar');
            Route::delete('{id}',                    [CartaoController::class, 'destroy'])->middleware('permissao:correcao.alterar');
            Route::put('{id}/vincular-aluno',         [CartaoController::class, 'vincularAluno'])->middleware('permissao:correcao.alterar');
            Route::post('{id}/resolver-ambiguidade',  [CartaoController::class, 'resolverAmbiguidade'])->middleware('permissao:correcao.alterar');
            Route::post('{id}/revisar',               [CartaoController::class, 'revisar'])->middleware('permissao:correcao.alterar');
        });

        // Alunos
        Route::prefix('alunos')->group(function () {
            Route::get('/',            [AlunoController::class, 'index'])->middleware('permissao:aluno.ver');
            Route::post('/',           [AlunoController::class, 'store'])->middleware('permissao:aluno.gerir');
            Route::get('{id}',         [AlunoController::class, 'show'])->middleware('permissao:aluno.ver');
            Route::put('{id}',         [AlunoController::class, 'update'])->middleware('permissao:aluno.gerir');
            Route::post('{id}/toggle', [AlunoController::class, 'toggle'])->middleware('permissao:aluno.desativar');
            // Resultado individual (MP-022)
            Route::get('{id}/resultados/{provaId}', [ResultadoController::class, 'show'])->middleware('permissao:resultado.ver');
            Route::get('{id}/resultados/{provaId}/pdf', [ResultadoController::class, 'pdf'])->middleware('permissao:resultado.compartilhar');
        });

        // Relatórios (MP-022)
        Route::prefix('relatorios')->group(function () {
            Route::get('prova/{id}',                       [RelatorioController::class, 'prova'])->middleware('permissao:relatorio.prova');
            Route::get('turma/{turmaId}/prova/{provaId}',   [RelatorioController::class, 'turmaProva'])->middleware('permissao:relatorio.prova');
            Route::get('turma/{turmaId}',                   [RelatorioController::class, 'turma'])->middleware('permissao:relatorio.turma');
            Route::get('aluno/{alunoId}',                   [RelatorioController::class, 'aluno'])->middleware('permissao:relatorio.turma');
            Route::get('escola/{id?}',                      [RelatorioConsolidadoController::class, 'escola'])->middleware('permissao:relatorio.escola');
            Route::get('nucleo/{id?}',                      [RelatorioConsolidadoController::class, 'nucleo'])->middleware('permissao:relatorio.nucleo');
            Route::get('rede/{id?}',                        [RelatorioConsolidadoController::class, 'rede'])->middleware('permissao:relatorio.rede');
        });

        // Turmas
        Route::prefix('turmas')->group(function () {
            Route::get('/',            [TurmaController::class, 'index'])->middleware('permissao:turma.ver');
            Route::post('/',           [TurmaController::class, 'store'])->middleware('permissao:turma.gerir');
            Route::get('{id}',         [TurmaController::class, 'show'])->middleware('permissao:turma.ver');
            Route::put('{id}',         [TurmaController::class, 'update'])->middleware('permissao:turma.gerir');
            Route::post('{id}/toggle', [TurmaController::class, 'toggle'])->middleware('permissao:turma.desativar');
            // Vínculos professor ↔ turma (MP-018)
            Route::get('{id}/correcao',                   [AcompanhamentoTurmaController::class, 'show'])->middleware('permissao:correcao.acompanhar');
            Route::get('{id}/professores',                [VinculoProfessorController::class, 'index'])->middleware('permissao:turma.ver');
            Route::post('{id}/professores',               [VinculoProfessorController::class, 'store'])->middleware('permissao:turma.vincular_professor');
            Route::delete('{id}/professores/{usuarioId}', [VinculoProfessorController::class, 'destroy'])->middleware('permissao:turma.vincular_professor');
        });

        // Turmas do professor
        Route::get('usuarios/{id}/turmas', [VinculoProfessorController::class, 'turmasDoProfessor'])->middleware('permissao:turma.ver')
            ;

        // Escolas
        Route::get('redes/{id}/logo',  [EscolaController::class, 'logoRede']);
        Route::post('redes/{id}/logo', [EscolaController::class, 'enviarLogoRede'])->middleware('permissao:rede.logo');

        Route::prefix('escolas')->group(function () {
            Route::get('{id}/logo',   [EscolaController::class, 'logo']);
            Route::post('{id}/logo',  [EscolaController::class, 'enviarLogo'])->middleware('permissao:escola.logo');
            Route::get('/',           [EscolaController::class, 'index'])->middleware('permissao:escola.ver');
            // Cadastro de escola: admin da rede e diretor de núcleo (no próprio núcleo)
            Route::post('/',          [EscolaController::class, 'store'])->middleware('permissao:escola.criar');
            Route::get('{id}',        [EscolaController::class, 'show'])->middleware('permissao:escola.ver');
            Route::put('{id}',        [EscolaController::class, 'update'])->middleware('permissao:escola.editar');
            Route::post('{id}/toggle',[EscolaController::class, 'toggle'])->middleware('permissao:escola.desativar');
        });

        // Censo Escolar (INEP): consulta no cadastro de escola (docs/21, etapa 6)
        Route::get('censo/escolas',        [CensoController::class, 'pesquisar'])->middleware('permissao:escola.criar');
        Route::get('censo/escolas/{inep}', [CensoController::class, 'buscar'])->middleware('permissao:escola.criar');

        // Núcleos
        Route::prefix('nucleos')->group(function () {
            Route::get('/',     [NucleoController::class, 'index'])->middleware('permissao:nucleo.ver');
            Route::post('/',    [NucleoController::class, 'store'])->middleware('permissao:nucleo.gerir');
            Route::get('{id}',  [NucleoController::class, 'show'])->middleware('permissao:nucleo.ver');
            Route::put('{id}',  [NucleoController::class, 'update'])->middleware('permissao:nucleo.gerir');
            Route::delete('{id}',[NucleoController::class, 'destroy'])->middleware('permissao:nucleo.gerir');
        });

        // Usuários / equipe
        Route::get('permissoes/perfis', [UsuarioController::class, 'catalogoPerfis'])->middleware('permissao:usuario.ver');

        Route::post('usuarios/convites-vinculo', [ConviteVinculoController::class, 'store'])->middleware(['permissao:usuario.gerir', 'throttle:10,1']);
        Route::prefix('usuarios')->group(function () {
            Route::get('/',          [UsuarioController::class, 'index'])->middleware('permissao:usuario.ver');
            // Quem cadastra quem e em qual escopo: App\Support\Cadastro (docs/21, etapa 0)
            Route::post('/',         [UsuarioController::class, 'store'])->middleware('permissao:usuario.gerir');
            Route::get('{id}',       [UsuarioController::class, 'show'])->middleware('permissao:usuario.ver');
            Route::put('{id}',       [UsuarioController::class, 'update'])->middleware('permissao:usuario.gerir');
            Route::post('{id}/convite', [UsuarioController::class, 'reenviarConvite'])->middleware('permissao:usuario.gerir');
            Route::post('{id}/link-senha', [UsuarioController::class, 'enviarLinkSenha'])->middleware(['permissao:usuario.gerir', 'throttle:5,1']);
            Route::post('{id}/toggle',[UsuarioController::class, 'toggle'])->middleware('permissao:usuario.gerir');
            // Permissões do usuário: padrão do perfil, extras e retiradas (docs/03 §1)
            Route::get('{id}/permissoes', [UsuarioController::class, 'permissoes'])->middleware('permissao:usuario.ver');
            Route::put('{id}/permissoes', [UsuarioController::class, 'definirPermissoes'])->middleware('permissao:usuario.permissoes');
        });

        // Configurações da rede (MP-024)
        Route::prefix('configuracoes')->group(function () {
            Route::get('/', [ConfiguracaoController::class, 'show'])->middleware('permissao:rede.configurar');
            Route::put('/', [ConfiguracaoController::class, 'update'])->middleware('permissao:rede.configurar');
        });

        // Assinaturas (MP-027)
        Route::prefix('assinaturas')->group(function () {
            Route::post('checkout',  [AssinaturaController::class, 'checkout']);
            Route::get('minha',      [AssinaturaController::class, 'minha']);
            Route::post('cancelar',  [AssinaturaController::class, 'cancelar']);
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
