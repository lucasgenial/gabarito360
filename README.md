# Gabarito360

Plataforma de gestao, aplicacao, leitura e correcao automatica de cartoes-resposta por fotografia.

O Gabarito360 foi projetado para nucleos de educacao que acompanham varias escolas. A solucao integra um painel web administrativo, um aplicativo Android para professores e aplicadores e um modulo OMR para identificar marcacoes em cartoes-resposta.

> **Status do projeto:** as fundacoes R2 a R5 e a integracao operacional R6
> foram implementadas na branch de refatoracao. O piloto continua bloqueado
> ate homologacao do OMR e dos dispositivos com dataset real.

## Sobre o sistema

O Gabarito360 centraliza o processo de aplicacao e correcao de provas objetivas:

- organiza nucleos, escolas, usuarios, turmas e alunos;
- permite criar provas, modelos de cartao e gabaritos oficiais;
- vincula provas a turmas e controla suas aplicacoes;
- le cartoes-resposta fotografados pelo aplicativo Android;
- destaca respostas em branco, duplas ou com baixa confianca;
- permite revisao manual antes da confirmacao;
- preserva o codigo impresso do cartao, quando existir, e vincula separadamente o codigo do sistema ao aluno selecionado;
- corrige automaticamente as respostas confirmadas;
- atualiza dashboards e relatorios em tempo real;
- preserva historico e auditoria das operacoes criticas.

O objetivo e reduzir trabalho manual, acelerar a entrega de resultados e permitir que escolas e gestores acompanhem o andamento das aplicacoes.

## Como funciona

```text
Gestor prepara escola, turma, alunos, prova e gabarito
                         |
                         v
Aplicador seleciona a aplicacao e fotografa o cartao
                         |
                         v
OMR corrige perspectiva e detecta as respostas
                         |
                         v
Aplicador revisa alertas e confirma aluno e identificadores
                         |
                         v
Backend valida, corrige e registra o resultado
                         |
                         v
Dashboards e relatorios sao atualizados
```

O modulo OMR nao substitui a conferencia humana em casos duvidosos. Leituras com baixa confianca, respostas em branco ou dupla marcacao devem ser apresentadas ao aplicador antes da confirmacao.

## Principais modulos

| Modulo | Responsabilidade |
|---|---|
| Autenticacao e acesso | Login, perfis, permissoes e escopo organizacional |
| Nucleos e escolas | Gestao da rede educacional |
| Turmas e alunos | Cadastros, vinculos e importacao por planilha |
| Provas e gabaritos | Criacao, publicacao, versionamento e vinculacao |
| Aplicacoes | Controle de inicio, progresso, pendencias e encerramento |
| Aplicativo Android | Captura, revisao, confirmacao e sincronizacao |
| OMR | Processamento da imagem e deteccao das marcacoes |
| Correcao automatica | Calculo de acertos, erros, brancos e nota |
| Dashboards | Acompanhamento operacional em tempo real |
| Relatorios | Analises e exportacoes por aluno, turma, escola e nucleo |
| Auditoria | Rastreabilidade de alteracoes e operacoes sensiveis |

## Perfis de usuario

- **Administrador Geral:** configuracao e supervisao de todo o sistema.
- **Gestor do Nucleo:** gestao das escolas e indicadores consolidados.
- **Responsavel da Escola:** gestao de usuarios, turmas, alunos e aplicacoes da escola.
- **Professor:** acesso as turmas e aplicacoes vinculadas.
- **Aplicador:** captura, revisao e confirmacao dos cartoes-resposta.
- **Leitor/Consulta:** acesso somente a dashboards e relatorios autorizados.
- **Suporte Tecnico:** diagnostico controlado e auditado.

## Aplicativo Android

O aplicativo sera utilizado durante a aplicacao real da prova. O fluxo principal inclui:

1. Login do professor ou aplicador.
2. Selecao da prova, turma e aluno.
3. Captura orientada da foto do cartao.
4. Processamento OMR.
5. Conferencia das respostas detectadas.
6. Correcao manual, quando necessaria.
7. Confirmacao do aluno, do codigo impresso quando houver e do codigo do sistema quando utilizado.
8. Envio do resultado ao backend.
9. Atualizacao dos alunos lidos e pendentes.

O modo offline temporario e a sincronizacao automatica estao planejados para uma etapa posterior ao MVP online.

## Modulo OMR

O processamento inicial sera voltado a um modelo padronizado semelhante ao cartao da OBMEP, com 20 questoes e alternativas de A a E.

Pipeline planejado:

1. Validacao da qualidade da imagem.
2. Conversao para escala de cinza e binarizacao.
3. Deteccao dos marcadores de referencia.
4. Correcao de perspectiva.
5. Localizacao das areas de resposta.
6. Calculo do preenchimento de cada alternativa.
7. Identificacao de marcacoes, brancos, duplas e duvidas.
8. Retorno de confianca por questao e da leitura geral.
9. Revisao manual antes da confirmacao.

A estrategia recomendada e hibrida: retorno rapido no aplicativo e possibilidade de validacao ou reprocessamento no backend.

## Arquitetura planejada

| Camada | Tecnologia |
|---|---|
| Backend e API REST | Laravel 12 |
| Banco de dados | MariaDB |
| Aplicativo Android | Flutter |
| Processamento OMR | OpenCV |
| Cache e filas | Redis |
| Tempo real | Laravel Reverb / WebSockets |
| Painel web | Laravel Blade, Livewire e Tailwind CSS |
| Arquivos | Storage compativel com S3 |
| Infraestrutura | Docker, Nginx e TLS |

```text
Painel Web --------\
                    \
App Android --------> API Laravel ----> MariaDB
                         |   |   \
                         |   |    \----> Storage S3
                         |   |
                         |   \---------> Redis / Filas
                         |
                         \-------------> Reverb / WebSockets

App Android e Workers ----> OpenCV / OMR
```

## Regras fundamentais

- Um aluno pode possuir apenas um cartao valido por prova.
- Um codigo impresso pode ser vinculado a apenas um aluno dentro da prova, e o codigo do sistema, quando utilizado, e unico globalmente.
- Novas leituras e reprocessamentos devem preservar o historico.
- Alteracoes manuais nas respostas devem ser auditadas.
- Leituras com baixa confianca exigem revisao explicita.
- O aplicador acessa somente turmas e aplicacoes vinculadas.
- A escola acessa somente seus dados.
- O nucleo acessa somente suas escolas.
- Confirmacoes mobile devem ser idempotentes para evitar duplicidade.
- Resultados devem registrar a versao do gabarito utilizada.

## Seguranca e LGPD

O sistema deve proteger os dados pessoais de alunos e usuarios desde o inicio:

- autorizacao por perfil e escopo;
- minimizacao da coleta e exibicao de dados pessoais;
- comunicacao protegida por HTTPS/TLS;
- senhas armazenadas somente com hash forte;
- auditoria de operacoes administrativas e alteracoes manuais;
- controle de acesso e retencao das imagens dos cartoes;
- exportacoes autorizadas e auditadas;
- suporte tecnico com acesso restrito;
- historico preservado sem exclusoes operacionais indevidas.

## Escopo do MVP

O primeiro MVP deve permitir uma aplicacao real controlada:

- cadastro de nucleo, escolas, usuarios e perfis;
- cadastro de turmas e alunos;
- importacao validada de alunos;
- cadastro de prova, questoes, modelo e gabarito oficial;
- vinculacao da prova a turma;
- criacao e inicio da aplicacao;
- aplicativo Android com captura, OMR, revisao e confirmacao;
- vinculacao unica entre cartao e aluno;
- correcao automatica;
- dashboard simples de progresso;
- relatorio basico por turma em CSV e PDFs canonicos por aluno, prova e turma/prova;
- auditoria das operacoes criticas.

## Roadmap resumido

| Etapa | Objetivo |
|---|---|
| 0. Fundacao | Aprovar documentacao, regras, cartao inicial e criterios do piloto |
| 1. Backend seguro | Autenticacao, autorizacao, nucleos, escolas e usuarios |
| 2. Estrutura academica | Turmas, alunos, provas, gabaritos e aplicacoes |
| 3. Prototipo OMR | Pipeline reproduzivel, calibracao e dataset rotulado |
| 4. App Android | Captura, revisao, confirmacao e fluxo completo |
| 5. Tempo real e relatorios | Dashboard, encerramento e exportacao CSV |
| 6. Piloto controlado | Validacao com turmas e dispositivos reais |

Versoes posteriores incluem modo offline, XLSX, recorrection, multiplos modelos
de cartao, dashboard autenticado do aluno e integracoes externas. CSV e os PDFs
canonicos de aluno, prova e turma/prova fazem parte do primeiro painel funcional.

## Documentacao

| Documento | Conteudo |
|---|---|
| [Visao geral](docs/01-visao-geral.md) | Objetivos, escopo, arquitetura e riscos |
| [Requisitos funcionais](docs/02-requisitos-funcionais.md) | Funcionalidades identificadas e priorizadas |
| [Requisitos nao funcionais](docs/03-requisitos-nao-funcionais.md) | Seguranca, desempenho, disponibilidade e qualidade |
| [Regras de negocio](docs/04-regras-de-negocio.md) | Regras de integridade, acesso e operacao |
| [Casos de uso](docs/05-casos-de-uso.md) | Atores, fluxos e matriz resumida de permissoes |
| [Modelagem do banco](docs/06-modelagem-banco.md) | Modelagem relacional canonica para MariaDB |
| [API REST](docs/07-api.md) | Contratos e endpoints iniciais |
| [Aplicativo Android](docs/08-mobile-android.md) | Telas, navegacao, fluxos e sincronizacao |
| [Modulo OMR](docs/09-modulo-omr.md) | Pipeline, confianca, calibracao e testes |
| [Dashboards e relatorios](docs/10-dashboard-relatorios.md) | Indicadores, filtros e exportacoes |
| [Roadmap e MVP](docs/11-roadmap-mvp.md) | Etapas, backlog e criterios de conclusao |

## Backend

A API e o painel Laravel estao em [`backend/`](backend/README.md). A base atual inclui API REST, Sanctum, filas, policies, requests, resources, services, painel administrativo e o endpoint `GET /api/v1/health`.

Consulte o [README do backend](backend/README.md) para requisitos e comandos de execucao local.

## App Android e OMR

O cliente Flutter esta em [`mobile/`](mobile/README.md) e o contrato OpenCV
executavel esta em [`omr/`](omr/README.md). O status e os gates da integracao
operacional estao em
[`docs/18-contrato-r6-integracao-operacional.md`](docs/18-contrato-r6-integracao-operacional.md).

### Refatoracao em andamento

O desenvolvimento após a MP-028 foi reorientado para alinhar a aplicação ao
mockup funcional em [`style-system/`](style-system/) e ao MariaDB. O plano vigente está em
[`docs/13-plano-refatoracao-mockup-mariadb.md`](docs/13-plano-refatoracao-mockup-mariadb.md).

O ambiente local reproduzível usa MariaDB portátil em `.local/`. Consulte o
[README do backend](backend/README.md) para os comandos de setup, start e stop.

## Estrutura atual do repositorio

```text
gabarito360/
|-- AGENTS.md
|-- README.md
|-- backend/
\-- docs/
    |-- 01-visao-geral.md
    |-- 02-requisitos-funcionais.md
    |-- 03-requisitos-nao-funcionais.md
    |-- 04-regras-de-negocio.md
    |-- 05-casos-de-uso.md
    |-- 06-banco-de-dados.md
    |-- 06-modelagem-banco.md
    |-- 07-api.md
    |-- 08-mobile-android.md
    |-- 09-modulo-omr.md
    |-- 10-dashboard-relatorios.md
    \-- 11-roadmap-mvp.md
```

## Desenvolvimento

As decisoes bloqueadoras iniciais foram registradas e orientam a implementacao, especialmente:

- modelo fisico inicial do cartao-resposta;
- politica de pontuacao e anulacao de questoes;
- unicidade de matricula, codigo impresso e codigo do sistema;
- metas mensuraveis de qualidade do OMR;
- dispositivos Android homologados;
- politica LGPD e retencao de imagens;
- abordagem inicial do painel web.

Toda nova funcionalidade deve incluir validacao, autenticacao, controle de permissao, auditoria quando aplicavel e testes proporcionais ao risco.

## Licenca

A licenca do projeto ainda nao foi definida.
