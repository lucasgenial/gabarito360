# Plano de Refatoração pelo Mockup e MariaDB

## 1. Estado e objetivo

Em 12 de junho de 2026, a execução do plano anterior foi suspensa após a MP-028.
A branch de trabalho é `refatoracao-mockup-mariadb`.

Objetivo: alinhar produto, documentação, banco, backend e painel web ao mockup
funcional em `style-system/`, mantendo o que já está tecnicamente sólido e
substituindo as fundações que criariam retrabalho.

## 2. Diagnóstico consolidado

### O que será preservado

- Laravel 12 e organização em requests, resources, policies, services e actions.
- Autenticação web/API, Sanctum, auditoria e escopos de autorização.
- Entidades e regras já úteis de núcleos, escolas, usuários, turmas, alunos,
  modelos de cartão, provas, questões e gabaritos.
- Testes de domínio e autorização independentes do PostgreSQL.
- Documentação de OMR, LGPD e segurança que não conflite com a nova direção.

### O que será refatorado

- Migrations e testes dependentes de PostgreSQL.
- Configuração local, CI e documentação de banco.
- Shell visual atual, que ativa escuro pelo sistema operacional.
- Rotas e telas web para corresponder ao mockup.
- Perfis/cargos, modelo acadêmico e dados necessários às telas.
- Plano executável posterior à MP-028.

### O que não será feito

- Reescrever o backend inteiro sem aproveitar contratos e testes válidos.
- Copiar HTML/CSS/JS do protótipo diretamente para produção.
- Iniciar aplicações/OMR antes da nova fundação passar nos gates.
- Implementar auto-cadastro, exclusão permanente ou integrações apenas porque
  aparecem visualmente no protótipo.

## 3. Decisões técnicas

### Banco

- Banco alvo: MariaDB.
- Desenvolvimento imediato: instância local portátil, isolada em `.local/`.
- Produção futura: MariaDB em container separado.
- UUIDs: gerados pela aplicação Laravel, sem função específica do banco.
- JSON: coluna `json`, com validação também na aplicação.
- Datas: armazenadas em UTC; timezone convertido na borda.
- Regras simples: FKs, índices, uniques e checks compatíveis.
- Regras entre múltiplas entidades: services transacionais, locks e testes.
- Unicidades condicionais: modeladas explicitamente, sem índices parciais.

### Web

- Blade e Livewire continuam como estratégia do painel.
- `style-system/` define telas, composição e navegação.
- Componentes compartilhados substituem CSS/JS inline.
- Tema claro é padrão; escuro depende de escolha explícita.
- Dashboards são selecionados por contexto e permissões, não por HTML separado
  duplicado em produção.

## 4. Número mínimo de etapas

O mínimo responsável é **oito etapas contando a etapa R0 já executada, com sete
etapas restantes**. Reduzir além disso misturaria mudança de banco, domínio e
interface no mesmo gate, dificultando diagnóstico e rollback.

## R0 - Congelar e registrar a nova direção

Objetivo: impedir novas implementações sobre premissas antigas.

Ações:

- parar ambiente atual;
- criar branch de refatoração;
- versionar o mockup como referência;
- registrar ADR-D012, matriz funcional e este plano;
- marcar planos PostgreSQL anteriores como suspensos.

Aceite:

- nenhuma execução da MP-029;
- fontes oficiais e precedência documentadas;
- branch publicada sem apagar histórico.

Não fazer:

- migrations MariaDB;
- telas de produção;
- alterações de regra de negócio.

## R1 - Canonizar produto, rotas e modelo de dados

**Status: concluída em 12 de junho de 2026.**

Objetivo: fechar o contrato antes de refatorar código.

Ações:

- validar a matriz das 30 telas;
- decidir itens pendentes: auto-cadastro, gov.br, aluno autenticado, agenda,
  integrações e plano;
- definir mapa canônico de rotas web;
- separar cargo institucional de perfil de autorização;
- produzir modelagem MariaDB completa;
- atualizar requisitos, regras, casos de uso, API e roadmap.

Aceite:

- cada tela canônica possui rota, ator, permissão, dados e estado;
- modelagem MariaDB não depende de recurso PostgreSQL;
- duplicidades do protótipo possuem decisão.

Entregáveis:

- `docs/decisoes/ADR-D013-contrato-produto-web-r1.md`;
- `docs/15-mapa-rotas-web.md`;
- `docs/06-modelagem-banco.md` canônico para MariaDB;
- requisitos, regras, casos de uso, API, dashboards e matriz funcional alinhados.

Não fazer:

- migrations;
- frontend;
- novos endpoints.

## R2 - Subir fundação MariaDB e restaurar o gate técnico

**Status: concluída em 12 de junho de 2026.**

Objetivo: tornar backend e testes reproduzíveis com MariaDB.

Ações:

- criar scripts portáteis de setup/start/stop;
- configurar conexões `mysql` e `mysql_testing`;
- refazer baseline das migrations para MariaDB;
- substituir PL/pgSQL por constraints portáveis e services transacionais;
- adaptar factories, seeders, testes de infraestrutura e CI;
- remover dependência operacional do PostgreSQL após validação.

Aceite:

- `migrate:fresh --seed` funciona em MariaDB vazio;
- suíte completa passa em MariaDB;
- setup local é reproduzível;
- nenhuma migration exige PostgreSQL.

Entregáveis:

- scripts portáteis de setup, start e stop em `scripts/local/`;
- conexões `mariadb` e `mariadb_testing` isoladas;
- baseline de migrations compatível com MariaDB;
- regras entre entidades protegidas por actions transacionais e testes;
- CI executando migrations, seeders e suíte completa em MariaDB 11.4;
- documentação operacional atualizada sem dependência de PostgreSQL.

Não fazer:

- novas telas;
- novos módulos funcionais;
- OMR.

## R3 - Alinhar domínio e contratos ao mockup

**Status: concluída em 12 de junho de 2026.**

Objetivo: fornecer os dados reais necessários às telas canônicas.

Ações:

- implementar disciplinas, períodos letivos, séries/anos e temas/habilidades;
- ampliar escola, aluno, responsável e equipe escolar;
- alinhar cargos, perfis, disciplinas e turmas dos membros;
- ampliar prova, padrões e vínculo com aplicação;
- consolidar contratos para aplicações, leituras, resultados e relatórios;
- atualizar OpenAPI, policies e testes.

Aceite:

- toda informação obrigatória das telas possui fonte de dados autorizada;
- nenhum dashboard depende de número estático;
- contratos e policies cobrem os novos contextos.

Entregáveis:

- contrato executável em `docs/16-contrato-dominio-r3.md`;
- fundação acadêmica, equipe, responsáveis, preferências e LGPD;
- contratos persistentes para aplicações, leituras, resultados e relatórios;
- modelos e relacionamentos Eloquent para as fontes canônicas;
- permissões e policies para configurações, resultados e relatórios;
- dados locais demonstrativos alinhados à estrutura acadêmica;
- testes de schema, relacionamentos, constraints, permissões e escopo.

Não fazer:

- composição final das 30 telas;
- tempo real;
- OMR real.

## R4 - Construir fundação visual canônica

Status: **concluída em 12 de junho de 2026**.

Objetivo: transformar o mockup em biblioteca reutilizável.

Ações:

- congelar tokens reconciliados entre mockup e documentação;
- implementar layout, cabeçalho, navegação responsiva, breadcrumb e menu;
- implementar botão de tema com claro padrão;
- criar componentes de formulário, tabela, card, KPI, gráfico, modal, toast e
  estados;
- criar testes de acessibilidade, responsividade e contrato visual.

Aceite:

- shell funciona em celular, tablet e desktop;
- claro é padrão mesmo com sistema operacional escuro;
- alternância persiste;
- nenhum componente de produção depende de CSS inline do mockup.

Não fazer:

- páginas com dados estáticos;
- regras de negócio dentro dos componentes.

Entregas realizadas:

- tokens oficiais reconciliados e congelados em `backend/resources/css/tokens.css`
  e `docs/design/tokens-web-r4.md`;
- shell autenticado responsivo com sidebar desktop, drawer móvel, cabeçalho,
  breadcrumb e menu de conta;
- tema claro padrão com alternância explícita persistida;
- catálogo Blade ampliado para formulários, navegação, feedback, KPI e gráfico
  com alternativa tabular;
- componentes compartilhados aplicados nas telas administrativas existentes;
- contrato automatizado de acessibilidade, responsividade e tema.

## R5 - Implementar fatias funcionais web

Objetivo: entregar o fluxo web completo em ordem de dependência.

Ordem interna mínima:

1. acesso, perfil, sessões e preferências;
2. dashboards por contexto;
3. escolas, equipe e perfis;
4. turmas, alunos e importação;
5. provas, padrões e gabaritos;
6. aplicações e acompanhamento da correção;
7. resultados e relatórios.

Cada item deve ser uma fatia vertical pequena com migration já aprovada, policy,
request, service/action, rota, tela e testes.

Aceite:

- telas canônicas usam dados reais;
- permissões e escopo são demonstrados por testes;
- fluxos funcionam responsivamente.

Não fazer:

- integrações opcionais não aprovadas;
- duplicar telas apenas por papel quando composição puder ser parametrizada.

## R6 - Integrar OMR, tempo real e qualidade operacional

Objetivo: conectar captura, correção, dashboards e relatórios.

Ações:

- implementar aplicações, leituras e revisão de ambiguidades;
- integrar OMR e app Flutter;
- publicar progresso via Reverb;
- gerar resultados e relatórios;
- executar testes de carga, segurança, LGPD e acessibilidade.

Aceite:

- fluxo real completo da prova ao relatório;
- métricas do dashboard derivam do banco;
- revisão manual é auditada;
- gates OMR e LGPD aprovados.

Não fazer:

- liberar piloto com dados estáticos ou métricas não verificadas.

## R7 - Empacotar, homologar e preparar merge

Status: **implementacao concluida em 13 de junho de 2026; homologacao integrada
e merge bloqueados ate a CI de containers/restauracao passar.**

Objetivo: tornar a nova fundação implantável e integrar a branch.

Ações:

- criar containers separados para aplicação, MariaDB, Redis e serviços;
- validar backup/restauração;
- atualizar documentação operacional;
- executar regressão completa;
- preparar PR e plano de merge.

Aceite:

- ambiente sobe de forma reproduzível;
- CI passa;
- documentação corresponde ao sistema;
- PR contém histórico temático e revisão.

Não fazer:

- merge direto sem homologação;
- expor MariaDB publicamente.

## 5. Estratégia de agentes especializados

Não é necessário criar agentes permanentes. Durante a implementação, recomenda-se
usar no máximo três agentes especializados em paralelo, sempre sob integração do
agente principal:

| Agente | Responsabilidade | Quando usar |
|---|---|---|
| Dados/MariaDB | modelagem, migrations, portabilidade e testes de persistência | R1 a R3 |
| Web/Design System | inventário do mockup, componentes, responsividade e acessibilidade | R1, R4 e R5 |
| QA/Contratos | matriz funcional, OpenAPI, permissões, regressão e documentação | R1 a R7 |

O agente principal deve manter decisões de domínio, revisar conflitos e integrar
commits. Não paralelizar alterações simultâneas nas mesmas migrations, rotas ou
componentes fundamentais.

## 6. Estratégia Git

- Branch vigente: `refatoracao-mockup-mariadb`.
- Um commit por decisão ou fatia vertical.
- Não misturar migração de banco com refatoração visual no mesmo commit.
- Publicar a branch após cada gate concluído.
- Não fazer merge em `main` antes de R7.

## 7. Próxima execução recomendada

R5 foi concluída com rotas canônicas, consultas escopadas, telas responsivas e
testes web. O contrato da entrega está em `docs/17-contrato-r5-fatias-web.md`.

R6 possui implementacao tecnica e contrato em
`docs/18-contrato-r6-integracao-operacional.md`. O fluxo automatizado foi
validado, mas a homologacao fisica do OMR e dos dispositivos continua
bloqueante. Nao iniciar piloto antes desses gates.

Proxima etapa recomendada: **R7 - Empacotar, homologar e preparar merge**,
preservando o bloqueio formal do piloto.
