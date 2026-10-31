# 41 — T1: mapa de consultas e consumidores

Base: main `54bf834`, 31/10/2026. Complementa [o modelo T1](40-clientes-espacos-e-vinculos.md). Inventário conservador dos métodos públicos dos controladores, serviços, jobs, comandos, regras e middlewares existentes. Inclui métodos sem SQL próprio porque delegam acesso a dados. Não declara que o isolamento já existe. Métodos privados e relações dos modelos devem ser revisados junto dos seus consumidores, não como exceções ao escopo.

## Classes e pontos de entrada

| Arquivo | Métodos públicos a revisar | Regra |
|---|---|---|
| `apps/api/app/Http/Controllers/Api/AcompanhamentoTurmaController.php` | show | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/AlunoController.php` | index, store, show, update, toggle | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/AssinaturaController.php` | checkout, minha, cancelar | Cliente comercial e identidade do evento; cobrança não concede escopo pedagógico |
| `apps/api/app/Http/Controllers/Api/AuthController.php` | login, logout, me, update, trocarEmail, confirmarEmail, consultarConvite, recusarConvite, esqueceuSenha, redefinirSenha | Identidade global; vínculo explícito para convite/contexto; não desativar outras relações |
| `apps/api/app/Http/Controllers/Api/CartaoController.php` | index, imagem, arquivo, destroy, status, store, envio, repetirEnvio, pdf, show, vincularAluno, resolverAmbiguidade, revisar, revisao, fecharCorrecao, reabrirCorrecao | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/CensoController.php` | buscar, pesquisar | Catálogo global explícito; mutações restritas à plataforma, sem acesso pedagógico implícito |
| `apps/api/app/Http/Controllers/Api/ConfiguracaoController.php` | show, update | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/DashboardController.php` | aluno, professor, coordenador, dirEscolar, dirNucleo, admin, secretaria, aplicador | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/EscolaController.php` | index, store, show, update, toggle, logo, enviarLogo, logoRede, enviarLogoRede | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/GabaritoController.php` | show, salvar, publicar | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/LeadController.php` | store | Operação da plataforma; não confiar em espaço enviado publicamente; dados mínimos e envio autorizado |
| `apps/api/app/Http/Controllers/Api/NucleoController.php` | index, store, show, update, destroy | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/PlanoController.php` | index | Catálogo global explícito; mutações restritas à plataforma, sem acesso pedagógico implícito |
| `apps/api/app/Http/Controllers/Api/ProvaController.php` | index, store, show, update, publicar, destroy | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/RelatorioConsolidadoController.php` | escola, nucleo, rede | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/RelatorioController.php` | prova, turmaProva, turma, aluno | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/ResultadoController.php` | show, pdf | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/TurmaController.php` | index, store, show, update, toggle | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/UsuarioController.php` | index, store, show, reenviarConvite, enviarLinkSenha, update, toggle, catalogoPerfis, permissoes, definirPermissoes | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/VinculoProfessorController.php` | index, store, destroy, turmasDoProfessor | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Controllers/Api/WebhookController.php` | __construct, mercadoPago | Cliente comercial e identidade do evento; cobrança não concede escopo pedagógico |
| `apps/api/app/Support/Cabecalho.php` | escola, unidade, logoPath | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Support/Cadastro.php` | perfisGeridos, podeGerirPerfil, escolaNoEscopo, nucleoNoEscopo | Vínculo ativo, restrições por turma/aluno e unicidade contextual; nunca ampliar escopo |
| `apps/api/app/Support/CatalogoPermissoes.php` | porPerfil | Catálogo global explícito; mutações restritas à plataforma, sem acesso pedagógico implícito |
| `apps/api/app/Support/Censo.php` | linhaParaBase, paraEscola, titulo | Catálogo global explícito; mutações restritas à plataforma, sem acesso pedagógico implícito |
| `apps/api/app/Support/Correcao.php` | fechada, bloqueio, situacao | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Support/DadoProtegido.php` | conta, hash | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Support/Escopo.php` | escolaIds, turmaIds, provas, prova, provaComConteudo | Vínculo ativo, restrições por turma/aluno e unicidade contextual; nunca ampliar escopo |
| `apps/api/app/Support/FalhasProtegidas.php` | log | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Support/Permissao.php` | efetivas, pode, tipoNoPerfil, podeDefinir, limparCache | Vínculo ativo, restrições por turma/aluno e unicidade contextual; nunca ampliar escopo |
| `apps/api/app/Services/ContaUsuario.php` | convidar, dadosDoConvite, convitePendente, recusarConvite, pedirTrocaDeEmail, confirmarTrocaDeEmail, enviarLinkDeSenha | Identidade global; vínculo explícito para convite/contexto; não desativar outras relações |
| `apps/api/app/Services/MercadoPagoService.php` | __construct, criarPreferenciaCheckout, criarAssinaturaRecorrente, cancelarAssinatura, buscarPagamento, validarWebhook | Cliente comercial e identidade do evento; cobrança não concede escopo pedagógico |
| `apps/api/app/Services/Omr/AtualizadorStatusProva.php` | atualizar | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Services/Omr/CalculadoraNota.php` | metaMinima, calcular | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Services/Omr/FilaCartoes.php` | receber, repetir | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Services/Omr/GeradorCartoes.php` | gerar | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Services/Omr/ImagensCartao.php` | caminho, recorte, gerar, apagar, url | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Services/Omr/NativoOmrDriver.php` | processar, identificacao | Processador técnico; autorização do objeto pelo chamador, sem contexto global implícito |
| `apps/api/app/Services/Omr/OmrDriverInterface.php` | processar | Processador técnico; autorização do objeto pelo chamador, sem contexto global implícito |
| `apps/api/app/Services/Omr/OmrIdentificacaoInterface.php` | identificacao | Processador técnico; autorização do objeto pelo chamador, sem contexto global implícito |
| `apps/api/app/Services/Omr/ResultadoEnvio.php` | concluir, resposta | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Services/Omr/SimuladoOmrDriver.php` | processar | Processador técnico; autorização do objeto pelo chamador, sem contexto global implícito |
| `apps/api/app/Services/Turnstile.php` | validar | Operação da plataforma; não confiar em espaço enviado publicamente; dados mínimos e envio autorizado |
| `apps/api/app/Jobs/ProcessarCartaoJob.php` | __construct, backoff, middleware, handle, failed | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Jobs/VerificarInadimplenciaJob.php` | handle | Cliente comercial e identidade do evento; cobrança não concede escopo pedagógico |
| `apps/api/app/Console/Commands/EmailTeste.php` | handle | Operação da plataforma; não confiar em espaço enviado publicamente; dados mínimos e envio autorizado |
| `apps/api/app/Console/Commands/GerarImagensCartoes.php` | handle | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Console/Commands/ImportarAlunos.php` | handle | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Console/Commands/ImportarCenso.php` | handle | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Console/Commands/ImportarProvas.php` | handle | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Console/Commands/SincronizarPermissoes.php` | handle | Catálogo global explícito; mutações restritas à plataforma, sem acesso pedagógico implícito |
| `apps/api/app/Rules/UnicoProtegido.php` | __construct, validate | Vínculo ativo, restrições por turma/aluno e unicidade contextual; nunca ampliar escopo |
| `apps/api/app/Http/Middleware/CheckAssinaturaAtiva.php` | handle | Cliente comercial e identidade do evento; cobrança não concede escopo pedagógico |
| `apps/api/app/Http/Middleware/CheckPerfil.php` | handle | Vínculo ativo, restrições por turma/aluno e unicidade contextual; nunca ampliar escopo |
| `apps/api/app/Http/Middleware/CheckPermissao.php` | handle | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Middleware/ProtegerRespostas.php` | handle | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |
| `apps/api/app/Http/Middleware/SenhaTrocada.php` | handle | Contexto autorizado do espaço; revisar também helpers, joins, relações e gravações delegadas |

## Revisão transversal obrigatória

- Todos os métodos públicos dos controladores acima entram na revisão; helpers privados e consultas anônimas pertencem ao mesmo escopo. Rotas sem controlador em `apps/api/routes/api.php` e comandos agendados em `apps/api/routes/console.php` também precisam de classificação. Health público só retorna estado técnico.
- Modelos em `apps/api/app/Models`: relações e scopes devem conferir espaço dos dois lados. Não considerar um `find`, `exists`, `unique` ou relacionamento seguro apenas porque o controller autenticou o usuário.
- `apps/api/app/Providers/AppServiceProvider.php`, regras de unicidade e bootstraps: listeners e mecanismos de falha não podem depender da última requisição em workers persistentes. Logs sanitizados L3 permanecem, com contexto mínimo.
- `apps/web/app/Services/ApiClient.php`, controllers web, middleware de acesso, sessão, formulários e downloads: propagar contexto por aba; não guardar seleção global que misture vínculos. Revisar todos os consumidores de API, não apenas telas com seletor.
- `apps/web/public/js/cartoes-fila.js` e demais scripts que persistem estado: vincular recibos, chaves e polling ao contexto; revalidar após troca/encerramento de vínculo.
- Arquivos de cartão e URLs assinadas, PDFs, logos, caches, locks, exportações, importações e scripts operacionais precisam de espaço validado ou justificativa global. Dados de fixtures e seeders não autorizam execução em produção.
- Busca por novas consultas em cada PR: `DB::`, modelos Eloquent, SQL direto, agregações, joins, relacionamentos e acesso a Storage. O mapa é baseline e deve crescer com arquivos novos; regex e CI não comprovam autorização por si só.

## Critérios por família de operação

| Família | Conferência exigida |
|---|---|
| Listar/buscar/contar | Filtrar antes de paginação/agregação; impedir vazamento por total, ordenação ou mensagem de duplicidade |
| Detalhar/alterar/excluir | Resolver ID dentro do espaço autorizado; testar ID válido de outro espaço |
| Criar/importar/vincular | Espaço calculado pelo servidor; todas as FKs no mesmo espaço; matrícula/CPF não deduplicam instituições |
| Painel/relatório superior | Somente descendentes autorizados, preservando limites de perfil e suspensão |
| Job/retentativa | Contexto e relações revalidados, autorização revogada tratada, idempotência por espaço |
| Arquivo/PDF/link | Autorizar objeto e contexto, inclusive acesso após revogação e caches intermediários |
| Conta/convite/permissão | Conta global; ações institucionais limitadas ao vínculo, sem modificar vínculos alheios |
| Catálogo/plataforma | Exceção explícita e permissão própria; não reutilizar como consulta global de dados escolares |
