# Estrutura do código

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento descreve o monorepo do Gabarito360: as pastas, as convenções de código, os testes e a integração contínua (CI).

## 1. Visão do monorepo

| Pasta | Conteúdo |
|---|---|
| `apps/api/` | API Laravel. Regras de negócio, autenticação, OMR e relatórios. |
| `apps/web/` | Web Laravel (BFF). Telas, sessão e proxy para a API. |
| `scripts/` | Verificações, ensaios, builds de front-end e operação. |
| `infra/` | Serviços systemd, publicação da homologação e configuração de borda. |
| `deploy/` | Configuração do Nginx e servidor de borda da página pública. |
| `mockups/` | Desenhos HTML de referência das telas. |
| `style-system/` | Cópia de referência do sistema de estilo. |
| `previas/` | Protótipos locais de interface, como o editor de painéis. |
| `docs/` | Documentação. Veja [docs/README.md](../README.md). |
| `AGENTS.md`, `CLAUDE.md` | Regras permanentes de trabalho. |

Não existem `apps/android` nem `apps/ios`. Os aplicativos móveis estão adiados (FN-010).

## 2. `apps/api`

| Caminho | Conteúdo |
|---|---|
| `app/Http/Controllers/Api/` | Controladores. Um por recurso (provas, cartões, turmas, painéis, cópias, pedidos de titulares). |
| `app/Http/Middleware/` | `ExigirContextoVinculo`, `CheckPermissao`, `CheckPerfil`, `SenhaTrocada`, `ProtegerRespostas`, `RecusarCredenciaisNaUrl`, `RegistrarAuditoria`, `CheckAssinaturaAtiva`. |
| `app/Models/` | Modelos Eloquent. |
| `app/Services/` | Serviços. `Services/Omr/` guarda o motor de leitura, a fila e as imagens dos cartões. |
| `app/Support/` | Regras reutilizáveis: `Permissao`, `Escopo`, `Espacos`, `ConsultasPedagogicas`, `ContextoVinculo`, catálogos de painéis. |
| `app/Jobs/` | `ProcessarCartaoJob`, `GravarAuditoria`, trabalhos de cópias e inadimplência. |
| `app/Console/Commands/` | Comandos `g360:*` (importação, inventário, corte, catálogo, conciliação). |
| `app/Rules/`, `app/Casts/`, `app/Mail/`, `app/Logging/` | Regras de validação, casts, e-mails e logs protegidos. |
| `config/` | Configuração. Destaques: `omr.php`, `paineis.php`, `espacos.php`, `vinculos.php`, `copias.php`, `catalogo.php`, `auditoria.php`. |
| `database/migrations/`, `database/seeders/` | Esquema e dados iniciais. |
| `omr/` | Scripts Python do motor de leitura e dos PDFs. Veja [06](06-omr-e-documentos.md). |
| `routes/api.php` | Rotas da API sob `/api/v1`. É a fonte de verdade das rotas. |
| `routes/console.php` | Agenda: inadimplência diária e rotinas de cópias. |
| `tests/` | Testes `Feature`, `Unit`, `Fixtures` e `Support`. |

## 3. `apps/web`

| Caminho | Conteúdo |
|---|---|
| `app/Http/Controllers/` | Controladores de tela. Chamam a API pelo `ApiClient`. |
| `app/Http/Middleware/` | `Autenticado`, `SerializarSessao`, `RevisaoAcesso`, `ExigirSessaoIntegra`, `RespostaNavegacao`, `RecursoNavegacao`, `ContextoPorAba`, `ProtegerRespostas`, `RecusarCredenciaisNaUrl`. |
| `app/Services/ApiClient.php` | Cliente HTTP da API. Prazo de 15 segundos. Envia o token no cabeçalho. |
| `app/Support/` | `ContextoWeb`, `RotasNavegacao`, `UrlContextual`, `Acesso`, contratos de painéis. |
| `app/Routing/GeradorUrl.php` | Geração de URLs fixas. |
| `resources/views/` | Views Blade por módulo. `components/` e `layouts/` guardam a base visual. |
| `public/js/`, `public/css/` | JavaScript externo e CSS. Veja [09](09-interface-e-design-system.md). |
| `config/services.php` | Define `services.api.base_url` (variável `API_BASE_URL`). |
| `routes/web.php` | Rotas do navegador. É a fonte de verdade das telas. |
| `tests/` | Testes `Feature`, `Unit`, `Fixtures` e `Support`. |

O Web não tem tabelas de negócio. Sua pasta `database/` serve à sessão.

## 4. `scripts/`

| Pasta ou arquivo | Função |
|---|---|
| `scripts/verificar_telas.py`, `verificar_imagens.py`, `verificar_funcionalidades.py` | Verificações de estrutura, imagens e catálogo. |
| `scripts/tests/` | Testes Node e Python de front-end, PDFs e Nginx. |
| `scripts/auditoria/` | Ensaios de fila, restauração, navegação e interface. |
| `scripts/ensaios/` | Ensaios de corte, cópia e restauração (T2, AU1, CI1). |
| `scripts/operacao/` | Roteiros de publicação, backup, conferência de release. |
| `scripts/ci/` | Verificação do MariaDB descartável. |
| `scripts/tailwind/`, `paineis-grid/`, `graficos/`, `calendario/`, `notificacoes/` | Builds dos pacotes de front-end versionados em `apps/web/public/`. |
| `scripts/branding/` | Geração e verificação dos assets da marca. |

O roteiro oficial de publicação é `scripts/operacao/deploy.sh`. O arquivo `infra/deploy.sh` só encaminha para ele. O procedimento está em [Operação](../operacao/README.md).

## 5. Convenções

### Código

1. Escreva nomes de domínio em português (`provas`, `cartoes`, `turmas`).
2. Comece cada arquivo de código com um comentário de arquivo e função, quando o arquivo é novo.
3. Mantenha regra de negócio na API. Mantenha apresentação no Web.
4. Mantenha JavaScript executável em arquivos externos. Não use `<script>` inline nem `onclick`. Transmita dados por `data-*`.
5. Use modais da aplicação para confirmações. Não use `window.confirm`, `alert` ou `prompt`.
6. Nunca altere uma migration já executada. Crie uma nova.
7. Nunca coloque credencial, token ou código na URL.

### Git

- Commits seguem `tipo(escopo): descrição curta em pt-BR`. Tipos: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`. Escopos: `api`, `web`, `docs`, `db`, `infra`.
- O autor é o responsável (`lucasgenial`). Nenhuma IA aparece como autora.
- A publicação ocorre primeiro na homologação. Produção exige decisão expressa.

### Versão

A versão exibida vem de `apps/web/config/versao.php`. O rodapé mostra "Gabarito360 - v0.1.0 - Homologação" em telas a partir de 768 pixels.

## 6. Testes

| Camada | Ferramenta | Onde |
|---|---|---|
| API e Web | PHPUnit (`php artisan test`) | `apps/api/tests`, `apps/web/tests` |
| Banco | SQLite nos testes comuns. MariaDB descartável nos testes marcados. | CI `integridade-mariadb` |
| Front-end | `node --test` | `scripts/tests/*.test.mjs` |
| Navegador | Ensaios Chromium | `scripts/auditoria/*.mjs` |
| PDFs e OMR | Python | `scripts/tests/test_*.py` |

## 7. Integração contínua

O fluxo `.github/workflows/verificacao.yml` roda a cada push. Ele tem quatro grupos de jobs:

| Job | Verifica |
|---|---|
| `auditoria-recuperacao` | Fila, cópias e restauração de banco e fila em contêineres novos. |
| `telas` | Catálogo de telas, imagens e funcionalidades. Builds reproduzíveis (`git diff --exit-code`). Testes Node, PDFs e OMR. Ensaios de navegação. Nginx público. |
| `testes` (matriz `api` e `web`) | `composer audit --locked --no-dev`, `composer install`, `php artisan test`, ensaios de renderização e de exclusão da sessão. |
| `integridade-mariadb` | Armazenamento e commits do MariaDB descartável e o teste `ProgressoCorrecaoTest`. |

A CI também verifica a consistência do catálogo de funcionalidades. Veja [catálogo](../funcionalidades/README.md) e [008 CI de integração](../funcionalidades/008-ci-integracao/planejamento.md).

## Divergências encontradas

| Documento antigo | Afirmação | Situação no código |
|---|---|---|
| `12-arquitetura.md` | Pasta `Controllers/Api/V1/`. | Os controladores ficam em `Controllers/Api/`. A versão está no prefixo da rota. |
| `12-arquitetura.md` | Views `alunos`, `turmas`, `equipe`, `dashboard`. | Há também `correcao`, `copias`, `catalogo-atendimento`, `pedidos`, `navegacao`. `equipe` virou `membros`. |
| `168-estabilizacao-turmas-e-contratos.md` | Estrutura-alvo `apps/api/plataforma/`. | Não existe. É a FN-009 (candidata). |
| `12-arquitetura.md` | Uso de `apps/android` e `apps/ios`. | Não existem. |
