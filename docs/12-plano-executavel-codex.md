# Gabarito360 - Plano Executavel Codex

## 1. Objetivo

Este documento transforma a documentacao existente do Gabarito360 em uma sequencia executavel de micropassos pequenos, seguros, testaveis e versionaveis.

Cada micropasso deve resultar em uma alteracao tematica que possa ser revisada, validada e revertida sem depender de mudancas futuras ainda nao implementadas. O plano nao autoriza implementacao fora do escopo de cada etapa.

## 2. Estado inicial considerado

- A documentacao funcional, tecnica, relacional, mobile, OMR e de MVP ja existe em `docs/`.
- O backend Laravel 12 ja existe em `backend/`.
- Laravel Sanctum esta instalado, mas login e autorizacao ainda nao foram implementados.
- O endpoint `GET /api/health` e os testes basicos estao implementados.
- Nao existem migrations, models, CRUDs ou regras de negocio do dominio.
- O app Flutter, o modulo OMR, o painel administrativo e a infraestrutura Docker ainda nao existem.
- O MVP deve operar online primeiro. Offline completo, recorrection, PDF/XLSX e dashboards consolidados sao evolucoes posteriores.

## 3. Principios de execucao

1. Executar um micropasso por vez.
2. Nao iniciar um micropasso enquanto suas dependencias nao estiverem aceitas.
3. Interromper a sequencia quando uma verificacao falhar.
4. Manter autorizacao, validacao, auditoria e testes junto da funcionalidade.
5. Preservar historico; usar inativacao, substituicao ou cancelamento em vez de exclusao fisica operacional.
6. Publicar eventos e executar efeitos externos somente depois do commit da transacao.
7. Nao declarar qualidade do OMR sem dataset versionado e teste reproduzivel.
8. Nao versionar segredos, dados pessoais reais ou imagens identificaveis.
9. Nao executar comandos Git automaticamente. Os comandos de commit deste documento sao apenas sugestoes.
10. Atualizar a documentacao quando uma decisao aprovada alterar contratos ou regras.

## 4. Ordem geral de desenvolvimento

```text
Preparacao e decisoes
        |
        v
Fundacao Laravel e qualidade
        |
        v
Autenticacao, perfis e isolamento
        |
        v
Cadastros organizacionais e academicos
        |
        v
Provas, gabaritos e aplicacoes
        |
        +-------------------+
        v                   v
OMR reproduzivel       App Flutter online
        +-------------------+
                |
                v
Confirmacao, correcao e resultados
                |
        +-------+-------+
        v               v
Dashboards         Relatorios
        +-------+-------+
                |
                v
Hardening, LGPD, deploy e piloto
```

## 5. Fases e gates

| Fase | Micropassos | Gate de conclusao |
|---|---|---|
| FASE 0 - Preparacao do repositorio | MP-001 a MP-005 | Decisoes bloqueadoras, escopo, qualidade e piloto documentados |
| FASE 1 - Backend Laravel base | MP-006 a MP-010 | Backend reproduzivel, versionado, testavel e observavel |
| FASE 2 - Autenticacao e perfis | MP-011 a MP-015 | Isolamento vertical e horizontal comprovado por testes |
| FASE 3 - Nucleos, escolas e usuarios | MP-016 a MP-019 | Estrutura organizacional gerenciavel sem acesso cruzado |
| FASE 4 - Turmas e alunos | MP-020 a MP-023 | Estrutura academica e importacao funcionais |
| FASE 5 - Provas, questoes e gabaritos | MP-024 a MP-028 | Prova completa pode ser publicada e vinculada |
| FASE 6 - Aplicacoes de prova | MP-029 a MP-033 | Backend executa fluxo completo com leitura simulada |
| FASE 7 - Leitura de cartoes e OMR | MP-034 a MP-039 | OMR real atinge gate mensuravel no dataset homologado |
| FASE 8 - App Android Flutter | MP-040 a MP-045 | Aplicador conclui fluxo online real em aparelho homologado |
| FASE 9 - Dashboards em tempo real | MP-046 a MP-049 | Progresso consistente atualiza em ate 5 segundos |
| FASE 10 - Relatorios e exportacoes | MP-050 a MP-053 | Relatorio por turma e CSV coerentes e auditados |
| FASE 11 - Auditoria, seguranca e LGPD | MP-054 a MP-058 | Controles, retencao e validacao integrada aprovados |
| FASE 12 - Deploy e infraestrutura | MP-059 a MP-063 | Homologacao reproduzivel e piloto liberavel |

## 6. Definicao de pronto para cada micropasso

Um micropasso somente esta pronto quando:

- seus criterios de aceite foram atendidos;
- os comandos de verificacao aplicaveis passaram;
- nao existem alteracoes fora do escopo declarado;
- testes proporcionais ao risco foram adicionados ou atualizados;
- documentacao e contratos afetados foram atualizados;
- nenhum segredo ou dado pessoal real foi incluido;
- a alteracao esta pequena o suficiente para um commit tematico.

# FASE 0 - Preparacao do repositorio

## MP-001 - Registrar decisoes bloqueadoras do MVP

Objetivo:
Resolver e registrar as decisoes que bloqueiam migrations, OMR, seguranca e experiencia do piloto.

Acoes:
- Definir unicidade da matricula, identificacao externa e interna do cartao, politica de anulacao, motivo de correcao manual e retencao.
- Registrar responsavel, data, decisao, justificativa e impacto das decisoes; preservar decisoes substituidas e registrar suas sucessoras.

Arquivos envolvidos:
- `docs/decisoes/ADR-*.md` (novos)
- `docs/04-regras-de-negocio.md`
- `docs/06-modelagem-banco.md`

Critérios de aceite:
- Nenhuma decisao bloqueadora permanece sem responsavel e prazo.
- Decisoes aprovadas nao contradizem a modelagem ou as regras.

Verificação:
```bash
rg "PENDENTE|A DEFINIR" docs -g "*.md" -g "!12-plano-executavel-codex.md"
rg "D00[1-9]|D010" docs/decisoes/README.md docs/11-roadmap-mvp.md
```

Dependências:
- Nenhuma.

Não fazer nesta etapa:
- Nao criar migrations nem alterar codigo do backend.
- Nao decidir limiares OMR sem amostras reais.

Commit sugerido:
```bash
git add docs/decisoes docs/04-regras-de-negocio.md docs/06-modelagem-banco.md
git commit -m "documentacao: registrar decisoes bloqueadoras do mvp"
git push
```

## MP-002 - Fechar escopo, permissoes e abordagem do painel MVP

Objetivo:
Transformar o escopo documentado em uma fronteira clara para implementacao.

Acoes:
- Aprovar funcionalidades dentro e fora do MVP e a matriz de permissoes por acao e escopo.
- Escolher e registrar a abordagem do painel web; recomendacao inicial: Laravel Blade/Livewire para reduzir complexidade no MVP.

Arquivos envolvidos:
- `docs/01-visao-geral.md`
- `docs/05-casos-de-uso.md`
- `docs/11-roadmap-mvp.md`
- `docs/decisoes/ADR-painel-web.md` (novo)

Critérios de aceite:
- Cada perfil possui acoes permitidas e escopos definidos.
- Funcionalidades V2 nao aparecem como obrigatorias para liberar o MVP.

Verificação:
```bash
rg "Fora do MVP|Matriz resumida de permissoes|painel" docs
```

Dependências:
- MP-001.

Não fazer nesta etapa:
- Nao criar telas nem instalar dependencias de frontend.
- Nao ampliar o MVP com offline completo, PDF ou XLSX.

Commit sugerido:
```bash
git add docs/01-visao-geral.md docs/05-casos-de-uso.md docs/11-roadmap-mvp.md docs/decisoes
git commit -m "documentacao: fechar escopo e permissoes do mvp"
git push
```

## MP-003 - Definir convencoes de desenvolvimento e qualidade

Objetivo:
Padronizar organizacao, revisao, testes, API e nomenclatura antes das funcionalidades.

Acoes:
- Documentar convencoes Laravel, fronteiras entre Actions, Services, Policies, Requests e Resources.
- Definir verificacoes obrigatorias por commit e por pull request.

Arquivos envolvidos:
- `CONTRIBUTING.md` (novo)
- `backend/README.md`
- `docs/07-api.md`

Critérios de aceite:
- Ha criterio objetivo para escolher a camada de cada regra.
- Comandos minimos de formatacao, analise e testes estao documentados.

Verificação:
```bash
rg "Pint|test|Policy|Request|Resource|Service|Action" CONTRIBUTING.md backend/README.md
```

Dependências:
- MP-002.

Não fazer nesta etapa:
- Nao refatorar a base existente sem necessidade comprovada.
- Nao executar comandos Git automaticamente.

Commit sugerido:
```bash
git add CONTRIBUTING.md backend/README.md docs/07-api.md
git commit -m "documentacao: definir convencoes de desenvolvimento"
git push
```

## MP-004 - Definir ambientes, segredos, arquivos e retencao

Objetivo:
Estabelecer configuracoes seguras e responsabilidades por ambiente.

Acoes:
- Documentar `local`, `test`, `homologacao` e `producao`, incluindo PostgreSQL, Redis, storage e e-mail.
- Documentar a aplicacao da politica de segredos, classificacao, retencao e descarte definida no `ADR-D006`.

Arquivos envolvidos:
- `docs/infra/ambientes.md` (novo)
- `docs/seguranca/retencao-e-arquivos.md` (novo)
- `backend/.env.example`

Critérios de aceite:
- Nenhum segredo real aparece em arquivos versionados.
- Cada classe de arquivo aplica acesso, retencao e descarte coerentes com o `ADR-D006`.

Verificação:
```bash
rg "DB_PASSWORD=.+|APP_KEY=base64|SECRET|TOKEN=.+" . -g "!backend/.env" -g "!backend/vendor/**"
```

Dependências:
- MP-001.

Não fazer nesta etapa:
- Nao provisionar producao.
- Nao armazenar imagens reais identificaveis no repositorio.

Commit sugerido:
```bash
git add docs/infra docs/seguranca backend/.env.example
git commit -m "documentacao: definir ambientes e politica de retencao"
git push
```

## MP-005 - Preparar cartao, dataset e metas do piloto OMR

Objetivo:
Reduzir antecipadamente o maior risco tecnico do projeto.

Acoes:
- Versionar a especificacao do cartao inicial e o formato de rotulagem do dataset conforme o `ADR-D001`.
- Preparar a matriz de homologacao e o protocolo de medicao conforme `ADR-D007` e `ADR-D008`.

Arquivos envolvidos:
- `docs/omr/modelo-cartao-v1.md` (novo)
- `docs/omr/dataset-e-metricas.md` (novo)
- `docs/09-modulo-omr.md`

Critérios de aceite:
- O cartao aprovado no `ADR-D001` possui especificacao versionada para marcadores e para a regiao do codigo impresso externo quando existente.
- As metas do `ADR-D008` podem ser verificadas por uma suite reproduzivel nos dispositivos avaliados pelo `ADR-D007`.

Verificação:
```bash
rg "acuracia|dataset|dispositivo|marcadores|codigo" docs/omr docs/09-modulo-omr.md
```

Dependências:
- MP-001.

Não fazer nesta etapa:
- Nao declarar acuracia sem dataset.
- Nao incluir dados pessoais reais nas amostras.

Commit sugerido:
```bash
git add docs/omr docs/09-modulo-omr.md
git commit -m "documentacao: definir cartao e metas do piloto omr"
git push
```

# FASE 1 - Backend Laravel base

## MP-006 - Validar e congelar a baseline Laravel

Objetivo:
Confirmar que a base atual sobe e oferece um ponto de partida confiavel.

Acoes:
- Validar versoes, health check, rotas, testes e formatacao.
- Ajustar somente inconsistencias tecnicas encontradas na baseline.

Arquivos envolvidos:
- `backend/composer.json`
- `backend/routes/api.php`
- `backend/tests/Feature/Api/HealthTest.php`
- `backend/README.md`

Critérios de aceite:
- Laravel inicia e `GET /api/health` responde conforme contrato.
- Suite e formatacao passam sem regras de negocio.

Verificação:
```bash
cd backend && composer validate --strict
cd backend && php artisan test
cd backend && php vendor/bin/pint --test
cd backend && php artisan route:list --except-vendor
```

Dependências:
- Gate da FASE 0.

Não fazer nesta etapa:
- Nao implementar autenticacao nem models do dominio.
- Nao alterar o contrato do health check sem justificativa.

Commit sugerido:
```bash
git add backend
git commit -m "backend: validar baseline laravel"
git push
```

## MP-007 - Padronizar API v1, erros e request id

Objetivo:
Criar um contrato tecnico consistente antes dos endpoints de negocio.

Acoes:
- Definir prefixo `/api/v1`, envelope de sucesso/erro e codigos estaveis.
- Adicionar `request_id` a respostas e logs, com tratamento global de excecoes.

Arquivos envolvidos:
- `backend/bootstrap/app.php`
- `backend/routes/api.php`
- `backend/app/Support/ApiResponse.php`
- `backend/app/Http/Middleware/RequestId.php` (novo)
- `backend/tests/Feature/Api/ApiContractTest.php` (novo)

Critérios de aceite:
- Toda resposta testada possui formato e `request_id` consistentes.
- Excecoes internas nao expõem detalhes sensiveis.

Verificação:
```bash
cd backend && php artisan test --filter=ApiContractTest
cd backend && php vendor/bin/pint --test
```

Dependências:
- MP-006.

Não fazer nesta etapa:
- Nao criar endpoints de negocio.
- Nao registrar payloads sensiveis nos logs.

Commit sugerido:
```bash
git add backend/bootstrap backend/routes backend/app/Support backend/app/Http/Middleware backend/tests/Feature/Api
git commit -m "backend: padronizar contrato e correlacao da api"
git push
```

## MP-008 - Configurar PostgreSQL de desenvolvimento e testes

Objetivo:
Tornar a persistencia PostgreSQL reproduzivel e isolada para testes.

Acoes:
- Configurar conexoes local e de teste, extensoes aprovadas e convencoes de UUID.
- Criar teste tecnico de conexao sem migrations de negocio.

Arquivos envolvidos:
- `backend/.env.example`
- `backend/phpunit.xml`
- `backend/config/database.php`
- `backend/database/migrations/*enable_postgresql_extensions.php` (novo)
- `backend/tests/Feature/Infrastructure/DatabaseTest.php` (novo)

Critérios de aceite:
- Migrations tecnicas sobem em banco PostgreSQL vazio.
- Testes usam banco isolado e nao dependem de SQLite.

Verificação:
```bash
cd backend && php artisan migrate:fresh --env=testing
cd backend && php artisan test --filter=DatabaseTest
```

Dependências:
- MP-006.
- Decisoes de banco da MP-001.

Não fazer nesta etapa:
- Nao criar tabelas do dominio.
- Nao apontar testes para banco compartilhado ou de producao.

Commit sugerido:
```bash
git add backend/.env.example backend/phpunit.xml backend/config/database.php backend/database/migrations backend/tests/Feature/Infrastructure
git commit -m "backend: configurar postgresql para desenvolvimento e testes"
git push
```

## MP-009 - Preparar filas, cache e storage tecnicos

Objetivo:
Validar as dependencias tecnicas que suportarao importacoes, OMR e relatorios.

Acoes:
- Configurar Redis para cache e filas e storage privado compativel com S3.
- Criar jobs e testes tecnicos sem regra de negocio.

Arquivos envolvidos:
- `backend/config/cache.php`
- `backend/config/queue.php`
- `backend/config/filesystems.php`
- `backend/app/Jobs/TechnicalProbeJob.php` (novo)
- `backend/tests/Feature/Infrastructure/QueueAndStorageTest.php` (novo)

Critérios de aceite:
- Job tecnico pode ser enfileirado e processado.
- Arquivo tecnico privado pode ser gravado, lido e removido no teste.

Verificação:
```bash
cd backend && php artisan test --filter=QueueAndStorageTest
cd backend && php artisan queue:work --once
```

Dependências:
- MP-008.
- Politica da MP-004.

Não fazer nesta etapa:
- Nao processar imagens OMR.
- Nao tornar arquivos privados publicos.

Commit sugerido:
```bash
git add backend/config backend/app/Jobs backend/tests/Feature/Infrastructure
git commit -m "backend: preparar filas cache e storage"
git push
```

## MP-010 - Criar pipeline de qualidade e OpenAPI inicial

Objetivo:
Automatizar as verificacoes minimas e manter contratos versionados.

Acoes:
- Configurar CI para Composer, Pint, testes e validacao do contrato OpenAPI.
- Documentar o health check e os envelopes tecnicos.

Arquivos envolvidos:
- `.github/workflows/backend-ci.yml` (novo)
- `docs/openapi.yaml` (novo)
- `backend/README.md`

Critérios de aceite:
- Pipeline executa em ambiente limpo.
- OpenAPI representa corretamente a baseline da API.

Verificação:
```bash
cd backend && composer validate --strict
cd backend && php vendor/bin/pint --test
cd backend && php artisan test
```

Dependências:
- MP-007 a MP-009.

Não fazer nesta etapa:
- Nao documentar endpoints ainda inexistentes como implementados.
- Nao ignorar falhas do pipeline.

Commit sugerido:
```bash
git add .github/workflows/backend-ci.yml docs/openapi.yaml backend/README.md
git commit -m "ci: adicionar pipeline e contrato inicial da api"
git push
```

# FASE 2 - Autenticacao e perfis

## MP-011 - Criar schema de usuarios, perfis e permissoes

Objetivo:
Implementar a base relacional de identidade e controle de acesso.

Acoes:
- Criar migrations, models, factories e seeders para usuarios, perfis, permissoes e associacoes.
- Aplicar UUIDs, escopos e restricoes da modelagem canonica.

Arquivos envolvidos:
- `backend/database/migrations/*usuarios_perfis_permissoes*.php` (novos)
- `backend/app/Models/User.php`
- `backend/app/Models/Perfil.php` (novo)
- `backend/app/Models/Permissao.php` (novo)
- `backend/database/seeders/AccessControlSeeder.php` (novo)

Critérios de aceite:
- Schema sobe e reverte em banco vazio.
- Perfis e permissoes do MVP sao semeados sem duplicidade.

Verificação:
```bash
cd backend && php artisan migrate:fresh --seed --env=testing
cd backend && php artisan test --filter=AccessControl
```

Dependências:
- Gate da FASE 1.
- Matriz aprovada na MP-002.

Não fazer nesta etapa:
- Nao criar login.
- Nao adicionar permissoes fora da matriz aprovada.

Commit sugerido:
```bash
git add backend/database backend/app/Models
git commit -m "backend: criar base de usuarios perfis e permissoes"
git push
```

## MP-012 - Implementar escopos e policies fundamentais

Objetivo:
Garantir autorizacao reutilizavel por perfil e escopo organizacional.

Acoes:
- Implementar resolvedor de escopos global, nucleo, escola e operacional.
- Criar policies base e testes de isolamento vertical e horizontal.

Arquivos envolvidos:
- `backend/app/Policies/*`
- `backend/app/Services/Authorization/*` (novos)
- `backend/app/Providers/AppServiceProvider.php`
- `backend/tests/Feature/Authorization/*` (novos)

Critérios de aceite:
- Acesso fora do escopo e negado no backend.
- Perfil de consulta nao executa mutacoes.

Verificação:
```bash
cd backend && php artisan test --testsuite=Feature --filter=Authorization
```

Dependências:
- MP-011.

Não fazer nesta etapa:
- Nao confiar em filtros enviados pelo cliente para autorizacao.
- Nao criar excecoes de suporte sem auditoria definida.

Commit sugerido:
```bash
git add backend/app/Policies backend/app/Services/Authorization backend/app/Providers backend/tests/Feature/Authorization
git commit -m "backend: implementar escopos e policies fundamentais"
git push
```

## MP-013 - Implementar login, logout e endpoint me

Objetivo:
Permitir autenticacao API segura com Sanctum.

Acoes:
- Implementar login, logout e `/me` com Form Requests e Resources.
- Revogar token atual no logout e bloquear usuario inativo.

Arquivos envolvidos:
- `backend/app/Http/Controllers/Api/Auth/*` (novos)
- `backend/app/Http/Requests/Auth/*` (novos)
- `backend/app/Http/Resources/UserResource.php` (novo)
- `backend/routes/api.php`
- `backend/tests/Feature/Auth/*` (novos)

Critérios de aceite:
- Usuario ativo autentica e recebe contexto autorizado.
- Usuario inativo, credencial invalida e token revogado sao rejeitados.

Verificação:
```bash
cd backend && php artisan test --filter=Auth
cd backend && php artisan route:list --path=api/v1
```

Dependências:
- MP-011 e MP-012.

Não fazer nesta etapa:
- Nao implementar recuperacao de senha ou MFA.
- Nao retornar senha, hash ou dados pessoais desnecessarios.

Commit sugerido:
```bash
git add backend/app/Http backend/routes/api.php backend/tests/Feature/Auth
git commit -m "backend: implementar autenticacao basica com sanctum"
git push
```

## MP-014 - Registrar dispositivos e proteger autenticacao mobile

Objetivo:
Emitir tokens revogaveis por dispositivo e reduzir abuso de autenticacao.

Acoes:
- Criar registro de dispositivo mobile e associar tokens ao dispositivo.
- Aplicar rate limit, revogacao e validacao de versao minima do app.

Arquivos envolvidos:
- `backend/database/migrations/*create_dispositivos_mobile_table.php` (novo)
- `backend/app/Models/DispositivoMobile.php` (novo)
- `backend/app/Http/Middleware/*` (novos)
- `backend/config/gabarito360.php` (novo)
- `backend/tests/Feature/Auth/MobileAuthenticationTest.php` (novo)

Critérios de aceite:
- Token mobile identifica usuario e dispositivo.
- Dispositivo revogado perde acesso e login abusivo recebe `429`.

Verificação:
```bash
cd backend && php artisan test --filter=MobileAuthenticationTest
```

Dependências:
- MP-013.

Não fazer nesta etapa:
- Nao implementar sincronizacao offline.
- Nao coletar identificadores invasivos do dispositivo.

Commit sugerido:
```bash
git add backend/database/migrations backend/app/Models backend/app/Http/Middleware backend/config/gabarito360.php backend/tests/Feature/Auth
git commit -m "backend: proteger autenticacao por dispositivo mobile"
git push
```

## MP-015 - Auditar e testar a matriz de acesso

Objetivo:
Fechar a fase com evidencia automatizada de isolamento e rastreabilidade de acesso.

Acoes:
- Auditar login, logout, bloqueios e mudancas de perfil.
- Criar testes parametrizados para todos os cruzamentos criticos da matriz.

Arquivos envolvidos:
- `backend/app/Services/Audit/*` (novos)
- `backend/tests/Feature/Authorization/PermissionMatrixTest.php` (novo)
- `backend/tests/Feature/Auth/*`

Critérios de aceite:
- Todos os cenarios criticos retornam `200/201`, `401` ou `403` conforme matriz.
- Eventos de acesso nao armazenam segredos.

Verificação:
```bash
cd backend && php artisan test --filter=PermissionMatrixTest
cd backend && php artisan test --filter=Auth
```

Dependências:
- MP-012 a MP-014.

Não fazer nesta etapa:
- Nao liberar CRUDs antes dos testes passarem.
- Nao registrar tokens ou senhas na auditoria.

Commit sugerido:
```bash
git add backend/app/Services/Audit backend/tests/Feature
git commit -m "backend: auditar e validar matriz de acesso"
git push
```

# FASE 3 - Nucleos, escolas e usuarios

## MP-016 - Implementar fatia vertical de nucleos

Objetivo:
Entregar cadastro, consulta, edicao e inativacao de nucleos com autorizacao.

Acoes:
- Criar migration, model, policy, requests, resource, action, controller e rotas.
- Cobrir unicidade, inativacao e escopo por testes.

Arquivos envolvidos:
- `backend/database/migrations/*create_nucleos_table.php` (novo)
- `backend/app/Models/Nucleo.php` (novo)
- `backend/app/{Actions,Policies,Http}/**/*Nucleo*` (novos)
- `backend/tests/Feature/Api/Nucleos/*` (novos)

Critérios de aceite:
- Apenas perfil autorizado altera nucleos.
- Nucleo inativado permanece consultavel no historico.

Verificação:
```bash
cd backend && php artisan test --filter=Nucleo
cd backend && php vendor/bin/pint --test
```

Dependências:
- Gate da FASE 2.

Não fazer nesta etapa:
- Nao implementar escolas no mesmo commit.
- Nao excluir fisicamente nucleos.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/routes/api.php backend/tests/Feature/Api/Nucleos
git commit -m "backend: implementar gestao de nucleos"
git push
```

## MP-017 - Implementar fatia vertical de escolas

Objetivo:
Gerenciar escolas dentro do nucleo autorizado.

Acoes:
- Criar schema e endpoints de escolas com filtros de escopo.
- Impedir acesso cruzado e movimentacao silenciosa entre nucleos.

Arquivos envolvidos:
- `backend/database/migrations/*create_escolas_table.php` (novo)
- `backend/app/Models/Escola.php` (novo)
- `backend/app/{Actions,Policies,Http}/**/*Escola*` (novos)
- `backend/tests/Feature/Api/Escolas/*` (novos)

Critérios de aceite:
- Codigo e unico dentro do nucleo.
- Gestor nao consulta ou altera escola de outro nucleo.

Verificação:
```bash
cd backend && php artisan test --filter=Escola
```

Dependências:
- MP-016.

Não fazer nesta etapa:
- Nao criar turmas ou alunos.
- Nao permitir troca de nucleo sem processo auditado.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/routes/api.php backend/tests/Feature/Api/Escolas
git commit -m "backend: implementar gestao de escolas"
git push
```

## MP-018 - Implementar gestao administrativa de usuarios

Objetivo:
Permitir criar, atualizar, inativar e atribuir perfis no escopo autorizado.

Acoes:
- Criar endpoints administrativos de usuarios e atribuicoes de perfil.
- Revogar tokens ao inativar e auditar mudancas de acesso.

Arquivos envolvidos:
- `backend/app/{Actions,Policies,Http}/**/*Usuario*`
- `backend/app/Observers/UserObserver.php` (novo)
- `backend/tests/Feature/Api/Usuarios/*` (novos)

Critérios de aceite:
- Gestor cria usuario apenas em seu escopo.
- Inativacao revoga acesso e preserva historico.

Verificação:
```bash
cd backend && php artisan test --filter=Usuario
cd backend && php artisan test --filter=PermissionMatrixTest
```

Dependências:
- MP-017.

Não fazer nesta etapa:
- Nao implementar convite por e-mail ou MFA.
- Nao expor documento completo em listagens.

Commit sugerido:
```bash
git add backend/app backend/routes/api.php backend/tests/Feature/Api/Usuarios
git commit -m "backend: implementar gestao administrativa de usuarios"
git push
```

## MP-019 - Criar painel administrativo minimo organizacional

Objetivo:
Permitir operar nucleos, escolas e usuarios sem chamadas manuais a API.

Acoes:
- Implementar telas minimas conforme abordagem aprovada na MP-002.
- Reutilizar autorizacao do backend e tokens visuais de `docs/ui_token_gov_brasil.json`.

Arquivos envolvidos:
- `backend/resources/views/admin/**` ou componentes equivalentes
- `backend/resources/css/app.css`
- `backend/routes/web.php`
- `backend/tests/Feature/Web/OrganizationPanelTest.php` (novo)

Critérios de aceite:
- Fluxos principais funcionam por teclado e respeitam escopo.
- Interface nao duplica regra de autorizacao como unica barreira.

Verificação:
```bash
cd backend && npm run build
cd backend && php artisan test --filter=OrganizationPanelTest
```

Dependências:
- MP-016 a MP-018.

Não fazer nesta etapa:
- Nao criar dashboard ou identidade visual fora dos tokens aprovados.
- Nao exibir dados pessoais desnecessarios.

Commit sugerido:
```bash
git add backend/resources backend/routes/web.php backend/tests/Feature/Web
git commit -m "painel: criar gestao organizacional minima"
git push
```

# FASE 4 - Turmas e alunos

## MP-020 - Implementar turmas e matriculas historicas

Objetivo:
Representar turmas por ano letivo e preservar o historico aluno-turma.

Acoes:
- Criar schema, models e endpoints de turmas e matriculas.
- Validar escola, ano letivo, codigo e apenas uma matricula ativa por aluno/ano.

Arquivos envolvidos:
- `backend/database/migrations/*turmas_matriculas*.php` (novos)
- `backend/app/Models/{Turma,MatriculaTurma}.php` (novos)
- `backend/app/{Actions,Policies,Http}/**/*Turma*` (novos)
- `backend/tests/Feature/Api/Turmas/*` (novos)

Critérios de aceite:
- Codigo nao duplica na mesma escola e ano.
- Historico permanece apos encerramento ou transferencia.

Verificação:
```bash
cd backend && php artisan test --filter=Turma
cd backend && php artisan test --filter=Matricula
```

Dependências:
- Gate da FASE 3.

Não fazer nesta etapa:
- Nao criar alunos por importacao.
- Nao apagar matriculas historicas.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/routes/api.php backend/tests/Feature/Api/Turmas
git commit -m "backend: implementar turmas e matriculas historicas"
git push
```

## MP-021 - Implementar fatia vertical de alunos

Objetivo:
Gerenciar alunos com minimizacao de dados e escopo escolar.

Acoes:
- Criar schema e endpoints de alunos com unicidade aprovada de matricula.
- Aplicar resources diferentes conforme permissao de visualizacao.

Arquivos envolvidos:
- `backend/database/migrations/*create_alunos_table.php` (novo)
- `backend/app/Models/Aluno.php` (novo)
- `backend/app/{Actions,Policies,Http}/**/*Aluno*` (novos)
- `backend/tests/Feature/Api/Alunos/*` (novos)

Critérios de aceite:
- Matricula respeita a regra aprovada.
- Usuario fora do escopo nao descobre a existencia do aluno.

Verificação:
```bash
cd backend && php artisan test --filter=Aluno
```

Dependências:
- MP-020.

Não fazer nesta etapa:
- Nao coletar CPF ou nascimento sem finalidade aprovada.
- Nao implementar importacao no mesmo commit.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/routes/api.php backend/tests/Feature/Api/Alunos
git commit -m "backend: implementar gestao de alunos"
git push
```

## MP-022 - Implementar vinculos de aplicadores com turmas

Objetivo:
Autorizar professores e aplicadores somente nas turmas vinculadas.

Acoes:
- Criar `aplicadores_turmas`, endpoints de vinculo e encerramento.
- Cobrir visibilidade operacional com testes de policy.

Arquivos envolvidos:
- `backend/database/migrations/*create_aplicadores_turmas_table.php` (novo)
- `backend/app/Models/AplicadorTurma.php` (novo)
- `backend/app/{Actions,Policies,Http}/**/*AplicadorTurma*` (novos)
- `backend/tests/Feature/Authorization/TurmaAssignmentTest.php` (novo)

Critérios de aceite:
- Um vinculo ativo equivalente nao duplica.
- Aplicador nao vinculado nao lista a turma.

Verificação:
```bash
cd backend && php artisan test --filter=TurmaAssignmentTest
```

Dependências:
- MP-020 e MP-021.

Não fazer nesta etapa:
- Nao autorizar aplicacao ainda inexistente.
- Nao apagar vinculos encerrados.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/routes/api.php backend/tests/Feature/Authorization
git commit -m "backend: vincular aplicadores a turmas"
git push
```

## MP-023 - Implementar importacao validada de alunos por CSV

Objetivo:
Importar alunos em lote somente depois de validacao e confirmacao explicitas.

Acoes:
- Definir template, upload privado, validacao por linha e resumo previo.
- Processar confirmacao em fila e transacao, sem duplicidade silenciosa.

Arquivos envolvidos:
- `backend/app/Jobs/ImportStudentsJob.php` (novo)
- `backend/app/Services/Import/StudentCsvImporter.php` (novo)
- `backend/app/Http/Controllers/Api/StudentImportController.php` (novo)
- `backend/tests/Feature/Api/StudentImports/*` (novos)
- `docs/modelos/importacao-alunos.csv` (novo)

Critérios de aceite:
- Arquivo invalido mostra erros por linha antes de gravar.
- Reenvio e confirmacao nao criam alunos duplicados.

Verificação:
```bash
cd backend && php artisan test --filter=StudentImport
cd backend && php artisan queue:work --once
```

Dependências:
- MP-009 e MP-021.

Não fazer nesta etapa:
- Nao aceitar XLSX no MVP.
- Nao persistir linhas invalidas parcialmente sem confirmacao.

Commit sugerido:
```bash
git add backend/app backend/routes/api.php backend/tests/Feature/Api/StudentImports docs/modelos
git commit -m "backend: implementar importacao validada de alunos"
git push
```

# FASE 5 - Provas, questoes e gabaritos

## MP-024 - Implementar modelos versionados de cartao

Objetivo:
Persistir a configuracao OMR homologada sem permitir mutacao historica.

Acoes:
- Criar schema, model e endpoints de modelos de cartao.
- Bloquear alteracao de versao homologada usada em prova ou aplicacao.
- Definir por modelo o tipo, a regiao e a normalizacao do codigo impresso, incluindo a opcao `sem_codigo`.

Arquivos envolvidos:
- `backend/database/migrations/*create_modelos_cartao_table.php` (novo)
- `backend/app/Models/ModeloCartao.php` (novo)
- `backend/app/{Actions,Policies,Http}/**/*ModeloCartao*` (novos)
- `backend/tests/Feature/Api/ModelosCartao/*` (novos)

Critérios de aceite:
- Configuracao possui versao, marcadores, regioes e limiares.
- Codigo impresso externo e codigo adicional do sistema possuem regras distintas conforme `ADR-D010`.
- Versao homologada e imutavel.

Verificação:
```bash
cd backend && php artisan test --filter=ModeloCartao
```

Dependências:
- Gate da FASE 4.
- Especificacao da MP-005.

Não fazer nesta etapa:
- Nao processar imagens.
- Nao esconder limiares no codigo.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/routes/api.php backend/tests/Feature/Api/ModelosCartao
git commit -m "backend: implementar modelos versionados de cartao"
git push
```

## MP-025 - Implementar provas e questoes em rascunho

Objetivo:
Permitir criar e editar provas objetivas antes da publicacao.

Acoes:
- Criar schema e fatia vertical de provas e questoes.
- Validar proprietario organizacional, quantidades e compatibilidade com o modelo.

Arquivos envolvidos:
- `backend/database/migrations/*provas_questoes*.php` (novos)
- `backend/app/Models/{Prova,Questao}.php` (novos)
- `backend/app/{Actions,Policies,Http}/**/*Prova*` (novos)
- `backend/tests/Feature/Api/Provas/*` (novos)

Critérios de aceite:
- Prova pertence exatamente a nucleo ou escola.
- Numero de questao nao duplica na prova.

Verificação:
```bash
cd backend && php artisan test --filter=Prova
cd backend && php artisan test --filter=Questao
```

Dependências:
- MP-024.

Não fazer nesta etapa:
- Nao publicar prova.
- Nao permitir edicao fora do escopo.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/routes/api.php backend/tests/Feature/Api/Provas
git commit -m "backend: implementar provas e questoes em rascunho"
git push
```

## MP-026 - Implementar gabarito oficial em rascunho

Objetivo:
Criar versoes de gabarito e respostas oficiais completas e validaveis.

Acoes:
- Criar schema, models e endpoints de gabaritos e respostas.
- Validar alternativas, anulacao, pesos e completude.

Arquivos envolvidos:
- `backend/database/migrations/*gabaritos_oficiais*.php` (novos)
- `backend/app/Models/{GabaritoOficial,GabaritoResposta}.php` (novos)
- `backend/app/{Actions,Policies,Http}/**/*Gabarito*` (novos)
- `backend/tests/Feature/Api/Gabaritos/*` (novos)

Critérios de aceite:
- Rascunho registra uma resposta por questao.
- Inconsistencias impedem publicacao futura.

Verificação:
```bash
cd backend && php artisan test --filter=Gabarito
```

Dependências:
- MP-025.
- Politica de anulacao aprovada na MP-001.

Não fazer nesta etapa:
- Nao alterar resultado ou recorrerigir.
- Nao marcar gabarito incompleto como vigente.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/routes/api.php backend/tests/Feature/Api/Gabaritos
git commit -m "backend: implementar gabarito oficial em rascunho"
git push
```

## MP-027 - Implementar publicacao transacional da prova

Objetivo:
Publicar prova e gabarito somente quando todos os requisitos forem validos.

Acoes:
- Criar action transacional de publicacao e tornar o gabarito vigente imutavel.
- Auditar publicacao e cobrir conflitos concorrentes.

Arquivos envolvidos:
- `backend/app/Actions/Provas/PublishProva.php` (novo)
- `backend/app/Policies/ProvaPolicy.php`
- `backend/tests/Feature/Api/Provas/PublishProvaTest.php` (novo)

Critérios de aceite:
- Prova incompleta nao publica.
- Existe apenas um gabarito vigente por prova.

Verificação:
```bash
cd backend && php artisan test --filter=PublishProvaTest
```

Dependências:
- MP-026.

Não fazer nesta etapa:
- Nao implementar alteracao de gabarito publicado.
- Nao publicar sem modelo homologado.

Commit sugerido:
```bash
git add backend/app/Actions/Provas backend/app/Policies backend/tests/Feature/Api/Provas
git commit -m "backend: publicar prova e gabarito de forma transacional"
git push
```

## MP-028 - Vincular provas publicadas a turmas

Objetivo:
Autorizar explicitamente quais turmas podem receber uma prova.

Acoes:
- Criar `prova_turmas`, endpoints de vinculo e validacao de escopo.
- Adicionar tela administrativa minima de prova, gabarito e vinculos.

Arquivos envolvidos:
- `backend/database/migrations/*create_prova_turmas_table.php` (novo)
- `backend/app/Models/ProvaTurma.php` (novo)
- `backend/app/{Actions,Http}/**/*ProvaTurma*` (novos)
- `backend/resources/views/admin/provas/**` (novos)
- `backend/tests/Feature/Api/Provas/ProvaTurmaTest.php` (novo)

Critérios de aceite:
- Apenas prova publicada e turma autorizada podem ser vinculadas.
- Vinculo duplicado e rejeitado.

Verificação:
```bash
cd backend && php artisan test --filter=ProvaTurmaTest
cd backend && npm run build
```

Dependências:
- MP-027.

Não fazer nesta etapa:
- Nao criar aplicacao.
- Nao vincular prova a turma fora do escopo.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/resources backend/routes backend/tests/Feature/Api/Provas
git commit -m "backend: vincular provas publicadas a turmas"
git push
```

# FASE 6 - Aplicacoes de prova

## MP-029 - Criar aplicacoes e snapshot de alunos

Objetivo:
Preparar uma aplicacao reproduzivel para uma prova, turma e gabarito especificos.

Acoes:
- Criar schema de aplicacoes, aplicadores e alunos previstos.
- Criar aplicacao em estado `aguardando` com snapshot transacional.

Arquivos envolvidos:
- `backend/database/migrations/*aplicacoes*.php` (novos)
- `backend/app/Models/{Aplicacao,AplicacaoAluno,AplicacaoAplicador}.php` (novos)
- `backend/app/Actions/Aplicacoes/CreateAplicacao.php` (novo)
- `backend/tests/Feature/Api/Aplicacoes/CreateAplicacaoTest.php` (novo)

Critérios de aceite:
- Aplicacao congela prova, modelo, gabarito e alunos previstos.
- Nao e criada para prova nao publicada ou turma nao vinculada.

Verificação:
```bash
cd backend && php artisan test --filter=CreateAplicacaoTest
```

Dependências:
- Gate da FASE 5.

Não fazer nesta etapa:
- Nao iniciar aplicacao nem receber leituras.
- Nao recalcular snapshot silenciosamente.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/tests/Feature/Api/Aplicacoes
git commit -m "backend: criar aplicacoes e snapshot de alunos"
git push
```

## MP-030 - Implementar consulta, inicio e finalizacao da aplicacao

Objetivo:
Controlar o ciclo de vida operacional da aplicacao.

Acoes:
- Criar endpoints de consulta, alunos, inicio e finalizacao.
- Validar estado, vinculo do aplicador e auditoria.

Arquivos envolvidos:
- `backend/app/{Actions,Policies,Http}/**/*Aplicacao*`
- `backend/routes/api.php`
- `backend/tests/Feature/Api/Aplicacoes/LifecycleTest.php` (novo)

Critérios de aceite:
- Apenas usuario autorizado inicia ou finaliza.
- Transicoes invalidas sao rejeitadas com codigo estavel.

Verificação:
```bash
cd backend && php artisan test --filter=LifecycleTest
```

Dependências:
- MP-029.

Não fazer nesta etapa:
- Nao implementar reabertura no MVP.
- Nao aceitar confirmacao apos finalizacao.

Commit sugerido:
```bash
git add backend/app backend/routes/api.php backend/tests/Feature/Api/Aplicacoes
git commit -m "backend: controlar ciclo de vida das aplicacoes"
git push
```

## MP-031 - Persistir leitura preliminar simulada

Objetivo:
Estabilizar o contrato de leitura antes do OMR e do app real.

Acoes:
- Criar schema de arquivos, leituras e respostas detectadas.
- Aceitar payload simulado de 20 respostas, confiancas, alertas, codigo impresso detectado e codigo do sistema proposto quando houver.

Arquivos envolvidos:
- `backend/database/migrations/*leituras_respostas_arquivos*.php` (novos)
- `backend/app/Models/{Arquivo,LeituraCartao,RespostaDetectada}.php` (novos)
- `backend/app/Http/Controllers/Api/LeituraCartaoController.php` (novo)
- `backend/tests/Feature/Api/Leituras/CreateSimulatedReadingTest.php` (novo)

Critérios de aceite:
- Cada tentativa e preservada e possui uma resposta por questao.
- Codigo impresso detectado e codigo do sistema proposto permanecem em campos distintos.
- Leitura simulada nao cria resultado valido.

Verificação:
```bash
cd backend && php artisan test --filter=CreateSimulatedReadingTest
```

Dependências:
- MP-030.

Não fazer nesta etapa:
- Nao processar imagem real.
- Nao confirmar automaticamente a leitura.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/routes/api.php backend/tests/Feature/Api/Leituras
git commit -m "backend: persistir leitura preliminar simulada"
git push
```

## MP-032 - Confirmar cartao de forma idempotente e transacional

Objetivo:
Implementar a transacao critica que vincula aluno, cartao e leitura.

Acoes:
- Criar cartoes, logs de sincronizacao e confirmacao por `Idempotency-Key`.
- Aplicar locks, constraints e conflitos `409` distintos para aluno, codigo impresso e codigo do sistema.

Arquivos envolvidos:
- `backend/database/migrations/*cartoes_logs_sincronizacao*.php` (novos)
- `backend/app/Models/{CartaoResposta,LogSincronizacao}.php` (novos)
- `backend/app/Actions/Leituras/ConfirmReading.php` (novo)
- `backend/tests/Feature/Api/Leituras/ConfirmReadingTest.php` (novo)
- `backend/tests/Feature/Concurrency/ReadingConfirmationTest.php` (novo)

Critérios de aceite:
- Reenvio igual retorna o resultado anterior.
- Aluno, codigo impresso na prova ou codigo do sistema reutilizado nunca geram dois cartoes validos.
- Codigo impresso e codigo do sistema nunca sobrescrevem um ao outro.

Verificação:
```bash
cd backend && php artisan test --filter=ConfirmReadingTest
cd backend && php artisan test --filter=ReadingConfirmationTest
```

Dependências:
- MP-031.

Não fazer nesta etapa:
- Nao publicar evento antes do commit.
- Nao resolver conflito sobrescrevendo dados.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/tests/Feature/Api/Leituras backend/tests/Feature/Concurrency
git commit -m "backend: confirmar cartao com idempotencia e transacao"
git push
```

## MP-033 - Corrigir prova e concluir fluxo backend simulado

Objetivo:
Gerar resultado vigente e atualizar pendencias depois da confirmacao.

Acoes:
- Criar resultados e respostas corrigidas com snapshot do gabarito.
- Atualizar aluno para lido, auditar e disparar evento somente apos commit.

Arquivos envolvidos:
- `backend/database/migrations/*resultados*.php` (novos)
- `backend/app/Models/{Resultado,ResultadoResposta}.php` (novos)
- `backend/app/Actions/Resultados/CalculateResult.php` (novo)
- `backend/app/Events/ReadingConfirmed.php` (novo)
- `backend/tests/Feature/Flows/SimulatedApplicationFlowTest.php` (novo)

Critérios de aceite:
- Totais e nota correspondem ao gabarito congelado.
- Fluxo simulado cria aplicacao, confirma, corrige, atualiza e finaliza.

Verificação:
```bash
cd backend && php artisan test --filter=SimulatedApplicationFlowTest
cd backend && php artisan test
```

Dependências:
- MP-032.

Não fazer nesta etapa:
- Nao implementar recorrection.
- Nao calcular dashboard por dados nao vigentes.

Commit sugerido:
```bash
git add backend/database/migrations backend/app backend/tests/Feature/Flows
git commit -m "backend: concluir correcao e fluxo simulado da aplicacao"
git push
```

# FASE 7 - Leitura de cartoes e OMR

## MP-034 - Criar modulo OMR isolado e contrato executavel

Objetivo:
Separar o processamento OpenCV do dominio Laravel e tornar sua saida reproduzivel.

Acoes:
- Criar modulo `omr/` com ambiente, contrato JSON e teste de smoke.
- Adotar no MVP processamento OpenCV em worker/servico, mantendo validacao de captura no app.

Arquivos envolvidos:
- `omr/README.md` (novo)
- `omr/pyproject.toml` (novo)
- `omr/src/**` (novos)
- `omr/tests/**` (novos)
- `docs/09-modulo-omr.md`

Critérios de aceite:
- Modulo recebe uma imagem de fixture e retorna contrato validavel.
- Dependencias e versoes sao reproduziveis.

Verificação:
```bash
cd omr && python -m pytest
cd omr && python -m ruff check .
```

Dependências:
- Gate da FASE 6.
- MP-005.

Não fazer nesta etapa:
- Nao integrar ao Laravel.
- Nao implementar limiares ocultos ou IA generativa.

Commit sugerido:
```bash
git add omr docs/09-modulo-omr.md
git commit -m "omr: criar modulo isolado e contrato executavel"
git push
```

## MP-035 - Versionar dataset e harness de regressao

Objetivo:
Medir o OMR com dados rotulados e separar calibracao de teste.

Acoes:
- Criar manifesto anonimo de imagens, rotulos e particoes.
- Implementar runner que compara saida com resposta esperada.

Arquivos envolvidos:
- `omr/dataset/manifest.example.json` (novo)
- `omr/src/evaluation/**` (novos)
- `omr/tests/test_dataset_contract.py` (novo)
- `docs/omr/dataset-e-metricas.md`

Critérios de aceite:
- Dataset rejeita amostra sem rotulo ou particao.
- Relatorio calcula metricas definidas sem alterar o conjunto de teste.

Verificação:
```bash
cd omr && python -m pytest tests/test_dataset_contract.py
cd omr && python -m src.evaluation --help
```

Dependências:
- MP-034.

Não fazer nesta etapa:
- Nao versionar imagens com dados pessoais.
- Nao calibrar usando o conjunto de teste.

Commit sugerido:
```bash
git add omr/dataset omr/src/evaluation omr/tests docs/omr/dataset-e-metricas.md
git commit -m "omr: criar dataset e avaliacao reproduzivel"
git push
```

## MP-036 - Implementar validacao de entrada e qualidade

Objetivo:
Recusar ou sinalizar imagens inadequadas antes da deteccao.

Acoes:
- Validar formato, tamanho, resolucao, nitidez, contraste e iluminacao.
- Retornar falhas acionaveis no contrato.

Arquivos envolvidos:
- `omr/src/quality/**` (novos)
- `omr/tests/test_quality.py` (novo)
- `omr/config/modelo-cartao-v1.json` (novo)

Critérios de aceite:
- Imagens invalidas geram motivo estavel.
- Metricas de qualidade fazem parte da saida.

Verificação:
```bash
cd omr && python -m pytest tests/test_quality.py
```

Dependências:
- MP-035.

Não fazer nesta etapa:
- Nao detectar respostas.
- Nao aceitar imagem ruim silenciosamente.

Commit sugerido:
```bash
git add omr/src/quality omr/tests/test_quality.py omr/config
git commit -m "omr: validar entrada e qualidade da imagem"
git push
```

## MP-037 - Detectar marcadores e corrigir perspectiva

Objetivo:
Normalizar a imagem para as coordenadas do modelo homologado.

Acoes:
- Detectar marcadores, validar geometria e aplicar transformacao de perspectiva.
- Cobrir rotacao, inclinacao e marcadores ausentes.

Arquivos envolvidos:
- `omr/src/geometry/**` (novos)
- `omr/tests/test_geometry.py` (novo)
- `omr/fixtures/**` (novos anonimizados)

Critérios de aceite:
- Imagem processavel e normalizada nas dimensoes esperadas.
- Falha geometrica nao produz respostas.

Verificação:
```bash
cd omr && python -m pytest tests/test_geometry.py
```

Dependências:
- MP-036.

Não fazer nesta etapa:
- Nao classificar bolhas.
- Nao ajustar tolerancias somente para uma imagem.

Commit sugerido:
```bash
git add omr/src/geometry omr/tests/test_geometry.py omr/fixtures
git commit -m "omr: detectar marcadores e corrigir perspectiva"
git push
```

## MP-038 - Detectar marcacoes, alertas e confianca

Objetivo:
Classificar cada questao como marcada, branca, dupla, duvidosa ou falha.

Acoes:
- Recortar regioes, medir preenchimentos A-E e calcular confiancas.
- Persistir todos os limiares na configuracao versionada.

Arquivos envolvidos:
- `omr/src/marks/**` (novos)
- `omr/src/confidence/**` (novos)
- `omr/tests/test_marks.py` (novo)
- `omr/config/modelo-cartao-v1.json`

Critérios de aceite:
- Saida processavel possui exatamente 20 respostas.
- Branco, dupla e baixa confianca sao sinalizados.

Verificação:
```bash
cd omr && python -m pytest tests/test_marks.py
cd omr && python -m src.evaluation --config config/modelo-cartao-v1.json
```

Dependências:
- MP-037.

Não fazer nesta etapa:
- Nao confirmar respostas pelo aplicador.
- Nao esconder classificacao duvidosa como marcada.

Commit sugerido:
```bash
git add omr/src/marks omr/src/confidence omr/tests/test_marks.py omr/config
git commit -m "omr: detectar marcacoes alertas e confianca"
git push
```

## MP-039 - Ler codigo impresso e integrar OMR ao backend

Objetivo:
Transformar upload autorizado em leitura preliminar real, rastreavel e revisavel.

Acoes:
- Implementar leitura do codigo impresso conforme o modelo, com fallback manual ou registro de ausencia.
- Integrar storage, fila OMR, persistencia de metricas e suite de regressao ao backend.

Arquivos envolvidos:
- `omr/src/code_reader/**` (novos)
- `backend/app/Jobs/ProcessReadingImage.php` (novo)
- `backend/app/Services/Omr/*` (novos)
- `backend/tests/Feature/Omr/*` (novos)
- `omr/tests/**`

Critérios de aceite:
- Upload real gera leitura preliminar sem confirmar automaticamente.
- OMR nao gera nem substitui o codigo do sistema.
- Resultado e reproduzivel para mesma imagem, modelo e configuracao.

Verificação:
```bash
cd omr && python -m pytest
cd backend && php artisan test --filter=Omr
cd backend && php artisan queue:work --once
```

Dependências:
- MP-038.
- MP-009 e MP-031.

Não fazer nesta etapa:
- Nao liberar piloto se metas da MP-005 falharem.
- Nao expor caminhos internos ou imagens sem autorizacao.

Commit sugerido:
```bash
git add omr backend/app/Jobs backend/app/Services/Omr backend/tests/Feature/Omr
git commit -m "omr: integrar processamento real ao backend"
git push
```

# FASE 8 - App Android Flutter

## MP-040 - Criar projeto Flutter e arquitetura base

Objetivo:
Criar um app Android testavel, acessivel e alinhado aos tokens visuais.

Acoes:
- Criar projeto em `mobile/`, ambientes, navegacao, estado e tratamento global de erros.
- Incorporar tokens aplicaveis de `docs/ui_token_gov_brasil.json`.

Arquivos envolvidos:
- `mobile/pubspec.yaml` (novo)
- `mobile/lib/**` (novos)
- `mobile/test/**` (novos)
- `mobile/README.md` (novo)

Critérios de aceite:
- App compila para Android e teste inicial passa.
- Arquitetura e ambientes estao documentados.

Verificação:
```bash
cd mobile && flutter pub get
cd mobile && flutter analyze
cd mobile && flutter test
```

Dependências:
- Contratos estabilizados nas FASES 2 e 6.

Não fazer nesta etapa:
- Nao implementar login ou camera.
- Nao adicionar suporte iOS como requisito do MVP.

Commit sugerido:
```bash
git add mobile
git commit -m "mobile: criar projeto flutter e arquitetura base"
git push
```

## MP-041 - Implementar autenticacao e cliente API mobile

Objetivo:
Autenticar aplicador e manter token protegido por dispositivo.

Acoes:
- Implementar cliente HTTP, login, logout, storage seguro e expiracao.
- Tratar erros estaveis da API sem expor detalhes internos.

Arquivos envolvidos:
- `mobile/lib/features/auth/**` (novos)
- `mobile/lib/core/network/**` (novos)
- `mobile/lib/core/storage/**` (novos)
- `mobile/test/features/auth/**` (novos)

Critérios de aceite:
- Usuario ativo autentica e logout remove acesso local.
- Token nao e gravado em armazenamento inseguro.

Verificação:
```bash
cd mobile && flutter analyze
cd mobile && flutter test test/features/auth
```

Dependências:
- MP-040.
- MP-013 e MP-014.

Não fazer nesta etapa:
- Nao implementar offline.
- Nao registrar token em log.

Commit sugerido:
```bash
git add mobile/lib/features/auth mobile/lib/core mobile/test/features/auth
git commit -m "mobile: implementar autenticacao e cliente api"
git push
```

## MP-042 - Implementar aplicacoes, resumo e alunos

Objetivo:
Apresentar apenas o contexto operacional autorizado do aplicador.

Acoes:
- Implementar lista de aplicacoes, resumo, inicio/finalizacao e alunos.
- Exibir lidos, pendentes e busca sem dados pessoais excessivos.

Arquivos envolvidos:
- `mobile/lib/features/applications/**` (novos)
- `mobile/lib/features/students/**` (novos)
- `mobile/test/features/applications/**` (novos)

Critérios de aceite:
- Aplicador ve somente aplicacoes autorizadas.
- Estado local atualiza apos respostas validas da API.

Verificação:
```bash
cd mobile && flutter test test/features/applications
cd mobile && flutter analyze
```

Dependências:
- MP-041.
- MP-030.

Não fazer nesta etapa:
- Nao capturar imagem.
- Nao inferir permissoes apenas no app.

Commit sugerido:
```bash
git add mobile/lib/features/applications mobile/lib/features/students mobile/test/features/applications
git commit -m "mobile: implementar aplicacoes resumo e alunos"
git push
```

## MP-043 - Implementar captura orientada e upload

Objetivo:
Capturar uma imagem adequada e criar leitura preliminar no backend.

Acoes:
- Solicitar camera, exibir guia, validar qualidade basica e permitir refazer.
- Fazer upload privado com identificador idempotente, codigo do sistema proposto opcional e estados de progresso.

Arquivos envolvidos:
- `mobile/lib/features/capture/**` (novos)
- `mobile/android/**`
- `mobile/test/features/capture/**` (novos)

Critérios de aceite:
- Permissao negada e imagem inadequada possuem recuperacao clara.
- Upload repetido nao cria tentativa divergente silenciosa.

Verificação:
```bash
cd mobile && flutter test test/features/capture
cd mobile && flutter analyze
```

Dependências:
- MP-042.
- MP-039.

Não fazer nesta etapa:
- Nao confirmar leitura.
- Nao solicitar localizacao ou armazenamento sem necessidade.

Commit sugerido:
```bash
git add mobile/lib/features/capture mobile/android mobile/test/features/capture
git commit -m "mobile: implementar captura orientada e upload"
git push
```

## MP-044 - Implementar conferencia e correcao manual

Objetivo:
Exigir revisao humana de respostas e alertas antes da confirmacao.

Acoes:
- Exibir 20 respostas, confiancas, codigo impresso detectado, codigo do sistema quando utilizado e alertas.
- Permitir alteracao manual com motivo obrigatorio conforme o `ADR-D005`.

Arquivos envolvidos:
- `mobile/lib/features/review/**` (novos)
- `mobile/test/features/review/**` (novos)

Critérios de aceite:
- Branco, dupla e baixa confianca sao destacados por texto, icone e cor.
- Resposta detectada e final permanecem distintas no payload.
- Codigo impresso e codigo do sistema aparecem em campos separados.

Verificação:
```bash
cd mobile && flutter test test/features/review
cd mobile && flutter analyze
```

Dependências:
- MP-043.

Não fazer nesta etapa:
- Nao confirmar alertas sem aceite explicito.
- Nao substituir silenciosamente a deteccao original.

Commit sugerido:
```bash
git add mobile/lib/features/review mobile/test/features/review
git commit -m "mobile: implementar conferencia e correcao manual"
git push
```

## MP-045 - Confirmar leitura e validar fluxo em aparelhos

Objetivo:
Concluir o fluxo online real do aplicador em dispositivos homologados.

Acoes:
- Implementar confirmacao de aluno, codigo impresso quando houver, codigo do sistema quando utilizado, resultado, pendencias e historico da sessao.
- Testar idempotencia, conflitos independentes de identificadores, perda de rede e aparelhos homologados.

Arquivos envolvidos:
- `mobile/lib/features/confirmation/**` (novos)
- `mobile/lib/features/results/**` (novos)
- `mobile/test/features/confirmation/**` (novos)
- `docs/mobile/matriz-homologacao.md` (novo)

Critérios de aceite:
- App marca aluno como lido somente apos resposta valida.
- Uma captura real percorre OMR, revisao, confirmacao e resultado.

Verificação:
```bash
cd mobile && flutter test
cd mobile && flutter analyze
cd mobile && flutter build apk --debug
```

Dependências:
- MP-044.
- Gate da FASE 7.

Não fazer nesta etapa:
- Nao implementar fila offline completa.
- Nao liberar dispositivo sem registro de homologacao.

Commit sugerido:
```bash
git add mobile docs/mobile
git commit -m "mobile: concluir fluxo online de leitura"
git push
```

# FASE 9 - Dashboards em tempo real

## MP-046 - Implementar snapshot consistente da aplicacao

Objetivo:
Fornecer uma fonte confiavel para cards, pendencias, alertas e reconexao.

Acoes:
- Criar consulta autorizada de previstos, lidos, pendentes, percentual e alertas.
- Garantir que apenas resultados e cartoes vigentes entrem nos totais.

Arquivos envolvidos:
- `backend/app/Queries/ApplicationDashboardQuery.php` (novo)
- `backend/app/Http/Controllers/Api/ApplicationDashboardController.php` (novo)
- `backend/tests/Feature/Api/Dashboards/ApplicationSnapshotTest.php` (novo)

Critérios de aceite:
- Totais correspondem ao banco para cenarios vazios e completos.
- Usuario fora do escopo recebe acesso negado.

Verificação:
```bash
cd backend && php artisan test --filter=ApplicationSnapshotTest
```

Dependências:
- Gate da FASE 8.

Não fazer nesta etapa:
- Nao configurar WebSocket.
- Nao usar resultados substituidos ou cancelados.

Commit sugerido:
```bash
git add backend/app/Queries backend/app/Http/Controllers/Api backend/tests/Feature/Api/Dashboards
git commit -m "dashboard: implementar snapshot consistente da aplicacao"
git push
```

## MP-047 - Configurar Reverb e canais autorizados

Objetivo:
Permitir assinatura de eventos sem vazamento entre escopos.

Acoes:
- Configurar Laravel Reverb e autenticacao de canais.
- Testar tentativa de assinatura fora do escopo.

Arquivos envolvidos:
- `backend/config/reverb.php` (novo ou publicado)
- `backend/routes/channels.php` (novo)
- `backend/bootstrap/app.php`
- `backend/tests/Feature/Broadcasting/ChannelAuthorizationTest.php` (novo)

Critérios de aceite:
- Canal da aplicacao exige usuario autorizado.
- Evento nao expoe dados pessoais desnecessarios.

Verificação:
```bash
cd backend && php artisan test --filter=ChannelAuthorizationTest
cd backend && php artisan route:list --except-vendor
```

Dependências:
- MP-046.

Não fazer nesta etapa:
- Nao publicar eventos antes do commit.
- Nao criar canais globais sem autorizacao.

Commit sugerido:
```bash
git add backend/config/reverb.php backend/routes/channels.php backend/bootstrap/app.php backend/tests/Feature/Broadcasting
git commit -m "dashboard: configurar reverb e canais autorizados"
git push
```

## MP-048 - Publicar eventos e criar dashboard operacional

Objetivo:
Atualizar a tela da aplicacao depois de confirmacoes e mudancas de estado.

Acoes:
- Publicar eventos apos commit para leitura confirmada, inicio e finalizacao.
- Criar cards, ultimas leituras, pendencias e alertas no painel.

Arquivos envolvidos:
- `backend/app/Events/*` (novos ou alterados)
- `backend/resources/views/dashboard/**` (novos)
- `backend/resources/js/**`
- `backend/tests/Feature/Web/ApplicationDashboardTest.php` (novo)

Critérios de aceite:
- Confirmacao valida atualiza a tela sem recarga manual.
- Indicadores mantem coerencia com o snapshot.

Verificação:
```bash
cd backend && npm run build
cd backend && php artisan test --filter=ApplicationDashboardTest
```

Dependências:
- MP-047.

Não fazer nesta etapa:
- Nao criar dashboards consolidados de nucleo e escola.
- Nao publicar payload completo do aluno no evento.

Commit sugerido:
```bash
git add backend/app/Events backend/resources backend/tests/Feature/Web
git commit -m "dashboard: criar acompanhamento operacional em tempo real"
git push
```

## MP-049 - Implementar resiliencia e medir latencia do dashboard

Objetivo:
Manter a tela consistente durante falhas de conexao e comprovar a meta de atualizacao.

Acoes:
- Implementar reconexao, recarga de snapshot e polling de contingencia.
- Criar teste de isolamento, consistencia e medicao de latencia.

Arquivos envolvidos:
- `backend/resources/js/**`
- `backend/tests/Feature/Broadcasting/DashboardConsistencyTest.php` (novo)
- `docs/qualidade/metricas-dashboard.md` (novo)

Critérios de aceite:
- Reconexao recupera um snapshot confiavel.
- Atualizacao ocorre em ate 5 segundos na condicao homologada.

Verificação:
```bash
cd backend && npm run build
cd backend && php artisan test --filter=DashboardConsistencyTest
```

Dependências:
- MP-048.

Não fazer nesta etapa:
- Nao mascarar perda de eventos sem recarregar snapshot.
- Nao declarar desempenho sem registrar condicoes do teste.

Commit sugerido:
```bash
git add backend/resources/js backend/tests/Feature/Broadcasting docs/qualidade
git commit -m "dashboard: adicionar resiliencia e validar latencia"
git push
```

# FASE 10 - Relatorios e exportacoes

## MP-050 - Implementar consulta de relatorio por turma

Objetivo:
Consultar resultados vigentes e pendencias com filtros autorizados.

Acoes:
- Criar query paginada, resumo da turma e contrato de filtros.
- Garantir coerencia entre linhas, totais e media.

Arquivos envolvidos:
- `backend/app/Queries/ClassReportQuery.php` (novo)
- `backend/app/Http/Controllers/Api/ClassReportController.php` (novo)
- `backend/tests/Feature/Api/Reports/ClassReportTest.php` (novo)

Critérios de aceite:
- Apenas resultados vigentes entram nos calculos.
- Alunos pendentes aparecem corretamente.

Verificação:
```bash
cd backend && php artisan test --filter=ClassReportTest
```

Dependências:
- Gate da FASE 9.

Não fazer nesta etapa:
- Nao gerar arquivo.
- Nao incluir ranking individual sem finalidade aprovada.

Commit sugerido:
```bash
git add backend/app/Queries backend/app/Http/Controllers/Api backend/tests/Feature/Api/Reports
git commit -m "relatorio: implementar consulta por turma"
git push
```

## MP-051 - Criar tela de relatorio por turma

Objetivo:
Permitir que gestor autorizado consulte e filtre resultados.

Acoes:
- Criar tabela, resumo, filtros, estados vazios e mensagens acessiveis.
- Aplicar escopo e minimizacao de dados pessoais.

Arquivos envolvidos:
- `backend/resources/views/reports/class/**` (novos)
- `backend/resources/js/**`
- `backend/tests/Feature/Web/ClassReportPageTest.php` (novo)

Critérios de aceite:
- Tela e API apresentam totais coerentes.
- Usuario fora do escopo nao acessa a pagina.

Verificação:
```bash
cd backend && npm run build
cd backend && php artisan test --filter=ClassReportPageTest
```

Dependências:
- MP-050.

Não fazer nesta etapa:
- Nao gerar PDF ou XLSX.
- Nao exibir documento completo do aluno.

Commit sugerido:
```bash
git add backend/resources backend/tests/Feature/Web
git commit -m "relatorio: criar tela de resultados por turma"
git push
```

## MP-052 - Implementar exportacao CSV segura e auditada

Objetivo:
Entregar a exportacao MVP sem formula injection ou acesso indevido.

Acoes:
- Gerar CSV com colunas aprovadas, neutralizacao de formulas e autorizacao.
- Auditar solicitacao e download.

Arquivos envolvidos:
- `backend/app/Actions/Reports/ExportClassCsv.php` (novo)
- `backend/app/Http/Controllers/Api/ReportExportController.php` (novo)
- `backend/tests/Feature/Api/Reports/ClassCsvExportTest.php` (novo)

Critérios de aceite:
- CSV corresponde ao relatorio em tela.
- Valores perigosos sao neutralizados e downloads sao auditados.

Verificação:
```bash
cd backend && php artisan test --filter=ClassCsvExportTest
```

Dependências:
- MP-051.

Não fazer nesta etapa:
- Nao gerar arquivo publico permanente.
- Nao aceitar filtros fora do escopo.

Commit sugerido:
```bash
git add backend/app/Actions/Reports backend/app/Http/Controllers/Api backend/tests/Feature/Api/Reports
git commit -m "relatorio: implementar exportacao csv segura"
git push
```

## MP-053 - Preparar exportacoes assincronas futuras

Objetivo:
Criar a base segura para relatorios longos e formatos V2 sem implementa-los ainda.

Acoes:
- Modelar solicitacao, estado, expiracao e URL temporaria de relatorio.
- Criar job tecnico e testes de autorizacao e expiracao.

Arquivos envolvidos:
- `backend/database/migrations/*create_relatorios_gerados_table.php` (novo)
- `backend/app/Models/RelatorioGerado.php` (novo)
- `backend/app/Jobs/GenerateReport.php` (novo)
- `backend/tests/Feature/Api/Reports/AsyncReportBaseTest.php` (novo)

Critérios de aceite:
- Solicitacao longa pode ser enfileirada e expirar.
- Download temporario exige autorizacao.

Verificação:
```bash
cd backend && php artisan test --filter=AsyncReportBaseTest
cd backend && php artisan queue:work --once
```

Dependências:
- MP-052.

Não fazer nesta etapa:
- Nao implementar PDF ou XLSX.
- Nao manter exportacoes indefinidamente.

Commit sugerido:
```bash
git add backend/database/migrations backend/app/Models backend/app/Jobs backend/tests/Feature/Api/Reports
git commit -m "relatorio: preparar base para exportacoes assincronas"
git push
```

# FASE 11 - Auditoria, seguranca e LGPD

## MP-054 - Consolidar trilha imutavel de auditoria

Objetivo:
Garantir rastreabilidade uniforme para operacoes sensiveis de todas as fases.

Acoes:
- Criar ou consolidar schema, servico e consulta autorizada de auditoria.
- Cobrir alteracoes manuais, publicacao, confirmacao, exportacao e mudanca de acesso.

Arquivos envolvidos:
- `backend/database/migrations/*create_auditorias_table.php` (novo ou consolidado)
- `backend/app/Models/Auditoria.php` (novo)
- `backend/app/Services/Audit/*`
- `backend/tests/Feature/Audit/*` (novos)

Critérios de aceite:
- Usuario operacional nao altera ou remove auditoria.
- Eventos nao contêm segredos ou dados pessoais desnecessarios.

Verificação:
```bash
cd backend && php artisan test --filter=Audit
```

Dependências:
- Gate da FASE 10.
- Eventos auditaveis implementados nas fases anteriores.

Não fazer nesta etapa:
- Nao transformar auditoria em log tecnico irrestrito.
- Nao armazenar imagem, senha ou token.

Commit sugerido:
```bash
git add backend/database/migrations backend/app/Models backend/app/Services/Audit backend/tests/Feature/Audit
git commit -m "seguranca: consolidar trilha imutavel de auditoria"
git push
```

## MP-055 - Implementar retencao, descarte e minimizacao

Objetivo:
Aplicar as politicas LGPD aprovadas a imagens, logs e exportacoes.

Acoes:
- Criar jobs de expiracao e descarte auditado.
- Revisar Resources, logs e metadados para minimizar dados pessoais.

Arquivos envolvidos:
- `backend/app/Jobs/Retention/*` (novos)
- `backend/app/Services/Privacy/*` (novos)
- `backend/tests/Feature/Privacy/*` (novos)
- `docs/seguranca/retencao-e-arquivos.md`

Critérios de aceite:
- Arquivo expirado e descartado conforme politica e com auditoria.
- Logs e respostas nao exibem dados alem da finalidade.

Verificação:
```bash
cd backend && php artisan test --filter=Privacy
cd backend && php artisan test --filter=Retention
```

Dependências:
- MP-054.
- Politica aprovada na MP-004.

Não fazer nesta etapa:
- Nao apagar evidencia ainda exigida legalmente.
- Nao anonimizar sem preservar integridade historica necessaria.

Commit sugerido:
```bash
git add backend/app/Jobs/Retention backend/app/Services/Privacy backend/tests/Feature/Privacy docs/seguranca
git commit -m "lgpd: implementar retencao descarte e minimizacao"
git push
```

## MP-056 - Executar hardening da API e uploads

Objetivo:
Reduzir superficie de ataque antes da homologacao.

Acoes:
- Revisar rate limits, headers, CORS, MIME real, tamanho, timeouts e erros.
- Adicionar testes de autorizacao negativa e abuso dos endpoints criticos.

Arquivos envolvidos:
- `backend/bootstrap/app.php`
- `backend/config/**`
- `backend/app/Http/Middleware/**`
- `backend/tests/Feature/Security/*` (novos)

Critérios de aceite:
- Upload malformado e acesso indevido sao rejeitados.
- Respostas de erro nao expõem stack trace ou caminhos internos.

Verificação:
```bash
cd backend && php artisan test --filter=Security
cd backend && composer audit
```

Dependências:
- MP-055.

Não fazer nesta etapa:
- Nao corrigir alerta ignorando teste ou desabilitando protecao.
- Nao liberar storage ou banco publicamente.

Commit sugerido:
```bash
git add backend/bootstrap backend/config backend/app/Http/Middleware backend/tests/Feature/Security
git commit -m "seguranca: reforcar api e uploads"
git push
```

## MP-057 - Implementar observabilidade e alertas operacionais

Objetivo:
Detectar falhas, filas acumuladas e degradacao sem expor dados pessoais.

Acoes:
- Padronizar logs estruturados, metricas e health checks de dependencias.
- Definir alertas para erros, filas, OMR e tempo real.

Arquivos envolvidos:
- `backend/config/logging.php`
- `backend/app/Support/Observability/*` (novos)
- `docs/operacao/observabilidade.md` (novo)
- `backend/tests/Feature/Infrastructure/ObservabilityTest.php` (novo)

Critérios de aceite:
- Requisicao pode ser rastreada por `request_id`.
- Alertas possuem limiar, responsavel e acao documentados.

Verificação:
```bash
cd backend && php artisan test --filter=ObservabilityTest
rg "password|token|imagem" backend/storage/logs -i
```

Dependências:
- MP-056.

Não fazer nesta etapa:
- Nao registrar payloads completos por conveniencia.
- Nao criar alerta sem procedimento de resposta.

Commit sugerido:
```bash
git add backend/config/logging.php backend/app/Support/Observability docs/operacao backend/tests/Feature/Infrastructure
git commit -m "operacao: implementar observabilidade e alertas"
git push
```

## MP-058 - Executar validacao integrada e auditoria do piloto

Objetivo:
Comprovar que o MVP funciona ponta a ponta e falha de forma segura.

Acoes:
- Automatizar o cenario principal e os cenarios obrigatorios de falha.
- Executar auditoria amostral OMR, autorizacao, concorrencia e LGPD.

Arquivos envolvidos:
- `backend/tests/Feature/Flows/MvpEndToEndTest.php` (novo)
- `mobile/integration_test/**` (novos)
- `omr/tests/**`
- `docs/qualidade/relatorio-validacao-mvp.md` (novo)

Critérios de aceite:
- Fluxo completo passa sem intervencao direta no banco.
- Nenhuma duplicidade, acesso cruzado ou confirmacao silenciosa ocorre.

Verificação:
```bash
cd backend && php artisan test
cd mobile && flutter test
cd omr && python -m pytest
```

Dependências:
- MP-054 a MP-057.
- Gates das FASES 7 a 10.

Não fazer nesta etapa:
- Nao corrigir dados manualmente para fazer o teste passar.
- Nao liberar piloto com risco critico sem tratamento.

Commit sugerido:
```bash
git add backend/tests mobile/integration_test omr/tests docs/qualidade
git commit -m "qualidade: validar fluxo integrado do mvp"
git push
```

# FASE 12 - Deploy e infraestrutura

## MP-059 - Criar ambiente local com Docker Compose

Objetivo:
Tornar backend, PostgreSQL, Redis, worker, Reverb, OMR e storage reproduziveis.

Acoes:
- Criar imagens e Compose para desenvolvimento local.
- Adicionar health checks, volumes e redes privadas.

Arquivos envolvidos:
- `compose.yaml` (novo)
- `infra/docker/**` (novos)
- `.env.example` (novo ou alterado)
- `README.md`

Critérios de aceite:
- Ambiente sobe com procedimento documentado.
- Banco, Redis e storage nao ficam expostos sem necessidade.

Verificação:
```bash
docker compose config
docker compose up -d
docker compose ps
```

Dependências:
- MP-058.

Não fazer nesta etapa:
- Nao usar credenciais de producao.
- Nao tratar Compose local como deploy final.

Commit sugerido:
```bash
git add compose.yaml infra/docker .env.example README.md
git commit -m "infraestrutura: criar ambiente local com docker compose"
git push
```

## MP-060 - Preparar imagens de producao e Nginx

Objetivo:
Criar artefatos imutaveis e proxy seguro para homologacao.

Acoes:
- Criar Dockerfiles de producao, Nginx, limites e processo de workers.
- Executar containers como usuario sem privilegio quando aplicavel.

Arquivos envolvidos:
- `infra/docker/production/**` (novos)
- `infra/nginx/**` (novos)
- `docs/infra/deploy.md` (novo)

Critérios de aceite:
- Imagens constroem sem segredos embutidos.
- Nginx aplica TLS esperado, limites e encaminhamento correto.

Verificação:
```bash
docker compose -f compose.production.yaml config
docker build -f infra/docker/production/backend.Dockerfile backend
```

Dependências:
- MP-059.

Não fazer nesta etapa:
- Nao publicar imagem sem scan e versionamento.
- Nao expor PostgreSQL, Redis ou worker.

Commit sugerido:
```bash
git add infra/docker/production infra/nginx docs/infra/deploy.md compose.production.yaml
git commit -m "infraestrutura: preparar imagens de producao e nginx"
git push
```

## MP-061 - Criar pipeline de entrega para homologacao

Objetivo:
Automatizar build, verificacoes, migrations controladas e deploy de homologacao.

Acoes:
- Estender CI com build, scan, artefatos e deploy protegido.
- Documentar estrategia de migration, rollback e aprovacao.

Arquivos envolvidos:
- `.github/workflows/deploy-staging.yml` (novo)
- `.github/workflows/backend-ci.yml`
- `docs/infra/deploy.md`
- `docs/infra/rollback.md` (novo)

Critérios de aceite:
- Deploy so ocorre depois de todas as verificacoes.
- Rollback e migrations destrutivas exigem procedimento explicito.

Verificação:
```bash
docker compose -f compose.production.yaml config
cd backend && php artisan test
```

Dependências:
- MP-060.

Não fazer nesta etapa:
- Nao automatizar deploy de producao sem aprovacao.
- Nao executar migration destrutiva sem plano de reversao.

Commit sugerido:
```bash
git add .github/workflows docs/infra
git commit -m "ci: criar entrega automatizada para homologacao"
git push
```

## MP-062 - Implementar backup, restauracao e runbooks

Objetivo:
Comprovar continuidade operacional antes do piloto.

Acoes:
- Definir e testar backup/restauracao de banco e arquivos.
- Criar runbooks para falha de fila, OMR, WebSocket e indisponibilidade.

Arquivos envolvidos:
- `infra/backup/**` (novos)
- `docs/operacao/backup-e-restauracao.md` (novo)
- `docs/operacao/runbooks/**` (novos)

Critérios de aceite:
- Restauracao e executada em ambiente isolado e registrada.
- RPO e RTO definidos possuem evidencia de teste.

Verificação:
```bash
docker compose config
rg "RPO|RTO|restauracao|responsavel" docs/operacao
```

Dependências:
- MP-061.

Não fazer nesta etapa:
- Nao considerar backup valido sem testar restauracao.
- Nao sobrescrever ambiente ativo durante teste.

Commit sugerido:
```bash
git add infra/backup docs/operacao
git commit -m "operacao: documentar e validar continuidade"
git push
```

## MP-063 - Liberar homologacao e preparar piloto controlado

Objetivo:
Aplicar todos os gates e decidir formalmente se o MVP pode ir ao piloto.

Acoes:
- Executar checklist de deploy, seguranca, OMR, dispositivos, suporte e treinamento.
- Registrar aprovacao ou bloqueios com responsaveis e prazos.

Arquivos envolvidos:
- `docs/piloto/checklist-liberacao.md` (novo)
- `docs/piloto/plano-suporte.md` (novo)
- `docs/piloto/relatorio-homologacao.md` (novo)
- `README.md`

Critérios de aceite:
- Todos os gates obrigatorios possuem evidencia.
- Bloqueio critico impede liberacao e tem plano de correcao.

Verificação:
```bash
rg "PENDENTE|BLOQUEADO|NAO APROVADO" docs/piloto
cd backend && php artisan test
cd mobile && flutter test
cd omr && python -m pytest
```

Dependências:
- MP-062.
- Todos os gates anteriores.

Não fazer nesta etapa:
- Nao liberar piloto por prazo quando criterio critico falhar.
- Nao habilitar funcionalidades V2 sem validacao propria.

Commit sugerido:
```bash
git add docs/piloto README.md
git commit -m "documentacao: registrar liberacao do piloto controlado"
git push
```

## 7. Proximo micropasso recomendado

O proximo micropasso para execucao imediata e o **MP-001 - Registrar decisoes bloqueadoras do MVP**.

Ele deve ocorrer antes de migrations ou regras de negocio porque define escolhas que afetam diretamente unicidade, pontuacao, retencao, cartao, OMR e autorizacao.

## 8. Comandos Git sugeridos para este documento

Os comandos abaixo sao somente sugestoes e nao devem ser executados automaticamente:

```powershell
git add docs/12-plano-executavel-codex.md
git commit -m "documentacao: adiciona plano executavel do projeto"
git push
```
