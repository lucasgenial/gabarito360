> **Roadmap suspenso para reorientação.** O plano abaixo permanece como histórico
> do trabalho realizado até a MP-028, mas não deve orientar novas implementações
> até a conclusão do plano em `docs/13-plano-refatoracao-mockup-mariadb.md`.

# Gabarito360 - Plano Tecnico do MVP

## 1. Objetivo

Este documento define a ordem tecnica recomendada para entregar o MVP do Gabarito360. O objetivo do MVP e executar uma aplicacao real controlada de ponta a ponta:

1. Preparar nucleo, escola, usuarios, turma, alunos, prova e gabarito.
2. Criar e iniciar uma aplicacao.
3. Fotografar um cartao-resposta no app Android.
4. Processar e revisar as respostas detectadas.
5. Confirmar o vinculo entre cartao e aluno.
6. Corrigir a prova e persistir o resultado.
7. Acompanhar o progresso da aplicacao.
8. Exportar um relatorio basico por turma.

O plano detalha tarefas pequenas, dependencias, criterios de aceite e gates de conclusao. Nenhuma etapa deste documento representa implementacao concluida.

## 2. Escopo do MVP

### 2.1 Incluido

- Um nucleo com uma ou mais escolas.
- Perfis operacionais essenciais: administrador, gestor do nucleo, responsavel da escola, professor e aplicador.
- Matriz de acesso definida para todos os perfis, incluindo Leitor/Consulta e Suporte Tecnico.
- Cadastros de escolas, usuarios, turmas e alunos.
- Importacao validada de alunos por CSV.
- Prova objetiva padronizada com 20 questoes e alternativas A-E.
- Uma versao vigente do gabarito oficial.
- Um modelo homologado de cartao-resposta.
- Aplicacao vinculada a uma turma.
- Aplicativo Android online.
- Captura orientada da imagem.
- Leitura OMR, revisao humana e confirmacao.
- Correcao automatica.
- Dashboard simples por aplicacao.
- Relatorio por turma em tela e CSV.
- Auditoria de operacoes criticas.

### 2.2 Fora do MVP

- Modo offline completo e resolucao de conflitos.
- Relatorios PDF e XLSX.
- Dashboards consolidados avancados de nucleo e escola.
- Multiplos modelos configuraveis de cartao em producao.
- Alteracao de gabarito publicado e recorrection em lote.
- Integracoes com sistemas academicos externos.
- Aplicativo iOS.
- Correcao de questoes discursivas.
- Transferencia de alunos entre turmas e justificativa de ausencia.
- Gerenciamento de sessoes ativas e MFA.
- Interface completa de consulta de auditoria e console operacional de suporte.

### 2.3 Regra de corte para liberacao

Somente os itens de **2.1 Incluido** podem bloquear a liberacao do MVP. Funcionalidades marcadas como V2, V3 ou listadas em **2.2 Fora do MVP** nao devem ser adicionadas ao gate, mesmo quando a arquitetura preparar sua evolucao.

A matriz aprovada de perfis, acoes e escopos esta em [05-casos-de-uso.md](05-casos-de-uso.md). O corte e as regras de autorizacao foram registrados no [ADR-D011](decisoes/ADR-D011-escopo-e-permissoes-mvp.md).

## 3. Estrategia de desenvolvimento

### 3.1 Principios

- Trabalhar em tarefas pequenas e demonstraveis.
- Finalizar cada fase com um gate objetivo.
- Criar contratos antes de integrar clientes.
- Implementar autorizacao e auditoria junto da funcionalidade, nao depois.
- Automatizar testes de regras criticas desde o inicio.
- Medir a qualidade do OMR com dataset rotulado.
- Publicar eventos somente depois do commit da transacao.
- Preservar historico de leituras e resultados.

### 3.2 Estrategia de reducao de risco

O OMR e o maior risco tecnico. Por isso, a Fase 1 inclui um spike curto de viabilidade usando o cartao real, antes da implementacao completa do modulo na Fase 8.

O app Android pode avancar antes do OMR final usando respostas simuladas que respeitem o contrato definido. A integracao real ocorre somente depois que o pipeline OMR atingir o gate de qualidade.

### 3.3 Ordem macro recomendada

```text
Fase 1  Preparacao do projeto
  |
  v
Fase 2  Backend Laravel
  |
  v
Fase 3  Autenticacao e permissoes
  |
  v
Fase 4  Cadastros principais
  |
  v
Fase 5  Provas e gabaritos
  |
  v
Fase 6  Aplicacoes
  |
  +-----------> Fase 7 App Android com contrato simulado
  |                         |
  +-----------> Fase 8 Leitura OMR
                            |
                            v
                  Integracao mobile + OMR + backend
                            |
                +-----------+-----------+
                v                       v
        Fase 9 Dashboard       Fase 10 Relatorio
```

As fases 7 e 8 podem avançar parcialmente em paralelo depois que os contratos da Fase 6 estiverem estabilizados. As fases 9 e 10 dependem da confirmacao e correcao funcionando de ponta a ponta.

## 4. Decisoes obrigatorias antes do desenvolvimento

As decisoes bloqueadoras foram registradas para o MVP em 10 de junho de 2026. Decisoes vigentes devem ser revalidadas nos gates indicados e decisoes substituidas permanecem como historico:

| ID | Decisao | Responsaveis | Status | Registro |
|---|---|---|---|---|
| D001 | Modelo fisico do cartao inicial | Produto + OMR | Aceita | [ADR-D001](decisoes/ADR-D001-modelo-fisico-cartao.md) |
| D002 | Unicidade da matricula por escola | Produto + Backend | Aceita | [ADR-D002](decisoes/ADR-D002-unicidade-matricula.md) |
| D003 | Formato unico para o codigo do cartao | Produto + OMR + Backend | Substituida | [ADR-D003](decisoes/ADR-D003-codigo-cartao.md) |
| D004 | Politica de questao anulada | Produto + Pedagogico | Aceita | [ADR-D004](decisoes/ADR-D004-questao-anulada.md) |
| D005 | Motivo obrigatorio em correcao manual | Produto + Auditoria | Aceita | [ADR-D005](decisoes/ADR-D005-motivo-correcao-manual.md) |
| D006 | Retencao de imagens, exportacoes e logs | Seguranca + Produto | Aceita | [ADR-D006](decisoes/ADR-D006-retencao-imagens-logs.md) |
| D007 | Homologacao de dispositivos Android | Produto + Mobile + QA | Aceita | [ADR-D007](decisoes/ADR-D007-dispositivos-android.md) |
| D008 | Metas de qualidade OMR para o piloto | Produto + OMR + QA | Aceita | [ADR-D008](decisoes/ADR-D008-metas-qualidade-omr.md) |
| D009 | Painel web com Blade, Livewire e Tailwind | Arquitetura + Produto | Aceita | [ADR-D009](decisoes/ADR-D009-painel-web.md) |
| D010 | Separar codigo impresso externo e codigo do sistema | Produto + OMR + Mobile + Backend | Aceita | [ADR-D010](decisoes/ADR-D010-identificacao-cartao.md) |
| D011 | Corte de escopo e matriz de permissoes do MVP | Produto + Arquitetura + Seguranca | Aceita | [ADR-D011](decisoes/ADR-D011-escopo-e-permissoes-mvp.md) |

O indice com prazos de revalidacao esta em [decisoes/README.md](decisoes/README.md).

## 5. Fase 1 - Preparacao do projeto

### 5.1 Objetivo

Remover ambiguidades, estabelecer contratos tecnicos e preparar um ambiente reproduzivel antes da implementacao funcional.

### 5.2 Dependencias

Nenhuma.

### 5.3 Tarefas em ordem recomendada

| Ordem | ID | Tarefa pequena | Entregavel |
|---:|---|---|---|
| 1 | F1-T01 | Revalidar o escopo aprovado do MVP no inicio de cada release | Lista de funcionalidades dentro e fora do MVP |
| 2 | F1-T02 | Revalidar as decisoes `D001-D011` nos gates aplicaveis | Registro de decisoes tecnicas atualizado |
| 3 | F1-T03 | Revalidar a matriz aprovada de perfis e permissoes do MVP | Matriz por acao e escopo |
| 4 | F1-T04 | Revisar o modelo relacional e fechar decisoes bloqueadoras | Modelagem pronta para migrations |
| 5 | F1-T05 | Revisar os endpoints necessarios ao fluxo do MVP | Contrato REST priorizado |
| 6 | F1-T06 | Definir padroes de branch, commits, revisao e releases | Guia de contribuicao interno |
| 7 | F1-T07 | Definir ambientes `local`, `test`, `homologacao` e `producao` | Matriz de ambientes e configuracoes |
| 8 | F1-T08 | Definir estrategia de segredos, backups e armazenamento de arquivos | Decisao de infraestrutura |
| 9 | F1-T09 | Criar plano de testes por camada | Matriz de testes e responsabilidades |
| 10 | F1-T10 | Obter cartoes impressos reais e criar dataset OMR inicial | Dataset rotulado inicial |
| 11 | F1-T11 | Executar spike OMR com perspectiva e leitura basica | Relatorio de viabilidade e riscos |
| 12 | F1-T12 | Definir metricas do piloto | Metas de tempo, qualidade e erros |
| 13 | F1-T13 | Priorizar backlog das fases seguintes | Backlog ordenado e estimavel |

### 5.4 Criterios de aceite

- Escopo do MVP aprovado sem funcionalidades ambiguas.
- Modelo fisico do cartao inicial disponivel e versionado.
- Regras de unicidade, pontuacao e retencao definidas.
- Matriz de permissoes aprovada.
- Contrato inicial da API alinhado com web e mobile.
- Dataset OMR contem exemplos rotulados de marcacao, branco, dupla e imagem inadequada.
- Spike OMR confirma viabilidade ou registra mudancas necessarias no cartao.
- Metas mensuraveis do piloto foram definidas.

### 5.5 Gate da fase

Nenhuma implementacao funcional deve iniciar sem modelo de cartao, regras bloqueadoras e matriz de permissoes aprovados.

## 6. Fase 2 - Backend Laravel

### 6.1 Objetivo

Criar a fundacao tecnica do backend, banco, filas, arquivos, testes e observabilidade.

### 6.2 Dependencias

- Gate da Fase 1 aprovado.
- Decisoes de arquitetura, infraestrutura e modelagem fechadas.

### 6.3 Tarefas em ordem recomendada

| Ordem | ID | Tarefa pequena | Entregavel |
|---:|---|---|---|
| 1 | F2-T01 | Criar o projeto Laravel 12 | Aplicacao backend inicial |
| 2 | F2-T02 | Definir estrutura modular e convencoes do backend | Organizacao documentada do codigo |
| 3 | F2-T03 | Configurar Docker para aplicacao, PostgreSQL e Redis | Ambiente local reproduzivel |
| 4 | F2-T04 | Configurar variaveis por ambiente e exemplos sem segredos | Arquivo de exemplo seguro |
| 5 | F2-T05 | Configurar conexao PostgreSQL e extensoes aprovadas | Banco acessivel pela aplicacao |
| 6 | F2-T06 | Criar migrations na ordem definida pela modelagem | Schema inicial versionado |
| 7 | F2-T07 | Criar factories e seeders minimos para testes | Dados de teste reproduziveis |
| 8 | F2-T08 | Configurar Redis, filas e worker | Tarefa assincrona de prova |
| 9 | F2-T09 | Configurar storage local/S3 compativel | Upload e leitura controlados |
| 10 | F2-T10 | Padronizar respostas JSON e tratamento de erros | Envelope da API |
| 11 | F2-T11 | Adicionar `request_id` e logs estruturados | Correlacao de requisicoes |
| 12 | F2-T12 | Criar base do registro de auditoria | Servico de auditoria reutilizavel |
| 13 | F2-T13 | Configurar testes automatizados e banco isolado | Suite executavel localmente |
| 14 | F2-T14 | Configurar verificacoes de qualidade no pipeline | Pipeline inicial de CI |
| 15 | F2-T15 | Documentar inicializacao e comandos do projeto | Guia tecnico do backend |

### 6.4 Criterios de aceite

- Um desenvolvedor inicia backend, PostgreSQL e Redis com procedimento documentado.
- Migrations sobem em banco vazio e podem ser revertidas conforme estrategia aprovada.
- Testes executam usando ambiente isolado.
- API retorna formato padronizado de sucesso e erro.
- Logs possuem `request_id` sem registrar segredos.
- Fila processa uma tarefa de teste sem bloquear a requisicao.
- Upload de arquivo de teste respeita tipo e limite configurados.
- Pipeline executa verificacoes e testes iniciais.

### 6.5 Gate da fase

O backend esta reproduzivel, testavel e pronto para receber funcionalidades sem configuracoes manuais ocultas.

## 7. Fase 3 - Autenticacao e permissoes

### 7.1 Objetivo

Garantir que usuarios autenticados acessem somente acoes e dados autorizados por perfil e escopo.

### 7.2 Dependencias

- Backend e banco funcionais.
- Matriz de permissoes aprovada.

### 7.3 Tarefas em ordem recomendada

| Ordem | ID | Tarefa pequena | Entregavel |
|---:|---|---|---|
| 1 | F3-T01 | Criar seed dos perfis e permissoes do MVP | Catalogo inicial de acesso |
| 2 | F3-T02 | Implementar atribuicao de perfil por escopo | Vinculos global, nucleo e escola |
| 3 | F3-T03 | Implementar login web/API | Sessao ou token conforme cliente |
| 4 | F3-T04 | Implementar autenticacao por token para o app | Token revogavel por dispositivo |
| 5 | F3-T05 | Implementar logout e revogacao | Sessao/token invalidado |
| 6 | F3-T06 | Implementar endpoint `/me` | Usuario, perfis, escopos e permissoes |
| 7 | F3-T07 | Criar policies para nucleo e escola | Restricao organizacional |
| 8 | F3-T08 | Criar policy base para turma e aplicacao | Restricao operacional |
| 9 | F3-T09 | Implementar bloqueio de usuario inativo | Acesso negado e sessoes revogadas |
| 10 | F3-T10 | Aplicar rate limit no login | Protecao contra abuso |
| 11 | F3-T11 | Auditar login, logout e alteracoes de acesso | Eventos de seguranca |
| 12 | F3-T12 | Criar testes de acesso vertical e horizontal | Matriz de testes de autorizacao |

### 7.4 Criterios de aceite

- Usuario ativo autentica e recebe somente seus escopos.
- Credenciais invalidas nao revelam qual campo esta incorreto.
- Usuario inativo nao autentica e perde sessoes existentes.
- Gestor do nucleo nao acessa outro nucleo.
- Responsavel escolar nao acessa outra escola.
- Professor/aplicador nao acessa turma sem vinculo.
- Perfil de consulta nao altera recursos.
- Rotas protegidas retornam `401` ou `403` corretamente.
- Eventos de acesso e mudanca de perfil sao auditados.
- Testes cobrem todos os cruzamentos criticos de perfil e escopo.

### 7.5 Gate da fase

Nenhum cadastro de negocio deve ser liberado antes dos testes de isolamento entre escolas e nucleos passarem.

## 8. Fase 4 - Cadastros principais

### 8.1 Objetivo

Permitir preparar a estrutura organizacional e academica usada pelas provas e aplicacoes.

### 8.2 Dependencias

- Autenticacao e autorizacao aprovadas.
- Migrations das entidades principais disponiveis.

### 8.3 Tarefas em ordem recomendada

| Ordem | ID | Tarefa pequena | Entregavel |
|---:|---|---|---|
| 1 | F4-T01 | Implementar CRUD e inativacao de nucleos | Gestao de nucleos |
| 2 | F4-T02 | Implementar CRUD e inativacao de escolas | Gestao de escolas |
| 3 | F4-T03 | Implementar CRUD e inativacao de usuarios | Gestao de usuarios |
| 4 | F4-T04 | Implementar atribuicao de perfis no escopo | Gestao de acesso |
| 5 | F4-T05 | Implementar CRUD e inativacao de turmas | Gestao de turmas |
| 6 | F4-T06 | Implementar CRUD e inativacao de alunos | Gestao de alunos |
| 7 | F4-T07 | Implementar matricula do aluno na turma | Historico aluno-turma |
| 8 | F4-T08 | Implementar vinculo de professor/aplicador a turma | Autorizacao operacional |
| 9 | F4-T09 | Definir template CSV de alunos | Modelo publico de importacao |
| 10 | F4-T10 | Implementar upload e validacao previa do CSV | Relatorio de erros por linha |
| 11 | F4-T11 | Implementar confirmacao transacional da importacao | Importacao sem duplicidade |
| 12 | F4-T12 | Criar telas web minimas dos cadastros | Fluxo administrativo utilizavel |
| 13 | F4-T13 | Auditar inclusoes, alteracoes e inativacoes criticas | Rastreabilidade |
| 14 | F4-T14 | Criar testes de validacao, escopo e importacao | Suite da fase |

### 8.4 Criterios de aceite

- Administrador cria e inativa nucleo.
- Gestor cria escola somente dentro do proprio nucleo.
- Responsavel escolar gerencia somente usuarios, turmas e alunos da propria escola.
- Codigo da turma nao duplica no mesmo ano letivo e escola.
- Matricula do aluno respeita o escopo de unicidade aprovado.
- Transferencias ou inativacoes preservam historico.
- Aplicador vinculado enxerga a turma; aplicador nao vinculado nao enxerga.
- CSV invalido apresenta erros por linha antes da confirmacao.
- Importacao confirmada nao cria duplicidades silenciosas.
- Operacoes criticas sao auditadas.

### 8.5 Gate da fase

Um gestor autorizado consegue preparar escola, usuarios, turma e alunos sem intervencao tecnica.

## 9. Fase 5 - Provas e gabaritos

### 9.1 Objetivo

Permitir criar e publicar uma prova objetiva valida, associada ao modelo inicial de cartao e a um gabarito oficial completo.

### 9.2 Dependencias

- Cadastros principais aprovados.
- Modelo fisico de cartao homologado para o MVP.
- Politica de pontuacao definida.

### 9.3 Tarefas em ordem recomendada

| Ordem | ID | Tarefa pequena | Entregavel |
|---:|---|---|---|
| 1 | F5-T01 | Cadastrar a versao homologada do modelo de cartao | Modelo OMR versionado |
| 2 | F5-T02 | Implementar criacao de prova em rascunho | Prova editavel |
| 3 | F5-T03 | Implementar edicao e consulta de prova em rascunho | Gestao da prova |
| 4 | F5-T04 | Gerar ou cadastrar as 20 questoes A-E | Questoes vinculadas |
| 5 | F5-T05 | Implementar criacao da versao de gabarito | Cabecalho versionado |
| 6 | F5-T06 | Implementar preenchimento de resposta por questao | Respostas oficiais |
| 7 | F5-T07 | Validar completude e alternativas permitidas | Validador de gabarito |
| 8 | F5-T08 | Implementar publicacao transacional da prova e gabarito | Prova publicada |
| 9 | F5-T09 | Bloquear edicao direta apos publicacao | Integridade do gabarito |
| 10 | F5-T10 | Implementar vinculo da prova a turmas autorizadas | Disponibilidade por turma |
| 11 | F5-T11 | Criar telas web minimas de prova e gabarito | Fluxo administrativo utilizavel |
| 12 | F5-T12 | Auditar criacao, publicacao e vinculos | Trilha de auditoria |
| 13 | F5-T13 | Criar testes de estados, completude e escopo | Suite da fase |

### 9.4 Criterios de aceite

- Gestor autorizado cria prova em rascunho com 20 questoes A-E.
- Prova incompleta nao pode ser publicada.
- Cada questao possui uma resposta oficial valida conforme a politica do MVP.
- Modelo de cartao usado pela prova esta homologado e versionado.
- Publicacao registra versao vigente do gabarito.
- Gabarito publicado nao pode ser alterado diretamente.
- Prova publicada pode ser vinculada somente a turmas autorizadas.
- Escola nao acessa prova fora do proprio escopo, salvo prova do nucleo autorizada.
- Publicacao e vinculos sao auditados.

### 9.5 Gate da fase

Um gestor consegue preparar e publicar uma prova valida, com gabarito completo e turmas vinculadas.

## 10. Fase 6 - Aplicacoes

### 10.1 Objetivo

Controlar a execucao da prova em uma turma e implementar a transacao central de confirmacao e correcao.

### 10.2 Dependencias

- Prova publicada e vinculada a turma.
- Aplicadores vinculados.
- Regras de cartao e resultado aprovadas.

### 10.3 Tarefas em ordem recomendada

| Ordem | ID | Tarefa pequena | Entregavel |
|---:|---|---|---|
| 1 | F6-T01 | Implementar criacao da aplicacao | Aplicacao em estado `aguardando` |
| 2 | F6-T02 | Criar snapshot de alunos previstos | Registros em `aplicacao_alunos` |
| 3 | F6-T03 | Implementar vinculo de aplicadores especificos | Autorizacao da aplicacao |
| 4 | F6-T04 | Implementar consulta da aplicacao e alunos | Contexto para web e app |
| 5 | F6-T05 | Implementar inicio da aplicacao | Estado `em_andamento` |
| 6 | F6-T06 | Implementar finalizacao da aplicacao | Estado `finalizada` |
| 7 | F6-T07 | Definir contrato de criacao de leitura preliminar | Endpoint e payload estaveis |
| 8 | F6-T08 | Implementar persistencia de leitura preliminar simulada | Base para app e OMR |
| 9 | F6-T09 | Implementar revisao e respostas finais | Deteccao e resposta final preservadas |
| 10 | F6-T10 | Implementar idempotencia por operacao mobile | Reenvio sem duplicidade |
| 11 | F6-T11 | Implementar confirmacao transacional do cartao | Cartao vinculado ao aluno |
| 12 | F6-T12 | Implementar correcao automatica | Resultado e detalhes por questao |
| 13 | F6-T13 | Atualizar aluno para lido apos commit | Progresso consistente |
| 14 | F6-T14 | Rejeitar aluno, codigo impresso ou codigo do sistema duplicado | Conflitos `409` estaveis e distintos |
| 15 | F6-T15 | Impedir confirmacao em aplicacao finalizada | Regra de estado |
| 16 | F6-T16 | Publicar evento de leitura confirmada apos commit | Evento para dashboard |
| 17 | F6-T17 | Auditar inicio, finalizacao, revisoes e confirmacao | Trilha operacional |
| 18 | F6-T18 | Criar testes concorrentes da confirmacao | Garantia de unicidade |
| 19 | F6-T19 | Criar teste de fluxo completo com leitura simulada | Integracao ponta a ponta do backend |

### 10.4 Criterios de aceite

- Aplicacao so e criada para prova publicada e turma autorizada.
- Snapshot de alunos previstos e reproduzivel e preservado.
- Apenas aplicador vinculado ou gestor autorizado inicia e finaliza.
- Aplicacao finalizada rejeita novas confirmacoes.
- Confirmacao repetida com mesma chave retorna o mesmo resultado.
- Reuso divergente da chave idempotente gera conflito.
- Um aluno nao recebe dois cartoes validos na mesma prova.
- Um codigo impresso nao e vinculado a dois alunos na mesma prova.
- Um codigo do sistema nao e reutilizado em outro cartao quando informado.
- Resposta detectada e resposta final permanecem armazenadas.
- Resultado usa a versao correta do gabarito.
- Progresso e evento sao atualizados somente apos commit.
- Testes concorrentes comprovam que constraints evitam duplicidade.

### 10.5 Gate da fase

O backend executa o fluxo completo usando uma leitura simulada: criar aplicacao, iniciar, confirmar cartao, corrigir, atualizar pendencia e finalizar.

## 11. Fase 7 - App Android

### 11.1 Objetivo

Entregar o aplicativo Android online para o aplicador executar o fluxo da aplicacao com respostas simuladas e, posteriormente, integrar o OMR real.

### 11.2 Dependencias

- Endpoints de autenticacao, aplicacoes, alunos, leitura e confirmacao estabilizados.
- Dispositivos Android homologados definidos.

### 11.3 Tarefas em ordem recomendada

| Ordem | ID | Tarefa pequena | Entregavel |
|---:|---|---|---|
| 1 | F7-T01 | Criar o projeto Flutter e convencoes do app | Base mobile |
| 2 | F7-T02 | Configurar ambientes e cliente HTTP | Integracao segura com API |
| 3 | F7-T03 | Implementar armazenamento seguro do token | Sessao mobile |
| 4 | F7-T04 | Implementar tela e fluxo de login | Usuario autenticado |
| 5 | F7-T05 | Implementar tratamento global de erros e expiracao | Recuperacao previsivel |
| 6 | F7-T06 | Implementar lista de aplicacoes autorizadas | Selecao de aplicacao |
| 7 | F7-T07 | Implementar resumo da aplicacao | Totais e estado |
| 8 | F7-T08 | Implementar lista e busca de alunos | Lidos e pendentes |
| 9 | F7-T09 | Solicitar permissao e integrar camera | Captura de imagem |
| 10 | F7-T10 | Implementar guia visual de enquadramento | Captura orientada |
| 11 | F7-T11 | Implementar tela de processamento | Estado de espera/erro |
| 12 | F7-T12 | Implementar conferencia com resposta simulada | Grade de respostas |
| 13 | F7-T13 | Implementar edicao manual e motivo | Revisao rastreavel |
| 14 | F7-T14 | Implementar confirmacao de aluno, codigo impresso e codigo do sistema quando utilizado | Tela de confirmacao |
| 15 | F7-T15 | Enviar `Idempotency-Key` na confirmacao | Reenvio seguro |
| 16 | F7-T16 | Implementar resultado individual | Retorno da correcao |
| 17 | F7-T17 | Atualizar pendencias apos sucesso | Fluxo repetitivo da turma |
| 18 | F7-T18 | Implementar historico recente da sessao | Consulta operacional |
| 19 | F7-T19 | Criar testes de unidade e widgets | Suite mobile |
| 20 | F7-T20 | Testar o fluxo em aparelhos homologados | Relatorio de compatibilidade |

### 11.4 Criterios de aceite

- Usuario autentica e ve somente aplicacoes autorizadas.
- App apresenta alunos lidos e pendentes corretamente.
- Camera solicita somente permissoes necessarias.
- Aplicador captura ou refaz uma foto.
- Tela de conferencia exibe exatamente 20 questoes.
- Alertas simulados ficam destacados por texto, icone e cor.
- Alteracao manual exige os dados definidos pela politica.
- Confirmacao envia chave idempotente.
- App marca aluno como lido somente apos resposta valida do backend.
- Erros de rede, autorizacao e conflito exibem acao de recuperacao.
- Fluxo online funciona nos dispositivos homologados.

### 11.5 Gate da fase

O aplicador conclui o fluxo online completo usando leitura simulada, sem acesso indevido ou duplicidade.

## 12. Fase 8 - Leitura OMR

### 12.1 Objetivo

Entregar e integrar o pipeline OMR para o modelo inicial de cartao, com qualidade mensurada e revisao humana obrigatoria quando necessario.

### 12.2 Dependencias

- Modelo fisico do cartao homologado.
- Dataset rotulado inicial.
- Contrato de leitura estabilizado.
- Captura mobile funcional.

### 12.3 Tarefas em ordem recomendada

| Ordem | ID | Tarefa pequena | Entregavel |
|---:|---|---|---|
| 1 | F8-T01 | Expandir e revisar o dataset rotulado | Base de calibracao e teste |
| 2 | F8-T02 | Definir formato versionado de `configuracao_omr` | Contrato do modelo |
| 3 | F8-T03 | Implementar validacao de formato, tamanho e resolucao | Entrada segura |
| 4 | F8-T04 | Implementar metricas de nitidez, iluminacao e contraste | Qualidade inicial |
| 5 | F8-T05 | Implementar deteccao dos marcadores | Pontos de referencia |
| 6 | F8-T06 | Implementar correcao de perspectiva | Imagem normalizada |
| 7 | F8-T07 | Implementar recorte das regioes de resposta | Regioes por questao |
| 8 | F8-T08 | Implementar calculo de preenchimento por alternativa | Metricas A-E |
| 9 | F8-T09 | Classificar marcada, branca, dupla, duvidosa e falha | Resposta OMR |
| 10 | F8-T10 | Calcular confianca por questao e geral | Indicadores de revisao |
| 11 | F8-T11 | Implementar leitura do codigo impresso conforme o modelo | Codigo externo e confianca |
| 12 | F8-T12 | Gerar imagem processada para diagnostico quando permitido | Artefato visual |
| 13 | F8-T13 | Persistir metricas e versao do modelo | Rastreabilidade |
| 14 | F8-T14 | Integrar o pipeline ao contrato da API | Leitura preliminar real |
| 15 | F8-T15 | Integrar resposta OMR a tela de conferencia do app | Fluxo real mobile |
| 16 | F8-T16 | Implementar orientacoes de nova captura por falha | Recuperacao do usuario |
| 17 | F8-T17 | Criar suite automatizada de regressao OMR | Resultados reproduziveis |
| 18 | F8-T18 | Medir qualidade por dispositivo homologado | Relatorio de metricas |
| 19 | F8-T19 | Calibrar limiares sem usar o conjunto de teste | Configuracao homologada |
| 20 | F8-T20 | Executar auditoria amostral manual | Verificacao independente |

### 12.4 Criterios de aceite

- Pipeline processa o modelo homologado de 20 questoes A-E.
- Para cada imagem processavel, retorna exatamente uma entrada por questao.
- Detecta perspectiva dentro das tolerancias aprovadas.
- Classifica marcadas, brancas, duplas e duvidosas.
- Retorna confianca por questao e confianca geral.
- Imagens inadequadas geram falha acionavel, sem resposta silenciosamente incorreta.
- Codigo ilegivel pode ser informado manualmente no app.
- Mesma imagem, modelo e configuracao produzem resultado reproduzivel.
- Suite de regressao executa sobre dataset versionado.
- Metas OMR definidas na Fase 1 sao atingidas no conjunto de teste.
- Leituras de baixa confianca exigem revisao explicita no app.

### 12.5 Gate da fase

Uma captura real passa por app, OMR, revisao, confirmacao e correcao, com metricas de qualidade aprovadas.

## 13. Fase 9 - Dashboard simples

### 13.1 Objetivo

Permitir acompanhamento operacional de uma aplicacao em andamento.

### 13.2 Dependencias

- Confirmacao e correcao funcionando ponta a ponta.
- Eventos publicados apos commit.
- Indicadores definidos na documentacao.

### 13.3 Tarefas em ordem recomendada

| Ordem | ID | Tarefa pequena | Entregavel |
|---:|---|---|---|
| 1 | F9-T01 | Definir payload do snapshot da aplicacao | Contrato do dashboard |
| 2 | F9-T02 | Implementar consulta de previstos, lidos e pendentes | Indicadores base |
| 3 | F9-T03 | Implementar consulta de leituras com alerta | Lista operacional |
| 4 | F9-T04 | Configurar Laravel Reverb/WebSockets | Canal de tempo real |
| 5 | F9-T05 | Autorizar assinatura do canal da aplicacao | Isolamento do canal |
| 6 | F9-T06 | Publicar evento de leitura confirmada | Atualizacao incremental |
| 7 | F9-T07 | Publicar evento de aplicacao iniciada/finalizada | Estado em tempo real |
| 8 | F9-T08 | Criar tela web com cards de progresso | Dashboard simples |
| 9 | F9-T09 | Criar lista de pendentes e ultimas leituras | Visao operacional |
| 10 | F9-T10 | Atualizar tela por eventos | Tempo real |
| 11 | F9-T11 | Implementar reconexao e recarga do snapshot | Recuperacao de falha |
| 12 | F9-T12 | Implementar atualizacao periodica de contingencia | Fallback sem WebSocket |
| 13 | F9-T13 | Testar isolamento e consistencia dos totais | Suite do dashboard |
| 14 | F9-T14 | Medir latencia entre confirmacao e atualizacao | Evidencia de desempenho |

### 13.4 Criterios de aceite

- Dashboard mostra previstos, lidos, pendentes, percentual e alertas.
- Totais consideram somente cartoes e resultados vigentes.
- Confirmacao atualiza o dashboard em ate 5 segundos na condicao homologada.
- Usuario nao assina canal fora do proprio escopo.
- Perda de conexao nao deixa a tela permanentemente inconsistente.
- Reconexao recarrega um snapshot confiavel.
- Fallback periodico funciona quando o tempo real esta indisponivel.
- Aplicacao finalizada aparece com estado correto.

### 13.5 Gate da fase

Um gestor acompanha uma aplicacao real e identifica progresso, pendencias e alertas sem atualizar manualmente a pagina.

## 14. Fase 10 - Relatorio por turma

### 14.1 Objetivo

Entregar a consulta final e a exportacao CSV dos resultados vigentes de uma turma.

### 14.2 Dependencias

- Resultados vigentes consistentes.
- Aplicacao e dashboard validados.
- Regras de acesso e auditoria operacionais.

### 14.3 Tarefas em ordem recomendada

| Ordem | ID | Tarefa pequena | Entregavel |
|---:|---|---|---|
| 1 | F10-T01 | Definir colunas e filtros do relatorio MVP | Contrato do relatorio |
| 2 | F10-T02 | Implementar consulta paginada por turma e prova | Dados do relatorio |
| 3 | F10-T03 | Incluir aluno, status, acertos, erros, brancos e nota | Resultado individual |
| 4 | F10-T04 | Calcular totais e media da turma | Resumo da turma |
| 5 | F10-T05 | Incluir alunos pendentes no contexto do relatorio | Completude operacional |
| 6 | F10-T06 | Criar tela web de relatorio por turma | Consulta utilizavel |
| 7 | F10-T07 | Implementar filtros autorizados | Filtros por prova/status |
| 8 | F10-T08 | Implementar exportacao CSV | Arquivo do MVP |
| 9 | F10-T09 | Proteger CSV contra formula injection | Exportacao segura |
| 10 | F10-T10 | Auditar solicitacao e download | Rastreabilidade |
| 11 | F10-T11 | Validar coerencia entre tela, CSV e banco | Teste de consistencia |
| 12 | F10-T12 | Testar escopo e volume esperado do piloto | Suite do relatorio |

### 14.4 Criterios de aceite

- Usuario autorizado consulta o relatorio da turma.
- Usuario fora do escopo recebe acesso negado.
- Relatorio apresenta somente resultados vigentes.
- Totais e media correspondem aos resultados persistidos.
- Alunos pendentes sao identificados corretamente.
- Filtros mantem coerencia entre resumo e linhas.
- CSV abre corretamente e possui colunas aprovadas.
- Valores iniciados por caracteres de formula sao neutralizados.
- Solicitacao e download ficam auditados.

### 14.5 Gate da fase

O gestor encerra uma aplicacao, consulta resultados coerentes e exporta o CSV da turma.

## 15. Validacao integrada do MVP

Depois dos gates das 10 fases, executar uma validacao ponta a ponta antes do piloto.

### 15.1 Cenario minimo

1. Administrador cria nucleo e gestor.
2. Gestor cria escola e responsavel.
3. Responsavel cria aplicador, turma e alunos.
4. Responsavel importa alunos por CSV.
5. Gestor cria e publica prova e gabarito.
6. Gestor vincula prova a turma e cria aplicacao.
7. Aplicador autentica no app e inicia aplicacao.
8. Aplicador fotografa cartoes reais.
9. OMR detecta respostas e alertas.
10. Aplicador revisa e confirma.
11. Backend corrige e atualiza o dashboard.
12. Aplicador finaliza a aplicacao.
13. Gestor consulta e exporta o relatorio por turma.

### 15.2 Cenarios obrigatorios de falha

- Usuario tenta acessar escola fora do escopo.
- Aplicador tenta acessar turma nao vinculada.
- Prova incompleta tenta ser publicada.
- Aplicacao finalizada recebe nova confirmacao.
- Mesmo aluno recebe duas confirmacoes concorrentes.
- Mesmo codigo impresso e usado para alunos diferentes na mesma prova.
- Mesmo codigo do sistema e reutilizado em outro cartao.
- Mesma chave idempotente e reenviada.
- Chave idempotente e reutilizada com payload divergente.
- Foto inadequada e enviada ao OMR.
- Leitura possui branco, dupla e baixa confianca.
- Usuario tenta baixar relatorio fora do escopo.

### 15.3 Criterios de aceite integrado

- Fluxo principal e cenarios de falha passam sem intervencao direta no banco.
- Nenhuma operacao cria duplicidade silenciosa.
- Autorizacao e validada no backend em todos os fluxos.
- Alteracoes manuais, confirmacoes e exportacoes ficam auditadas.
- Dashboard e relatorio consideram somente resultados vigentes.
- Tempos e qualidade atendem as metas definidas para o piloto.
- Erros apresentam codigo estavel e orientacao de recuperacao.

## 16. Estrategia de testes por fase

| Tipo de teste | Aplicacao no MVP |
|---|---|
| Unidade | Regras de negocio, calculo, validadores e classificacao OMR |
| Integracao | Banco, API, storage, filas, auditoria e eventos |
| Autorizacao | Todos os perfis e escopos criticos |
| Contrato | API usada pelo web e app |
| Concorrencia | Confirmacao de cartao e idempotencia |
| Mobile | Unidade, widgets, API simulada e aparelhos reais |
| OMR | Dataset rotulado, regressao e auditoria amostral |
| Ponta a ponta | Fluxo completo da aplicacao |
| Desempenho | Confirmacao, dashboard, importacao e relatorio |
| Operacao | Backup, restauracao, logs e falhas de dependencias |

## 17. Definicao de pronto

Uma tarefa funcional so pode ser considerada pronta quando:

- criterios de aceite foram atendidos;
- validacao ocorre no backend;
- autenticacao e autorizacao foram aplicadas;
- operacoes criticas possuem auditoria;
- estados vazios, erros e conflitos foram tratados;
- testes proporcionais ao risco foram aprovados;
- documentacao afetada foi atualizada;
- logs nao expoem segredos ou dados pessoais desnecessarios;
- alteracoes de banco possuem migration e estrategia de reversao quando viavel;
- revisao de codigo foi concluida;
- funcionalidade foi demonstrada no ambiente de homologacao.

## 18. Criterios para liberar o piloto

- Todos os gates das fases foram aprovados.
- Cartao e configuracao OMR estao homologados.
- Aplicadores receberam orientacao de captura e revisao.
- Dispositivos do piloto foram testados.
- Backup e restauracao foram executados com sucesso.
- Monitoramento basico e alertas operacionais estao ativos.
- Politica de acesso e retencao de imagens foi aprovada.
- Plano de suporte durante o piloto foi definido.
- Auditoria amostral do fluxo apresentou resultado aceitavel.
- Responsaveis tecnico e operacional aprovaram a liberacao.

## 19. Evolucao apos o MVP

Prioridade sugerida depois do piloto:

1. Corrigir problemas identificados no uso real.
2. Implementar modo offline e sincronizacao com conflitos.
3. Adicionar dashboards consolidados de escola e nucleo.
4. Adicionar relatorios PDF e XLSX.
5. Implementar alteracao controlada de gabarito e recorrection.
6. Suportar multiplos modelos de cartao.
7. Adicionar integracoes externas e analises avancadas.
