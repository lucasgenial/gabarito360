# Gabarito360

Plataforma de gestao, aplicacao, leitura e correcao automatica de cartoes-resposta por fotografia.

O Gabarito360 foi projetado para nucleos de educacao que acompanham varias escolas. A solucao integra um painel web administrativo, um aplicativo Android para professores e aplicadores e um modulo OMR para identificar marcacoes em cartoes-resposta.

> **Status do projeto:** a V2 esta sendo reconstruida na branch
> `v2/mockup-canonico`. As 30 telas e todos os recursos de `style-system/`
> passam a ser o contrato integral do produto. A fundacao tecnica R0-R7 sera
> reaproveitada seletivamente; a interpretacao reduzida do mockup e as paginas
> web anteriores ficam como historico da V1.

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

O aplicativo é a superfície operacional Android da V2. O fluxo principal inclui:

1. Login do professor ou aplicador.
2. Selecao da prova, turma e aluno.
3. Captura orientada da foto do cartao.
4. Processamento OMR.
5. Conferencia das respostas detectadas.
6. Correcao manual, quando necessaria.
7. Confirmacao do aluno, do codigo impresso quando houver e do codigo do sistema quando utilizado.
8. Envio do resultado ao backend.
9. Atualização dos alunos lidos e pendentes.
10. Fila offline temporária, sincronização e resolução de conflitos.

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

## Arquitetura V2

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

## Escopo da V2

A V2 implementa integralmente as 30 telas e capacidades de
[`style-system/`](style-system/): acesso e onboarding, dashboards por ator,
escolas, equipe, turmas, alunos, provas, aplicações, correção, resultados,
relatórios, perfil, configurações, integrações, privacidade e LGPD.

Além da aplicação web responsiva, a V2 conclui o aplicativo Android e homologa
o pipeline OMR OpenCV para cartões e dispositivos reais.

## Roadmap resumido

| Etapa | Objetivo |
|---|---|
| V2-00 | Canonizar o mockup integral e preservar a V1 |
| V2-01 | Mapear cada tela, controle, dado, estado e teste |
| V2-02 | Ampliar domínio, MariaDB e API V2 |
| V2-03 | Reconstruir a fundação visual fiel ao mockup |
| V2-04 a V2-06 | Entregar todas as jornadas e telas web |
| V2-07 | Concluir o aplicativo Android |
| V2-08 | Implementar e homologar o OMR real |
| V2-09 | Homologar o produto integral e preparar lançamento |

## Documentacao

A documentacao canonica da nova versao esta em
[`docs/v2/`](docs/v2/README.md). Os documentos numerados diretamente em
`docs/` registram a V1 e continuam disponiveis para rastreabilidade, mas nao
podem reduzir o escopo definido pelo mockup funcional.

| Documento | Conteúdo |
|---|---|
| [Índice V2](docs/v2/README.md) | Precedência e conjunto canônico |
| [Inventário do mockup](docs/v2/02-inventario-funcional-mockup.md) | Todas as telas e capacidades |
| [Arquitetura e reaproveitamento](docs/v2/06-arquitetura-e-reaproveitamento-v1.md) | O que reutilizar, refatorar e substituir |
| [Modelagem MariaDB](docs/v2/07-modelagem-dados-mariadb.md) | Dados reutilizados e ampliações |
| [Matriz de rastreabilidade](docs/v2/15-matriz-rastreabilidade.md) | Mockup, implementação e evidência |
| [Plano executável V2](docs/v2/16-plano-executavel-v2.md) | Ordem e gates de reconstrução |

## Backend

A API e o painel Laravel estao em [`backend/`](backend/README.md). A base atual inclui API REST, Sanctum, filas, policies, requests, resources, services, painel administrativo e o endpoint `GET /api/v1/health`.

Consulte o [README do backend](backend/README.md) para requisitos e comandos de execucao local.

## App Android e OMR

O cliente Flutter existente em [`mobile/`](mobile/README.md) e o contrato
OpenCV em [`omr/`](omr/README.md) são fundações reutilizáveis, ainda não o
produto final. O contrato V2 está em
[`docs/v2/10-android-flutter.md`](docs/v2/10-android-flutter.md) e
[`docs/v2/11-omr-opencv.md`](docs/v2/11-omr-opencv.md).

### Reconstrucao V2

O mockup funcional em [`style-system/`](style-system/) e o handoff exportado
sao a fonte de verdade visual e funcional. A estrategia de reaproveitamento e o
plano vigente estao em
[`docs/v2/06-arquitetura-e-reaproveitamento-v1.md`](docs/v2/06-arquitetura-e-reaproveitamento-v1.md)
e [`docs/v2/16-plano-executavel-v2.md`](docs/v2/16-plano-executavel-v2.md).

O ambiente local reproduzível usa MariaDB portátil em `.local/`. Consulte o
[README do backend](backend/README.md) para os comandos de setup, start e stop.

### Ambiente containerizado

O R7 fornece um ambiente de homologacao em [`compose.yaml`](compose.yaml), com
Nginx, Laravel, MariaDB, Redis, filas, scheduler e Reverb separados. Somente o
Nginx publica porta; MariaDB e Redis permanecem na rede interna.

```powershell
Copy-Item .env.docker.example .env.docker
# Gere APP_KEY e substitua todos os segredos de .env.docker.
docker compose --env-file .env.docker up -d --build --wait
Invoke-RestMethod http://127.0.0.1:8080/api/v1/health
```

Consulte [deploy](docs/infra/deploy.md), [backup e restauracao](docs/operacao/backup-e-restauracao.md)
e o [contrato R7](docs/19-contrato-r7-empacotamento-homologacao.md).

## Estrutura atual do repositorio

```text
gabarito360/
|-- AGENTS.md
|-- README.md
|-- backend/
|-- mobile/
|-- omr/
|-- style-system/
\-- docs/
    |-- v2/
    \-- decisoes/
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
