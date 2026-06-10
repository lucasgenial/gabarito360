# Gabarito360 - Regras de Negocio

## 1. Convencoes

- **Identificador:** `RNnnn`.
- Regras devem ser aplicadas no backend, ainda que tambem sejam antecipadas pela interface.
- Operacoes historicas devem ser canceladas ou inativadas, evitando exclusao fisica sem politica aprovada.

## 2. Escopo organizacional e permissoes

| ID | Regra |
|---|---|
| RN001 | Uma escola deve pertencer a um unico nucleo ativo por vez. |
| RN002 | Um gestor de nucleo acessa apenas o proprio nucleo e suas escolas vinculadas. |
| RN003 | Um responsavel de escola acessa apenas dados da propria escola. |
| RN004 | Professor e aplicador acessam apenas turmas e aplicacoes as quais estejam vinculados. |
| RN005 | Leitor/Consulta nao pode alterar cadastros, leituras ou resultados. |
| RN006 | Acoes de suporte tecnico que acessem dados operacionais devem ser autorizadas e auditadas. |
| RN007 | Inativar usuario deve revogar suas sessoes e impedir novas autenticacoes. |

## 3. Turmas e alunos

| ID | Regra |
|---|---|
| RN008 | Uma turma pertence a uma escola e a um ano letivo. |
| RN009 | A matricula do aluno deve ser unica por escola, comparada de forma normalizada e sem diferenca entre maiusculas e minusculas. |
| RN010 | A transferencia de aluno deve preservar historico de aplicacoes e resultados. |
| RN011 | Aluno inativo permanece visivel em registros historicos. |
| RN012 | Importacoes so podem ser confirmadas depois da validacao de linhas invalidas e duplicadas. |

## 4. Avaliacoes, modelos e gabaritos

| ID | Regra |
|---|---|
| RN013 | Uma avaliacao em rascunho pode ser criada e editada por gestor autorizado; professores e aplicadores nao criam avaliacoes no MVP. |
| RN014 | Uma avaliacao so pode ser publicada com exatamente um modelo de cartao homologado e um gabarito valido. |
| RN015 | A quantidade de respostas do gabarito deve corresponder a quantidade de questoes da avaliacao. |
| RN016 | Cada questao possui no maximo uma alternativa correta, salvo modelo futuro explicitamente configurado. |
| RN017 | O gabarito oficial deve ser bloqueado apos o inicio da primeira aplicacao. |
| RN018 | Alterar gabarito bloqueado exige permissao especial, justificativa e registro de versao. |
| RN019 | Alteracao autorizada de gabarito deve marcar resultados afetados para recorrection. |
| RN020 | O modelo de cartao utilizado em uma leitura deve ser identificado por versao. |
| RN021 | Avaliacao arquivada permanece consultavel, mas nao aceita novas aplicacoes. |

## 5. Aplicacoes

| ID | Regra |
|---|---|
| RN022 | Uma aplicacao vincula uma avaliacao publicada a uma escola e turma autorizadas. |
| RN023 | Apenas aplicador vinculado ou gestor autorizado pode iniciar ou finalizar a aplicacao. |
| RN024 | Uma aplicacao finalizada nao aceita novas leituras confirmadas. |
| RN025 | Reabrir uma aplicacao exige permissao especial, justificativa e auditoria. |
| RN026 | Alunos previstos sao definidos a partir do vinculo ativo com a turma no momento de preparacao da aplicacao. |
| RN027 | Alunos sem leitura valida devem aparecer como pendentes ou ausentes, conforme registro. |

## 6. Cartoes, leituras e resultados

| ID | Regra |
|---|---|
| RN028 | Um aluno pode ter apenas um cartao valido confirmado por avaliacao. |
| RN029 | Um codigo impresso normalizado, quando informado, pode estar vinculado a apenas um aluno dentro da mesma avaliacao. |
| RN030 | O codigo impresso deve ser preservado e confirmado quando existir; o codigo do sistema e adicional e deve ser gerado quando nao houver codigo impresso ou quando o fluxo autorizado exigir. |
| RN031 | Uma nova tentativa de leitura nao apaga tentativas anteriores. |
| RN032 | Reprocessar ou substituir uma leitura confirmada exige permissao, justificativa e auditoria. |
| RN033 | Leituras de baixa confianca devem exigir revisao manual explicita. |
| RN034 | Questoes em branco, duplas ou duvidosas devem ser destacadas antes da confirmacao. |
| RN035 | Uma leitura com alertas pode ser confirmada somente apos aceite explicito do aplicador. |
| RN036 | Toda alteracao manual de resposta deve registrar valor detectado, valor final, usuario, data e motivo obrigatorio entre 10 e 500 caracteres. |
| RN037 | O resultado deve ser calculado com a versao de gabarito vigente registrada na correcao. |
| RN038 | Questao em branco ou com dupla marcacao vale como incorreta, salvo regra especifica da avaliacao. |
| RN039 | No MVP, questao anulada concede pontuacao integral a todos os resultados validos, incrementa `anuladas` e nao incrementa acertos, erros, brancos ou duplas. |
| RN040 | O backend deve rejeitar confirmacao duplicada, mesmo quando recebida repetidamente por sincronizacao. |
| RN041 | Cancelar uma leitura confirmada invalida seu resultado vigente, mas preserva o historico. |

## 7. OMR e qualidade

| ID | Regra |
|---|---|
| RN042 | O OMR deve retornar confianca por questao e confianca geral. |
| RN043 | Limiares de marcacao e confianca pertencem a versao do modelo de cartao. |
| RN044 | Falha na identificacao automatica do codigo impresso deve permitir digitacao manual; trocar o valor detectado exige justificativa, e cartao sem codigo impresso exige o motivo `cartao_sem_codigo_impresso`. |
| RN045 | Imagem que nao atenda aos criterios minimos deve ser recusada ou marcada para nova captura. |
| RN046 | A resposta final confirmada prevalece sobre a detectada para correcao, mantendo ambas no historico. |
| RN063 | O cartao inicial do MVP possui 20 questoes A-E, marcadores de referencia e nenhuma identificacao pessoal impressa; a regiao de codigo impresso depende do modelo. |
| RN064 | O codigo impresso externo e o codigo do sistema sao campos distintos e nenhum deles pode sobrescrever o outro. |
| RN067 | O codigo do sistema, quando utilizado, segue `G360-XXXXXXXXXXXX-C`, e unico globalmente e nao identifica fisicamente o papel sem ser afixado ao cartao. |

## 8. Offline e sincronizacao

| ID | Regra |
|---|---|
| RN047 | Toda operacao mobile sincronizavel deve possuir identificador unico gerado no dispositivo. |
| RN048 | Reenvio da mesma operacao nao pode criar duplicidade. |
| RN049 | Conflitos que alterem aluno, cartao ou resultado ja confirmado nao devem ser resolvidos silenciosamente. |
| RN050 | O app deve informar ao aplicador quais operacoes estao pendentes, sincronizadas ou com erro. |
| RN051 | Logout com operacoes pendentes deve alertar o usuario e preservar a fila local de forma segura. |

## 9. Auditoria, arquivos e retencao

| ID | Regra |
|---|---|
| RN052 | Devem ser auditadas alteracoes de gabarito, correcoes manuais, reprocessamentos, cancelamentos e mudancas de permissao. |
| RN053 | Registros de auditoria nao podem ser editados por usuarios operacionais. |
| RN054 | A coleta de localizacao aproximada depende de autorizacao e finalidade definida. |
| RN055 | Imagens originais confirmadas devem ser retidas por 180 dias apos a aplicacao; tentativas e artefatos processados por 30 dias; excecoes exigem retencao legal documentada. |
| RN056 | Exclusao ou anonimizacao de dados deve respeitar obrigacoes legais e preservacao de evidencias necessarias. |
| RN057 | Exportacoes devem respeitar o escopo de acesso do solicitante e ser auditadas. |
| RN065 | Exportacoes expiram em 7 dias, logs tecnicos em 90 dias, logs de sincronizacao em 180 dias e auditorias em 5 anos. |
| RN066 | A imagem original e obrigatoria para leituras online do MVP e deve permanecer em storage privado com acesso autorizado e auditado. |

## 10. Dashboards e relatorios

| ID | Regra |
|---|---|
| RN058 | Indicadores devem considerar apenas leituras e resultados validos vigentes. |
| RN059 | Dashboards de nucleo agregam apenas escolas vinculadas ao nucleo consultado. |
| RN060 | Resultados de alunos devem ser exibidos apenas a perfis com finalidade e permissao adequadas. |
| RN061 | Relatorios gerados devem registrar filtros, solicitante, data e versao dos dados quando aplicavel. |
| RN062 | Rankings devem informar criterio de ordenacao e tratamento de empates. |

## 11. Decisoes adotadas para o MVP

| Tema | Decisao |
|---|---|
| Matricula | Unica por escola, conforme [ADR-D002](decisoes/ADR-D002-unicidade-matricula.md). |
| Identificacao do cartao | Preservar codigo impresso externo e codigo do sistema em campos separados, conforme [ADR-D010](decisoes/ADR-D010-identificacao-cartao.md). |
| Questao anulada | Concede pontuacao integral a todos os resultados validos, conforme [ADR-D004](decisoes/ADR-D004-questao-anulada.md). |
| Correcao manual | Motivo obrigatorio em toda alteracao de resposta, conforme [ADR-D005](decisoes/ADR-D005-motivo-correcao-manual.md). |
| Retencao | Prazos definidos por classificacao, conforme [ADR-D006](decisoes/ADR-D006-retencao-imagens-logs.md). |
| Criacao de prova | Professores e aplicadores nao criam provas no MVP; a acao fica restrita a gestores autorizados. |
| Modelo por prova | Cada prova do MVP referencia exatamente um modelo de cartao homologado. |
| Limiares OMR | Devem ser calibrados com dataset real e versionados no modelo; nao existem limiares globais fixos. |

O registro completo de `D001-D009` esta em [decisoes/README.md](decisoes/README.md).
