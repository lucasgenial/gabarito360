# Gabarito360 - Aplicativo Android

## 1. Objetivo

O aplicativo Android permite que professores e aplicadores executem a leitura de cartoes-resposta durante uma aplicacao real. A experiencia deve ser simples, rapida e confiavel, com revisao humana antes da confirmacao e suporte futuro a operacao offline temporaria.

## 2. Tecnologia recomendada

- **Framework:** Flutter.
- **Plataforma inicial:** Android.
- **Camera:** integracao nativa por plugin homologado.
- **OMR:** OpenCV integrado ao app, com possibilidade de validacao no backend.
- **Persistencia local:** SQLite por camada de acesso estruturada.
- **Credenciais:** armazenamento seguro disponibilizado pelo sistema operacional.
- **Rede:** cliente HTTP com timeout, retry controlado e idempotencia.
- **Estado:** solucao consolidada no ecossistema Flutter, escolhida antes da implementacao.

## 3. Principios de experiencia

- Exibir apenas o contexto autorizado do aplicador.
- Reduzir passos repetitivos entre alunos da mesma turma.
- Manter botao de nova leitura facilmente acessivel.
- Mostrar claramente o estado online, offline e de sincronizacao.
- Diferenciar sucesso, alerta e erro por texto, icone e cor.
- Nunca confirmar silenciosamente resposta duvidosa.
- Evitar exibir dados pessoais alem do necessario durante a aplicacao.

## 4. Navegacao principal

```text
Login
  -> Inicio
    -> Selecionar aplicacao
      -> Resumo da turma
        -> Lista de alunos
        -> Nova leitura
          -> Captura
          -> Processamento
          -> Conferencia
          -> Confirmar aluno e codigo
          -> Resultado
        -> Pendencias
        -> Historico
    -> Sincronizacao
    -> Configuracoes
```

## 5. Telas

### 5.1 Login

**Objetivo:** autenticar o aplicador e registrar o dispositivo.

**Elementos:** e-mail, senha, mostrar/ocultar senha, entrar, recuperar senha, versao do app e status de conexao.

**Validacoes:** campos obrigatorios, formato de e-mail, bloqueio temporario informado de forma generica.

**Erros:** credencial invalida, conta inativa, servidor indisponivel, app desatualizado.

### 5.2 Inicio

**Objetivo:** apresentar aplicacoes autorizadas e estado operacional.

**Elementos:** aplicacoes recentes, filtros por escola/data/status, quantidade de sincronizacoes pendentes, identificacao resumida do usuario.

**Acoes:** abrir aplicacao, atualizar dados, acessar sincronizacao, sair.

### 5.3 Selecao de aplicacao

**Objetivo:** selecionar avaliacao e turma sem permitir acesso indevido.

**Elementos:** cards ou lista com avaliacao, escola, turma, data, status e progresso.

**Regra:** o app recebe somente aplicacoes autorizadas pelo backend.

### 5.4 Resumo da turma

**Objetivo:** concentrar a operacao de uma aplicacao.

**Elementos:** previstos, lidos, pendentes, alertas, status da aplicacao, ultima sincronizacao e botao destacado de nova leitura.

**Acoes:** iniciar/finalizar quando autorizado, abrir alunos, capturar cartao, abrir historico.

### 5.5 Lista de alunos

**Objetivo:** localizar aluno e visualizar estado.

**Elementos:** busca, filtros `todos`, `pendentes`, `lidos`, `ausentes`; nome, matricula resumida e estado.

**Acoes:** selecionar aluno antes da captura, abrir resultado autorizado ou iniciar leitura.

### 5.6 Captura da camera

**Objetivo:** obter imagem adequada ao OMR.

**Elementos:** guia de enquadramento, marcadores esperados, indicador de iluminacao/nitidez, botao de captura, flash e ajuda curta.

**Validacoes antes de aceitar:**

- Cartao suficientemente enquadrado.
- Resolucao minima.
- Nitidez e iluminacao aceitaveis.
- Marcadores de referencia detectaveis quando possivel.

**Erros:** camera sem permissao, imagem tremida, reflexo excessivo, cartao incompleto.

### 5.7 Processamento

**Objetivo:** comunicar progresso sem permitir confirmacao prematura.

**Elementos:** etapas resumidas, opcao de cancelar quando seguro e mensagem de processamento local/remoto.

**Saidas:** sucesso, parcial com alertas ou falha com orientacao para nova captura.

### 5.8 Conferencia da leitura

**Objetivo:** revisar respostas e resolver alertas.

**Elementos:** codigo detectado, confianca geral, grade de questoes, alternativa detectada/final, indicadores de branco, dupla ou duvida e recorte visual quando disponivel.

**Acoes:** alterar resposta, informar motivo, editar codigo, refazer foto e prosseguir.

**Regra:** questoes sinalizadas exigem revisao explicita.

### 5.9 Confirmacao do aluno e cartao

**Objetivo:** impedir vinculo incorreto.

**Elementos:** aluno selecionado, turma, avaliacao, codigo do cartao, resumo dos alertas e declaracao de confirmacao.

**Acoes:** trocar aluno, corrigir codigo, confirmar lancamento ou voltar.

**Validacoes:** aluno pertencente a aplicacao, codigo valido, alertas aceitos, operacao nao duplicada.

### 5.10 Resultado individual

**Objetivo:** informar conclusao e resumo autorizado.

**Elementos:** estado de sincronizacao, total de acertos/erros/brancos, nota quando permitida e proxima acao.

**Acoes:** nova leitura, voltar para turma e ver pendentes.

### 5.11 Pendencias

**Objetivo:** apoiar conclusao da aplicacao.

**Elementos:** alunos sem leitura, ausentes, leituras com alerta e operacoes nao sincronizadas.

### 5.12 Historico

**Objetivo:** consultar operacoes recentes do dispositivo/aplicador.

**Elementos:** aluno, codigo, horario, estado, alertas e sincronizacao.

**Regra:** alteracao de leitura confirmada segue fluxo autorizado, nunca edicao silenciosa.

### 5.13 Sincronizacao

**Objetivo:** tornar visivel a fila offline.

**Elementos:** quantidade por estado, ultima tentativa, erros, acao de tentar novamente e detalhes de conflito.

**Estados:** pendente, enviando, sincronizada, conflito e erro recuperavel.

### 5.14 Configuracoes

**Objetivo:** exibir opcoes operacionais seguras.

**Elementos:** dispositivo, versao, qualidade de imagem, politica de uso de dados, permissoes e diagnostico limitado.

## 6. Fluxo online de leitura

1. Aplicador abre uma aplicacao em andamento.
2. Seleciona o aluno antes ou depois da captura, conforme fluxo configurado.
3. Captura o cartao e recebe validacao de qualidade.
4. O app executa OMR e apresenta respostas detectadas.
5. Aplicador revisa alertas e corrige quando necessario.
6. Confirma aluno e codigo do cartao.
7. App envia leitura com `Idempotency-Key`.
8. Backend valida, persiste, corrige e retorna resultado.
9. App marca o aluno como lido e oferece nova leitura.

## 7. Fluxo offline futuro

### 7.1 Preparacao

- Aplicador autentica e sincroniza previamente aplicacoes, modelos e alunos autorizados.
- App registra validade e versao do contexto offline.

### 7.2 Operacao

- Captura, processamento e revisao ocorrem localmente.
- A confirmacao cria operacao local imutavel com UUID.
- Imagem e payload ficam protegidos ate sincronizacao ou descarte autorizado.

### 7.3 Sincronizacao

1. App detecta rede e envia operacoes na ordem adequada.
2. Backend valida idempotencia e estado atual.
3. Sucessos sao confirmados e dados locais sensiveis seguem politica de limpeza.
4. Erros recuperaveis entram em retry com espera progressiva.
5. Conflitos ficam visiveis para resolucao; o app nao sobrescreve resultado existente.

## 8. Dados locais minimos

| Grupo | Dados |
|---|---|
| Contexto | Usuario resumido, permissoes necessarias, aplicacoes e turmas autorizadas |
| Alunos | Identificador, nome, matricula resumida e estado na aplicacao |
| Modelos | Versao e configuracao OMR necessaria |
| Operacoes | UUID, payload confirmado, estado, tentativas e erros |
| Arquivos | Imagem temporaria protegida e checksum |

Tokens, imagens e dados pessoais devem utilizar mecanismos de protecao adequados ao dispositivo. A limpeza local deve ocorrer conforme sincronizacao e politica de retencao.

## 9. Permissoes Android

| Permissao | Uso | Regra |
|---|---|---|
| Camera | Capturar cartao | Obrigatoria para leitura |
| Rede | Sincronizar com API | Obrigatoria |
| Armazenamento | Evitar quando possivel | Preferir area privada do app |
| Localizacao | Registrar local aproximado | Opcional, explicita e condicionada a autorizacao |

## 10. Tratamento de erros

| Situacao | Comportamento |
|---|---|
| Sem internet | Informar modo offline ou impedir operacao que dependa da rede no MVP |
| Token expirado | Tentar renovacao segura; solicitar login quando necessario |
| Conflito de aluno/cartao | Manter operacao, explicar conflito e impedir sobrescrita |
| Aplicacao finalizada | Bloquear confirmacao e atualizar contexto |
| Imagem ruim | Orientar nova captura com motivo objetivo |
| Falha OMR | Permitir nova captura; digitacao completa somente se politica permitir |
| Servidor indisponivel | Preservar operacao segura e tentar novamente conforme versao |

## 11. Criterios de aceite do MVP mobile

- Usuario ve somente aplicacoes e alunos autorizados.
- Captura invalida nao avanca sem aviso.
- Leitura exibe todas as questoes e seus alertas antes da confirmacao.
- Alteracao manual fica identificada no payload e na auditoria.
- Confirmacao repetida nao cria resultado duplicado.
- App atualiza aluno para lido somente apos resposta valida do backend.
- Erros apresentam uma acao de recuperacao.
- Fluxos principais funcionam nos dispositivos definidos pela matriz de homologacao.

## 12. Estrategia de testes

- Testes unitarios de estado, validacao e montagem de payload.
- Testes de widget para telas e estados de erro.
- Testes de integracao com API simulada.
- Testes em aparelhos reais com cameras variadas.
- Testes de interrupcao: rotacao, app em segundo plano, encerramento e perda de rede.
- Testes de idempotencia, retry e conflitos.
- Testes de usabilidade com professores/aplicadores.
