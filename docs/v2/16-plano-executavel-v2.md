# Plano Executável da Reconstrução Gabarito360 V2

## 1. Objetivo e ordem

Este plano substitui os planos de execução anteriores para a V2. A reconstrução
é integral e sem legado (ADR-D016): o mockup é o contrato de produto e nenhum
artefato V1 é reaproveitado ou mantido.

O número mínimo responsável é **10 etapas**, incluindo esta preparação. Menos
etapas misturaria mudanças de produto, dados, web, Android e OMR em entregas
difíceis de validar ou reverter.

## 2. Estratégia de agentes

Não são necessários agentes permanentes. Durante etapas independentes podem ser
usados, no máximo, três especialistas:

| Especialidade | Uso |
|---|---|
| Produto/Web | inventário, paridade visual, responsividade e acessibilidade |
| Domínio/API/Dados | migrations, regras, policies, serviços e contratos |
| Mobile/OMR/QA | React Native, câmera, OpenCV, dataset e testes ponta a ponta |

O agente principal integra decisões e impede alterações concorrentes nas mesmas
migrations, rotas, contratos ou fundações visuais.

## V2-00 - Canonizar produto e congelar a V1

**Objetivo:** estabelecer a direção correta e congelar o legado para remoção.

**Ações:**
- criar a branch `v2/mockup-canonico`;
- declarar `style-system/` como contrato integral;
- criar documentação V2 e o plano de reconstrução/remoção (sem legado, ADR-D016);
- arquivar R0-R7 e documentos V1 como histórico (não governam a V2).

**Arquivos:** `AGENTS.md`, `README.md`, `docs/v2/*`,
`docs/decisoes/ADR-D014-v2-mockup-canonico.md`,
`docs/decisoes/ADR-D016-v2-sem-legado.md`.

**Aceite:** todas as 30 telas e capacidades aparecem no inventário; o plano de
reconstrução/remoção está explícito; o legado está marcado para remoção.

**Verificação:**
```powershell
git status --short
Get-ChildItem docs/v2 -File
Select-String -Path docs/v2/*.md -Pattern "style-system"
```

**Não fazer:** refatorar páginas, migrations, API, Flutter ou OMR.

**Commit sugerido:** `documentacao: canonizar produto e plano da v2`

## V2-01 - Produzir mapa executável tela por tela

**Depende de:** V2-00.

**Objetivo:** decompor cada tela e controle do mockup em contrato verificável.

**Ações:**
- mapear componentes, controles, rotas, atores, dados, regras e estados;
- mapear variantes e união de capacidades;
- criar baseline de screenshots nos nove viewports;
- registrar lacunas exatas da implementação atual.

**Arquivos prováveis:** `docs/v2/15-matriz-rastreabilidade.md`,
`docs/v2/telas/*.md`, `tests/visual/*`.

**Aceite:** cada controle visível possui destino de implementação e teste.

**Verificação:**
```powershell
Get-ChildItem style-system -Filter *.html | Measure-Object
Get-ChildItem docs/v2/telas -Filter *.md | Measure-Object
```

**Não fazer:** alterar domínio ou páginas de produção.

**Commit sugerido:** `documentacao: mapear telas e controles da v2`

## V2-02 - Fechar modelo de dados e contratos API V2

**Depende de:** V2-01.

**Objetivo:** sustentar integralmente o mockup com dados reais.

**Ações:**
- modelar o esquema MariaDB **único V2** do zero, cobrindo todas as capacidades;
- recriar a base com `migrate:fresh --seed` (sem migração de dados V1);
- modelar modelos, factories, seeders, policies e OpenAPI `/api/v2`;
- remover qualquer rota/contrato `/api/v1` (sem compatibilidade — ADR-D016).

Detalhamento por passos em [`21-plano-backend.md`](21-plano-backend.md).

**Arquivos prováveis:** `backend/database/migrations/*`,
`backend/app/Models/*`, `backend/app/Policies/*`, `docs/openapi-v2.yaml`.

**Aceite:** nenhuma tela depende de dado sem fonte; migrations e contratos têm testes.

**Verificação:**
```powershell
cd backend
php artisan migrate:fresh --seed
php artisan test
composer validate --strict
```

**Não fazer:** compor telas finais ou integrar câmera.

**Commit sugerido:** `dominio: ampliar dados e contratos para a v2`

## V2-03 - Reconstruir fundação visual fiel ao mockup

**Depende de:** V2-01; pode avançar em paralelo com V2-02 sem dados finais.

**Objetivo:** produzir shell e componentes com paridade visual reutilizável.

**Ações:**
- extrair e congelar tokens do mockup;
- reconstruir shell, govbar, header, navegação e conta;
- implementar todos os componentes e estados compartilhados;
- configurar testes visuais, responsivos e WCAG.

**Arquivos prováveis:** `backend/resources/css/*`,
`backend/resources/js/*`, `backend/resources/views/components/*`,
`backend/resources/views/layouts/*`.

**Aceite:** catálogo reproduz o mockup nos nove viewports; claro é padrão.

**Verificação:**
```powershell
cd backend
npm.cmd run build
php artisan test --filter=Design
```

**Não fazer:** preencher telas com números estáticos.

**Commit sugerido:** `design: reconstruir fundacao visual fiel ao mockup`

## V2-04 - Entregar acesso, conta e configurações integrais

**Depende de:** V2-02 e V2-03.

**Objetivo:** concluir `login.html`, `perfil.html` e `configuracoes.html`.

**Ações:**
- implementar login, manter conectado, recuperação e onboarding controlado;
- implementar perfil, senha, notificações, sessões e histórico;
- implementar aparência, acessibilidade, região, importação/exportação,
  plano/uso, integrações, privacidade e zona de perigo.

**Aceite:** todos os controles das três telas possuem comportamento real e seguro.

**Verificação:**
```powershell
cd backend
php artisan test --filter=Account
npm.cmd run build
```

**Não fazer:** simular integração conectada ou excluir dados diretamente.

**Commit sugerido:** `feat: entregar acesso conta e configuracoes v2`

## V2-05 - Entregar gestão organizacional e acadêmica

**Depende de:** V2-02, V2-03 e V2-04.

**Objetivo:** concluir escolas, equipe, perfis, turmas e alunos.

**Ações:**
- implementar fielmente listas, detalhes, formulários, filtros e modais;
- concluir permissões, dados profissionais, importações, responsáveis,
  histórico, ficha PDF e indicadores reais;
- validar todos os escopos e ações destrutivas controladas.

**Telas:** `escolas.html`, `escola-detalhe.html`, `perfis-equipe.html`,
`membro-*.html`, `turmas.html`, `turma-detalhe-2.html`, `aluno-*.html`.

**Aceite:** jornadas completas funcionam responsivamente e com dados reais.

**Verificação:**
```powershell
cd backend
php artisan test --filter=Organization
php artisan test --filter=Academic
npm.cmd run build
```

**Não fazer:** iniciar OMR ou usar métricas fictícias.

**Commit sugerido:** `feat: entregar gestao organizacional e academica v2`

## V2-06 - Entregar provas, aplicações, correção e relatórios web

**Depende de:** V2-05.

**Objetivo:** concluir o fluxo web da prova ao relatório.

**Ações:**
- implementar provas, padrões, editor de gabarito e exportações;
- implementar aplicações e acompanhamento geral/por turma;
- implementar resultados, relatórios, PDFs/CSV/XLSX previstos;
- ligar dashboards por ator, agenda e tempo real a dados reais.

**Telas:** `provas.html`, `criar-prova.html`, `gabarito.html`,
`acompanhar-correcao*.html`, `resultado*.html`, `relatorio*.html`,
`dashboard*.html`.

**Aceite:** todas as telas web do mockup têm paridade funcional e visual.

**Verificação:**
```powershell
cd backend
php artisan test
npm.cmd run build
```

**Não fazer:** aprovar piloto sem Android e OMR reais.

**Commit sugerido:** `feat: concluir fluxo web integral da v2`

## V2-07 - Concluir aplicativo Android

**Depende de:** contratos V2-02 e fluxo operacional V2-06.

**Objetivo:** construir o aplicativo React Native operacional completo (ADR-D015),
substituindo a base Flutter legada.

**Ações:**
- inicializar projeto React Native (TypeScript) organizado por features;
- portar tokens do mockup e tema claro padrão;
- implementar câmera, guia, qualidade, revisão, confirmação e histórico;
- implementar fila offline, sincronização e conflitos;
- validar tema, acessibilidade e dispositivos homologados.

**Aceite:** aplicador conclui fluxo real online e recupera falhas/offline.

**Verificação:**
```powershell
cd mobile
npm install
npm run lint
npx tsc --noEmit
npm test
cd android; ./gradlew assembleRelease
```

**Não fazer:** confirmar leitura ambígua automaticamente.

**Commit sugerido:** `feat: concluir aplicativo android v2`

## V2-08 - Implementar e homologar OMR real

**Depende de:** V2-07 e modelo físico aprovado.

**Objetivo:** substituir pré-homologação por pipeline OpenCV mensurado.

**Ações:**
- coletar/rotular dataset real;
- implementar pipeline e métricas;
- integrar Android/backend;
- calibrar e validar em dispositivos/cartões reais.

**Aceite:** metas aprovadas no conjunto de teste e revisão humana acionada corretamente.

**Verificação:**
```powershell
python -m pytest omr/tests -q
python -m omr.process --image <cartao-real> --config <modelo-homologado>
```

**Não fazer:** calibrar usando conjunto de teste ou ocultar falhas.

**Commit sugerido:** `feat: homologar pipeline omr da v2`

## V2-09 - Homologar produto integral e preparar lançamento

**Depende de:** V2-04 a V2-08.

**Objetivo:** provar que a V2 corresponde ao mockup e funciona ponta a ponta.

**Ações:**
- executar matriz visual/funcional completa;
- testar acessibilidade, segurança, LGPD, carga, backup e restauração;
- executar piloto controlado com dados autorizados;
- documentar divergências, operação, deploy e rollback.

**Aceite:** 30 telas homologadas, fluxo Android/OMR aprovado, CI verde e aceite humano.

**Verificação:**
```powershell
cd backend
php artisan test
npm.cmd run build
cd ../mobile
npm run lint
npm test
cd ..
python -m pytest omr/tests -q
docker compose --env-file .env.docker config --quiet
```

**Não fazer:** merge/deploy de produção sem aceite formal.

**Commit sugerido:** `release: preparar homologacao integral da v2`

## 3. Próxima execução recomendada

Executar **V2-01 - Produzir mapa executável tela por tela**. Essa etapa evita
uma nova interpretação parcial do mockup e cria o contrato objetivo para todas
as implementações seguintes.
