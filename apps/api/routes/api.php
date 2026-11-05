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
    Route::post('documentos/validar', \App\Http\Controllers\Api\ValidacaoDocumentoController::class)->middleware('throttle:20,1');

    // Webhook Mercado Pago (sem autenticação Sanctum — verificado por assinatura HMAC)
    Route::post('webhooks/mercadopago', [WebhookController::class, 'mercadoPago']);

    // Planos (público)
    Route::get('planos', [PlanoController::class, 'index']);

    // Fale Conosco (público)
    Route::post('leads', [LeadController::class, 'store'])->middleware('throttle:5,1');

    // Endereço antigo desativado: uma assinatura na URL não substitui autenticação.
    Route::get('cartoes/{id}/arquivo/{tipo}', fn () => abort(404))
        ->whereNumber('id')->whereIn('tipo', ImagensCartao::TIPOS)
        ->name('cartoes.arquivo');

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
        $dc=\App\Http\Controllers\Api\DisciplinaController::class;
        $ac=\App\Http\Controllers\Api\AreaConhecimentoController::class;
        Route::prefix('areas-conhecimento')->middleware('throttle:60,1')->group(function () use ($ac) {
            Route::get('opcoes', [$ac,'opcoes']);
            Route::get('', [$ac,'index']); Route::post('', [$ac,'store']);
            Route::get('{id}', [$ac,'show'])->whereNumber('id'); Route::put('{id}', [$ac,'update'])->whereNumber('id');
            Route::post('{id}/vinculos', [$ac,'vincular'])->whereNumber('id');
            Route::post('{id}/desvincular', [$ac,'desvincular'])->whereNumber('id');
        });
        Route::prefix('disciplinas')->middleware('throttle:60,1')->group(function () use ($dc) {
            Route::get('opcoes', [$dc,'opcoes']); Route::get('professores', [$dc,'professores']); Route::get('', [$dc,'index']); Route::post('', [$dc,'store']);
            Route::get('{id}', [$dc,'show'])->whereNumber('id'); Route::put('{id}', [$dc,'update'])->whereNumber('id');
            Route::post('{id}/professores', [$dc,'indicar'])->whereNumber('id');
        });
        Route::prefix('turmas/{turma}/disciplinas')->whereNumber('turma')->middleware('throttle:60,1')->group(function () use ($dc) {
            Route::get('', [$dc,'turma']); Route::post('', [$dc,'adicionar']);
            Route::get('{componente}', [$dc,'componente'])->whereNumber('componente');
            Route::post('{componente}/situacao', [$dc,'situacao'])->whereNumber('componente');
            Route::post('{componente}/atribuicoes', [$dc,'atribuir'])->whereNumber('componente');
            Route::post('{componente}/atribuicoes/{atribuicao}/encerrar', [$dc,'encerrar'])->whereNumber('componente')->whereNumber('atribuicao');
        });
        $sol=\App\Http\Controllers\Api\SolicitacaoCatalogoController::class;
        Route::prefix('catalogo/minhas-solicitacoes')->middleware('throttle:catalogo-operacao')->group(function()use($sol){
            Route::get('opcoes',[$sol,'opcoes']);Route::get('municipios',[$sol,'municipios']);
            Route::get('',[$sol,'index']);Route::post('',[$sol,'store']);
            Route::get('{id}',[$sol,'show'])->whereUuid('id');Route::post('{id}/acoes',[$sol,'alterar'])->whereUuid('id');
        });
        Route::prefix('catalogo/atendimento')->middleware('throttle:catalogo-operacao')->group(function()use($sol){
            Route::get('opcoes',[$sol,'opcoes']);Route::get('municipios',[$sol,'municipios']);Route::get('catalogo',[$sol,'buscar']);
            Route::post('autoridades',[$sol,'autoridade']);
            Route::post('instituicoes',[$sol,'gravar']);
            Route::get('instituicoes/{unidade}',[$sol,'unidade'])->whereUuid('unidade');Route::post('instituicoes/{unidade}',[$sol,'gravar'])->whereUuid('unidade');
            Route::get('',[$sol,'index']);Route::get('{id}',[$sol,'show'])->whereUuid('id');Route::post('{id}/acoes',[$sol,'alterar'])->whereUuid('id');
        });
        Route::prefix('catalogo/gestao')->middleware('throttle:catalogo-operacao')->group(function()use($sol){
            Route::get('',[$sol,'gestao']);Route::get('opcoes',[$sol,'opcoes']);Route::get('municipios',[$sol,'municipios']);
            Route::get('instituicoes/{unidade}',[$sol,'unidade'])->whereUuid('unidade');Route::post('instituicoes/{unidade}',[$sol,'gravar'])->whereUuid('unidade');
        });
        Route::prefix('catalogo')->middleware('permissao:catalogo.consultar')->group(function () {
            $c=\App\Http\Controllers\Api\CatalogoPessoalController::class;
            Route::get('ufs',[$c,'ufs'])->middleware('throttle:60,1');
            Route::get('municipios',[$c,'municipios'])->middleware('throttle:60,1');
            Route::get('instituicoes',[$c,'buscar'])->middleware('throttle:60,1');
            Route::get('instituicoes/{unidade}',[$c,'show'])->whereUuid('unidade')->middleware('throttle:60,1');
            Route::get('referencias-pessoais',[$c,'referencias'])->middleware('throttle:60,1');
            Route::post('referencias-pessoais',[$c,'selecionar'])->middleware(['permissao:catalogo.referencia_pessoal.gerir','throttle:10,1']);
            Route::post('referencias-pessoais/{id}/arquivar',[$c,'arquivar'])->whereUuid('id')->middleware(['permissao:catalogo.referencia_pessoal.gerir','throttle:10,1']);
        });
        Route::get('copias/operacao/{id}', [\App\Http\Controllers\Api\OperacaoCopiaController::class,'show'])->whereUuid('id')->middleware('throttle:60,1');
        Route::post('copias/operacao/{id}/solicitar', [\App\Http\Controllers\Api\OperacaoCopiaController::class,'solicitar'])->whereUuid('id')->middleware('throttle:10,1');
        Route::post('copias/operacao/{id}/revogar', [\App\Http\Controllers\Api\OperacaoCopiaController::class,'revogar'])->whereUuid('id')->middleware('throttle:10,1');
        Route::get('copias/operacao/{id}/acervo', [\App\Http\Controllers\Api\OperacaoCopiaController::class,'acervo'])->whereUuid('id')->middleware('throttle:30,1');
        Route::get('copias', [\App\Http\Controllers\Api\AcompanhamentoCopiaController::class,'index'])->middleware('throttle:60,1');
        Route::post('copias/cadastros/recebimentos', [\App\Http\Controllers\Api\DecisaoCadastroCopiaController::class,'emitir'])->middleware('throttle:5,1');
        Route::post('copias/cadastros/preparar', [\App\Http\Controllers\Api\DecisaoCadastroCopiaController::class,'preparar'])->middleware('throttle:10,1');
        Route::post('copias/cadastros/lotes', [\App\Http\Controllers\Api\DecisaoCadastroCopiaController::class,'criar'])->middleware('throttle:10,1');
        Route::get('copias/cadastros/lotes/{id}', [\App\Http\Controllers\Api\DecisaoCadastroCopiaController::class,'show'])->whereUuid('id')->middleware('throttle:60,1');
        Route::post('copias/cadastros/lotes/{id}/evidencia', [\App\Http\Controllers\Api\DecisaoCadastroCopiaController::class,'evidencia'])->whereUuid('id')->middleware('throttle:10,1');
        Route::post('copias/cadastros/lotes/{id}/aprovar', [\App\Http\Controllers\Api\DecisaoCadastroCopiaController::class,'aprovar'])->whereUuid('id')->middleware('throttle:10,1');
        Route::post('copias/resultados/recebimentos', [\App\Http\Controllers\Api\DecisaoResultadosCopiaController::class,'emitir'])->middleware('throttle:5,1');
        Route::post('copias/resultados/preparar', [\App\Http\Controllers\Api\DecisaoResultadosCopiaController::class,'preparar'])->middleware('throttle:10,1');
        Route::post('copias/resultados/lotes', [\App\Http\Controllers\Api\DecisaoResultadosCopiaController::class,'criar'])->middleware('throttle:10,1');
        Route::get('copias/resultados/lotes/{id}', [\App\Http\Controllers\Api\DecisaoResultadosCopiaController::class,'show'])->whereUuid('id')->middleware('throttle:60,1');
        Route::post('copias/resultados/lotes/{id}/evidencia', [\App\Http\Controllers\Api\DecisaoResultadosCopiaController::class,'evidencia'])->whereUuid('id')->middleware('throttle:10,1');
        Route::post('copias/resultados/lotes/{id}/aprovar', [\App\Http\Controllers\Api\DecisaoResultadosCopiaController::class,'aprovar'])->whereUuid('id')->middleware('throttle:10,1');
        Route::get('copias/resultados', [\App\Http\Controllers\Api\SelecaoResultadosCopiaController::class,'index'])->middleware('throttle:60,1');
        Route::post('copias/resultados/previa', [\App\Http\Controllers\Api\SelecaoResultadosCopiaController::class,'previa'])->middleware('throttle:20,1');
        Route::get('copias/cadastros', [\App\Http\Controllers\Api\SelecaoCadastroCopiaController::class,'index'])->middleware('throttle:60,1');
        Route::post('copias/cadastros/previa', [\App\Http\Controllers\Api\SelecaoCadastroCopiaController::class,'previa'])->middleware('throttle:20,1');
        Route::get('copias/materiais', [\App\Http\Controllers\Api\SelecaoMaterialCopiaController::class,'index'])->middleware('throttle:60,1');
        Route::post('copias/materiais/previa', [\App\Http\Controllers\Api\SelecaoMaterialCopiaController::class,'previa'])->middleware('throttle:20,1');
        Route::post('copias/recebimentos', [\App\Http\Controllers\Api\DecisaoMaterialCopiaController::class,'emitir'])->middleware('throttle:5,1');
        Route::post('copias/materiais/preparar', [\App\Http\Controllers\Api\DecisaoMaterialCopiaController::class,'preparar'])->middleware('throttle:10,1');
        Route::post('copias/materiais/lotes', [\App\Http\Controllers\Api\DecisaoMaterialCopiaController::class,'criar'])->middleware('throttle:10,1');
        Route::get('copias/materiais/lotes/{id}', [\App\Http\Controllers\Api\DecisaoMaterialCopiaController::class,'show'])->whereUuid('id')->middleware('throttle:60,1');
        Route::post('copias/materiais/lotes/{id}/revisar', [\App\Http\Controllers\Api\DecisaoMaterialCopiaController::class,'revisar'])->whereUuid('id')->middleware('throttle:10,1');
        Route::post('copias/materiais/lotes/{id}/aprovar', [\App\Http\Controllers\Api\DecisaoMaterialCopiaController::class,'aprovar'])->whereUuid('id')->middleware('throttle:10,1');
        Route::post('pedidos-titulares/exportacoes/{id}', [\App\Http\Controllers\Api\GeracaoExportacaoTitularController::class,'store'])->whereUuid('id')->middleware('throttle:10,1');
        Route::get('pedidos-titulares/exportacoes/{id}', [\App\Http\Controllers\Api\EntregaExportacaoTitularController::class,'show'])->whereUuid('id')->middleware('throttle:30,1');
        Route::get('pedidos-titulares/exportacoes/{id}/arquivo', [\App\Http\Controllers\Api\EntregaExportacaoTitularController::class,'arquivo'])->whereUuid('id')->middleware('throttle:10,1');
        Route::get('pedidos-titulares', [\App\Http\Controllers\Api\PedidoTitularController::class,'index'])->middleware('throttle:30,1');
        Route::get('pedidos-titulares/opcoes', [\App\Http\Controllers\Api\PedidoTitularController::class,'opcoes'])->middleware('throttle:30,1');
        Route::post('pedidos-titulares', [\App\Http\Controllers\Api\PedidoTitularController::class,'store'])->middleware('throttle:10,1');
        Route::get('pedidos-titulares/{id}', [\App\Http\Controllers\Api\PedidoTitularController::class,'show'])->whereUuid('id')->middleware('throttle:30,1');
        Route::post('pedidos-titulares/{id}/triagem', [\App\Http\Controllers\Api\PedidoTitularController::class,'triar'])->whereUuid('id')->middleware('throttle:10,1');
        Route::post('pedidos-titulares/{id}/verificacoes', [\App\Http\Controllers\Api\PedidoTitularController::class,'verificar'])->whereUuid('id')->middleware('throttle:10,1');
        Route::post('pedidos-titulares/{id}/decisao', [\App\Http\Controllers\Api\PedidoTitularController::class,'decidir'])->whereUuid('id')->middleware('throttle:10,1');
        Route::post('pedidos-titulares/{id}/sujeito', [\App\Http\Controllers\Api\PedidoTitularController::class,'vincularSujeito'])->whereUuid('id')->middleware('throttle:10,1');
        Route::get('autorizacoes-exportacao/pedidos/{id}', [\App\Http\Controllers\Api\AutorizacaoExportacaoTitularController::class,'show'])->whereUuid('id')->middleware('throttle:30,1');
        Route::post('autorizacoes-exportacao/pedidos/{id}', [\App\Http\Controllers\Api\AutorizacaoExportacaoTitularController::class,'store'])->whereUuid('id')->middleware('throttle:10,1');
        Route::post('autorizacoes-exportacao/{id}/revogar', [\App\Http\Controllers\Api\AutorizacaoExportacaoTitularController::class,'revogar'])->whereUuid('id')->middleware('throttle:10,1');
        Route::get('clientes', [\App\Http\Controllers\Api\ClienteController::class,'index']);
        Route::post('clientes', [\App\Http\Controllers\Api\ClienteController::class,'store'])->middleware('throttle:20,1');
        Route::get('clientes/{id}', [\App\Http\Controllers\Api\ClienteController::class,'show'])->whereNumber('id');
        Route::put('clientes/{id}', [\App\Http\Controllers\Api\ClienteController::class,'update'])->whereNumber('id')->middleware('throttle:20,1');
        Route::get('clientes/{id}/espacos', [\App\Http\Controllers\Api\ClienteController::class,'espacos'])->whereNumber('id');
        Route::get('acessos-internos', [\App\Http\Controllers\Api\GestaoAcessoInternoController::class,'index']);
        Route::post('acessos-internos', [\App\Http\Controllers\Api\GestaoAcessoInternoController::class,'store'])->middleware('throttle:10,1');
        Route::put('acessos-internos/{id}', [\App\Http\Controllers\Api\GestaoAcessoInternoController::class,'update'])->whereNumber('id')->middleware('throttle:20,1');
        Route::prefix('auth')->group(function () {
            Route::get('acessos-internos', [\App\Http\Controllers\Api\SelecaoInternaController::class, 'index'])->middleware('throttle:30,1');
            Route::post('acessos-internos/selecionar', [\App\Http\Controllers\Api\SelecaoInternaController::class, 'selecionar'])->middleware('throttle:20,1');
            Route::post('convites-vinculo/{id}/consultar', [ConviteVinculoController::class, 'consultar'])->whereNumber('id')->middleware('throttle:20,1');
            Route::post('convites-vinculo/{id}/responder', [ConviteVinculoController::class, 'responder'])->whereNumber('id')->middleware('throttle:10,1');
            Route::get('vinculos', [\App\Http\Controllers\Api\SelecaoVinculoController::class, 'index'])->middleware('throttle:30,1');
            Route::post('vinculos/selecionar', [\App\Http\Controllers\Api\SelecaoVinculoController::class, 'selecionar'])->middleware('throttle:20,1');
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('imagem', [\App\Http\Controllers\Api\ImagemUsuarioController::class,'salvar'])->middleware('throttle:imagem-perfil');
            Route::get('foto', [\App\Http\Controllers\Api\ImagemUsuarioController::class,'propria']);
            Route::get('me',     [AuthController::class, 'me']);
            Route::post('email', [AuthController::class, 'trocarEmail'])->middleware('throttle:5,1');
            Route::put('me',     [AuthController::class, 'update']);
        });

        // Provas
        Route::prefix('provas')->group(function () {
            Route::get('opcoes-disciplinas',[ProvaController::class,'opcoesDisciplinas']);
            Route::post('opcoes-disciplinas',[ProvaController::class,'opcoesDisciplinas']);
            Route::get('{provaId}/resultados/pdf', [ResultadoController::class, 'lote'])->middleware(['permissao:relatorio.prova', 'permissao:resultado.compartilhar', 'throttle:5,1']);
            Route::post('{id}/copiar', [ProvaController::class, 'copiar'])->middleware('permissao:prova.duplicar_propria');
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
        Route::get('provas/{id}/tentativas', [\App\Http\Controllers\Api\TentativaCartaoController::class,'index'])->middleware('permissao:correcao.acompanhar');
        Route::get('tentativas/{id}', [\App\Http\Controllers\Api\TentativaCartaoController::class,'show'])->whereUuid('id')->middleware('permissao:correcao.acompanhar');
        Route::get('tentativas/{id}/imagem', [\App\Http\Controllers\Api\TentativaCartaoController::class,'imagem'])->whereUuid('id')->middleware('permissao:correcao.acompanhar');
        Route::post('tentativas/{id}/aplicar', [\App\Http\Controllers\Api\TentativaCartaoController::class,'aplicar'])->whereUuid('id')->middleware('permissao:correcao.alterar');
        Route::post('tentativas/{id}/descartar', [\App\Http\Controllers\Api\TentativaCartaoController::class,'descartar'])->whereUuid('id')->middleware('permissao:correcao.alterar');
        Route::prefix('cartoes')->group(function () {
            Route::get('{id}/imagens/{tipo}', [CartaoController::class, 'arquivo'])
                ->whereNumber('id')->whereIn('tipo', ImagensCartao::TIPOS)
                ->middleware('permissao:correcao.acompanhar')->name('cartoes.imagem-autenticada');
            Route::get('{id}',                       [CartaoController::class, 'show'])->middleware('permissao:correcao.acompanhar');
            Route::get('{id}/imagem',                [CartaoController::class, 'imagem'])->middleware('permissao:correcao.acompanhar');
            Route::delete('{id}',                    [CartaoController::class, 'destroy'])->middleware('permissao:correcao.alterar');
            Route::put('{id}/vincular-aluno',         [CartaoController::class, 'vincularAluno'])->middleware('permissao:correcao.alterar');
            Route::post('{id}/resolver-ambiguidade',  [CartaoController::class, 'resolverAmbiguidade'])->middleware('permissao:correcao.alterar');
            Route::post('{id}/revisar',               [CartaoController::class, 'revisar'])->middleware('permissao:correcao.alterar');
        });

        // Alunos
        Route::prefix('alunos')->group(function () {
            Route::post('{id}/imagem', [\App\Http\Controllers\Api\ImagemAlunoController::class,'salvar'])->whereNumber('id')->middleware(['permissao:aluno.gerir','throttle:imagem-perfil']);
            Route::get('{id}/foto', [\App\Http\Controllers\Api\ImagemAlunoController::class,'mostrar'])->whereNumber('id')->middleware('permissao:aluno.ver');
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

        Route::get('usuarios/convites-vinculo/opcoes', [ConviteVinculoController::class, 'opcoes'])->middleware('permissao:usuario.gerir');
        Route::post('usuarios/convites-vinculo', [ConviteVinculoController::class, 'store'])->middleware(['permissao:usuario.gerir', 'throttle:10,1']);
        Route::get('usuarios/{id}/foto',[\App\Http\Controllers\Api\ImagemUsuarioController::class,'mostrar'])->whereNumber('id');
        Route::get('configuracoes/funcoes/opcoes',[\App\Http\Controllers\Api\ExcecaoFuncionalController::class,'opcoes']);
        Route::post('configuracoes/funcoes/consultar',[\App\Http\Controllers\Api\ExcecaoFuncionalController::class,'consultar'])->middleware('throttle:10,1');
        Route::post('configuracoes/funcoes',[\App\Http\Controllers\Api\ExcecaoFuncionalController::class,'salvar'])->middleware('throttle:10,1');
        Route::prefix('usuarios')->group(function () {
            Route::post('{id}/encerrar-vinculo',[\App\Http\Controllers\Api\GestaoVinculoController::class,'encerrar'])->middleware('permissao:usuario.gerir');
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
                ->middleware('perfil:dir_escolar,vice_dir_escolar');
            Route::get('coordenador',  [DashboardController::class, 'coordenador'])
                ->middleware('perfil:coordenador');
            Route::get('professor',    [DashboardController::class, 'professor'])
                ->middleware('perfil:professor,professor_autonomo');
            Route::get('aluno',        [DashboardController::class, 'aluno'])
                ->middleware('perfil:aluno');
            Route::get('secretaria',  [DashboardController::class, 'secretaria'])
                ->middleware('perfil:secretario_educacao');
            Route::get('aplicador',   [DashboardController::class, 'aplicador'])
                ->middleware('perfil:aplicador');
        });
    });

});
